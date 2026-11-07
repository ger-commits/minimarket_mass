<?php
// M: tabla cliente: cliente de la venta: solo DNI y/o RUC (al menos uno).
class Cliente
{
    public function __construct(
        public int $idCliente,
        public ?string $dni,   // opcional
        public ?string $ruc   // opcional
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_cliente'],
            $fila['dni'] === null ? null : (string)$fila['dni'],
            $fila['ruc'] === null ? null : (string)$fila['ruc']
        );
    }

    // al menos uno de los dos; DNI de 8 dígitos y RUC de 11, solo números
    public function esValido(): bool
    {
        if ($this->dni === null && $this->ruc === null) return false;
        if ($this->dni !== null && !preg_match('/^[0-9]{8}$/', $this->dni)) return false;
        if ($this->ruc !== null && !preg_match('/^[0-9]{11}$/', $this->ruc)) return false;
        return true;
    }

    // la factura exige un cliente con RUC; la boleta puede ir sin cliente
    public function puedeFacturar(): bool { return $this->ruc !== null; }
}
