<?php
// C: login del administrador y su panel. Solo entra quien sea Administrador con contraseña correcta.
require_once __DIR__ . '/../models/EmpleadoRepository.php';
require_once __DIR__ . '/../models/Administrador.php';
require_once __DIR__ . '/../models/AdminPanelRepository.php';

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

    // Datos JSON para el panel (solo lectura, solo de la tienda del administrador)
    public function apiVentas(): void
    {
        $admin = $this->adminActivo();
        if ($admin === null) { $this->json(['ok' => false, 'error' => 'No autorizado'], 401); return; }
        $filas = (new AdminPanelRepository())->ventas($admin->idTienda);
        $datos = array_map(function ($r) {
            $anulada = $r['estado'] === 'ANULADA';
            return [
                'comprobante' => $r['serie'] . '-' . str_pad((string)$r['numero_correlativo'], 5, '0', STR_PAD_LEFT),
                'fecha'       => date('d/m/Y H:i', strtotime($r['fecha_hora'])),
                'tienda'      => $r['nombre_tienda'],
                'cajero'      => $r['cajero'],
                'medio'       => $r['medio'] ? ucfirst(mb_strtolower($r['medio'])) : null,
                'total'       => number_format((float)$r['total'], 2, '.', ''),
                'estado'      => $anulada ? 'Anulada' : 'Pagada',
                'clase'       => $anulada ? 'bad' : 'ok',
            ];
        }, $filas);
        $this->json(['ok' => true, 'datos' => $datos]);
    }

    public function apiPersonal(): void
    {
        $admin = $this->adminActivo();
        if ($admin === null) { $this->json(['ok' => false, 'error' => 'No autorizado'], 401); return; }
        $filas = (new AdminPanelRepository())->personal($admin->idTienda);
        $datos = array_map(function ($r) {
            $activo = $r['estado'] === 'ACTIVO';
            return [
                'nombre' => $r['nombres'] . ' ' . $r['apellidos'],
                'dni'    => '••••••' . substr($r['dni'], -2),
                'rol'    => ucfirst(mb_strtolower($r['nombre_rol'])),
                'tienda' => $r['nombre_tienda'],
                'estado' => $activo ? 'Activo' : 'Inactivo',
                'clase'  => $activo ? 'ok' : 'bad',
                'turno'  => $r['turno'],
            ];
        }, $filas);
        $this->json(['ok' => true, 'datos' => $datos]);
    }

    // Devuelve el administrador de la sesión o null si no lo es
    private function adminActivo(): ?Administrador
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $e = isset($_SESSION['empleado']) ? unserialize($_SESSION['empleado']) : null;
        return $e instanceof Administrador ? $e : null;
    }

    private function json(array $cuerpo, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    }
}
