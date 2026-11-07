<?php
// M: traslados de mercadería entre tiendas, en dos pasos.
//  1) ENVÍO: el administrador de la tienda origen registra el traslado. El stock sale de su tienda (kardex de salida)
//     y el traslado queda EN_CAMINO. La tienda destino todavía no recibe nada.
//  2) RECEPCIÓN: el almacenero de la tienda destino cuenta lo que llegó y confirma. Solo entonces entra el stock
//     (kardex de entrada) y el traslado queda RECIBIDO. Si llegó menos de lo enviado, la diferencia queda registrada.
// Es la única conexión entre tiendas: cada tienda solo ve sus propios envíos y lo que le envían.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Administrador.php';
require_once __DIR__ . '/Almacenero.php';

class TrasladoRepository
{
    private const TIPO_SALIDA  = 3;   // TRASLADO DE SALIDA
    private const TIPO_ENTRADA = 4;   // TRASLADO DE ENTRADA

    // Tiendas activas a las que se puede enviar (todas menos la propia)
    public function tiendasDestino(int $idOrigen): array
    {
        $st = Database::conectar()->prepare(
            "SELECT id_tienda, nombre_tienda FROM tienda
              WHERE estado = 'ACTIVA' AND id_tienda <> ? ORDER BY nombre_tienda"
        );
        $st->execute([$idOrigen]);
        return $st->fetchAll();
    }

    // Productos activos con stock en la tienda origen
    public function stockDisponible(int $idOrigen): array
    {
        $st = Database::conectar()->prepare(
            "SELECT p.id_producto, p.nombre_producto, p.se_vende_por_peso, s.cantidad_actual
               FROM stock s JOIN producto p ON p.id_producto = s.id_producto
              WHERE s.id_tienda = ? AND s.cantidad_actual > 0 AND p.estado = 'ACTIVO' AND s.descontinuado = 'N'
              ORDER BY p.nombre_producto"
        );
        $st->execute([$idOrigen]);
        return $st->fetchAll();
    }

    // Traslados en los que participa la tienda (enviados o recibidos), con su estado
    public function historial(int $idTienda, int $limite = 20): array
    {
        $st = Database::conectar()->prepare(
            "SELECT t.id_traslado, t.fecha_hora, t.observacion, t.estado, t.fecha_recepcion, t.observacion_recepcion,
                    o.nombre_tienda AS origen, d.nombre_tienda AS destino,
                    t.id_tienda_origen, t.id_tienda_destino,
                    CONCAT(e.nombres, ' ', e.apellidos) AS empleado,
                    CONCAT(r.nombres, ' ', r.apellidos) AS receptor,
                    (SELECT COUNT(DISTINCT m.id_producto) FROM movimiento_stock m
                      WHERE m.id_traslado = t.id_traslado AND m.id_tipo_movimiento = 3) AS productos,
                    (SELECT COALESCE(SUM(td.cantidad_enviada - td.cantidad_recibida), 0) FROM traslado_detalle td
                      WHERE td.id_traslado = t.id_traslado AND td.cantidad_recibida IS NOT NULL) AS diferencia
               FROM traslado_mercaderia t
               JOIN tienda o   ON o.id_tienda = t.id_tienda_origen
               JOIN tienda d   ON d.id_tienda = t.id_tienda_destino
               JOIN empleado e ON e.id_empleado = t.id_empleado
               LEFT JOIN empleado r ON r.id_empleado = t.id_empleado_recibe
              WHERE t.id_tienda_origen = ? OR t.id_tienda_destino = ?
              ORDER BY t.fecha_hora DESC, t.id_traslado DESC
              LIMIT " . (int)$limite
        );
        $st->execute([$idTienda, $idTienda]);
        return $st->fetchAll();
    }

    // Traslados EN_CAMINO dirigidos a esta tienda, con sus líneas, para que el almacén los reciba
    public function porRecibir(int $idTienda): array
    {
        $pdo = Database::conectar();
        $st = $pdo->prepare(
            "SELECT t.id_traslado, DATE_FORMAT(t.fecha_hora, '%d/%m/%Y %H:%i') AS fecha, t.observacion,
                    o.nombre_tienda AS origen
               FROM traslado_mercaderia t JOIN tienda o ON o.id_tienda = t.id_tienda_origen
              WHERE t.id_tienda_destino = ? AND t.estado = 'EN_CAMINO'
              ORDER BY t.fecha_hora, t.id_traslado"
        );
        $st->execute([$idTienda]);
        $traslados = $st->fetchAll();
        if (!$traslados) return [];

        $q = $pdo->prepare(
            "SELECT td.id_detalle, td.id_producto, p.nombre_producto, p.se_vende_por_peso, um.abreviatura AS unidad,
                    td.codigo_lote, td.fecha_vencimiento, td.cantidad_enviada
               FROM traslado_detalle td JOIN producto p ON p.id_producto = td.id_producto
               JOIN unidad_medida um ON um.id_unidad = p.id_unidad
              WHERE td.id_traslado = ? ORDER BY p.nombre_producto, td.fecha_vencimiento, td.id_detalle"
        );
        foreach ($traslados as &$t) {
            $q->execute([$t['id_traslado']]);
            $t['lineas'] = $q->fetchAll();
        }
        return $traslados;
    }

    // PASO 1 (envío). $items = [['id_producto' => 1, 'cantidad' => '2.5'], ...]. Las líneas vacías o en cero se ignoran.
    public function registrar(Administrador $admin, int $idDestino, string $observacion, array $items): int
    {
        $origen = $admin->idTienda;
        if ($idDestino === $origen || $idDestino <= 0) {
            throw new InvalidArgumentException('Elige una tienda destino distinta a la tuya.');
        }
        $obs = trim($observacion);
        if (mb_strlen($obs) > 200) {
            throw new InvalidArgumentException('La observación no puede pasar de 200 caracteres.');
        }

        $pdo = Database::conectar();
        $st = $pdo->prepare('SELECT estado FROM tienda WHERE id_tienda = ?');
        $st->execute([$idDestino]);
        if ($st->fetchColumn() !== 'ACTIVA') {
            throw new InvalidArgumentException('La tienda destino no existe o no está activa.');
        }

        // Validar y agrupar las líneas por producto
        $lineas = [];
        foreach ($items as $it) {
            $prod = (int)($it['id_producto'] ?? 0);
            $txt  = trim((string)($it['cantidad'] ?? ''));
            if ($prod <= 0 || $txt === '') continue;
            $cant = filter_var(str_replace(',', '.', $txt), FILTER_VALIDATE_FLOAT);
            if ($cant === false || $cant <= 0) {
                throw new InvalidArgumentException('Las cantidades deben ser números mayores que cero.');
            }
            $st = $pdo->prepare('SELECT se_vende_por_peso, nombre_producto FROM producto WHERE id_producto = ?');
            $st->execute([$prod]);
            $p = $st->fetch();
            if (!$p) throw new InvalidArgumentException('Un producto del traslado no existe.');
            if ($p['se_vende_por_peso'] === 'N' && floor($cant) != $cant) {
                throw new InvalidArgumentException("{$p['nombre_producto']} se vende por unidad: usa cantidades enteras.");
            }
            $lineas[$prod] = round(($lineas[$prod] ?? 0) + $cant, 3);
        }
        if (!$lineas) {
            throw new InvalidArgumentException('Escribe la cantidad de al menos un producto.');
        }

        $pdo->beginTransaction();
        try {
            $ahora = date('Y-m-d H:i:s');
            $pdo->prepare(
                "INSERT INTO traslado_mercaderia (fecha_hora, observacion, estado, id_tienda_origen, id_tienda_destino, id_empleado)
                 VALUES (?, ?, 'EN_CAMINO', ?, ?, ?)"
            )->execute([$ahora, $obs === '' ? null : $obs, $origen, $idDestino, $admin->idEmpleado]);
            $idTraslado = (int)$pdo->lastInsertId();

            foreach ($lineas as $idProd => $cant) {
                $this->enviar($pdo, $idTraslado, $admin->idEmpleado, $origen, (int)$idProd, (float)$cant, $ahora);
            }

            $pdo->commit();
            return $idTraslado;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Saca un producto de la tienda origen (stock, lotes y kardex de salida) y guarda qué lotes salieron
    private function enviar(PDO $pdo, int $idTraslado, int $idEmpleado, int $origen,
                            int $idProd, float $cant, string $ahora): void
    {
        $st = $pdo->prepare(
            'SELECT p.nombre_producto, s.cantidad_actual, s.descontinuado
               FROM stock s JOIN producto p ON p.id_producto = s.id_producto
              WHERE s.id_tienda = ? AND s.id_producto = ? FOR UPDATE'
        );
        $st->execute([$origen, $idProd]);
        $f = $st->fetch();
        if (!$f) throw new RuntimeException('Un producto no está registrado en tu tienda.');
        if ($f['descontinuado'] === 'S') {
            throw new RuntimeException($f['nombre_producto'] . ' está descontinuado en tu tienda: no se puede trasladar. Reactívalo primero.');
        }
        $saldoOrigen = (float)$f['cantidad_actual'];
        if ($saldoOrigen < $cant) {
            throw new RuntimeException('Stock insuficiente de ' . $f['nombre_producto'] . ': tienes ' . $this->num($saldoOrigen) . '.');
        }

        // Lotes del origen: se consumen primero los que vencen antes (FEFO)
        $st = $pdo->prepare(
            'SELECT id_lote, codigo_lote, fecha_vencimiento, cantidad_actual FROM lote_producto
              WHERE id_tienda = ? AND id_producto = ? AND cantidad_actual > 0
              ORDER BY fecha_vencimiento, id_lote FOR UPDATE'
        );
        $st->execute([$origen, $idProd]);
        $pendiente = $cant;
        $tomas = [];
        foreach ($st->fetchAll() as $lote) {
            if ($pendiente <= 0) break;
            $t = min($pendiente, (float)$lote['cantidad_actual']);
            $tomas[] = ['lote' => $lote, 'cant' => $t];
            $pendiente -= $t;
        }
        // Lo que no cubren los lotes sale del stock sin lote
        if ($pendiente > 0.0005) $tomas[] = ['lote' => null, 'cant' => $pendiente];

        $det = $pdo->prepare(
            'INSERT INTO traslado_detalle (id_traslado, id_producto, codigo_lote, fecha_vencimiento, cantidad_enviada)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($tomas as $toma) {
            $c = round($toma['cant'], 3);
            $lote = $toma['lote'];
            $saldoOrigen = round($saldoOrigen - $c, 3);
            if ($lote !== null) {
                $pdo->prepare('UPDATE lote_producto SET cantidad_actual = cantidad_actual - ? WHERE id_lote = ?')
                    ->execute([$c, $lote['id_lote']]);
            }
            $this->kardex($pdo, $origen, $idProd, self::TIPO_SALIDA, $c, $saldoOrigen, $idTraslado, $idEmpleado, $lote['id_lote'] ?? null, $ahora);
            $det->execute([$idTraslado, $idProd, $lote['codigo_lote'] ?? null, $lote['fecha_vencimiento'] ?? null, $c]);
        }
        $pdo->prepare('UPDATE stock SET cantidad_actual = ? WHERE id_tienda = ? AND id_producto = ?')
            ->execute([$saldoOrigen, $origen, $idProd]);
    }

    // PASO 2 (recepción). $cantidades = [id_detalle => cantidad realmente recibida]. Solo el almacén de la tienda destino.
    public function recibir(Almacenero $almacenero, int $idTraslado, array $cantidades, string $observacion = ''): void
    {
        $obs = trim($observacion);
        if (mb_strlen($obs) > 200) throw new InvalidArgumentException('La observación no puede pasar de 200 caracteres.');

        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id_tienda_destino, estado FROM traslado_mercaderia WHERE id_traslado = ? FOR UPDATE');
            $st->execute([$idTraslado]);
            $t = $st->fetch();
            if (!$t || (int)$t['id_tienda_destino'] !== $almacenero->idTienda) {
                throw new RuntimeException('Ese traslado no está dirigido a tu tienda.');
            }
            if ($t['estado'] !== 'EN_CAMINO') throw new RuntimeException('Ese traslado ya fue recibido.');

            $st = $pdo->prepare(
                'SELECT td.id_detalle, td.id_producto, td.codigo_lote, td.fecha_vencimiento, td.cantidad_enviada,
                        p.nombre_producto, p.se_vende_por_peso
                   FROM traslado_detalle td JOIN producto p ON p.id_producto = td.id_producto
                  WHERE td.id_traslado = ? ORDER BY td.id_detalle FOR UPDATE'
            );
            $st->execute([$idTraslado]);
            $lineas = $st->fetchAll();
            if (!$lineas) throw new RuntimeException('El traslado no tiene productos.');

            // Validar todas las cantidades antes de mover nada
            $recibidas = [];
            $hayDiferencia = false;
            foreach ($lineas as $l) {
                $id = (int)$l['id_detalle'];
                if (!array_key_exists($id, $cantidades) || trim((string)$cantidades[$id]) === '') {
                    throw new InvalidArgumentException('Escribe la cantidad recibida de ' . $l['nombre_producto'] . ' (puede ser 0).');
                }
                $r = filter_var(str_replace(',', '.', trim((string)$cantidades[$id])), FILTER_VALIDATE_FLOAT);
                $enviada = (float)$l['cantidad_enviada'];
                if ($r === false || $r < 0) {
                    throw new InvalidArgumentException('Las cantidades recibidas deben ser números iguales o mayores que cero.');
                }
                if ($r > $enviada + 0.0005) {
                    throw new InvalidArgumentException('No puedes recibir más de lo enviado de ' . $l['nombre_producto'] . ' (' . $this->num($enviada) . ').');
                }
                if ($l['se_vende_por_peso'] === 'N' && floor($r) != $r) {
                    throw new InvalidArgumentException($l['nombre_producto'] . ' se cuenta por unidad: usa cantidades enteras.');
                }
                $r = round($r, 3);
                if ($r < round($enviada, 3)) $hayDiferencia = true;
                $recibidas[$id] = $r;
            }
            if ($hayDiferencia && $obs === '') {
                throw new InvalidArgumentException('Llegó menos de lo enviado: explica la diferencia en la observación.');
            }

            $ahora = date('Y-m-d H:i:s');
            $destino = $almacenero->idTienda;
            foreach ($lineas as $l) {
                $id = (int)$l['id_detalle'];
                $c = $recibidas[$id];
                $pdo->prepare('UPDATE traslado_detalle SET cantidad_recibida = ? WHERE id_detalle = ?')->execute([$c, $id]);
                if ($c <= 0) continue;

                $idProd = (int)$l['id_producto'];
                $pdo->prepare('INSERT IGNORE INTO stock (id_tienda, id_producto, cantidad_actual, stock_minimo) VALUES (?, ?, 0, 0)')
                    ->execute([$destino, $idProd]);
                $q = $pdo->prepare('SELECT cantidad_actual FROM stock WHERE id_tienda = ? AND id_producto = ? FOR UPDATE');
                $q->execute([$destino, $idProd]);
                $saldo = round((float)$q->fetchColumn() + $c, 3);

                $idLote = null;
                if ($l['codigo_lote'] !== null) {
                    // El mismo lote entra en la tienda destino (se suma si ya existía)
                    $pdo->prepare(
                        'INSERT INTO lote_producto (codigo_lote, fecha_vencimiento, cantidad_inicial, cantidad_actual, id_tienda, id_producto)
                         VALUES (?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE cantidad_inicial = cantidad_inicial + VALUES(cantidad_inicial),
                                                 cantidad_actual  = cantidad_actual + VALUES(cantidad_actual)'
                    )->execute([$l['codigo_lote'], $l['fecha_vencimiento'], $c, $c, $destino, $idProd]);
                    $q = $pdo->prepare('SELECT id_lote FROM lote_producto WHERE id_tienda = ? AND id_producto = ? AND codigo_lote = ?');
                    $q->execute([$destino, $idProd, $l['codigo_lote']]);
                    $idLote = (int)$q->fetchColumn();
                }
                $pdo->prepare('UPDATE stock SET cantidad_actual = ? WHERE id_tienda = ? AND id_producto = ?')
                    ->execute([$saldo, $destino, $idProd]);
                $this->kardex($pdo, $destino, $idProd, self::TIPO_ENTRADA, $c, $saldo, $idTraslado, $almacenero->idEmpleado, $idLote, $ahora);
            }

            $pdo->prepare(
                "UPDATE traslado_mercaderia SET estado = 'RECIBIDO', fecha_recepcion = ?, id_empleado_recibe = ?, observacion_recepcion = ?
                  WHERE id_traslado = ?"
            )->execute([$ahora, $almacenero->idEmpleado, $obs === '' ? null : $obs, $idTraslado]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    // Anula un envío que sigue EN_CAMINO: el stock (y los lotes) vuelven a la tienda de origen.
    // Solo lo puede hacer el administrador de la tienda que envió.
    public function anular(Administrador $admin, int $idTraslado, string $motivo): void
    {
        $motivo = trim($motivo);
        if ($motivo === '') throw new InvalidArgumentException('Escribe el motivo de la anulación.');
        if (mb_strlen($motivo) > 150) throw new InvalidArgumentException('El motivo no puede pasar de 150 caracteres.');

        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT id_tienda_origen, estado FROM traslado_mercaderia WHERE id_traslado = ? FOR UPDATE');
            $st->execute([$idTraslado]);
            $t = $st->fetch();
            if (!$t || (int)$t['id_tienda_origen'] !== $admin->idTienda) {
                throw new RuntimeException('Solo puedes anular envíos de tu tienda.');
            }
            if ($t['estado'] !== 'EN_CAMINO') {
                throw new RuntimeException($t['estado'] === 'RECIBIDO'
                    ? 'Ese traslado ya fue recibido: no se puede anular.' : 'Ese traslado ya estaba anulado.');
            }

            $st = $pdo->prepare('SELECT id_producto, codigo_lote, fecha_vencimiento, cantidad_enviada FROM traslado_detalle WHERE id_traslado = ? ORDER BY id_detalle FOR UPDATE');
            $st->execute([$idTraslado]);
            $ahora = date('Y-m-d H:i:s');
            $origen = $admin->idTienda;
            foreach ($st->fetchAll() as $l) {
                $idProd = (int)$l['id_producto'];
                $c = round((float)$l['cantidad_enviada'], 3);

                $pdo->prepare('INSERT IGNORE INTO stock (id_tienda, id_producto, cantidad_actual, stock_minimo) VALUES (?, ?, 0, 0)')
                    ->execute([$origen, $idProd]);
                $q = $pdo->prepare('SELECT cantidad_actual FROM stock WHERE id_tienda = ? AND id_producto = ? FOR UPDATE');
                $q->execute([$origen, $idProd]);
                $saldo = round((float)$q->fetchColumn() + $c, 3);

                $idLote = null;
                if ($l['codigo_lote'] !== null) {
                    // El lote vuelve a su tienda (se suma si todavía existe)
                    $pdo->prepare(
                        'INSERT INTO lote_producto (codigo_lote, fecha_vencimiento, cantidad_inicial, cantidad_actual, id_tienda, id_producto)
                         VALUES (?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE cantidad_actual = cantidad_actual + VALUES(cantidad_actual)'
                    )->execute([$l['codigo_lote'], $l['fecha_vencimiento'], $c, $c, $origen, $idProd]);
                    $q = $pdo->prepare('SELECT id_lote FROM lote_producto WHERE id_tienda = ? AND id_producto = ? AND codigo_lote = ?');
                    $q->execute([$origen, $idProd, $l['codigo_lote']]);
                    $idLote = (int)$q->fetchColumn();
                }
                $pdo->prepare('UPDATE stock SET cantidad_actual = ? WHERE id_tienda = ? AND id_producto = ?')
                    ->execute([$saldo, $origen, $idProd]);
                $this->kardex($pdo, $origen, $idProd, self::TIPO_ENTRADA, $c, $saldo, $idTraslado, $admin->idEmpleado, $idLote, $ahora,
                              'Traslado N.º ' . $idTraslado . ' anulado: el stock volvió al origen');
            }

            // fecha_recepcion y id_empleado_recibe guardan aquí cuándo y quién anuló; observacion_recepcion, el motivo
            $pdo->prepare(
                "UPDATE traslado_mercaderia SET estado = 'ANULADO', fecha_recepcion = ?, id_empleado_recibe = ?, observacion_recepcion = ?
                  WHERE id_traslado = ?"
            )->execute([$ahora, $admin->idEmpleado, $motivo, $idTraslado]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private function kardex(PDO $pdo, int $tienda, int $idProd, int $tipo, float $cant, float $saldo,
                            int $idTraslado, int $idEmpleado, ?int $idLote, string $ahora, ?string $observacion = null): void
    {
        $pdo->prepare(
            'INSERT INTO movimiento_stock (fecha_hora, cantidad, stock_nuevo, observacion, id_tienda, id_producto,
                                           id_tipo_movimiento, id_empleado, id_traslado, id_lote)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$ahora, $cant, $saldo, $observacion, $tienda, $idProd, $tipo, $idEmpleado, $idTraslado, $idLote]);
    }

    private function num(float $x): string
    {
        return rtrim(rtrim(number_format($x, 3, '.', ''), '0'), '.');
    }
}
