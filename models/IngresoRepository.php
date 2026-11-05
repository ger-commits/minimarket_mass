<?php
// Registra ingresos de mercadería, actualiza stock, crea lotes opcionales y escribe el kardex.
require_once __DIR__ . '/Database.php';

class IngresoRepository
{
    private const TIPO_INGRESO = 7;

    public function registrar(int $idTienda, int $idEmpleado, int $idProducto, string $cantidadTexto,
                              string $observacion = '', ?string $codigoLote = null, ?string $fechaVencimiento = null): int
    {
        $cantidad = filter_var(str_replace(',', '.', trim($cantidadTexto)), FILTER_VALIDATE_FLOAT);
        if ($idTienda <= 0 || $idEmpleado <= 0 || $idProducto <= 0 || $cantidad === false || $cantidad <= 0) {
            throw new InvalidArgumentException('Selecciona un producto y una cantidad mayor que cero.');
        }
        $observacion = trim($observacion);
        if (mb_strlen($observacion) > 200) throw new InvalidArgumentException('La observación no puede superar 200 caracteres.');

        $pdo = Database::conectar();
        $st = $pdo->prepare("SELECT nombre_producto, se_vende_por_peso, estado FROM producto WHERE id_producto = ?");
        $st->execute([$idProducto]);
        $producto = $st->fetch();
        if (!$producto || $producto['estado'] !== 'ACTIVO') throw new InvalidArgumentException('El producto no existe o está inactivo.');
        if ($producto['se_vende_por_peso'] === 'N' && floor((float)$cantidad) != (float)$cantidad) {
            throw new InvalidArgumentException('Este producto se vende por unidad: ingresa una cantidad entera.');
        }
        $cantidad = round((float)$cantidad, 3);
        $codigoLote = trim((string)$codigoLote);
        $fechaVencimiento = trim((string)$fechaVencimiento);
        if (($codigoLote === '') xor ($fechaVencimiento === '')) {
            throw new InvalidArgumentException('Para registrar un lote debes indicar código y fecha de vencimiento.');
        }
        if ($codigoLote !== '') {
            $fecha = DateTime::createFromFormat('!Y-m-d', $fechaVencimiento);
            if (mb_strlen($codigoLote) > 20 || !$fecha || $fecha->format('Y-m-d') !== $fechaVencimiento) {
                throw new InvalidArgumentException('El código de lote o la fecha de vencimiento no son válidos.');
            }
        }
        if ($fechaVencimiento !== '' && $fechaVencimiento < date('Y-m-d')) {
            throw new InvalidArgumentException('No se puede ingresar un lote con fecha de vencimiento pasada.');
        }
        // Si el producto ya se controla por lotes en esta tienda, exige lote para mantener la trazabilidad.
        $qLotes = $pdo->prepare('SELECT COUNT(*) FROM lote_producto WHERE id_tienda = ? AND id_producto = ?');
        $qLotes->execute([$idTienda, $idProducto]);
        if ((int)$qLotes->fetchColumn() > 0 && $codigoLote === '') {
            throw new InvalidArgumentException('Este producto ya se controla por lotes: completa código y vencimiento.');
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT IGNORE INTO stock (id_tienda, id_producto, cantidad_actual, stock_minimo) VALUES (?, ?, 0, 0)')
                ->execute([$idTienda, $idProducto]);
            $st = $pdo->prepare('SELECT cantidad_actual FROM stock WHERE id_tienda = ? AND id_producto = ? FOR UPDATE');
            $st->execute([$idTienda, $idProducto]);
            $nuevoStock = round((float)$st->fetchColumn() + $cantidad, 3);

            $pdo->prepare('INSERT INTO ingreso_mercaderia (fecha_hora, observacion, id_empleado) VALUES (NOW(), ?, ?)')
                ->execute([$observacion === '' ? null : $observacion, $idEmpleado]);
            $idIngreso = (int)$pdo->lastInsertId();

            $idLote = null;
            if ($codigoLote !== '') {
                $qLote = $pdo->prepare('SELECT id_lote, fecha_vencimiento FROM lote_producto WHERE id_tienda = ? AND id_producto = ? AND codigo_lote = ? FOR UPDATE');
                $qLote->execute([$idTienda, $idProducto, $codigoLote]);
                $loteExistente = $qLote->fetch();
                if ($loteExistente) {
                    if ($loteExistente['fecha_vencimiento'] !== $fechaVencimiento) {
                        throw new InvalidArgumentException('Ese código de lote ya existe con otra fecha de vencimiento.');
                    }
                    $idLote = (int)$loteExistente['id_lote'];
                    $pdo->prepare('UPDATE lote_producto SET cantidad_inicial = cantidad_inicial + ?, cantidad_actual = cantidad_actual + ? WHERE id_lote = ?')
                        ->execute([$cantidad, $cantidad, $idLote]);
                } else {
                    $pdo->prepare('INSERT INTO lote_producto (codigo_lote, fecha_vencimiento, cantidad_inicial, cantidad_actual, id_tienda, id_producto) VALUES (?, ?, ?, ?, ?, ?)')
                        ->execute([$codigoLote, $fechaVencimiento, $cantidad, $cantidad, $idTienda, $idProducto]);
                    $idLote = (int)$pdo->lastInsertId();
                }
            }

            $pdo->prepare('UPDATE stock SET cantidad_actual = ? WHERE id_tienda = ? AND id_producto = ?')
                ->execute([$nuevoStock, $idTienda, $idProducto]);
            $pdo->prepare('INSERT INTO movimiento_stock (fecha_hora, cantidad, stock_nuevo, observacion, id_tienda, id_producto, id_tipo_movimiento, id_empleado, id_ingreso, id_lote) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$cantidad, $nuevoStock, $observacion === '' ? null : $observacion, $idTienda, $idProducto, self::TIPO_INGRESO, $idEmpleado, $idIngreso, $idLote]);

            $pdo->commit();
            return $idIngreso;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
