<?php
// M: sesión del empleado. Guarda quién entró y la caja; las pantallas internas la exigen.
require_once __DIR__ . '/Empleado.php';
require_once __DIR__ . '/Cajero.php';
require_once __DIR__ . '/Almacenero.php';
require_once __DIR__ . '/Administrador.php';
require_once __DIR__ . '/EmpleadoRepository.php';

class Sesion
{
    private static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // token CSRF para los formularios internos
    public static function token(): string
    {
        self::iniciar();
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    }

    // devuelve el empleado de la sesión, releído de la base en cada petición (si lo dan de baja, pierde el acceso)
    public static function empleado(): ?Empleado
    {
        self::iniciar();
        if (empty($_SESSION['empleado'])) return null;
        $guardado = unserialize($_SESSION['empleado']);
        if (!$guardado instanceof Empleado) return null;
        return (new EmpleadoRepository())->buscarPorDni($guardado->dni);
    }

    // si no hay sesión válida, manda al login
    public static function requerir(): Empleado
    {
        $empleado = self::empleado();
        if ($empleado === null) {
            header('Location: index.php?r=login');
            exit;
        }
        return $empleado;
    }

    public static function idCaja(): ?int
    {
        self::iniciar();
        return $_SESSION['id_caja'] ?? null;
    }
}