<?php
// M: mermas (pérdidas de mercadería). Descuenta stock de la tienda consumiendo primero los lotes
// que vencen antes (FEFO) y deja una línea en el kardex por cada lote tocado. Todo en una transacción.
require_once __DIR__ . '/Database.php';

class MermaRepository
{
    private const TIPO_MERMA = 5;   // MERMA (tipo_movimiento)

    private const MOTIVOS = [
        'VENCIDO' => 'Vencido',
        'DAÑADO'  => 'Dañado',
        'ROBO'    => 'Robo',
        'OTRO'    => 'Otro',
    ];

    public function motivos(): array
    {
        return self::MOTIVOS;
    }

    // $items = [id_producto => cantidad_texto]. Las líneas vacías se ignoran.
    public function registrar(int $idTienda, int $idEmpleado, string $motivo, string $observacion, array $items): int
    {
        $motivo = trim($motivo);
        if (!array_key_exists($motivo, self::MOTIVOS)) {
            throw new InvalidArgumentException('Elige un motivo válido.');
        }
        $obs = trim($observacion);
        if (mb_strlen($obs) > 200) {
            throw new InvalidArgumentException('La observación no puede pasar de 200 caracteres.');
        }

        $pdo = Database::conectar();

        // Validar y agrupar las líneas
        $lineas = [];
        foreach ($items as $idProd => $txt) {
            $idProd = (int)$idProd;
            $txt = trim((string)$txt);
            if ($idProd <= 0 || $txt === '') continue;
            $cant = filter_var(str_replace(',', '.', $txt), FILTER_VALIDATE_FLOAT);
            if ($cant === false || $cant <= 0) {
                throw new InvalidArgumentException('Las cantidades deben ser números mayores que cero.');
            }
            $st = $pdo->prepare('SELECT se_vende_por_peso, nombre_producto FROM producto WHERE id_producto = ?');
            $st->execute([$idProd]);
            $p = $st->fetch();
            if (!$p) throw new InvalidArgumentException('Un producto no existe.');
            if ($p['se_vende_por_peso'] === 'N' && floor($cant) != $cant) {
                throw new InvalidArgumentException("{$p['nombre_producto']} se vende por unidad: usa cantidades enteras.");
            }
            $lineas[$idProd] = round(($lineas[$idProd] ?? 0) + $cant, 3);
        }
        if (!$lineas) {
            throw new InvalidArgumentException('Escribe la cantidad de al menos un producto.');
        }

        $pdo->beginTransaction();
        try {
            $ahora = date('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO merma (fecha_hora, motivo, observacion, id_empleado) VALUES (?, ?, ?, ?)')
                ->execute([$ahora, $motivo, $obs === '' ? null : $obs, $idEmpleado]);
            $idMerma = (int)$pdo->lastInsertId();

            foreach ($lineas as $idProd => $cant) {
                $this->descontar($pdo, $idMerma, $idEmpleado, $idTienda, (int)$idProd, (float)$cant, $ahora);
            }

            $pdo->commit();
            return $idMerma;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Da de baja exactamente el lote seleccionado (por ejemplo, uno vencido desde la alerta).
    public function registrarLote(int $idTienda, int $idEmpleado, int $idLote, string $observacion = ''): int
    {
        if ($idLote <= 0) throw new InvalidArgumentException('El lote seleccionado no es válido.');
        $obs = trim($observacion);
        if (mb_strlen($obs) > 200) throw new InvalidArgumentException('La observación no puede superar 200 caracteres.');
        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT l.id_lote, l.id_producto, l.cantidad_actual, p.nombre_producto, s.cantidad_actual AS stock_actual
                FROM lote_producto l JOIN producto p ON p.id_producto=l.id_producto
                JOIN stock s ON s.id_tienda=l.id_tienda AND s.id_producto=l.id_producto
                WHERE l.id_lote=? AND l.id_tienda=? FOR UPDATE');
            $st->execute([$idLote, $idTienda]);
            $lote = $st->fetch();
            if (!$lote || (float)$lote['cantidad_actual'] <= 0) throw new InvalidArgumentException('El lote no existe en tu tienda o ya está agotado.');
            $cantidad = round((float)$lote['cantidad_actual'], 3);
            if ((float)$lote['stock_actual'] < $cantidad) throw new RuntimeException('El stock registrado no coincide con la cantidad del lote.');
            $pdo->prepare('INSERT INTO merma (fecha_hora, motivo, observacion, id_empleado) VALUES (NOW(), ?, ?, ?)')
                ->execute(['VENCIDO', $obs === '' ? 'Baja del lote vencido' : $obs, $idEmpleado]);
            $idMerma = (int)$pdo->lastInsertId();
            $nuevo = round((float)$lote['stock_actual'] - $cantidad, 3);
            $pdo->prepare('UPDATE lote_producto SET cantidad_actual=0 WHERE id_lote=?')->execute([$idLote]);
            $pdo->prepare('UPDATE stock SET cantidad_actual=? WHERE id_tienda=? AND id_producto=?')->execute([$nuevo, $idTienda, (int)$lote['id_producto']]);
            $pdo->prepare('INSERT INTO movimiento_stock (fecha_hora,cantidad,stock_nuevo,observacion,id_tienda,id_producto,id_tipo_movimiento,id_empleado,id_merma,id_lote)
                VALUES (NOW(),?,?,?,?,?,?,?, ?, ?)')->execute([$cantidad, $nuevo, $obs === '' ? 'Baja del lote vencido' : $obs, $idTienda, (int)$lote['id_producto'], self::TIPO_MERMA, $idEmpleado, $idMerma, $idLote]);
            $pdo->commit();
            return $idMerma;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    // Historial de mermas de la tienda (las que tienen movimientos en ella)
    public function historial(int $idTienda, int $limite = 20): array
    {
        $st = Database::conectar()->prepare(
            "SELECT mr.id_merma, mr.fecha_hora, mr.motivo, mr.observacion,
                    CONCAT(e.nombres, ' ', e.apellidos) AS empleado,
                    COUNT(DISTINCT m.id_producto) AS productos
               FROM merma mr
               JOIN movimiento_stock m ON m.id_merma = mr.id_merma AND m.id_tienda = ?
               JOIN empleado e ON e.id_empleado = mr.id_empleado
              GROUP BY mr.id_merma, mr.fecha_hora, mr.motivo, mr.observacion, e.nombres, e.apellidos
              ORDER BY mr.fecha_hora DESC, mr.id_merma DESC
              LIMIT " . (int)$limite
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    // Da de baja una cantidad de un producto: lotes primero (FEFO), luego el stock sin lote
    private function descontar(PDO $pdo, int $idMerma, int $idEmpleado, int $tienda, int $idProd, float $cant, string $ahora): void
    {
        $st = $pdo->prepare(
            'SELECT p.nombre_producto, s.cantidad_actual
               FROM stock s JOIN producto p ON p.id_producto = s.id_producto
              WHERE s.id_tienda = ? AND s.id_producto = ? FOR UPDATE'
        );
        $st->execute([$tienda, $idProd]);
        $f = $st->fetch();
        if (!$f) throw new RuntimeException('Un producto no está registrado en tu tienda.');

        $saldo = (float)$f['cantidad_actual'];
        if ($saldo < $cant) {
            throw new RuntimeException('Stock insuficiente de ' . $f['nombre_producto'] . ': tienes ' . $this->num($saldo) . '.');
        }

        $st = $pdo->prepare(
            'SELECT id_lote, cantidad_actual FROM lote_producto
              WHERE id_tienda = ? AND id_producto = ? AND cantidad_actual > 0
              ORDER BY fecha_vencimiento, id_lote FOR UPDATE'
        );
        $st->execute([$tienda, $idProd]);

        $pendiente = $cant;
        $tomas = [];
        foreach ($st->fetchAll() as $lote) {
            if ($pendiente <= 0) break;
            $t = min($pendiente, (float)$lote['cantidad_actual']);
            $tomas[] = ['id_lote' => (int)$lote['id_lote'], 'cant' => $t];
            $pendiente -= $t;
        }
        if ($pendiente > 0) {
            $tomas[] = ['id_lote' => null, 'cant' => $pendiente];   // lo que no cubren los lotes
        }

        foreach ($tomas as $toma) {
            $c = round($toma['cant'], 3);
            $saldo = round($saldo - $c, 3);

            if ($toma['id_lote'] !== null) {
                $pdo->prepare('UPDATE lote_producto SET cantidad_actual = cantidad_actual - ? WHERE id_lote = ?')
                    ->execute([$c, $toma['id_lote']]);
            }

            $pdo->prepare(
                'INSERT INTO movimiento_stock (fecha_hora, cantidad, stock_nuevo, id_tienda, id_producto,
                                               id_tipo_movimiento, id_empleado, id_merma, id_lote)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$ahora, $c, $saldo, $tienda, $idProd, self::TIPO_MERMA, $idEmpleado, $idMerma, $toma['id_lote']]);
        }

        $pdo->prepare('UPDATE stock SET cantidad_actual = ? WHERE id_tienda = ? AND id_producto = ?')
            ->execute([$saldo, $tienda, $idProd]);
    }

    private function num(float $x): string
    {
        return rtrim(rtrim(number_format($x, 3, '.', ''), '0'), '.');
    }
}