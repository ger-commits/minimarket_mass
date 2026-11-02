<?php
// C: stock de la tienda del empleado que ingresó (cada tienda ve su propio stock)
require_once __DIR__ . '/../models/StockRepository.php';

class StockController
{
    public function lista(int $idTienda): void
    {
        $stock = (new StockRepository())->porTienda($idTienda);
        require __DIR__ . '/../views/stock/lista.php';
    }
}
