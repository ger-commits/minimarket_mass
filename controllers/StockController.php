<?php
// Almacenamiento: acceso exclusivo del perfil almacenero y operaciones sobre la tienda de su sesión.
require_once __DIR__ . '/../models/StockRepository.php';
require_once __DIR__ . '/../models/IngresoRepository.php';
require_once __DIR__ . '/../models/MermaRepository.php';
require_once __DIR__ . '/../models/Sesion.php';

class StockController
{
    private function almaceneroOLogin(): Almacenero
    {
        $empleado = Sesion::empleado();
        if (!($empleado instanceof Almacenero)) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || ($_GET['r'] ?? '') === 'stock_api') {
                $this->json(['ok' => false, 'mensaje' => 'Tu sesión de almacenero no está activa.'], 401);
            }
            header('Location: index.php?r=almacenero');
            exit;
        }
        return $empleado;
    }

    public function lista(): void
    {
        $empleado = $this->almaceneroOLogin();
        $csrf = Sesion::token();
        $nombreTienda = '';
        try {
            $st = Database::conectar()->prepare('SELECT nombre_tienda FROM tienda WHERE id_tienda = ?');
            $st->execute([$empleado->idTienda]);
            $nombreTienda = (string)$st->fetchColumn();
        } catch (Throwable $e) { error_log($e->getMessage()); }
        require __DIR__ . '/../views/stock/app.php';
    }

    // API JSON consumida por el diseño moderno de almacenamiento.
    public function api(): void
    {
        $empleado = $this->almaceneroOLogin();
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->json(['ok' => true, 'data' => $this->estado($empleado->idTienda)]);
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->json(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);

            $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!hash_equals(Sesion::token(), $csrf)) $this->json(['ok' => false, 'mensaje' => 'La sesión venció. Actualiza la página e inténtalo de nuevo.'], 419);

            $in = json_decode(file_get_contents('php://input'), true);
            if (!is_array($in)) $this->json(['ok' => false, 'mensaje' => 'Los datos enviados no son válidos.'], 400);
            $accion = (string)($in['accion'] ?? '');
            if ($accion === 'ingreso') {
                (new IngresoRepository())->registrar($empleado->idTienda, $empleado->idEmpleado,
                    (int)($in['id_producto'] ?? 0), (string)($in['cantidad'] ?? ''),
                    (string)($in['observacion'] ?? ''), isset($in['codigo_lote']) ? (string)$in['codigo_lote'] : null,
                    isset($in['fecha_vencimiento']) ? (string)$in['fecha_vencimiento'] : null);
                $this->json(['ok' => true, 'mensaje' => 'Ingreso registrado. El stock, el lote y el Kardex se actualizaron en MySQL.']);
            }
            if ($accion === 'merma') {
                (new MermaRepository())->registrar($empleado->idTienda, $empleado->idEmpleado,
                    (string)($in['motivo'] ?? ''), (string)($in['observacion'] ?? ''),
                    [(int)($in['id_producto'] ?? 0) => (string)($in['cantidad'] ?? '')]);
                $this->json(['ok' => true, 'mensaje' => 'Merma registrada. Se actualizó el stock y el Kardex en MySQL.']);
            }
            if ($accion === 'merma_lote') {
                (new MermaRepository())->registrarLote($empleado->idTienda, $empleado->idEmpleado,
                    (int)($in['id_lote'] ?? 0), (string)($in['observacion'] ?? 'Lote vencido'));
                $this->json(['ok' => true, 'mensaje' => 'El lote pasó a merma y el movimiento quedó registrado en el Kardex.']);
            }
            if ($accion === 'stock_minimo') {
                $idProducto = (int)($in['id_producto'] ?? 0);
                $minimo = filter_var((string)($in['stock_minimo'] ?? ''), FILTER_VALIDATE_FLOAT);
                if ($idProducto <= 0 || $minimo === false || $minimo < 0) throw new InvalidArgumentException('El stock mínimo debe ser un número igual o mayor que cero.');
                $pdo = Database::conectar();
                $pdo->prepare('INSERT IGNORE INTO stock (id_tienda, id_producto, cantidad_actual, stock_minimo) VALUES (?, ?, 0, 0)')->execute([$empleado->idTienda, $idProducto]);
                $st = $pdo->prepare('UPDATE stock SET stock_minimo = ? WHERE id_tienda = ? AND id_producto = ?');
                $st->execute([round((float)$minimo, 3), $empleado->idTienda, $idProducto]);
                $this->json(['ok' => true, 'mensaje' => 'Stock mínimo actualizado en MySQL.']);
            }
            $this->json(['ok' => false, 'mensaje' => 'Acción no reconocida.'], 400);
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            error_log('API almacenamiento: ' . $e->getMessage());
            $this->json(['ok' => false, 'mensaje' => 'No se pudo completar la operación. Revisa la conexión y el registro de errores.'], 500);
        }
    }

    private function estado(int $idTienda): array
    {
        $pdo = Database::conectar();
        $st = $pdo->prepare('SELECT id_tienda, nombre_tienda FROM tienda WHERE id_tienda = ?');
        $st->execute([$idTienda]);
        $tienda = $st->fetch() ?: ['id_tienda' => $idTienda, 'nombre_tienda' => 'Mi almacén'];

        $st = $pdo->prepare("SELECT p.id_producto, p.nombre_producto, p.codigo_barras, p.precio_venta,
                    p.se_vende_por_peso, um.abreviatura AS unidad,
                    COALESCE(s.cantidad_actual,0) AS stock, COALESCE(s.stock_minimo,0) AS stock_minimo
               FROM producto p JOIN unidad_medida um ON um.id_unidad = p.id_unidad
               LEFT JOIN stock s ON s.id_producto = p.id_producto AND s.id_tienda = ?
              WHERE p.estado = 'ACTIVO' ORDER BY p.nombre_producto");
        $st->execute([$idTienda]); $productos = $st->fetchAll();

        $st = $pdo->prepare('SELECT id_lote, codigo_lote, fecha_vencimiento, cantidad_inicial, cantidad_actual, id_producto FROM lote_producto WHERE id_tienda = ? ORDER BY fecha_vencimiento, id_lote');
        $st->execute([$idTienda]); $lotes = $st->fetchAll();

        $st = $pdo->prepare("SELECT m.id_movimiento, m.fecha_hora, m.cantidad, m.stock_nuevo, m.observacion,
                    m.id_producto, m.id_tipo_movimiento, tm.nombre_movimiento, tm.efecto,
                    m.id_lote, m.id_merma, m.id_ingreso, m.id_venta, m.id_traslado,
                    p.nombre_producto, um.abreviatura AS unidad, CONCAT(e.nombres,' ',e.apellidos) AS empleado
               FROM movimiento_stock m JOIN producto p ON p.id_producto=m.id_producto
               JOIN unidad_medida um ON um.id_unidad=p.id_unidad
               JOIN tipo_movimiento tm ON tm.id_tipo_movimiento=m.id_tipo_movimiento
               JOIN empleado e ON e.id_empleado=m.id_empleado
              WHERE m.id_tienda=? ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 500");
        $st->execute([$idTienda]); $movimientos = $st->fetchAll();

        $st = $pdo->prepare("SELECT mr.id_merma, mr.fecha_hora, mr.motivo, mr.observacion, m.id_producto, SUM(m.cantidad) AS cantidad, p.nombre_producto, um.abreviatura AS unidad, CONCAT(e.nombres,' ',e.apellidos) AS empleado FROM merma mr JOIN movimiento_stock m ON m.id_merma = mr.id_merma JOIN producto p ON p.id_producto = m.id_producto JOIN unidad_medida um ON um.id_unidad = p.id_unidad JOIN empleado e ON e.id_empleado = mr.id_empleado WHERE m.id_tienda = ? GROUP BY mr.id_merma, mr.fecha_hora, mr.motivo, mr.observacion, m.id_producto, p.nombre_producto, um.abreviatura, e.nombres, e.apellidos ORDER BY mr.fecha_hora DESC, mr.id_merma DESC");
        $st->execute([$idTienda]); $mermas = $st->fetchAll();

        return ['tienda' => $tienda, 'productos' => $productos, 'lotes' => $lotes, 'movimientos' => $movimientos, 'mermas' => $mermas];
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}