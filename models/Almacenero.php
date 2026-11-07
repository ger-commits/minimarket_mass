<?php
// M: rol almacenero. Registra mermas de su tienda (mueven el stock y el kardex) y recibe la mercadería de los traslados.
require_once __DIR__ . '/Empleado.php';

class Almacenero extends Empleado
{
    public function rol(): string { return 'ALMACENERO'; }
}
