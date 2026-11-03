<?php
// C: login del administrador y su panel. Solo entra quien sea Administrador con contraseña correcta.
require_once __DIR__ . '/../models/EmpleadoRepository.php';
require_once __DIR__ . '/../models/Administrador.php';

class AdminController
{
    public function mostrarLogin(string $dniAnterior = '', ?string $error = null): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
        $csrf = $_SESSION['csrf_admin'];
        require __DIR__ . '/../views/auth/login_administrador.php';
    }

    public function login(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $dni = trim($_POST['dni'] ?? '');
        $clave = $_POST['contrasena'] ?? '';
        if (!hash_equals($_SESSION['csrf_admin'] ?? '', $_POST['csrf'] ?? '')) {
            $this->mostrarLogin($dni, 'La sesión expiró. Intenta de nuevo.');
            return;
        }
        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            $this->mostrarLogin($dni, 'El DNI debe tener 8 dígitos.');
            return;
        }
        $empleado = (new EmpleadoRepository())->autenticar($dni, $clave);
        if ($empleado === null) {
            $this->mostrarLogin($dni, 'DNI o contraseña incorrectos.');
            return;
        }
        if (!$empleado instanceof Administrador) {
            $this->mostrarLogin($dni, 'Esta cuenta no tiene permiso de administrador.');
            return;
        }
        session_regenerate_id(true);
        $_SESSION['empleado'] = serialize($empleado);
        header('Location: index.php?r=admin_panel');
    }

    public function panel(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $empleado = isset($_SESSION['empleado']) ? unserialize($_SESSION['empleado']) : null;
        if (!$empleado instanceof Administrador) {
            header('Location: index.php?r=admin_login');
            exit;
        }
        require __DIR__ . '/../views/admin/panel.php';
    }
}
