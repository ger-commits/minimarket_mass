<?php
// M: tabla serie_comprobante: serie de comprobantes de una tienda (B001, F001...) y su último número emitido.
class SerieComprobante
{
    public function __construct(
        public int $idSerie,
        public string $serie,   // B001, F001...
        public int $ultimoNumero,   // ultimo correlativo emitido
        public int $idTienda,
        public int $idTipoComprobante
    ) {}

    // arma el objeto a partir de una fila de la tabla (resultado de PDO con FETCH_ASSOC)
    public static function desde(array $fila): self
    {
        return new self(
            (int)$fila['id_serie'],
            (string)$fila['serie'],
            (int)$fila['ultimo_numero'],
            (int)$fila['id_tienda'],
            (int)$fila['id_tipo_comprobante']
        );
    }

    // la serie empieza con B (boleta) o F (factura) y tiene 4 caracteres, por ejemplo B001
    public function serieValida(): bool { return (bool)preg_match('/^[BF][0-9A-Z]{3}$/', $this->serie); }

    // el correlativo avanza de uno en uno
    public function siguienteNumero(): int { return $this->ultimoNumero + 1; }
}
