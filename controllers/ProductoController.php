<?php
// C: recibe el pedido y llama al Repository (catálogo)
require_once __DIR__ . '/../models/ProductoRepository.php';

class ProductoController
{
    public function lista(): void
    {
        $productos = (new ProductoRepository())->listar();
        require __DIR__ . '/../views/productos/lista.php';
    }
}
