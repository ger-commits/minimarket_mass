<?php
// M: datos de ventas y de la boleta (usa la vista y el procedimiento de database/boleta.sql)
require_once __DIR__ . '/Database.php';

class VentaRepository
{
    public function boleta(string $serie, int $correlativo): array
    {
        $st = Database::conectar()->prepare(
            "SELECT comprobante, tipo_comprobante, fecha_hora, estado, tienda, caja, cajero, cliente,
                    item, producto, unidad, cantidad, precio_unitario, importe, total
               FROM vw_comprobante WHERE serie = ? AND numero_correlativo = ? ORDER BY item"
        );
        $st->execute([$serie, $correlativo]);
        return $st->fetchAll();
    }

    public function ventasPorDia(): array
    {
        return Database::conectar()->query(
            "SELECT DATE(fecha_hora) AS dia, COUNT(*) AS comprobantes, SUM(total) AS total
               FROM venta WHERE estado = 'EMITIDA' GROUP BY DATE(fecha_hora) ORDER BY dia"
        )->fetchAll();
    }

    // ===== Terminal de caja (POS) =====

    // Productos activos por código de barras exacto o por parte del nombre, con el stock de la tienda
    public function buscarProductos(int $idTienda, string $texto, int $limite = 8): array
    {
        $texto = trim($texto);
        if ($texto === '') return [];
        $like = '%' . addcslashes($texto, '%_\\') . '%';
        $st = Database::conectar()->prepare(
            "SELECT p.id_producto, p.nombre_producto, p.codigo_barras, p.precio_venta, p.se_vende_por_peso,
                    u.abreviatura, COALESCE(s.cantidad_actual, 0) AS stock
               FROM producto p
               JOIN unidad_medida u ON u.id_unidad = p.id_unidad
               LEFT JOIN stock s ON s.id_producto = p.id_producto AND s.id_tienda = ?
              WHERE p.estado = 'ACTIVO' AND (p.codigo_barras = ? OR p.nombre_producto LIKE ?)
              ORDER BY (p.codigo_barras = ?) DESC, p.nombre_producto LIMIT " . (int)$limite
        );
        $st->execute([$idTienda, $texto, $like, $texto]);
        return $st->fetchAll();
    }

    // Registra la venta completa en UNA transacción: serie y correlativo, cliente, detalle, pago, stock (con lote) y kardex.
    // $pago: ['medio' => 'EF'|'TJ'|'SIP', 'recibido' => float, 'tipo_tarjeta', 'marca', 'ultimos4', 'autorizacion', 'modalidad', 'operacion']
    public function registrar(int $idTienda, int $idEmpleado, int $idTurno, array $items, string $tipo, string $dni, string $ruc, array $pago): array
    {
        if (!$items) throw new InvalidArgumentException('La venta no tiene productos.');
        if (!in_array($tipo, ['B', 'F'], true)) throw new InvalidArgumentException('Tipo de comprobante no válido.');
        if ($tipo === 'F' && !preg_match('/^[0-9]{11}$/', $ruc)) throw new InvalidArgumentException('El RUC debe tener 11 dígitos.');
        if ($tipo === 'B' && $dni !== '' && !preg_match('/^[0-9]{8}$/', $dni)) throw new InvalidArgumentException('El DNI debe tener 8 dígitos.');
        $medio = $pago['medio'] ?? '';
        if (!in_array($medio, ['EF', 'TJ', 'SIP'], true)) throw new InvalidArgumentException('Medio de pago no válido.');

        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("SELECT 1 FROM caja_turno WHERE id_caja_turno = ? AND id_empleado = ? AND estado = 'ABIERTO' FOR UPDATE");
            $st->execute([$idTurno, $idEmpleado]);
            if ($st->fetchColumn() === false) throw new RuntimeException('Abra la caja antes de vender.');

            $st = $pdo->prepare(
                'SELECT s.id_serie, s.serie, s.ultimo_numero FROM serie_comprobante s
                   JOIN tipo_comprobante t ON t.id_tipo_comprobante = s.id_tipo_comprobante
                  WHERE s.id_tienda = ? AND t.letra_serie = ? ORDER BY s.id_serie LIMIT 1 FOR UPDATE'
            );
            $st->execute([$idTienda, $tipo]);
            $serie = $st->fetch();
            if (!$serie) throw new RuntimeException('La tienda no tiene serie para este comprobante.');
            $numero = (int)$serie['ultimo_numero'] + 1;
            $pdo->prepare('UPDATE serie_comprobante SET ultimo_numero = ? WHERE id_serie = ?')->execute([$numero, $serie['id_serie']]);

            // cliente (solo DNI / RUC, según el diseño de la base)
            $idCliente = null;
            $doc = $tipo === 'F' ? ['ruc', $ruc] : ($dni !== '' ? ['dni', $dni] : null);
            if ($doc) $idCliente = $this->clientePorDocumento($pdo, $doc[0], $doc[1]);

            // líneas: se juntan las repetidas; el precio y el stock salen SIEMPRE de la base
            $juntos = [];
            foreach ($items as $it) {
                $id = (int)($it['id_producto'] ?? 0);
                $q = (int)round(((float)($it['cantidad'] ?? 0)) * 1000);
                if ($id <= 0 || $q <= 0) throw new InvalidArgumentException('Cantidad no válida.');
                $juntos[$id] = ($juntos[$id] ?? 0) + $q;
            }
            ksort($juntos);   // mismo orden de bloqueo siempre: evita bloqueos cruzados entre cajas
            $lineas = [];
            $totalCent = 0;
            foreach ($juntos as $id => $qMil) {
                $st = $pdo->prepare("SELECT nombre_producto, precio_venta, se_vende_por_peso FROM producto WHERE id_producto = ? AND estado = 'ACTIVO'");
                $st->execute([$id]);
                $p = $st->fetch();
                if (!$p) throw new RuntimeException('Un producto de la venta ya no está disponible.');
                if ($p['se_vende_por_peso'] !== 'S' && $qMil % 1000 !== 0) {
                    throw new InvalidArgumentException('«' . $p['nombre_producto'] . '» se vende por unidades enteras.');
                }
                $st = $pdo->prepare('SELECT cantidad_actual FROM stock WHERE id_tienda = ? AND id_producto = ? FOR UPDATE');
                $st->execute([$idTienda, $id]);
                $disp = $st->fetchColumn();
                $dispMil = $disp === false ? 0 : (int)round((float)$disp * 1000);
                if ($dispMil < $qMil) {
                    throw new RuntimeException('Stock insuficiente de «' . $p['nombre_producto'] . '» (disponible ' . $this->mil($dispMil) . ').');
                }
                $precioCent = (int)round((float)$p['precio_venta'] * 100);
                $importeCent = intdiv($qMil * $precioCent + 500, 1000);   // cantidad x precio, redondeado al céntimo
                $totalCent += $importeCent;
                $lineas[] = ['id' => $id, 'qMil' => $qMil, 'precio' => $precioCent, 'importe' => $importeCent, 'dispMil' => $dispMil];
            }

            $pdo->prepare(
                "INSERT INTO venta (numero_correlativo, fecha_hora, total, estado, id_serie, id_caja_turno, id_cliente)
                 VALUES (?, NOW(), ?, 'EMITIDA', ?, ?, ?)"
            )->execute([$numero, $this->cent($totalCent), $serie['id_serie'], $idTurno, $idCliente]);
            $idVenta = (int)$pdo->lastInsertId();

            foreach ($lineas as $l) {
                $pdo->prepare('INSERT INTO detalle_venta (cantidad, precio_unitario, importe, id_venta, id_producto) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$this->mil($l['qMil']), $this->cent($l['precio']), $this->cent($l['importe']), $idVenta, $l['id']]);
                $this->descontarStock($pdo, $idTienda, $idEmpleado, $idVenta, $l);
            }

            $vuelto = 0;
            $pdo->prepare("INSERT INTO pago_venta (monto_pagado, fecha_hora, estado, id_venta, id_metodo_pago) VALUES (?, NOW(), 'VALIDO', ?, ?)")
                ->execute([$this->cent($totalCent), $idVenta, ['EF' => 1, 'TJ' => 2, 'SIP' => 3][$medio]]);
            $idPago = (int)$pdo->lastInsertId();
            if ($medio === 'EF') {
                $recCent = (int)round(((float)($pago['recibido'] ?? 0)) * 100);
                if ($recCent < $totalCent) throw new RuntimeException('Monto insuficiente: falta S/ ' . $this->cent($totalCent - $recCent) . '.');
                $vuelto = $recCent - $totalCent;
                $pdo->prepare('INSERT INTO efectivo (id_pago, monto_recibido, vuelto) VALUES (?, ?, ?)')
                    ->execute([$idPago, $this->cent($recCent), $this->cent($vuelto)]);
            } elseif ($medio === 'TJ') {
                $tt = strtoupper(trim((string)($pago['tipo_tarjeta'] ?? '')));
                $marca = trim((string)($pago['marca'] ?? ''));
                $u4 = trim((string)($pago['ultimos4'] ?? ''));
                $aut = trim((string)($pago['autorizacion'] ?? ''));
                if (!in_array($tt, ['CREDITO', 'DEBITO'], true)) throw new InvalidArgumentException('Elija crédito o débito.');
                if ($marca === '' || mb_strlen($marca) > 20) throw new InvalidArgumentException('Indique la marca de la tarjeta.');
                if (!preg_match('/^[0-9]{4}$/', $u4)) throw new InvalidArgumentException('Ingrese los 4 últimos dígitos de la tarjeta.');
                if ($aut === '' || mb_strlen($aut) > 10) throw new InvalidArgumentException('Ingrese el código de autorización (máx. 10).');
                $pdo->prepare('INSERT INTO tarjeta (id_pago, tipo_tarjeta, marca_tarjeta, ultimos_4_digitos, codigo_autorizacion) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$idPago, $tt, $marca, $u4, $aut]);
            } else {
                $mod = strtoupper(trim((string)($pago['modalidad'] ?? '')));
                $op = trim((string)($pago['operacion'] ?? ''));
                if (!in_array($mod, ['QR', 'TARJETA_SIP'], true)) throw new InvalidArgumentException('Elija QR o tarjeta Sip.');
                if ($op === '' || mb_strlen($op) > 30) throw new InvalidArgumentException('Ingrese el código de operación (máx. 30).');
                $pdo->prepare('INSERT INTO billetera_digital_sip (id_pago, modalidad_pago, codigo_operacion) VALUES (?, ?, ?)')
                    ->execute([$idPago, $mod, $op]);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'codigo_operacion')) throw new RuntimeException('Ese código de operación ya fue registrado en otra venta.');
            error_log('registrar venta: ' . $e->getMessage());
            throw new RuntimeException('No se pudo registrar la venta.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        return [
            'id_venta' => $idVenta,
            'comprobante' => $serie['serie'] . '-' . str_pad((string)$numero, 6, '0', STR_PAD_LEFT),
            'tipo' => $tipo,
            'total' => (float)$this->cent($totalCent),
            'vuelto' => (float)$this->cent($vuelto),
            'medio' => $medio,
            'cliente' => $tipo === 'F' ? 'RUC ' . $ruc : ($dni !== '' ? 'DNI ' . $dni : 'Cliente varios'),
            'lineas' => count($lineas),
        ];
    }

    // Ventas de hoy de la caja (todas, vigentes y anuladas)
    public function delDia(int $idCaja): array
    {
        $st = Database::conectar()->prepare(
            "SELECT v.id_venta, s.serie, v.numero_correlativo, v.fecha_hora, v.total, v.estado, s.id_tipo_comprobante AS tipo,
                    CASE WHEN cl.ruc IS NOT NULL THEN CONCAT('RUC ', cl.ruc) WHEN cl.dni IS NOT NULL THEN CONCAT('DNI ', cl.dni) ELSE 'Sin datos' END AS cliente,
                    (SELECT mp.nombre_metodo FROM pago_venta pv JOIN metodo_pago mp ON mp.id_metodo_pago = pv.id_metodo_pago
                      WHERE pv.id_venta = v.id_venta ORDER BY pv.id_pago LIMIT 1) AS medio,
                    (SELECT COUNT(*) FROM detalle_venta d WHERE d.id_venta = v.id_venta) AS lineas,
                    EXISTS (SELECT 1 FROM solicitud_anulacion sa WHERE sa.id_venta = v.id_venta AND sa.estado = 'PENDIENTE') AS pendiente
               FROM venta v
               JOIN serie_comprobante s ON s.id_serie = v.id_serie
               JOIN caja_turno ct ON ct.id_caja_turno = v.id_caja_turno
               LEFT JOIN cliente cl ON cl.id_cliente = v.id_cliente
              WHERE ct.id_caja = ? AND DATE(v.fecha_hora) = CURDATE()
              ORDER BY v.fecha_hora DESC, v.id_venta DESC"
        );
        $st->execute([$idCaja]);
        return $st->fetchAll();
    }

    // Busca una venta de la tienda por comprobante (B001-000123) o solo por el correlativo
    public function buscarComprobante(int $idTienda, string $texto): ?array
    {
        $texto = strtoupper(trim($texto));
        if (preg_match('/^([BF][0-9]{3})-?([0-9]{1,8})$/', $texto, $m)) {
            $serie = $m[1]; $num = (int)$m[2];
        } elseif (preg_match('/^[0-9]{1,8}$/', $texto)) {
            $serie = null; $num = (int)$texto;
        } else {
            return null;
        }
        $st = Database::conectar()->prepare(
            "SELECT v.id_venta, s.serie, v.numero_correlativo, v.total, v.estado, v.fecha_hora, ct.id_empleado AS id_cajero,
                    tc.nombre_comprobante AS tipo,
                    (SELECT GROUP_CONCAT(DISTINCT mp.nombre_metodo ORDER BY mp.id_metodo_pago SEPARATOR ', ')
                       FROM pago_venta pv JOIN metodo_pago mp ON mp.id_metodo_pago = pv.id_metodo_pago WHERE pv.id_venta = v.id_venta) AS medio,
                    (SELECT COUNT(*) FROM detalle_venta d WHERE d.id_venta = v.id_venta) AS lineas,
                    (SELECT sa.id_solicitud FROM solicitud_anulacion sa WHERE sa.id_venta = v.id_venta AND sa.estado = 'PENDIENTE' LIMIT 1) AS id_pendiente
               FROM venta v
               JOIN serie_comprobante s ON s.id_serie = v.id_serie
               JOIN tipo_comprobante tc ON tc.id_tipo_comprobante = s.id_tipo_comprobante
               JOIN caja_turno ct ON ct.id_caja_turno = v.id_caja_turno
              WHERE s.id_tienda = ? AND v.numero_correlativo = ? AND (? IS NULL OR s.serie = ?)
              ORDER BY v.fecha_hora DESC LIMIT 1"
        );
        $st->execute([$idTienda, $num, $serie, $serie]);
        return $st->fetch() ?: null;
    }

    // ---- ayudas internas ----

    private function clientePorDocumento(PDO $pdo, string $campo, string $valor): int
    {
        $campo = $campo === 'ruc' ? 'ruc' : 'dni';   // nombre de columna fijo, nunca texto del usuario
        $st = $pdo->prepare("SELECT id_cliente FROM cliente WHERE $campo = ?");
        $st->execute([$valor]);
        $id = $st->fetchColumn();
        if ($id !== false) return (int)$id;
        $pdo->prepare("INSERT INTO cliente ($campo) VALUES (?)")->execute([$valor]);
        return (int)$pdo->lastInsertId();
    }

    // Baja el stock (primero los lotes que vencen antes; el resto sin lote) y deja cada salida en el kardex
    private function descontarStock(PDO $pdo, int $idTienda, int $idEmpleado, int $idVenta, array $l): void
    {
        $restante = $l['qMil'];
        $saldo = $l['dispMil'];
        $salidas = [];
        $st = $pdo->prepare(
            'SELECT id_lote, cantidad_actual FROM lote_producto
              WHERE id_tienda = ? AND id_producto = ? AND cantidad_actual > 0 AND fecha_vencimiento >= CURDATE()
              ORDER BY fecha_vencimiento, id_lote FOR UPDATE'
        );
        $st->execute([$idTienda, $l['id']]);
        foreach ($st->fetchAll() as $lote) {
            if ($restante <= 0) break;
            $toma = min($restante, (int)round((float)$lote['cantidad_actual'] * 1000));
            if ($toma <= 0) continue;
            $pdo->prepare('UPDATE lote_producto SET cantidad_actual = cantidad_actual - ? WHERE id_lote = ?')
                ->execute([$this->mil($toma), $lote['id_lote']]);
            $salidas[] = [$toma, (int)$lote['id_lote']];
            $restante -= $toma;
        }
        if ($restante > 0) $salidas[] = [$restante, null];

        $pdo->prepare('UPDATE stock SET cantidad_actual = cantidad_actual - ? WHERE id_tienda = ? AND id_producto = ?')
            ->execute([$this->mil($l['qMil']), $idTienda, $l['id']]);
        foreach ($salidas as [$cant, $idLote]) {
            $saldo -= $cant;
            $pdo->prepare(
                'INSERT INTO movimiento_stock (fecha_hora, cantidad, stock_nuevo, observacion, id_tienda, id_producto,
                                               id_tipo_movimiento, id_empleado, id_venta, id_lote)
                 VALUES (NOW(), ?, ?, NULL, ?, ?, 1, ?, ?, ?)'
            )->execute([$this->mil($cant), $this->mil($saldo), $idTienda, $l['id'], $idEmpleado, $idVenta, $idLote]);
        }
    }

    private function cent(int $c): string { return number_format($c / 100, 2, '.', ''); }
    private function mil(int $m): string { return number_format($m / 1000, 3, '.', ''); }
}
