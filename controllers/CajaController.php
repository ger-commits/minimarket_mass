<?php
// C: terminal de caja. Muestra la pantalla (views/caja/pos.php) y responde en JSON lo que ella pide:
// turno de caja, productos, ventas, y la solicitud de anulación. Todo se lee y se guarda en MySQL.
// Solo entra un CAJERO con sesión; las acciones que guardan datos exigen el token CSRF de la pantalla.
require_once __DIR__ . '/../models/EmpleadoRepository.php';
require_once __DIR__ . '/../models/CajaRepository.php';
require_once __DIR__ . '/../models/CajaTurnoRepository.php';
require_once __DIR__ . '/../models/VentaRepository.php';
require_once __DIR__ . '/../models/SolicitudAnulacionRepository.php';
require_once __DIR__ . '/../models/Sesion.php';
require_once __DIR__ . '/../models/Config.php';
require_once __DIR__ . '/../models/EscanerCajaRepository.php';

class CajaController
{
    public function pos(): void
    {
        $this->iniciarSesion();
        if (!($this->cajero() instanceof Cajero) || Sesion::idCaja() === null) { header('Location: index.php'); exit; }
        $_SESSION['csrf_caja'] ??= bin2hex(random_bytes(16));
        $csrf = $_SESSION['csrf_caja'];
        require __DIR__ . '/../views/caja/pos.php';
    }

    public function estado(): void
    {
        [$c, $idCaja] = $this->requerir();
        $caja = (new CajaRepository())->buscar($idCaja);
        $st = Database::conectar()->prepare('SELECT nombre_tienda FROM tienda WHERE id_tienda = ?');
        $st->execute([$c->idTienda]);
        $turnos = new CajaTurnoRepository();
        $t = $turnos->abiertoDe($c->idEmpleado);
        $turno = null;
        if ($t) {
            $r = $turnos->resumen((int)$t['id_caja_turno']);
            $turno = [
                'id' => (int)$t['id_caja_turno'], 'apertura' => $t['fecha_hora_apertura'], 'inicial' => (float)$t['monto_inicial'],
                // Conteo a ciegas: la caja NO recibe el efectivo esperado; solo el servidor lo compara al cerrar.
                'otros' => (float)$r['otros'],
                'anterior' => (bool)$t['de_dia_anterior'],
                'id_caja' => (int)$t['id_caja'], 'numero_caja' => (int)$t['numero_caja'],
            ];
        }
        $cerrados = array_map(function ($f) {
            // si el turno guardó su esperado al cerrar se usa ese (queda fijo); los turnos antiguos lo recalculan
            $esp = $f['monto_esperado'] !== null
                ? (float)$f['monto_esperado']
                : round((float)$f['monto_inicial'] + (float)$f['efectivo'] + (float)$f['movimientos'], 2);
            return ['id' => (int)$f['id_caja_turno'], 'apertura' => $f['fecha_hora_apertura'], 'cierre' => $f['fecha_hora_cierre'],
                    'inicial' => (float)$f['monto_inicial'], 'esperado' => $esp, 'contado' => (float)$f['monto_cierre'],
                    'dif' => round((float)$f['monto_cierre'] - $esp, 2), 'obs' => (string)($f['observacion_cierre'] ?? '')];
        }, $turnos->cerrados($idCaja));
        $this->json(['ok' => true, 'datos' => [
            'empleado' => $c->nombreCompleto(), 'tienda' => $st->fetchColumn() ?: '',
            'caja' => (int)($caja['numero_caja'] ?? 0), 'caja_activa' => ($caja['estado'] ?? '') === 'ACTIVA',
            'turno' => $turno, 'cerrados' => $cerrados,
        ]]);
    }

    public function abrir(): void
    {
        [$c, $idCaja] = $this->requerir(true);
        $b = $this->cuerpo();
        $this->protegido(function () use ($c, $idCaja, $b) {
            (new CajaTurnoRepository())->abrir($idCaja, $c->idEmpleado, (float)($b['monto_inicial'] ?? -1));
            return [];
        });
    }

    public function cerrar(): void
    {
        [$c] = $this->requerir(true);
        $b = $this->cuerpo();
        $this->protegido(function () use ($c, $b) {
            $turnos = new CajaTurnoRepository();
            $t = $turnos->abiertoDe($c->idEmpleado);
            if (!$t) throw new RuntimeException('No hay un turno abierto para cerrar.');
            if (!isset($b['contado']) || !is_numeric($b['contado'])) throw new InvalidArgumentException('Ingrese el efectivo contado.');
            $cierre = $turnos->cerrar((int)$t['id_caja_turno'], $c->idEmpleado, (float)$b['contado'], (string)($b['observacion'] ?? ''));
            // al cerrar la caja el celular escáner queda desvinculado: el siguiente cajero vincula el suyo
            try { (new EscanerCajaRepository())->desvincular(Sesion::idCaja()); } catch (Throwable $e) { error_log('caja: ' . $e->getMessage()); }
            return ['cierre' => $cierre, 'turno' => (int)$t['id_caja_turno']];
        });
    }

    // Retiro a bóveda, gasto o ingreso de efectivo en el turno abierto del cajero
    public function movimiento(): void
    {
        [$c] = $this->requerir(true);
        $b = $this->cuerpo();
        $this->protegido(function () use ($c, $b) {
            $turnos = new CajaTurnoRepository();
            $t = $turnos->abiertoDe($c->idEmpleado);
            if (!$t) throw new RuntimeException('Abra la caja antes de registrar movimientos.');
            $id = $turnos->registrarMovimiento((int)$t['id_caja_turno'], (string)($b['tipo'] ?? ''), (float)($b['monto'] ?? 0), (string)($b['motivo'] ?? ''));
            return ['id' => $id];
        });
    }

    public function movimientos(): void
    {
        [$c] = $this->requerir();
        $this->protegido(function () use ($c) {
            $turnos = new CajaTurnoRepository();
            $t = $turnos->abiertoDe($c->idEmpleado);
            if (!$t) return ['movimientos' => []];
            return ['movimientos' => array_map(fn($m) => [
                'id' => (int)$m['id_movimiento_caja'], 'hora' => substr((string)$m['fecha_hora'], 11, 5),
                'tipo' => $m['tipo'], 'monto' => (float)$m['monto'], 'motivo' => $m['motivo'],
            ], $turnos->movimientos((int)$t['id_caja_turno']))];
        });
    }

    public function productos(): void
    {
        [$c] = $this->requerir();
        $this->protegido(function () use ($c) {
            $filas = (new VentaRepository())->buscarProductos($c->idTienda, (string)($_GET['q'] ?? ''));
            return ['productos' => array_map(fn($p) => [
                'id' => (int)$p['id_producto'], 'nombre' => $p['nombre_producto'], 'codigo' => $p['codigo_barras'],
                'precio' => (float)$p['precio_venta'], 'peso' => $p['se_vende_por_peso'] === 'S',
                'unidad' => $p['abreviatura'], 'stock' => (float)$p['stock'],
            ], $filas)];
        });
    }

    public function vender(): void
    {
        [$c] = $this->requerir(true);
        $b = $this->cuerpo();
        $this->protegido(function () use ($c, $b) {
            $t = (new CajaTurnoRepository())->abiertoDe($c->idEmpleado);
            if (!$t) throw new RuntimeException('Abra la caja antes de vender.');
            if ($t['de_dia_anterior']) throw new RuntimeException('El turno abierto es de un día anterior. Ciérrelo y abra uno nuevo para vender.');
            $venta = (new VentaRepository())->registrar(
                $c->idTienda, $c->idEmpleado, (int)$t['id_caja_turno'],
                is_array($b['items'] ?? null) ? $b['items'] : [],
                (string)($b['tipo'] ?? 'B'), preg_replace('/\D/', '', (string)($b['dni'] ?? '')), preg_replace('/\D/', '', (string)($b['ruc'] ?? '')),
                is_array($b['pago'] ?? null) ? $b['pago'] : []
            );
            return ['venta' => $venta];
        });
    }

    public function ventas(): void
    {
        [, $idCaja] = $this->requerir();
        $this->protegido(function () use ($idCaja) {
            $claves = [1 => 'ef', 2 => 'tj', 3 => 'sip'];
            return ['ventas' => array_map(fn($v) => [
                'id_venta' => (int)$v['id_venta'],
                'num' => $v['serie'] . '-' . str_pad((string)$v['numero_correlativo'], 6, '0', STR_PAD_LEFT),
                'hora' => substr($v['fecha_hora'], 11, 8), 'cli' => $v['cliente'], 'lineas' => (int)$v['lineas'],
                'medio' => $this->claveMedio((string)$v['medio']), 'total' => (float)$v['total'],
                'estado' => $v['estado'] === 'ANULADA' ? 'anulada' : 'vigente', 'pendiente' => (bool)$v['pendiente'],
            ], (new VentaRepository())->delDia($idCaja))];
        });
    }

    public function buscarVenta(): void
    {
        [$c] = $this->requerir();
        $this->protegido(function () use ($c) {
            $v = (new VentaRepository())->buscarComprobante($c->idTienda, (string)($_GET['q'] ?? ''));
            if (!$v) return ['venta' => null];
            return ['venta' => [
                'id_venta' => (int)$v['id_venta'],
                'num' => $v['serie'] . '-' . str_pad((string)$v['numero_correlativo'], 6, '0', STR_PAD_LEFT),
                'tipo' => $v['tipo'], 'medio' => $v['medio'], 'lineas' => (int)$v['lineas'], 'total' => (float)$v['total'],
                'estado' => $v['estado'], 'propia' => (int)$v['id_cajero'] === $c->idEmpleado,
                'id_pendiente' => $v['id_pendiente'] === null ? null : (int)$v['id_pendiente'],
            ]];
        });
    }

    public function solicitar(): void
    {
        [$c] = $this->requerir(true);
        $b = $this->cuerpo();
        $this->protegido(function () use ($c, $b) {
            $id = (new SolicitudAnulacionRepository())->solicitar((int)($b['id_venta'] ?? 0), $c->idEmpleado, (string)($b['motivo'] ?? ''));
            return ['id_solicitud' => $id];
        });
    }

    // La pantalla la consulta cada pocos segundos mientras espera la respuesta del administrador
    public function solicitud(): void
    {
        [$c] = $this->requerir();
        $this->protegido(function () use ($c) {
            $s = (new SolicitudAnulacionRepository())->estadoParaCajero((int)($_GET['id'] ?? 0), $c->idEmpleado);
            if (!$s) throw new RuntimeException('La solicitud no existe.');
            return ['solicitud' => [
                'id' => (int)$s['id_solicitud'], 'estado' => $s['estado'], 'motivo_rechazo' => $s['motivo_rechazo'],
                'administrador' => $s['administrador'], 'nota_credito' => $s['nota_credito'], 'codigo_central' => $s['codigo_central'],
                'movimientos_stock' => (int)$s['movimientos_stock'], 'estado_venta' => $s['estado_venta'],
            ]];
        });
    }

    // ---- celular como escáner ----

    // Genera el enlace (QR) para el celular de esta caja. El anterior deja de funcionar.
    public function escanerVincular(): void
    {
        [$c, $idCaja] = $this->requerir(true);
        $this->protegido(function () use ($c, $idCaja) {
            $token = (new EscanerCajaRepository())->vincular($idCaja, $c->idEmpleado);
            $dir   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $modo  = strtolower(trim((string)Config::get('ESCANER_MODO', 'usb'))) === 'wifi' ? 'wifi' : 'usb';
            $base  = trim((string)Config::get('ESCANER_URL_BASE', ''));
            $local = false;
            if ($base !== '') {
                $base = rtrim($base, '/') . '/';
            } elseif ($modo === 'usb') {
                // Por cable (adb reverse) el celular ve esta PC como «localhost» en el mismo puerto; y localhost permite la cámara sin https.
                $puerto = (int)Config::get('ESCANER_PUERTO', '') ?: ($https ? 80 : (int)($_SERVER['SERVER_PORT'] ?? 80));
                $base   = 'http://localhost' . ($puerto === 80 ? '' : ':' . $puerto) . $dir . '/';
            } else {
                $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $local = preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/i', $host) === 1;
                $base  = ($https ? 'https' : 'http') . '://' . $host . $dir . '/';
            }
            $esLocalhost = preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?/#i', $base) === 1;
            $usb    = $esLocalhost && !$local;
            $seguro = stripos($base, 'https://') === 0 || $esLocalhost;
            $puerto = (int)(parse_url($base, PHP_URL_PORT) ?: (stripos($base, 'https://') === 0 ? 443 : 80));
            return [
                'url' => $base . 'index.php?r=caja_escaner&c=' . $idCaja . '&t=' . $token,
                'usb' => $usb, 'puerto' => $puerto, 'local' => $local, 'seguro' => $seguro,
            ];
        });
    }

    public function escanerDesvincular(): void
    {
        [, $idCaja] = $this->requerir(true);
        $this->protegido(function () use ($idCaja) {
            (new EscanerCajaRepository())->desvincular($idCaja);
            return [];
        });
    }

    // La caja consulta cada segundo: ¿hay celular?, ¿está conectado?, ¿llegaron códigos nuevos?
    public function escanerLeer(): void
    {
        [, $idCaja] = $this->requerir();
        $this->protegido(fn() => (new EscanerCajaRepository())->estado($idCaja, (int)($_GET['desde'] ?? 0)));
    }

    // ---- ayudas ----

    private function claveMedio(string $m): string
    {
        $m = strtoupper($m);
        return $m === 'EFECTIVO' ? 'ef' : ($m === 'TARJETA' ? 'tj' : 'sip');
    }

    private function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    private function cajero(): ?Empleado
    {
        return Sesion::empleado();
    }

    // Devuelve [cajero, id_caja] o corta con 401. Si $escribe, exige además el token CSRF.
    private function requerir(bool $escribe = false): array
    {
        $this->iniciarSesion();
        $c = $this->cajero();
        if (!$c instanceof Cajero || Sesion::idCaja() === null) { $this->json(['ok' => false, 'error' => 'La sesión terminó. Vuelva a ingresar.'], 401); exit; }
        if ($escribe) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf_caja'] ?? '', $token)) {
                $this->json(['ok' => false, 'error' => 'Solicitud no válida. Recargue la pantalla.'], 403); exit;
            }
        }
        return [$c, Sesion::idCaja()];
    }

    private function cuerpo(): array
    {
        $d = json_decode((string)file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }

    // Ejecuta la acción y responde {ok:true,...}; los errores de regla se muestran tal cual, los demás se registran
    private function protegido(callable $accion): void
    {
        try {
            $this->json(['ok' => true] + $accion());
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            error_log('caja: ' . $e->getMessage());
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
