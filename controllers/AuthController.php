<?php
// C: cada rol tiene su propia pantalla de ingreso. Cajero: pantalla de caja. Almacenero: logística. Administrador: panel.
require_once __DIR__ . '/../models/EmpleadoRepository.php';
require_once __DIR__ . '/../models/CajaRepository.php';
require_once __DIR__ . '/../models/Config.php';

class AuthController
{
    // ---------- cajero (pantalla de caja: la caja de la terminal debe estar ACTIVA y ser de su tienda) ----------
    public function mostrar(string $dniAnterior = '', ?string $error = null): void
    {
        $caja = $this->cajaDeTerminal();
        $csrf = bin2hex(random_bytes(16));
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        $dni = trim($_POST['dni'] ?? '');
        $clave = $_POST['contrasena'] ?? '';
        $error = null;
        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            $error = 'El DNI debe tener 8 dígitos.';
        } else {
            $caja = $this->cajaDeTerminal();
            $empleado = (new EmpleadoRepository())->autenticar($dni, $clave);
            if ($empleado === null) {
                $error = 'DNI o contraseña incorrectos.';
            } elseif (!$empleado instanceof Cajero) {
                $error = 'Esta cuenta no es de cajero.';
            } elseif ($caja === null || $caja['estado'] !== 'ACTIVA') {
                $error = 'La caja de esta terminal no está activa.';
            } elseif ((int)$caja['id_tienda'] !== $empleado->idTienda) {
                $error = 'Esta caja pertenece a otra tienda.';
            } else {
                $this->iniciarSesion($empleado);
                $_SESSION['id_caja'] = (int)$caja['id_caja'];
                header('Location: index.php?r=caja');
                return;
            }
        }
        $this->mostrar($dni, $error);
    }

    // ---------- almacenero (pantalla de logística) ----------
    public function mostrarAlmacenero(string $dniAnterior = '', ?string $error = null): void
    {
        $csrf = bin2hex(random_bytes(16));
        require __DIR__ . '/../views/auth/login_logistica.php';
    }

    public function loginAlmacenero(): void
    {
        $dni = trim($_POST['dni'] ?? '');
        $clave = $_POST['contrasena'] ?? '';
        $error = null;
        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            $error = 'El DNI debe tener 8 dígitos.';
        } else {
            $empleado = (new EmpleadoRepository())->autenticar($dni, $clave);
            if ($empleado === null) {
                $error = 'DNI o contraseña incorrectos.';
            } elseif (!$empleado instanceof Almacenero) {
                $error = 'Esta cuenta no es de almacenero.';
            } else {
                $this->iniciarSesion($empleado);
                header('Location: index.php?r=stock');
                return;
            }
        }
        $this->mostrarAlmacenero($dni, $error);
    }

    // ---------- administrador (panel de administración) ----------
    public function mostrarAdministrador(string $dniAnterior = '', ?string $error = null): void
    {
        $csrf = bin2hex(random_bytes(16));
        require __DIR__ . '/../views/auth/login_administrador.php';
    }

    public function loginAdministrador(): void
    {
        $dni = trim($_POST['dni'] ?? '');
        $clave = $_POST['contrasena'] ?? '';
        $error = null;
        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            $error = 'El DNI debe tener 8 dígitos.';
        } else {
            $empleado = (new EmpleadoRepository())->autenticar($dni, $clave);
            if ($empleado === null) {
                $error = 'DNI o contraseña incorrectos.';
            } elseif (!$empleado instanceof Administrador) {
                $error = 'Esta cuenta no es de administrador.';
            } else {
                $this->iniciarSesion($empleado);
                header('Location: index.php?r=admin');
                return;
            }
        }
        $this->mostrarAdministrador($dni, $error);
    }

    // guarda al empleado en la sesión con un id nuevo (evita fijar sesiones ajenas)
    private function iniciarSesion(Empleado $empleado): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);
        $_SESSION['empleado'] = serialize($empleado);
    }

    // la caja fija de la terminal sale del .env (CAJA_ID)
    private function cajaDeTerminal(): ?array
    {
        return (new CajaRepository())->buscar((int)Config::get('CAJA_ID', '0'));
    }
}