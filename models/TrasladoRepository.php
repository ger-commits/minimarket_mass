<?php
// M: traslados de mercadería entre tiendas. Cabecera en traslado_mercaderia; por cada producto,
// una salida (tienda origen) y una entrada (tienda destino) en el kardex, todo en una transacción.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Administrador.php';

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
              WHERE s.id_tienda = ? AND s.cantidad_actual > 0 AND p.estado = 'ACTIVO'
              ORDER BY p.nombre_producto"
        );
        $st->execute([$idOrigen]);
        return $st->fetchAll();
    }

    // Traslados en los que participa la tienda (enviados o recibidos)
    public function historial(int $idTienda, int $limite = 20): array
    {
        $st = Database::conectar()->prepare(
            "SELECT t.id_traslado, t.fecha_hora, t.observacion,
                    o.nombre_tienda AS origen, d.nombre_tienda AS destino,
                    CONCAT(e.nombres, ' ', e.apellidos) AS empleado,
                    (SELECT COUNT(*) FROM movimiento_stock m
                      WHERE m.id_traslado = t.id_traslado AND m.id_tipo_movimiento = 3) AS productos
               FROM traslado_mercaderia t
               JOIN tienda o   ON o.id_tienda = t.id_tienda_origen
               JOIN tienda d   ON d.id_tienda = t.id_tienda_destino
               JOIN empleado e ON e.id_empleado = t.id_empleado
              WHERE t.id_tienda_origen = ? OR t.id_tienda_destino = ?
              ORDER BY t.fecha_hora DESC, t.id_traslado DESC
              LIMIT " . (int)$limite
        );
        $st->execute([$idTienda, $idTienda]);
        return $st->fetchAll();
    }

    // $items = [['id_producto' => 1, 'cantidad' => '2.5'], ...]. Las líneas vacías o en cero se ignoran.
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
                'INSERT INTO traslado_mercaderia (fecha_hora, observacion, id_tienda_origen, id_tienda_destino, id_empleado)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$ahora, $obs === '' ? null : $obs, $origen, $idDestino, $admin->idEmpleado]);
            $idTraslado = (int)$pdo->lastInsertId();

            foreach ($lineas as $idProd => $cant) {
                $this->mover($pdo, $idTraslado, $admin->idEmpleado, $origen, $idDestino, (int)$idProd, (float)$cant, $ahora);
            }

            $pdo->commit();
            return $idTraslado;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Mueve un producto de la tienda origen a la destino (stock, lotes y kardex)
    private function mover(PDO $pdo, int $idTraslado, int $idEmpleado, int $origen, int $destino,
                           int $idProd, float $cant, string $ahora): void
    {
        // 1) Origen: bloquear la fila de stock y validar existencias
        $st = $pdo->prepare(
            'SELECT p.nombre_producto, s.cantidad_actual
               FROM stock s JOIN producto p ON p.id_producto = s.id_producto
              WHERE s.id_tienda = ? AND s.id_producto = ? FOR UPDATE'
        );
        $st->execute([$origen, $idProd]);
        $f = $st->fetch();
        if (!$f) throw new RuntimeException('Un producto no está registrado en tu tienda.');
        $saldoOrigen = (float)$f['cantidad_actual'];
        if ($saldoOrigen < $cant) {
            throw new RuntimeException('Stock insuficiente de ' . $f['nombre_producto'] . ': tienes ' . $this->num($saldoOrigen) . '.');
        }

        // 2) Destino: asegurar la fila de stock y bloquearla
        $pdo->prepare('INSERT IGNORE INTO stock (id_tienda, id_producto, cantidad_actual, stock_minimo) VALUES (?, ?, 0, 0)')
            ->execute([$destino, $idProd]);
        $st = $pdo->prepare('SELECT cantidad_actual FROM stock WHERE id_tienda = ? AND id_producto = ? FOR UPDATE');
        $st->execute([$destino, $idProd]);
        $saldoDestino = (float)$st->fetchColumn();

        // 3) Lotes del origen: se consumen primero los que vencen antes (FEFO)
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
        if ($pendiente > 0) $tomas[] = ['lote' => null, 'cant' => $pendiente];

        foreach ($tomas as $toma) {
            $c = round($toma['cant'], 3);
            $lote = $toma['lote'];
            $saldoOrigen  = round($saldoOrigen - $c, 3);
            $saldoDestino = round($saldoDestino + $c, 3);

            $idLoteDestino = null;
            if ($lote !== null) {
                $pdo->prepare('UPDATE lote_producto SET cantidad_actual = cantidad_actual - ? WHERE id_lote = ?')
                    ->execute([$c, $lote['id_lote']]);
                // El mismo lote entra en la tienda destino (se suma si ya existía)
                $pdo->prepare(
                    'INSERT INTO lote_producto (codigo_lote, fecha_vencimiento, cantidad_inicial, cantidad_actual, id_tienda, id_producto)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE cantidad_inicial = cantidad_inicial + VALUES(cantidad_inicial),
                                             cantidad_actual  = cantidad_actual + VALUES(cantidad_actual)'
                )->execute([$lote['codigo_lote'], $lote['fecha_vencimiento'], $c, $c, $destino, $idProd]);
                $q = $pdo->prepare('SELECT id_lote FROM lote_producto WHERE id_tienda = ? AND id_producto = ? AND codigo_lote = ?');
                $q->execute([$destino, $idProd, $lote['codigo_lote']]);
                $idLoteDestino = (int)$q->fetchColumn();
            }

            $this->kardex($pdo, $origen,  $idProd, self::TIPO_SALIDA,  $c, $saldoOrigen,  $idTraslado, $idEmpleado, $lote['id_lote'] ?? null, $ahora);
            $this->kardex($pdo, $destino, $idProd, self::TIPO_ENTRADA, $c, $saldoDestino, $idTraslado, $idEmpleado, $idLoteDestino,  $ahora);
        }

        // 4) Guardar el saldo final de cada tienda
        $pdo->prepare('UPDATE stock SET cantidad_actual = ? WHERE id_tienda = ? AND id_producto = ?')
            ->execute([$saldoOrigen, $origen, $idProd]);
        $pdo->prepare('UPDATE stock SET cantidad_actual = ? WHERE id_tienda = ? AND id_producto = ?')
            ->execute([$saldoDestino, $destino, $idProd]);
    }

    private function kardex(PDO $pdo, int $tienda, int $idProd, int $tipo, float $cant, float $saldo,
                            int $idTraslado, int $idEmpleado, ?int $idLote, string $ahora): void
    {
        $pdo->prepare(
            'INSERT INTO movimiento_stock (fecha_hora, cantidad, stock_nuevo, id_tienda, id_producto,
                                           id_tipo_movimiento, id_empleado, id_traslado, id_lote)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$ahora, $cant, $saldo, $tienda, $idProd, $tipo, $idEmpleado, $idTraslado, $idLote]);
    }

    private function num(float $x): string
    {
        return rtrim(rtrim(number_format($x, 3, '.', ''), '0'), '.');
    }
}
