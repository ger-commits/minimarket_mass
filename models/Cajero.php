<?php
// M: rol cajero. Solo vende con un turno de caja abierto en una caja de su tienda.
require_once __DIR__ . '/Empleado.php';
require_once __DIR__ . '/Database.php';

class Cajero extends Empleado
{
    public function rol(): string { return 'CAJERO'; }

    public function turnoAbierto(): ?int
    {
        $st = Database::conectar()->prepare(
            "SELECT id_caja_turno FROM caja_turno WHERE id_empleado = ? AND estado = 'ABIERTO'"
        );
        $st->execute([$this->idEmpleado]);
        $id = $st->fetchColumn();
        return $id === false ? null : (int)$id;
    }

    public function puedeVender(): bool { return $this->turnoAbierto() !== null; }
}
