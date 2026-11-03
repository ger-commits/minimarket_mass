<?php
// M: solicitudes de anulación. El CAJERO de la venta la solicita; el ADMINISTRADOR de la misma tienda la aprueba o la rechaza.
// Al aprobar, en una sola transacción: la venta pasa a ANULADA, los pagos a ANULADO y el stock vuelve al inventario
// (un movimiento de ANULACION DE VENTA en el kardex por cada producto vendido, con su lote si lo tenía).
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Administrador.php';
require_once __DIR__ . '/SolicitudAnulacion.php';

class SolicitudAnulacionRepository
{
    private const TIPO_VENTA = 1;
    private const TIPO_ANULACION = 2;

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
        $st = $this->pdo->prepare(
            'SELECT v.estado, ct.id_empleado AS id_cajero
               FROM venta v JOIN caja_turno ct ON ct.id_caja_turno = v.id_caja_turno
              WHERE v.id_venta = ?'
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
             VALUES (?, ?, 'PENDIENTE', ?, ?)"
        );
        $st->execute([date('Y-m-d H:i:s'), $motivo, $idVenta, $idCajero]);
        return (int)$this->pdo->lastInsertId();
    }

    // Bandeja del administrador: solicitudes pendientes de ventas de su tienda.
    public function pendientesDeTienda(int $idTienda): array
    {
        $st = $this->pdo->prepare(
            "SELECT sa.id_solicitud, sa.fecha_hora_solicitud, s.serie, v.numero_correlativo, v.total, sa.motivo,
                    CONCAT(e.nombres, ' ', e.apellidos) AS solicitante
               FROM solicitud_anulacion sa
               JOIN venta v             ON v.id_venta = sa.id_venta
               JOIN serie_comprobante s ON s.id_serie = v.id_serie
               JOIN empleado e          ON e.id_empleado = sa.id_empleado_solicita
              WHERE sa.estado = 'PENDIENTE' AND s.id_tienda = ?
              ORDER BY sa.fecha_hora_solicitud"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    public function buscar(int $idSolicitud): ?SolicitudAnulacion
    {
        $st = $this->pdo->prepare('SELECT * FROM solicitud_anulacion WHERE id_solicitud = ?');
        $st->execute([$idSolicitud]);
        $f = $st->fetch();
        return $f ? SolicitudAnulacion::desdeFila($f) : null;
    }

    // El administrador AUTORIZA la anulación.
    public function aprobar(int $idSolicitud, Administrador $admin): void
    {
        $this->transaccion(function () use ($idSolicitud, $admin) {
            [$sol, $tiendaVenta] = $this->cargarPendiente($idSolicitud, $admin);
            $ahora = date('Y-m-d H:i:s');

            $this->ejecutar(
                "UPDATE solicitud_anulacion SET estado = 'APROBADA', fecha_hora_resolucion = ?, id_empleado_resuelve = ?
                  WHERE id_solicitud = ?",
                [$ahora, $admin->idEmpleado, $idSolicitud]
            );
            $this->ejecutar("UPDATE venta SET estado = 'ANULADA' WHERE id_venta = ?", [$sol->idVenta]);
            $this->ejecutar("UPDATE pago_venta SET estado = 'ANULADO' WHERE id_venta = ?", [$sol->idVenta]);

            // el stock vuelve: una entrada en el kardex por cada movimiento de venta
            $st = $this->pdo->prepare(
                'SELECT id_tienda, id_producto, cantidad, id_lote FROM movimiento_stock
                  WHERE id_venta = ? AND id_tipo_movimiento = ?'
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
                "UPDATE solicitud_anulacion SET estado = 'RECHAZADA', fecha_hora_resolucion = ?, id_empleado_resuelve = ?,
                        motivo_rechazo = ? WHERE id_solicitud = ?",
                [date('Y-m-d H:i:s'), $admin->idEmpleado, $motivoRechazo, $idSolicitud]
            );
        });
    }

    // Carga la solicitud pendiente y comprueba las reglas: tienda del administrador y solicitante distinto.
    private function cargarPendiente(int $idSolicitud, Administrador $admin): array
    {
        $bloqueo = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $st = $this->pdo->prepare('SELECT * FROM solicitud_anulacion WHERE id_solicitud = ?' . $bloqueo);
        $st->execute([$idSolicitud]);
        $f = $st->fetch();
        if (!$f) throw new RuntimeException('La solicitud no existe.');
        $sol = SolicitudAnulacion::desdeFila($f);
        if (!$sol->estaPendiente()) throw new RuntimeException('La solicitud ya fue resuelta.');
        if (!$sol->resolucionValida($admin->idEmpleado)) {
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

    private function ejecutar(string $sql, array $params): void
    {
        $this->pdo->prepare($sql)->execute($params);
    }

    // todo o nada
    private function transaccion(callable $accion): void
    {
        $this->pdo->beginTransaction();
        try {
            $accion();
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
