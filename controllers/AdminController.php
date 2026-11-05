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

        $st = $db->prepare('SELECT COUNT(*) FROM stock WHERE id_tienda = ? AND cantidad_actual <= stock_minimo');
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
            'SELECT p.nombre_producto, st.cantidad_actual, st.stock_minimo
               FROM stock st JOIN producto p ON p.id_producto = st.id_producto
              WHERE st.id_tienda = ? ORDER BY (st.cantidad_actual <= st.stock_minimo) DESC, p.nombre_producto LIMIT 60'
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
        $turnos = (new TurnoTrabajoRepository())->delDia($t, date('Y-m-d'));

        $prodRepo = new ProductoRepository();
        $productos = $prodRepo->listarAdmin();
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
                    (new SolicitudAnulacionRepository())->aprobar((int)$_POST['id_solicitud'], $admin);
                    $this->volver('Anulación aprobada: la venta quedó anulada y el stock volvió al inventario.');
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
                    $this->volver('Traslado registrado: el stock se descontó de tu tienda y se sumó en la destino.');
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
        $total = count((new SolicitudAnulacionRepository())->pendientesDeTienda($admin->idTienda));
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => true, 'total' => $total]);
    }

}