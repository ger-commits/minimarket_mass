<?php
// C: recibe el DNI y la contraseña del empleado y llama al EmpleadoRepository
require_once __DIR__ . '/../models/EmpleadoRepository.php';
require_once __DIR__ . '/../models/CajaRepository.php';
require_once __DIR__ . '/../models/Config.php';

class AuthController
{
    // prepara lo que necesita la pantalla de login (caja, token, DNI anterior) y la muestra
    public function mostrar(string $dniAnterior = '', ?string $error = null): void
    {
        $caja = (new CajaRepository())->buscar((int)Config::get('CAJA_ID', '0'));
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
            $empleado = (new EmpleadoRepository())->autenticar($dni, $clave);
            if ($empleado) {
                session_start();
                $_SESSION['empleado'] = serialize($empleado);
                header('Location: index.php?r=productos');
                return;
            }
            $error = 'DNI o contraseña incorrectos.';
        }
        $this->mostrar($dni, $error);
    }
}