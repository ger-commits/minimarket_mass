<?php
// M: turnos de trabajo (horario) que el ADMINISTRADOR programa a los empleados de SU tienda.
// No es el turno de caja (caja_turno): este es el horario, el otro controla el dinero.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Administrador.php';
require_once __DIR__ . '/TurnoTrabajo.php';

class TurnoTrabajoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::conectar();
    }

    // Programa el turno de un empleado un día. Devuelve el id del turno.
    public function programar(Administrador $admin, int $idEmpleado, string $fecha, string $horaInicio, string $horaFin): int
    {
        if ($horaFin <= $horaInicio) throw new InvalidArgumentException('La hora de salida debe ser posterior a la de entrada.');

        $st = $this->pdo->prepare("SELECT id_tienda FROM empleado WHERE id_empleado = ? AND estado = 'ACTIVO'");
        $st->execute([$idEmpleado]);
        $tienda = $st->fetchColumn();
        if ($tienda === false) throw new RuntimeException('El empleado no existe o no está activo.');
        if (!$admin->puedeProgramarTurno((int)$tienda)) {
            throw new RuntimeException('Solo puedes programar turnos a empleados de tu tienda.');
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM turno_trabajo WHERE id_empleado = ? AND fecha = ?');
        $st->execute([$idEmpleado, $fecha]);
        if ((int)$st->fetchColumn() > 0) throw new RuntimeException('El empleado ya tiene un turno ese día.');

        $st = $this->pdo->prepare(
            'INSERT INTO turno_trabajo (fecha, hora_inicio, hora_fin, id_empleado, id_empleado_programa) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$fecha, $horaInicio, $horaFin, $idEmpleado, $admin->idEmpleado]);
        return (int)$this->pdo->lastInsertId();
    }

    // Rol de turnos de una tienda en un día.
    public function delDia(int $idTienda, string $fecha): array
    {
        $st = $this->pdo->prepare(
            "SELECT tt.id_turno_trabajo, CONCAT(e.nombres, ' ', e.apellidos) AS empleado, r.nombre_rol,
                    tt.hora_inicio, tt.hora_fin
               FROM turno_trabajo tt
               JOIN empleado e ON e.id_empleado = tt.id_empleado
               JOIN rol r      ON r.id_rol = e.id_rol
              WHERE e.id_tienda = ? AND tt.fecha = ?
              ORDER BY tt.hora_inicio, e.apellidos"
        );
        $st->execute([$idTienda, $fecha]);
        return $st->fetchAll();
    }

    public function deEmpleado(int $idEmpleado): array
    {
        $st = $this->pdo->prepare('SELECT * FROM turno_trabajo WHERE id_empleado = ? ORDER BY fecha');
        $st->execute([$idEmpleado]);
        return array_map(fn($f) => TurnoTrabajo::desde($f), $st->fetchAll());
    }
}