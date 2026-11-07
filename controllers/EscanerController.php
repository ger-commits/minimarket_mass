<?php
// M: endpoints del escáner de celular.
// - La PC (almacenero con sesión) crea el enlace y lee los códigos nuevos.
// - El celular envía códigos usando solo el token del enlace (no inicia sesión).
require_once __DIR__ . '/../models/EscanerRepository.php';
require_once __DIR__ . '/../models/Sesion.php';
require_once __DIR__ . '/../models/Config.php';

class EscanerController
{
    private function almacenero(): Almacenero
    {
        $empleado = Sesion::empleado();
        if (!($empleado instanceof Almacenero)) {
            $this->json(['ok' => false, 'mensaje' => 'Tu sesión de almacenero no está activa.'], 401);
        }
        return $empleado;
    }

    // PC: crea un enlace nuevo para el celular (se muestra como QR)
    public function vincular(): void
    {
        $empleado = $this->almacenero();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
        if (!hash_equals(Sesion::token(), $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            $this->json(['ok' => false, 'mensaje' => 'La sesión venció. Actualiza la página e inténtalo de nuevo.'], 419);
        }
        try {
            $datos = (new EscanerRepository())->vincular($empleado->idEmpleado);
            // Dirección https pública de la carpeta public/ (túnel). Es una variable propia del almacén: no usa ESCANER_URL_BASE (caja).
            $base = rtrim(trim((string)Config::get('ALMACEN_ESCANER_URL_BASE', '')), '/');
            $datos['base'] = preg_match('#^https://[A-Za-z0-9.\-]+(:\d+)?(/[^\s]*)?$#', $base) === 1 ? $base : '';
            $this->json(['ok' => true, 'data' => $datos]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(['ok' => false, 'mensaje' => 'No se pudo crear el enlace: ' . $e->getMessage()], 500);
        }
    }

    // PC: códigos nuevos desde el último que ya se mostró
    public function pendientes(): void
    {
        $empleado = $this->almacenero();
        try {
            $desde = max(0, (int)($_GET['after'] ?? 0));
            $this->json(['ok' => true, 'data' => (new EscanerRepository())->pendientes($empleado->idEmpleado, $desde)]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(['ok' => false, 'mensaje' => 'No se pudieron leer los códigos del celular.'], 500);
        }
    }

    // Celular: envía un código, o un aviso de que sigue conectado (hola)
    public function enviar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
        $in = json_decode(file_get_contents('php://input'), true);
        if (!is_array($in)) $this->json(['ok' => false, 'mensaje' => 'Los datos enviados no son válidos.'], 400);

        $token = (string)($in['t'] ?? '');
        $repo = new EscanerRepository();
        try {
            if (!empty($in['hola'])) {
                $repo->latido($token);
                $this->json(['ok' => true, 'mensaje' => 'Celular conectado.']);
            }
            $repo->registrarCodigo($token, (string)($in['c'] ?? ''));
            $this->json(['ok' => true, 'mensaje' => 'Enviado al administrador.']);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'mensaje' => $e->getMessage()], 400);
        } catch (UnexpectedValueException $e) {
            $this->json(['ok' => false, 'mensaje' => $e->getMessage()], 410);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(['ok' => false, 'mensaje' => 'Error del servidor. Intenta de nuevo.'], 500);
        }
    }

    private function json(array $datos, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
