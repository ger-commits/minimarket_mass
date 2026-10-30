<?php
// M: clase base de los roles. El empleado pertenece a UNA tienda, tiene UN rol e ingresa con su DNI.
abstract class Empleado
{
    public function __construct(
        public int $idEmpleado,
        public string $nombres,
        public string $apellidos,
        public string $dni,        // CHAR(8): es el usuario de ingreso
        public int $idTienda
    ) {}

    abstract public function rol(): string;

    public function nombreCompleto(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
