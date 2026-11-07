<?php
// M: solicitudes de anulación. El CAJERO de la venta la solicita; el ADMINISTRADOR de la misma tienda la aprueba o la rechaza.
// Al aprobar, en UNA sola transacción (todo o nada):
//   1. la solicitud pasa a APROBADA y la venta a ANULADA, y sus pagos a ANULADO;
//   2. el stock vuelve al inventario (un movimiento de ANULACION DE VENTA en el kardex por cada movimiento de la venta, con su lote);
//   3. se emite la nota de crédito (serie BC.. para boletas, FC.. para facturas);
//   4. se registra la anulación en el Sistema central (tabla registro_anulacion_central).
// Si la venta se pagó en efectivo en un turno de caja ya cerrado, el dinero devuelto sale del turno abierto del cajero
// (movimiento_caja de tipo DEVOLUCION). Requiere las tablas de database/anulacion.sql.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Administrador.php';
require_once __DIR__ . '/SolicitudAnulacion.php';

class SolicitudAnulacionRepository
{
    private const TIPO_VENTA = 1;
    private const TIPO_ANULACION = 2;
    private const METODO_EFECTIVO = 1;
    // Motivos que puede elegir el cajero. VENCIDO y DAÑADO: el producto no vuelve al stock y pasa a merma.
    // ERROR: el cliente se confundió de producto, así que el producto vuelve al stock. (El robo lo registra el almacenero.)
    private const MOTIVOS = ['VENCIDO', 'DAÑADO', 'ERROR'];

    // Devuelve el motivo que empieza el texto ('VENCIDO: detalle' -> 'VENCIDO'), o null si no es válido
    private function categoriaMerma(string $motivo): ?string
    {
        $inicio = mb_strtoupper(trim($motivo));
        foreach (self::MOTIVOS as $cat) {
            if (str_starts_with($inicio, $cat)) return $cat;
        }
        return null;
    }

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::conectar();
    }

    // El cajero pide anular una venta suya. Devuelve el id de la solicitud.
    public function solicitar(int $idVenta, int $idCajero, string $motivo): int
    {
        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo) > 150) {
            throw new InvalidArgumentException('El motivo es obligatorio (máximo 150 caracteres).');
        }
        if ($this->categoriaMerma($motivo) === null) {
            throw new InvalidArgumentException('Elige el motivo: Vencido, Dañado o Robo.');
        }
        return $this->transaccion(function () use ($idVenta, $idCajero, $motivo) {
            $bloqueo = $this->bloqueo();
            $st = $this->pdo->prepare(
                'SELECT v.estado, ct.id_empleado AS id_cajero
                   FROM venta v JOIN caja_turno ct ON ct.id_caja_turno = v.id_caja_turno
                  WHERE v.id_venta = ?' . $bloqueo
            );
            $st->execute([$idVenta]);
            $venta = $st->fetch();
            if (!$venta) throw new RuntimeException('La venta no existe.');
            if ($venta['estado'] !== 'EMITIDA') throw new RuntimeException('Solo se puede anular una venta emitida.');
            if ((int)$venta['id_cajero'] !== $idCajero) {
                throw new RuntimeException('Solo el cajero de la venta puede solicitar su anulación.');
            }
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM solicitud_anulacion WHERE id_venta = ? AND estado = 'PENDIENTE'");
            $st->execute([$idVenta]);
            if ((int)$st->fetchColumn() > 0) throw new RuntimeException('La venta ya tiene una solicitud pendiente.');

            $st = $this->pdo->prepare(
                "INSERT INTO solicitud_anulacion (fecha_hora_solicitud, motivo, estado, id_venta, id_empleado_solicita)
                 VALUES (NOW(), ?, 'PENDIENTE', ?, ?)"
            );
            $st->execute([$motivo, $idVenta, $idCajero]);
            return (int)$this->pdo->lastInsertId();
        });
    }

    // Bandeja del administrador: solicitudes pendientes de ventas de su tienda.
    public function pendientesDeTienda(int $idTienda): array
    {
        $st = $this->pdo->prepare($this->consultaBandeja() .
            " WHERE sa.estado = 'PENDIENTE' AND s.id_tienda = ? ORDER BY sa.fecha_hora_solicitud");
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    // Últimas solicitudes ya resueltas (aprobadas o rechazadas) de la tienda.
    public function resueltasDeTienda(int $idTienda, int $limite = 10): array
    {
        $st = $this->pdo->prepare($this->consultaBandeja() .
            " WHERE sa.estado <> 'PENDIENTE' AND s.id_tienda = ?
              ORDER BY sa.fecha_hora_resolucion DESC, sa.id_solicitud DESC LIMIT " . (int)$limite);
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    private function consultaBandeja(): string
    {
        return "SELECT sa.id_solicitud, sa.fecha_hora_solicitud, sa.fecha_hora_resolucion, sa.estado, sa.motivo, sa.motivo_rechazo,
                       sa.id_venta, s.serie, v.numero_correlativo, v.total, v.fecha_hora AS fecha_venta,
                       tc.nombre_comprobante AS tipo, c.numero_caja,
                       CONCAT(e.nombres, ' ', e.apellidos) AS solicitante,
                       CONCAT(adm.nombres, ' ', adm.apellidos) AS resuelto_por,
                       CONCAT(snc.serie, '-', LPAD(nc.numero_correlativo, 6, '0')) AS nota_credito,
                       (SELECT COUNT(*) FROM detalle_venta d WHERE d.id_venta = v.id_venta) AS lineas,
                       (SELECT GROUP_CONCAT(DISTINCT mp.nombre_metodo ORDER BY mp.id_metodo_pago SEPARATOR ', ')
                          FROM pago_venta pv JOIN metodo_pago mp ON mp.id_metodo_pago = pv.id_metodo_pago
                         WHERE pv.id_venta = v.id_venta) AS medios
                  FROM solicitud_anulacion sa
                  JOIN venta v             ON v.id_venta = sa.id_venta
                  JOIN serie_comprobante s ON s.id_serie = v.id_serie
                  JOIN tipo_comprobante tc ON tc.id_tipo_comprobante = s.id_tipo_comprobante
                  JOIN caja_turno ct       ON ct.id_caja_turno = v.id_caja_turno
                  JOIN caja c              ON c.id_caja = ct.id_caja
                  JOIN empleado e          ON e.id_empleado = sa.id_empleado_solicita
                  LEFT JOIN empleado adm   ON adm.id_empleado = sa.id_empleado_resuelve
                  LEFT JOIN nota_credito nc ON nc.id_solicitud = sa.id_solicitud
                  LEFT JOIN serie_nota_credito snc ON snc.id_serie_nc = nc.id_serie_nc";
    }

    public function buscar(int $idSolicitud): ?SolicitudAnulacion
    {
        $st = $this->pdo->prepare('SELECT * FROM solicitud_anulacion WHERE id_solicitud = ?');
        $st->execute([$idSolicitud]);
        $f = $st->fetch();
        return $f ? SolicitudAnulacion::desde($f) : null;
    }

    // Lo que ve el CAJERO mientras espera: estado de su solicitud y, si fue aprobada, lo que hizo el sistema.
    public function estadoParaCajero(int $idSolicitud, int $idCajero): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT sa.id_solicitud, sa.estado, sa.motivo_rechazo, sa.fecha_hora_resolucion, sa.id_venta,
                    v.estado AS estado_venta, nc.monto AS monto_nota,
                    CONCAT(snc.serie, '-', LPAD(nc.numero_correlativo, 6, '0')) AS nota_credito,
                    rc.codigo_central,
                    CONCAT(adm.nombres, ' ', adm.apellidos) AS administrador,
                    (SELECT COUNT(*) FROM movimiento_stock ms
                      WHERE ms.id_venta = sa.id_venta AND ms.id_tipo_movimiento = ?) AS movimientos_stock
               FROM solicitud_anulacion sa
               JOIN venta v ON v.id_venta = sa.id_venta
               LEFT JOIN empleado adm ON adm.id_empleado = sa.id_empleado_resuelve
               LEFT JOIN nota_credito nc ON nc.id_solicitud = sa.id_solicitud
               LEFT JOIN serie_nota_credito snc ON snc.id_serie_nc = nc.id_serie_nc
               LEFT JOIN registro_anulacion_central rc ON rc.id_solicitud = sa.id_solicitud
              WHERE sa.id_solicitud = ? AND sa.id_empleado_solicita = ?"
        );
        $st->execute([self::TIPO_ANULACION, $idSolicitud, $idCajero]);
        return $st->fetch() ?: null;
    }

    // El administrador AUTORIZA la anulación. Devuelve la nota de crédito y el código del Sistema central.
    public function aprobar(int $idSolicitud, Administrador $admin): array
    {
        return $this->transaccion(function () use ($idSolicitud, $admin) {
            [$sol, $tiendaVenta] = $this->cargarPendiente($idSolicitud, $admin);
            $ahora = (string)$this->pdo->query('SELECT NOW()')->fetchColumn();

            // la venta se bloquea para que nadie más la anule o la toque a la vez
            $st = $this->pdo->prepare(
                'SELECT v.estado, v.total, v.id_caja_turno, v.numero_correlativo, s.serie, s.id_tipo_comprobante
                   FROM venta v JOIN serie_comprobante s ON s.id_serie = v.id_serie
                  WHERE v.id_venta = ?' . $this->bloqueo()
            );
            $st->execute([$sol->idVenta]);
            $venta = $st->fetch();
            if (!$venta) throw new RuntimeException('La venta no existe.');
            if ($venta['estado'] !== 'EMITIDA') throw new RuntimeException('La venta ya está anulada.');

            // dinero en efectivo que hay que devolver (se calcula ANTES de marcar los pagos como ANULADO)
            $st = $this->pdo->prepare(
                "SELECT COALESCE(SUM(monto_pagado), 0) FROM pago_venta
                  WHERE id_venta = ? AND estado = 'VALIDO' AND id_metodo_pago = ?"
            );
            $st->execute([$sol->idVenta, self::METODO_EFECTIVO]);
            $efectivo = round((float)$st->fetchColumn(), 2);

            // si el turno de la venta sigue abierto, el pago ANULADO ya sale del cuadre; si está cerrado, la devolución
            // sale del turno abierto del cajero
            $idTurnoDevolucion = null;
            if ($efectivo > 0) {
                $st = $this->pdo->prepare("SELECT estado FROM caja_turno WHERE id_caja_turno = ?");
                $st->execute([$venta['id_caja_turno']]);
                if ($st->fetchColumn() === 'CERRADO') {
                    $st = $this->pdo->prepare(
                        "SELECT id_caja_turno FROM caja_turno WHERE id_empleado = ? AND estado = 'ABIERTO'"
                    );
                    $st->execute([$sol->idEmpleadoSolicita]);
                    $idTurnoDevolucion = $st->fetchColumn();
                    if ($idTurnoDevolucion === false) {
                        throw new RuntimeException('El cajero debe tener un turno de caja abierto para devolver el efectivo de esta venta.');
                    }
                }
            }

            $categoria = $this->categoriaMerma($sol->motivo);
            if ($categoria === null) {
                throw new RuntimeException('Esta solicitud no tiene un motivo válido (Vencido, Dañado o Robo). Recházala y que el cajero la vuelva a pedir.');
            }

            // 1. solicitud aprobada, venta y pagos anulados
            $this->ejecutar(
                "UPDATE solicitud_anulacion SET estado = 'APROBADA', fecha_hora_resolucion = ?, id_empleado_resuelve = ?
                  WHERE id_solicitud = ?",
                [$ahora, $admin->idEmpleado, $idSolicitud]
            );
            $this->ejecutar("UPDATE venta SET estado = 'ANULADA' WHERE id_venta = ?", [$sol->idVenta]);
            $this->ejecutar("UPDATE pago_venta SET estado = 'ANULADO' WHERE id_venta = ?", [$sol->idVenta]);
            if ($idTurnoDevolucion !== null) {
                $this->ejecutar(
                    "INSERT INTO movimiento_caja (fecha_hora, tipo, monto, motivo, id_caja_turno, id_venta)
                     VALUES (?, 'DEVOLUCION', ?, ?, ?, ?)",
                    [$ahora, $efectivo, 'Anulación de venta ' . $venta['serie'] . '-' . str_pad((string)$venta['numero_correlativo'], 6, '0', STR_PAD_LEFT),
                     $idTurnoDevolucion, $sol->idVenta]
                );
            }

            // 2. el stock: ERROR lo devuelve al inventario; VENCIDO y DAÑADO no, y la baja queda en merma
            if ($categoria === 'ERROR') {
                $st = $this->pdo->prepare(
                    'SELECT id_tienda, id_producto, cantidad, id_lote FROM movimiento_stock
                      WHERE id_venta = ? AND id_tipo_movimiento = ? ORDER BY id_movimiento'
                );
                $st->execute([$sol->idVenta, self::TIPO_VENTA]);
                foreach ($st->fetchAll() as $m) {
                    $cant = (float)$m['cantidad'];
                    $this->ejecutar(
                        'UPDATE stock SET cantidad_actual = cantidad_actual + ? WHERE id_tienda = ? AND id_producto = ?',
                        [$cant, $m['id_tienda'], $m['id_producto']]
                    );
                    if ($m['id_lote'] !== null) {
                        $this->ejecutar('UPDATE lote_producto SET cantidad_actual = cantidad_actual + ? WHERE id_lote = ?',
                            [$cant, $m['id_lote']]);
                    }
                    $q = $this->pdo->prepare('SELECT cantidad_actual FROM stock WHERE id_tienda = ? AND id_producto = ?');
                    $q->execute([$m['id_tienda'], $m['id_producto']]);
                    $nuevo = (float)$q->fetchColumn();
                    $this->ejecutar(
                        'INSERT INTO movimiento_stock (fecha_hora, cantidad, stock_nuevo, observacion, id_tienda, id_producto,
                                                       id_tipo_movimiento, id_empleado, id_venta, id_lote)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$ahora, $cant, $nuevo, 'Anulación de venta', $m['id_tienda'], $m['id_producto'],
                         self::TIPO_ANULACION, $admin->idEmpleado, $sol->idVenta, $m['id_lote']]
                    );
                }
            } else {
                $st = $this->pdo->prepare(
                    'SELECT id_tienda, id_producto, cantidad, id_lote FROM movimiento_stock
                      WHERE id_venta = ? AND id_tipo_movimiento = ? ORDER BY id_movimiento'
                );
                $st->execute([$sol->idVenta, self::TIPO_VENTA]);
                $movimientosVenta = $st->fetchAll();
                $this->ejecutar(
                    'INSERT INTO merma (fecha_hora, motivo, observacion, id_empleado) VALUES (?, ?, ?, ?)',
                    [$ahora, $categoria, mb_substr('Devolución de la venta ' . $venta['serie'] . '-' . str_pad((string)$venta['numero_correlativo'], 6, '0', STR_PAD_LEFT) . ': ' . $sol->motivo, 0, 200), $admin->idEmpleado]
                );
                $idMerma = (int)$this->pdo->lastInsertId();
                foreach ($movimientosVenta as $m) {
                    $q = $this->pdo->prepare('SELECT cantidad_actual FROM stock WHERE id_tienda = ? AND id_producto = ?');
                    $q->execute([$m['id_tienda'], $m['id_producto']]);
                    $actual = (float)$q->fetchColumn();
                    $this->ejecutar(
                        'INSERT INTO movimiento_stock (fecha_hora, cantidad, stock_nuevo, observacion, id_tienda, id_producto,
                                                       id_tipo_movimiento, id_empleado, id_lote, id_merma)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [$ahora, (float)$m['cantidad'], $actual, 'Baja por devolución (stock ya descontado en la venta)',
                         $m['id_tienda'], $m['id_producto'], 5, $admin->idEmpleado, $m['id_lote'], $idMerma]
                    );
                }
            }

            // 3. nota de crédito con el siguiente correlativo de la serie de la tienda
            $st = $this->pdo->prepare(
                'SELECT id_serie_nc, serie FROM serie_nota_credito WHERE id_tienda = ? AND id_tipo_comprobante = ?' . $this->bloqueo()
            );
            $st->execute([$tiendaVenta, $venta['id_tipo_comprobante']]);
            $serieNc = $st->fetch();
            if (!$serieNc) {
                throw new RuntimeException('No hay una serie de notas de crédito para esta tienda (ejecute database/anulacion.sql).');
            }
            $this->ejecutar('UPDATE serie_nota_credito SET ultimo_numero = ultimo_numero + 1 WHERE id_serie_nc = ?', [$serieNc['id_serie_nc']]);
            $st = $this->pdo->prepare('SELECT ultimo_numero FROM serie_nota_credito WHERE id_serie_nc = ?');
            $st->execute([$serieNc['id_serie_nc']]);
            $numeroNc = (int)$st->fetchColumn();
            $this->ejecutar(
                'INSERT INTO nota_credito (numero_correlativo, fecha_hora, motivo, monto, id_serie_nc, id_venta, id_solicitud)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$numeroNc, $ahora, $sol->motivo, $venta['total'], $serieNc['id_serie_nc'], $sol->idVenta, $idSolicitud]
            );
            $idNota = (int)$this->pdo->lastInsertId();
            $notaCredito = $serieNc['serie'] . '-' . str_pad((string)$numeroNc, 6, '0', STR_PAD_LEFT);
            $comprobante = $venta['serie'] . '-' . str_pad((string)$venta['numero_correlativo'], 6, '0', STR_PAD_LEFT);

            // 4. registro de la anulación en el Sistema central
            $codigoCentral = 'ANU-' . date('Ymd', strtotime($ahora)) . '-' . str_pad((string)$idSolicitud, 6, '0', STR_PAD_LEFT);
            $this->ejecutar(
                "INSERT INTO registro_anulacion_central (codigo_central, fecha_hora_registro, estado, detalle, id_solicitud, id_venta, id_nota_credito)
                 VALUES (?, ?, 'REGISTRADO', ?, ?, ?, ?)",
                [$codigoCentral, $ahora, json_encode([
                    'comprobante'  => $comprobante,
                    'nota_credito' => $notaCredito,
                    'monto'        => (float)$venta['total'],
                    'motivo'       => $sol->motivo,
                    'autorizado_por' => $admin->idEmpleado,
                    'solicitado_por' => $sol->idEmpleadoSolicita,
                ], JSON_UNESCAPED_UNICODE), $idSolicitud, $sol->idVenta, $idNota]
            );

            return ['comprobante' => $comprobante, 'nota_credito' => $notaCredito, 'codigo_central' => $codigoCentral];
        });
    }

    // El administrador RECHAZA la solicitud (la venta sigue EMITIDA y puede volver a solicitarse).
    public function rechazar(int $idSolicitud, Administrador $admin, string $motivoRechazo): void
    {
        $motivoRechazo = trim($motivoRechazo);
        if ($motivoRechazo === '' || mb_strlen($motivoRechazo) > 150) {
            throw new InvalidArgumentException('El motivo de rechazo es obligatorio (máximo 150 caracteres).');
        }
        $this->transaccion(function () use ($idSolicitud, $admin, $motivoRechazo) {
            $this->cargarPendiente($idSolicitud, $admin);
            $this->ejecutar(
                "UPDATE solicitud_anulacion SET estado = 'RECHAZADA', fecha_hora_resolucion = NOW(), id_empleado_resuelve = ?,
                        motivo_rechazo = ? WHERE id_solicitud = ?",
                [$admin->idEmpleado, $motivoRechazo, $idSolicitud]
            );
        });
    }

    // Carga la solicitud pendiente y comprueba las reglas: tienda del administrador y solicitante distinto.
    private function cargarPendiente(int $idSolicitud, Administrador $admin): array
    {
        $st = $this->pdo->prepare('SELECT * FROM solicitud_anulacion WHERE id_solicitud = ?' . $this->bloqueo());
        $st->execute([$idSolicitud]);
        $f = $st->fetch();
        if (!$f) throw new RuntimeException('La solicitud no existe.');
        $sol = SolicitudAnulacion::desde($f);
        if (!$sol->estaPendiente()) throw new RuntimeException('La solicitud ya fue resuelta.');
        if ($admin->idEmpleado === $sol->idEmpleadoSolicita) {
            throw new RuntimeException('Quien resuelve debe ser distinto de quien solicita.');
        }
        $st = $this->pdo->prepare(
            'SELECT s.id_tienda FROM venta v JOIN serie_comprobante s ON s.id_serie = v.id_serie WHERE v.id_venta = ?'
        );
        $st->execute([$sol->idVenta]);
        $tienda = (int)$st->fetchColumn();
        if (!$admin->puedeResolverSolicitud($tienda)) {
            throw new RuntimeException('Solo el administrador de la tienda de la venta puede resolver la solicitud.');
        }
        return [$sol, $tienda];
    }

    private function bloqueo(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    }

    private function ejecutar(string $sql, array $params): void
    {
        $this->pdo->prepare($sql)->execute($params);
    }

    // todo o nada; devuelve lo que devuelva la acción
    private function transaccion(callable $accion): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultado = $accion();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}