<?php
// M: tabla turno_trabajo: horario de trabajo que el administrador programa a un empleado de su tienda (no es el turno de caja).
require_once __DIR__ . '/Administrador.php';
class TurnoTrabajo
{
    public function __construct(
        public int $idTurnoTrabajo,
        public string $fecha,
        public string $horaInicio,
        public string $horaFin,
        public int $idEmpleado,   // empleado que trabaja
        public int $idEmpleadoPrograma   // administrador que lo programa
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_turno_trabajo'],
            (string)$fila['fecha'],
            (string)$fila['hora_inicio'],
            (string)$fila['hora_fin'],
            (int)$fila['id_empleado'],
            (int)$fila['id_empleado_programa']
        );
    }

    // el horario es válido si la hora de salida es posterior a la de entrada
    public function horarioValido(): bool { return $this->horaFin > $this->horaInicio; }

    public function horas(): float
    {
        return round((strtotime($this->horaFin) - strtotime($this->horaInicio)) / 3600, 2);
    }

    // el administrador solo programa turnos a empleados de su propia tienda
    public function loProgramaAdministrador(Administrador $admin, int $idTiendaDelEmpleado): bool
    {
        return $admin->puedeProgramarTurno($idTiendaDelEmpleado) && $admin->idEmpleado === $this->idEmpleadoPrograma;
    }
}
