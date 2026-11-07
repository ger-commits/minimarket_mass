<?php
// C: panel del administrador. Solo entra con sesión de administrador y solo opera sobre su propia tienda.
require_once __DIR__ . '/../models/Sesion.php';
require_once __DIR__ . '/../models/Administrador.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/SolicitudAnulacionRepository.php';
require_once __DIR__ . '/../models/TurnoTrabajoRepository.php';
require_once __DIR__ . '/../models/ProductoRepository.php';
require_once __DIR__ . '/../models/TrasladoRepository.php';
require_once __DIR__ . '/../models/MermaRepository.php';
require_once __DIR__ . '/../models/ReporteRepository.php';
require_once __DIR__ . '/../models/SolicitudProductoRepository.php';

class AdminController
{
    public function panel(): void
    {
        $admin = $this->adminOLogin();
        $t = $admin->idTienda;
        $db = Database::conectar();

        $st = $db->prepare('SELECT nombre_tienda FROM tienda WHERE id_tienda = ?');
        $st->execute([$t]);
        $tiendaNombre = (string)$st->fetchColumn();

        // resumen de hoy, solo de la tienda del administrador
        $st = $db->prepare(
            "SELECT COALESCE(SUM(v.total), 0) AS total, COUNT(*) AS n
               FROM venta v JOIN serie_comprobante s ON s.id_serie = v.id_serie
              WHERE s.id_tienda = ? AND v.estado = 'EMITIDA' AND DATE(v.fecha_hora) = CURDATE()"
        );
        $st->execute([$t]);
        $hoy = $st->fetch();

        $st = $db->prepare(
            "SELECT COUNT(*) FROM stock st JOIN producto p ON p.id_producto = st.id_producto
              WHERE st.id_tienda = ? AND st.cantidad_actual <= st.stock_minimo AND p.estado <> 'ELIMINADO'"
        );
        $st->execute([$t]);
        $stockBajo = (int)$st->fetchColumn();

        $st = $db->prepare(
            'SELECT s.serie, v.numero_correlativo, v.fecha_hora, v.total, v.estado
               FROM venta v JOIN serie_comprobante s ON s.id_serie = v.id_serie
              WHERE s.id_tienda = ? ORDER BY v.fecha_hora DESC LIMIT 15'
        );
        $st->execute([$t]);
        $ventas = $st->fetchAll();

        $st = $db->prepare(
            "SELECT p.nombre_producto, st.cantidad_actual, st.stock_minimo
               FROM stock st JOIN producto p ON p.id_producto = st.id_producto
              WHERE st.id_tienda = ? AND p.estado <> 'ELIMINADO'
              ORDER BY (st.cantidad_actual <= st.stock_minimo) DESC, p.nombre_producto LIMIT 60"
        );
        $st->execute([$t]);
        $inventario = $st->fetchAll();

        $st = $db->prepare(
            "SELECT e.id_empleado, e.nombres, e.apellidos, r.nombre_rol
               FROM empleado e JOIN rol r ON r.id_rol = e.id_rol
              WHERE e.id_tienda = ? AND e.estado = 'ACTIVO' ORDER BY e.apellidos"
        );
        $st->execute([$t]);
        $personal = $st->fetchAll();

        $pendientes = (new SolicitudAnulacionRepository())->pendientesDeTienda($t);
        $productosPendientes = (new SolicitudProductoRepository())->pendientesDeTienda($t);
        $turnos = (new TurnoTrabajoRepository())->delDia($t, date('Y-m-d'));

        $prodRepo = new ProductoRepository();
        $productos = array_values(array_filter($prodRepo->listarAdmin($t), fn($p) => $p['estado'] !== 'ELIMINADO'));
        $catalogos = $prodRepo->catalogos();

        $trasRepo = new TrasladoRepository();
        $tiendasDestino = $trasRepo->tiendasDestino($t);
        $stockOrigen = $trasRepo->stockDisponible($t);
        $historialTraslados = $trasRepo->historial($t);

        // Mermas y reportes: consultas reales filtradas por la tienda del administrador.
        $mermas = (new MermaRepository())->historial($t, 50);
        $reportes = (new ReporteRepository())->todo($t);

        $aviso = $_SESSION['aviso'] ?? null;
        unset($_SESSION['aviso']);
        $empleado = $admin;
        $titulo = 'Panel del administrador';
        require __DIR__ . '/../views/admin/panel.php';
    }

    // recibe los formularios del panel (solo POST, con token CSRF)
    public function accion(): void
    {
        $admin = $this->adminOLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(Sesion::token(), $_POST['csrf'] ?? '')) {
            $this->volver('La solicitud no es válida. Vuelve a intentarlo.', 'error');
        }
        try {
            switch ($_POST['accion'] ?? '') {
                case 'aprobar':
                    $repo = new SolicitudAnulacionRepository();
                    $solicitud = $repo->buscar((int)$_POST['id_solicitud']);
                    // "ERROR" (el cliente se confundió) devuelve el producto al stock; Vencido o Dañado pasan a merma
                    $vuelveAlStock = $solicitud && str_starts_with(mb_strtoupper(trim($solicitud->motivo)), 'ERROR');
                    $repo->aprobar((int)$_POST['id_solicitud'], $admin);
                    $this->volver($vuelveAlStock
                        ? 'Anulación aprobada: la venta quedó anulada y el stock volvió al inventario.'
                        : 'Anulación aprobada: la venta quedó anulada y el producto pasó a merma (no volvió al stock).');
                case 'rechazar':
                    (new SolicitudAnulacionRepository())->rechazar((int)$_POST['id_solicitud'], $admin, $_POST['motivo'] ?? '');
                    $this->volver('Solicitud rechazada.');
                case 'programar':
                    (new TurnoTrabajoRepository())->programar(
                        $admin,
                        (int)$_POST['id_empleado'],
                        $_POST['fecha'] ?? '',
                        $_POST['hora_inicio'] ?? '',
                        $_POST['hora_fin'] ?? ''
                    );
                    $this->volver('Turno programado.');
                case 'producto_permitir':
                    (new SolicitudProductoRepository())->responder($admin, (int)($_POST['id_solicitud_producto'] ?? 0), true);
                    $this->volver('Producto permitido: el almacenero de tu tienda ya puede registrarlo.');
                case 'producto_rechazar':
                    (new SolicitudProductoRepository())->responder($admin, (int)($_POST['id_solicitud_producto'] ?? 0), false, (string)($_POST['motivo'] ?? ''));
                    $this->volver('Producto rechazado. Se avisó al almacenero.');
                case 'producto_descontinuar':
                    (new ProductoRepository())->descontinuar((int)($_POST['id_producto'] ?? 0), $admin);
                    $this->volver('Producto descontinuado en tu tienda. Ya no se vende aquí; su stock se conserva. Las otras tiendas no cambian.');
                case 'producto_reactivar':
                    (new ProductoRepository())->reactivar((int)($_POST['id_producto'] ?? 0), $admin);
                    $this->volver('Producto reactivado en tu tienda.');
                case 'producto_guardar':
                    $prodRepo = new ProductoRepository();
                    $idProd = (int)($_POST['id_producto'] ?? 0);
                    if ($idProd > 0) {
                        $prodRepo->actualizar($idProd, $_POST);
                        $this->volver('Producto actualizado.');
                    }
                    $prodRepo->crear($_POST);
                    $this->volver('Producto creado. Tiene stock 0 en cada tienda: registra su ingreso.');
                case 'merma_registrar':
                    (new MermaRepository())->registrar(
                        $admin->idTienda,
                        $admin->idEmpleado,
                        (string)($_POST['motivo'] ?? ''),
                        (string)($_POST['observacion'] ?? ''),
                        [(int)($_POST['id_producto'] ?? 0) => (string)($_POST['cantidad'] ?? '')]
                    );
                    $this->volver('Merma registrada: el stock y el kardex fueron actualizados.');
                case 'traslado_anular':
                    (new TrasladoRepository())->anular($admin, (int)($_POST['id_traslado'] ?? 0), (string)($_POST['motivo'] ?? ''));
                    $this->volver('Envío anulado: el stock volvió a tu tienda.');
                case 'traslado_registrar':
                    $items = [];
                    foreach (($_POST['cantidad'] ?? []) as $idP => $cantP) {
                        $items[] = ['id_producto' => (int)$idP, 'cantidad' => $cantP];
                    }
                    (new TrasladoRepository())->registrar(
                        $admin,
                        (int)($_POST['id_tienda_destino'] ?? 0),
                        (string)($_POST['observacion'] ?? ''),
                        $items
                    );
                    $this->volver('Traslado enviado: el stock salió de tu tienda y está en camino. Entrará a la tienda destino cuando su almacenero confirme la recepción.');
                default:
                    $this->volver('Acción no reconocida.', 'error');
            }
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->volver($e->getMessage(), 'error');     // reglas de negocio: se muestran al administrador
        } catch (Throwable $e) {
            error_log('Panel admin: ' . $e->getMessage());  // errores técnicos: solo al log
            $this->volver('No se pudo completar la acción.', 'error');
        }
    }

    private function adminOLogin(): Administrador
    {
        $empleado = Sesion::empleado();
        if (!$empleado instanceof Administrador) {
            header('Location: index.php?r=administrador');
            exit;
        }
        return $empleado;
    }

    private function volver(string $texto, string $tipo = 'ok'): void
    {
        $_SESSION['aviso'] = ['texto' => $texto, 'tipo' => $tipo];
        header('Location: index.php?r=admin');
        exit;
    }

    // Consulta ligera que el panel usa para saber si llegó una solicitud nueva o se resolvió una
    public function pendientesJson(): void
    {
        $admin = $this->adminOLogin();
        $t = $admin->idTienda;
        // anulaciones + productos nuevos por autorizar: si cambia el total, el panel se recarga
        $total = count((new SolicitudAnulacionRepository())->pendientesDeTienda($t))
               + count((new SolicitudProductoRepository())->pendientesDeTienda($t));
        // firma de lo que cambia en la tienda (ventas, stock, movimientos, turnos): si cambia, el panel se recarga
        $db = Database::conectar();
        $st = $db->prepare(
            "SELECT COUNT(*) AS ventas, COALESCE(SUM(v.total), 0) AS monto
               FROM venta v JOIN serie_comprobante s ON s.id_serie = v.id_serie
              WHERE s.id_tienda = ?"
        );
        $st->execute([$t]);
        $v = $st->fetch();
        $st = $db->prepare('SELECT COALESCE(SUM(cantidad_actual), 0) AS stock, (SELECT COUNT(*) FROM movimiento_stock WHERE id_tienda = ?) AS movs FROM stock WHERE id_tienda = ?');
        $st->execute([$t, $t]);
        $s = $st->fetch();
        $st = $db->prepare("SELECT COUNT(*), COALESCE(SUM(estado = 'EN_CAMINO'), 0) FROM traslado_mercaderia WHERE id_tienda_origen = ? OR id_tienda_destino = ?");
        $st->execute([$t, $t]);
        $tr = $st->fetch(PDO::FETCH_NUM);
        $firma = implode('|', [$v['ventas'], $v['monto'], $s['stock'], $s['movs'], $total, $tr[0], $tr[1]]);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => true, 'total' => $total, 'firma' => $firma]);
    }

}