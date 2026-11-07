<?php
// M: tabla lote_producto: lote con fecha de vencimiento de un producto perecible en una tienda.
class LoteProducto
{
    public function __construct(
        public int $idLote,
        public string $codigoLote,
        public string $fechaVencimiento,
        public float $cantidadInicial,   // lo que llego en el lote
        public float $cantidadActual,   // lo que queda en la tienda
        public int $idTienda,
        public int $idProducto
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_lote'],
            (string)$fila['codigo_lote'],
            (string)$fila['fecha_vencimiento'],
            (float)$fila['cantidad_inicial'],
            (float)$fila['cantidad_actual'],
            (int)$fila['id_tienda'],
            (int)$fila['id_producto']
        );
    }

    public function estaVencido(?string $hoy = null): bool
    {
        return $this->fechaVencimiento < ($hoy ?? date('Y-m-d'));
    }

    // un lote vencido solo sale como merma, nunca como venta
    public function sePuedeVender(?string $hoy = null): bool
    {
        return !$this->estaVencido($hoy) && $this->cantidadActual > 0;
    }
}
