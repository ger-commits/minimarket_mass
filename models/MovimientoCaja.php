<?php
// M: tabla movimiento_caja: ingreso, retiro, gasto o devolución de dinero dentro de un turno de caja.
class MovimientoCaja
{
    public function __construct(
        public int $idMovimientoCaja,
        public string $fechaHora,
        public string $tipo,   // INGRESO, RETIRO, GASTO o DEVOLUCION
        public float $monto,
        public string $motivo,
        public int $idCajaTurno,
        public ?int $idVenta   // opcional: solo en DEVOLUCION (venta de la que se devuelve el dinero)
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_movimiento_caja'],
            (string)$fila['fecha_hora'],
            (string)$fila['tipo'],
            (float)$fila['monto'],
            (string)$fila['motivo'],
            (int)$fila['id_caja_turno'],
            $fila['id_venta'] === null ? null : (int)$fila['id_venta']
        );
    }

    // solo el movimiento de tipo DEVOLUCION lleva venta
    public function coherente(): bool
    {
        return ($this->tipo === 'DEVOLUCION') === ($this->idVenta !== null) && $this->monto > 0;
    }
}
