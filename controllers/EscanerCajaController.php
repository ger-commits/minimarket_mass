<?php
// Lado del celular del CAJERO. No usa sesión de empleado: el celular entra con el token secreto que muestra la caja (QR).
// movil()  → pantalla con la cámara.   enviar() → recibe un código leído (o un aviso de conexión) en JSON.
require_once __DIR__ . '/../models/EscanerCajaRepository.php';

class EscanerCajaController
{
    public function movil(): void
    {
        $idCaja = (int)($_GET['c'] ?? 0);
        $token  = (string)($_GET['t'] ?? '');
        $valido = $idCaja > 0 && preg_match('/^[0-9a-f]{32}$/', $token) === 1
            && (new EscanerCajaRepository())->valido($idCaja, $token);
        // el token viaja en la URL: que no se guarde ni se envíe como referer a las librerías externas
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        require __DIR__ . '/../views/escaner/movil.php';
    }

    public function enviar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->json(['ok' => false, 'error' => 'Método no permitido.'], 405); return; }
        $d = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($d)) { $this->json(['ok' => false, 'error' => 'Datos no válidos.'], 400); return; }
        $idCaja = (int)($d['c'] ?? 0);
        $token  = (string)($d['t'] ?? '');
        if ($idCaja <= 0 || !preg_match('/^[0-9a-f]{32}$/', $token)) { $this->json(['ok' => false, 'error' => 'Enlace no válido.', 'vencido' => true], 410); return; }
        try {
            (new EscanerCajaRepository())->enviar($idCaja, $token, trim((string)($d['codigo'] ?? '')), !empty($d['ping']));
            $this->json(['ok' => true]);
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage(), 'vencido' => $e->getCode() === 410], $e->getCode() === 410 ? 410 : 422);
        } catch (Throwable $e) {
            error_log('escaner caja: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Ocurrió un error. Intente de nuevo.'], 500);
        }
    }

    private function json(array $cuerpo, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    }
}
