<?php
// C: catálogo de productos; solo para quien tiene sesión
require_once __DIR__ . '/../models/ProductoRepository.php';
require_once __DIR__ . '/../models/Sesion.php';

class ProductoController
{
    public function lista(): void
    {
        $empleado = Sesion::requerir();
        $productos = (new ProductoRepository())->listar();
        require __DIR__ . '/../views/productos/lista.php';
    }
}