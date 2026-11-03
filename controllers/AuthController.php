<?php
// C: recibe el DNI y la contraseña del empleado. Solo entra si es cajero, la caja de esta terminal está ACTIVA y es de su tienda.
require_once __DIR__ . '/../models/EmpleadoRepository.php';
require_once __DIR__ . '/../models/CajaRepository.php';
require_once __DIR__ . '/../models/Config.php';

class AuthController
{
    // prepara lo que necesita la pantalla de login (caja, token, DNI anterior) y la muestra
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
                $error = 'Solo los cajeros pueden ingresar a esta caja.';
            } elseif ($caja === null || $caja['estado'] !== 'ACTIVA') {
                $error = 'La caja de esta terminal no está activa.';
            } elseif ((int)$caja['id_tienda'] !== $empleado->idTienda) {
                $error = 'Esta caja pertenece a otra tienda.';
            } else {
                session_start();
                session_regenerate_id(true);
                $_SESSION['empleado'] = serialize($empleado);
                $_SESSION['id_caja'] = (int)$caja['id_caja'];
                header('Location: index.php?r=caja');
                return;
            }
        }
        $this->mostrar($dni, $error);
    }

    // la caja fija de la terminal sale del .env (CAJA_ID)
    private function cajaDeTerminal(): ?array
    {
        return (new CajaRepository())->buscar((int)Config::get('CAJA_ID', '0'));
    }
}