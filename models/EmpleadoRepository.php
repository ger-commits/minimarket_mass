<?php
// M: login con DNI y contraseña. La contraseña se guarda como hash (bcrypt) y se compara con password_verify.
// Devuelve el objeto según el rol.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Cajero.php';
require_once __DIR__ . '/Almacenero.php';
require_once __DIR__ . '/Administrador.php';

class EmpleadoRepository
{
    public function autenticar(string $dni, string $clave): ?Empleado
    {
        $st = Database::conectar()->prepare("SELECT contrasena FROM empleado WHERE dni = ? AND estado = 'ACTIVO'");
        $st->execute([$dni]);
        $hash = $st->fetchColumn();
        // mismo resultado si el DNI no existe o la clave es incorrecta (no se revela cuál falló)
        if ($hash === false || !password_verify($clave, $hash)) return null;
        return $this->buscarPorDni($dni);
    }

    public function buscarPorDni(string $dni): ?Empleado
    {
        $st = Database::conectar()->prepare(
            "SELECT e.id_empleado, e.nombres, e.apellidos, e.dni, e.id_tienda, UPPER(r.nombre_rol) AS rol
               FROM empleado e JOIN rol r ON r.id_rol = e.id_rol
              WHERE e.dni = ? AND e.estado = 'ACTIVO'"
        );
        $st->execute([$dni]);
        $f = $st->fetch();
        if (!$f) return null;
        $args = [(int)$f['id_empleado'], $f['nombres'], $f['apellidos'], $f['dni'], (int)$f['id_tienda']];
        return match ($f['rol']) {
            'ADMINISTRADOR' => new Administrador(...$args),
            'ALMACENERO'    => new Almacenero(...$args),
            default         => new Cajero(...$args),
        };
    }
}
