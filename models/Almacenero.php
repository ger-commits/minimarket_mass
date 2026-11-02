<?php
// M: rol almacenero. Registra traslados entre tiendas y mermas (mueven el stock y el kardex).
require_once __DIR__ . '/Empleado.php';

class Almacenero extends Empleado
{
    public function rol(): string { return 'ALMACENERO'; }
}
