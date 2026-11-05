<?php
// M: turno de caja (tabla caja_turno). El cajero abre la caja con un monto inicial y la cierra contando el efectivo.
// Efectivo esperado = monto inicial + ventas en efectivo vigentes del turno + ingresos - retiros - gastos - devoluciones.
require_once __DIR__ . '/Database.php';

class CajaTurnoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::conectar();
    }

    // Turno ABIERTO del cajero (con los datos de su caja), o null.
    public function abiertoDe(int $idEmpleado): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT ct.id_caja_turno, ct.fecha_hora_apertura, ct.monto_inicial, ct.estado, ct.id_caja, c.numero_caja
               FROM caja_turno ct JOIN caja c ON c.id_caja = ct.id_caja
              WHERE ct.id_empleado = ? AND ct.estado = 'ABIERTO'"
        );
        $st->execute([$idEmpleado]);
        return $st->fetch() ?: null;
    }

    public function abrir(int $idCaja, int $idEmpleado, float $montoInicial): int
    {
        if ($montoInicial < 0 || $montoInicial > 99999999.99) throw new InvalidArgumentException('Ingrese un monto inicial válido.');
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM caja_turno WHERE estado = 'ABIERTO' AND (id_caja = ? OR id_empleado = ?) FOR UPDATE");
            $st->execute([$idCaja, $idEmpleado]);
            if ((int)$st->fetchColumn() > 0) throw new RuntimeException('Ya hay un turno abierto en esta caja o para este cajero.');
            $st = $this->pdo->prepare(
                "INSERT INTO caja_turno (fecha_hora_apertura, monto_inicial, estado, id_caja, id_empleado) VALUES (NOW(), ?, 'ABIERTO', ?, ?)"
            );
            $st->execute([round($montoInicial, 2), $idCaja, $idEmpleado]);
            $id = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    // Totales del turno para el cuadre
    public function resumen(int $idTurno): array
    {
        $st = $this->pdo->prepare(
            "SELECT
               (SELECT COALESCE(SUM(pv.monto_pagado), 0) FROM pago_venta pv JOIN venta v ON v.id_venta = pv.id_venta
                 WHERE v.id_caja_turno = ? AND v.estado = 'EMITIDA' AND pv.estado = 'VALIDO' AND pv.id_metodo_pago = 1) AS efectivo,
               (SELECT COALESCE(SUM(pv.monto_pagado), 0) FROM pago_venta pv JOIN venta v ON v.id_venta = pv.id_venta
                 WHERE v.id_caja_turno = ? AND v.estado = 'EMITIDA' AND pv.estado = 'VALIDO' AND pv.id_metodo_pago <> 1) AS otros,
               (SELECT COALESCE(SUM(CASE WHEN tipo = 'INGRESO' THEN monto ELSE -monto END), 0)
                  FROM movimiento_caja WHERE id_caja_turno = ?) AS movimientos"
        );
        $st->execute([$idTurno, $idTurno, $idTurno]);
        return $st->fetch();
    }

    public function esperado(int $idTurno, float $montoInicial): float
    {
        $r = $this->resumen($idTurno);
        return round($montoInicial + (float)$r['efectivo'] + (float)$r['movimientos'], 2);
    }

    // Cierra el turno con el efectivo contado. Devuelve esperado, contado y diferencia.
    public function cerrar(int $idTurno, int $idEmpleado, float $contado): array
    {
        if ($contado < 0 || $contado > 99999999.99) throw new InvalidArgumentException('Ingrese el efectivo contado.');
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("SELECT monto_inicial FROM caja_turno WHERE id_caja_turno = ? AND id_empleado = ? AND estado = 'ABIERTO' FOR UPDATE");
            $st->execute([$idTurno, $idEmpleado]);
            $ini = $st->fetchColumn();
            if ($ini === false) throw new RuntimeException('No hay un turno abierto para cerrar.');
            $esperado = $this->esperado($idTurno, (float)$ini);
            $st = $this->pdo->prepare("UPDATE caja_turno SET estado = 'CERRADO', fecha_hora_cierre = NOW(), monto_cierre = ? WHERE id_caja_turno = ?");
            $st->execute([round($contado, 2), $idTurno]);
            $this->pdo->commit();
            return ['esperado' => $esperado, 'contado' => round($contado, 2), 'diferencia' => round($contado - $esperado, 2)];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    // Últimos turnos cerrados de la caja, con su cuadre
    public function cerrados(int $idCaja, int $limite = 5): array
    {
        $st = $this->pdo->prepare(
            "SELECT ct.id_caja_turno, ct.fecha_hora_apertura, ct.fecha_hora_cierre, ct.monto_inicial, ct.monto_cierre,
                    (SELECT COALESCE(SUM(pv.monto_pagado), 0) FROM pago_venta pv JOIN venta v ON v.id_venta = pv.id_venta
                      WHERE v.id_caja_turno = ct.id_caja_turno AND v.estado = 'EMITIDA' AND pv.estado = 'VALIDO' AND pv.id_metodo_pago = 1) AS efectivo,
                    (SELECT COALESCE(SUM(CASE WHEN tipo = 'INGRESO' THEN monto ELSE -monto END), 0)
                       FROM movimiento_caja WHERE id_caja_turno = ct.id_caja_turno) AS movimientos
               FROM caja_turno ct
              WHERE ct.id_caja = ? AND ct.estado = 'CERRADO'
              ORDER BY ct.fecha_hora_cierre DESC, ct.id_caja_turno DESC LIMIT " . (int)$limite
        );
        $st->execute([$idCaja]);
        return $st->fetchAll();
    }
}
