<?php
// M: avisos para el almacén de su tienda (por ejemplo, un producto eliminado por el administrador).
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Sesion.php';

class NotificacionController
{
    // GET ?after=N: avisos nuevos de la tienda. after=-1 solo devuelve el último id (para no mostrar avisos viejos al abrir).
    public function almacen(): void
    {
        $empleado = Sesion::empleado();
        if (!($empleado instanceof Almacenero)) {
            $this->json(['ok' => false, 'mensaje' => 'Tu sesión de almacenero no está activa.'], 401);
        }
        $after = (int)($_GET['after'] ?? -1);
        try {
            $pdo = Database::conectar();
            $st = $pdo->prepare('SELECT COALESCE(MAX(id_notificacion), 0) FROM notificacion_almacen WHERE id_tienda = ?');
            $st->execute([$empleado->idTienda]);
            $ultimo = (int)$st->fetchColumn();

            $avisos = [];
            if ($after >= 0) {
                $st = $pdo->prepare(
                    "SELECT id_notificacion, tipo, mensaje, DATE_FORMAT(creado, '%H:%i') AS hora
                       FROM notificacion_almacen
                      WHERE id_tienda = ? AND id_notificacion > ? AND creado >= NOW() - INTERVAL 1 DAY
                      ORDER BY id_notificacion LIMIT 50"
                );
                $st->execute([$empleado->idTienda, $after]);
                $avisos = array_map(fn($f) => [
                    'id' => (int)$f['id_notificacion'], 'tipo' => $f['tipo'], 'mensaje' => $f['mensaje'], 'hora' => $f['hora'],
                ], $st->fetchAll());
            }
            $this->json(['ok' => true, 'data' => ['ultimo' => $ultimo, 'avisos' => $avisos]]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(['ok' => false, 'mensaje' => 'No se pudieron leer los avisos.'], 500);
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
