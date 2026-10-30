<?php
// M: rol administrador. Es el único que anula ventas, y solo las de su propia tienda.
require_once __DIR__ . '/Empleado.php';

class Administrador extends Empleado
{
    public function rol(): string { return 'ADMINISTRADOR'; }

    public function puedeAnular(int $idTiendaDeLaVenta): bool
    {
        return $this->idTienda === $idTiendaDeLaVenta;
    }
}
