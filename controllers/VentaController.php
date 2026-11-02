<?php
// C: muestra una boleta o factura a partir de serie + correlativo
require_once __DIR__ . '/../models/VentaRepository.php';

class VentaController
{
    public function boleta(string $serie, int $correlativo): void
    {
        $lineas = (new VentaRepository())->boleta($serie, $correlativo);
        require __DIR__ . '/../views/ventas/boleta.php';
    }
}
