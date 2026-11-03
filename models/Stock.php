<?php
// M: tabla stock: stock de un producto en una tienda (llave compuesta tienda + producto).
class Stock
{
    public function __construct(
        public int $idTienda,
        public int $idProducto,
        public float $cantidadActual,
        public float $stockMinimo
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_tienda'],
            (int)$fila['id_producto'],
            (float)$fila['cantidad_actual'],
            (float)$fila['stock_minimo']
        );
    }

    // el stock nunca es negativo; se repone cuando llega al mínimo o menos
    public function debeReponerse(): bool { return $this->cantidadActual <= $this->stockMinimo; }

    public function alcanza(float $cantidad): bool { return $cantidad > 0 && $cantidad <= $this->cantidadActual; }
}
