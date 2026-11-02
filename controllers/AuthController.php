<?php
// C: recibe el DNI del empleado y llama al EmpleadoRepository
require_once __DIR__ . '/../models/EmpleadoRepository.php';

class AuthController
{
    public function login(): void
    {
        $dni = trim($_POST['dni'] ?? '');
        $error = null;
        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            $error = 'El DNI debe tener 8 dígitos.';
        } else {
            $empleado = (new EmpleadoRepository())->buscarPorDni($dni);
            if ($empleado) {
                session_start();
                $_SESSION['empleado'] = serialize($empleado);
                header('Location: index.php?r=productos');
                return;
            }
            $error = 'No existe un empleado activo con ese DNI.';
        }
        require __DIR__ . '/../views/auth/login.php';
    }
}
