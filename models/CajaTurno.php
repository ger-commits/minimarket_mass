<?php
// M: tabla caja_turno: turno de caja: apertura y cierre del dinero de una caja por un cajero.
class CajaTurno
{
    public function __construct(
        public int $idCajaTurno,
        public string $fechaHoraApertura,
        public float $montoInicial,
        public ?string $fechaHoraCierre,   // opcional: NULL mientras esta abierto
        public ?float $montoCierre,   // opcional: NULL mientras esta abierto
        public string $estado,
        public int $idCaja,
        public int $idEmpleado   // cajero responsable
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_caja_turno'],
            (string)$fila['fecha_hora_apertura'],
            (float)$fila['monto_inicial'],
            $fila['fecha_hora_cierre'] === null ? null : (string)$fila['fecha_hora_cierre'],
            $fila['monto_cierre'] === null ? null : (float)$fila['monto_cierre'],
            (string)$fila['estado'],
            (int)$fila['id_caja'],
            (int)$fila['id_empleado']
        );
    }

    public function estaAbierto(): bool { return $this->estado === 'ABIERTO'; }

    // monto de cierre esperado = inicial + efectivo cobrado + ingresos - retiros - gastos - devoluciones
    public function montoEsperado(float $efectivoCobrado, float $ingresos, float $retiros, float $gastos, float $devoluciones): float
    {
        return round($this->montoInicial + $efectivoCobrado + $ingresos - $retiros - $gastos - $devoluciones, 2);
    }

    // diferencia entre lo contado al cerrar y lo esperado (0 = cuadra)
    public function diferencia(float $montoEsperado): ?float
    {
        return $this->montoCierre === null ? null : round($this->montoCierre - $montoEsperado, 2);
    }
}
