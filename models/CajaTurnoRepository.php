<?php
// M: turno de caja (tabla caja_turno). El cajero abre la caja con un monto inicial y la cierra contando el efectivo.
// Efectivo esperado = monto inicial + ventas en efectivo vigentes del turno + ingresos - retiros - gastos - devoluciones.
// Al cerrar se guarda el esperado y la observación (migracion_002_cuadre_caja.sql): así el cuadre de un turno cerrado no cambia después.
require_once __DIR__ . '/Database.php';

class CajaTurnoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::conectar();
    }

    private ?bool $conCuadre = null;

    // ¿Ya se ejecutó migracion_002_cuadre_caja.sql? Si no, el cierre funciona igual pero sin guardar el esperado.
    private function tieneCuadre(): bool
    {
        if ($this->conCuadre === null) {
            $this->conCuadre = (bool)$this->pdo->query("SHOW COLUMNS FROM caja_turno LIKE 'monto_esperado'")->fetch();
        }
        return $this->conCuadre;
    }

    // Turno ABIERTO del cajero (con los datos de su caja), o null.
    public function abiertoDe(int $idEmpleado): ?array
    {
        $st = $this->pdo->prepare(
            "SELECT ct.id_caja_turno, ct.fecha_hora_apertura, ct.monto_inicial, ct.estado, ct.id_caja, c.numero_caja,
                    (DATE(ct.fecha_hora_apertura) < CURDATE()) AS de_dia_anterior
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

    // Retiro a bóveda, gasto o ingreso de efectivo del turno abierto (tabla movimiento_caja). Las devoluciones las crea la anulación.
    public function registrarMovimiento(int $idTurno, string $tipo, float $monto, string $motivo): int
    {
        $tipo = strtoupper(trim($tipo));
        if (!in_array($tipo, ['RETIRO', 'GASTO', 'INGRESO'], true)) throw new InvalidArgumentException('Elija el tipo de movimiento.');
        $monto = round($monto, 2);
        if ($monto <= 0 || $monto > 99999999.99) throw new InvalidArgumentException('Ingrese un monto válido.');
        $motivo = mb_substr(trim($motivo), 0, 100);
        if ($motivo === '') throw new InvalidArgumentException('Escriba el motivo del movimiento.');
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("SELECT monto_inicial FROM caja_turno WHERE id_caja_turno = ? AND estado = 'ABIERTO' FOR UPDATE");
            $st->execute([$idTurno]);
            $ini = $st->fetchColumn();
            if ($ini === false) throw new RuntimeException('No hay un turno abierto.');
            if ($tipo !== 'INGRESO' && $monto > $this->esperado($idTurno, (float)$ini)) {
                throw new RuntimeException('El monto supera el efectivo que hay en la caja.');
            }
            $st = $this->pdo->prepare("INSERT INTO movimiento_caja (fecha_hora, tipo, monto, motivo, id_caja_turno, id_venta) VALUES (NOW(), ?, ?, ?, ?, NULL)");
            $st->execute([$tipo, $monto, $motivo, $idTurno]);
            $id = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function movimientos(int $idTurno): array
    {
        $st = $this->pdo->prepare(
            "SELECT id_movimiento_caja, fecha_hora, tipo, monto, motivo FROM movimiento_caja
              WHERE id_caja_turno = ? ORDER BY fecha_hora DESC, id_movimiento_caja DESC"
        );
        $st->execute([$idTurno]);
        return $st->fetchAll();
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
    public function cerrar(int $idTurno, int $idEmpleado, float $contado, string $observacion = ''): array
    {
        if ($contado < 0 || $contado > 99999999.99) throw new InvalidArgumentException('Ingrese el efectivo contado.');
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare("SELECT monto_inicial FROM caja_turno WHERE id_caja_turno = ? AND id_empleado = ? AND estado = 'ABIERTO' FOR UPDATE");
            $st->execute([$idTurno, $idEmpleado]);
            $ini = $st->fetchColumn();
            if ($ini === false) throw new RuntimeException('No hay un turno abierto para cerrar.');
            $esperado = $this->esperado($idTurno, (float)$ini);
            $contado     = round($contado, 2);
            $diferencia  = round($contado - $esperado, 2);
            $observacion = mb_substr(trim($observacion), 0, 200);
            if ($diferencia != 0 && $observacion === '') {
                throw new InvalidArgumentException('El efectivo contado no coincide con lo que registra el sistema. Cuente de nuevo; si el monto es correcto, escriba el motivo en Observación y confirme otra vez.');
            }
            if ($this->tieneCuadre()) {
                $st = $this->pdo->prepare("UPDATE caja_turno SET estado = 'CERRADO', fecha_hora_cierre = NOW(), monto_cierre = ?, monto_esperado = ?, observacion_cierre = ? WHERE id_caja_turno = ?");
                $st->execute([$contado, $esperado, $observacion !== '' ? $observacion : null, $idTurno]);
            } else {
                $st = $this->pdo->prepare("UPDATE caja_turno SET estado = 'CERRADO', fecha_hora_cierre = NOW(), monto_cierre = ? WHERE id_caja_turno = ?");
                $st->execute([$contado, $idTurno]);
            }
            $this->pdo->commit();
            return ['esperado' => $esperado, 'contado' => $contado, 'diferencia' => $diferencia];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    // Últimos turnos cerrados de la caja, con su cuadre
    public function cerrados(int $idCaja, int $limite = 5): array
    {
        $st = $this->pdo->prepare(
            "SELECT ct.id_caja_turno, ct.fecha_hora_apertura, ct.fecha_hora_cierre, ct.monto_inicial, ct.monto_cierre, "
                . ($this->tieneCuadre() ? 'ct.monto_esperado, ct.observacion_cierre,' : 'NULL AS monto_esperado, NULL AS observacion_cierre,') . "
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
