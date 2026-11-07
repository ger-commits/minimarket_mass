<?php
// M: producto nuevo con autorización. El almacenero escanea un código que no está en el catálogo, el administrador
// de SU MISMA tienda lo permite o lo rechaza y, solo si lo permite, el almacenero registra los datos del producto.
// Cada tienda solo ve y resuelve sus propias solicitudes.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Administrador.php';
require_once __DIR__ . '/Almacenero.php';
require_once __DIR__ . '/ProductoRepository.php';

class SolicitudProductoRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::conectar();
    }

    // El almacenero pide permiso para registrar un código que no existe. Devuelve [id, estado, mensaje].
    public function solicitar(Almacenero $a, string $codigo): array
    {
        $codigo = trim($codigo);
        if (!preg_match('/^[0-9]{8,14}$/', $codigo)) {
            throw new InvalidArgumentException('El código debe tener entre 8 y 14 dígitos.');
        }
        if ($this->idProductoPorCodigo($codigo) !== null) {
            throw new InvalidArgumentException('Ese código ya está en el catálogo.');
        }

        // Si ya hay una solicitud abierta de esta tienda para el mismo código, no se crea otra
        $st = $this->pdo->prepare(
            "SELECT id_solicitud_producto, estado FROM solicitud_producto
              WHERE id_tienda = ? AND codigo_barras = ? AND estado IN ('PENDIENTE','APROBADA')
              ORDER BY id_solicitud_producto DESC LIMIT 1"
        );
        $st->execute([$a->idTienda, $codigo]);
        $abierta = $st->fetch();
        if ($abierta) {
            $id = (int)$abierta['id_solicitud_producto'];
            return $abierta['estado'] === 'APROBADA'
                ? [$id, 'APROBADA', 'El administrador ya permitió este producto. Entra a "Productos nuevos" para registrarlo.']
                : [$id, 'PENDIENTE', 'Ya enviaste este código. Espera la respuesta del administrador.'];
        }

        $this->pdo->prepare(
            'INSERT INTO solicitud_producto (codigo_barras, estado, fecha_hora_solicitud, id_tienda, id_empleado_solicita)
             VALUES (?, ?, NOW(), ?, ?)'
        )->execute([$codigo, 'PENDIENTE', $a->idTienda, $a->idEmpleado]);
        return [(int)$this->pdo->lastInsertId(), 'PENDIENTE', 'Producto no registrado. Se envió la solicitud al administrador de tu tienda.'];
    }

    // Solicitudes pendientes que debe resolver el administrador de la tienda
    public function pendientesDeTienda(int $idTienda): array
    {
        $st = $this->pdo->prepare(
            "SELECT sp.id_solicitud_producto, sp.codigo_barras, sp.fecha_hora_solicitud,
                    CONCAT(e.nombres, ' ', e.apellidos) AS solicitante
               FROM solicitud_producto sp JOIN empleado e ON e.id_empleado = sp.id_empleado_solicita
              WHERE sp.id_tienda = ? AND sp.estado = 'PENDIENTE'
              ORDER BY sp.fecha_hora_solicitud"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    // Lo que ve el almacén: pendientes, permitidas (por registrar) y las rechazadas de los últimos 7 días
    public function delAlmacen(int $idTienda): array
    {
        $st = $this->pdo->prepare(
            "SELECT id_solicitud_producto AS id, codigo_barras AS codigo, estado, motivo_rechazo AS motivo,
                    DATE_FORMAT(fecha_hora_solicitud, '%d/%m/%Y %H:%i') AS fecha
               FROM solicitud_producto
              WHERE id_tienda = ?
                AND (estado IN ('PENDIENTE','APROBADA')
                     OR (estado = 'RECHAZADA' AND fecha_hora_respuesta >= NOW() - INTERVAL 7 DAY))
              ORDER BY id_solicitud_producto DESC LIMIT 50"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    // El administrador permite o rechaza. Solo puede resolver solicitudes de su tienda.
    public function responder(Administrador $admin, int $idSolicitud, bool $permitir, string $motivo = ''): void
    {
        $motivo = trim($motivo);
        if (!$permitir && $motivo === '') throw new InvalidArgumentException('Escribe el motivo del rechazo.');
        if (mb_strlen($motivo) > 150) throw new InvalidArgumentException('El motivo no puede pasar de 150 caracteres.');

        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('SELECT * FROM solicitud_producto WHERE id_solicitud_producto = ? FOR UPDATE');
            $st->execute([$idSolicitud]);
            $s = $st->fetch();
            if (!$s) throw new RuntimeException('La solicitud no existe.');
            if ((int)$s['id_tienda'] !== $admin->idTienda) {
                throw new RuntimeException('Solo puedes resolver solicitudes de tu tienda.');
            }
            if ($s['estado'] !== 'PENDIENTE') throw new RuntimeException('La solicitud ya fue resuelta.');
            if ($permitir && $this->idProductoPorCodigo($s['codigo_barras']) !== null) {
                throw new RuntimeException('Ese código ya fue registrado en el catálogo; no hace falta autorizarlo.');
            }

            $this->pdo->prepare(
                'UPDATE solicitud_producto SET estado = ?, fecha_hora_respuesta = NOW(), motivo_rechazo = ?, id_empleado_responde = ?
                  WHERE id_solicitud_producto = ?'
            )->execute([$permitir ? 'APROBADA' : 'RECHAZADA', $permitir ? null : $motivo, $admin->idEmpleado, $idSolicitud]);

            $mensaje = $permitir
                ? 'El administrador permitió el producto con código ' . $s['codigo_barras'] . '. Ya puedes registrarlo en "Productos nuevos".'
                : 'El administrador rechazó el producto con código ' . $s['codigo_barras'] . ': ' . $motivo;
            $this->pdo->prepare(
                'INSERT INTO notificacion_almacen (id_tienda, tipo, mensaje, creado) VALUES (?, ?, ?, NOW())'
            )->execute([$admin->idTienda, $permitir ? 'PRODUCTO_PERMITIDO' : 'PRODUCTO_RECHAZADO', mb_substr($mensaje, 0, 255)]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    // El almacenero llena los datos y registra el producto de una solicitud ya permitida.
    // $datos usa los mismos campos que el formulario del administrador (nombre, precio, id_categoria, ...).
    public function registrarProducto(Almacenero $a, int $idSolicitud, array $datos): int
    {
        $st = $this->pdo->prepare('SELECT * FROM solicitud_producto WHERE id_solicitud_producto = ?');
        $st->execute([$idSolicitud]);
        $s = $st->fetch();
        if (!$s || (int)$s['id_tienda'] !== $a->idTienda) throw new RuntimeException('La solicitud no existe en tu tienda.');
        if ($s['estado'] !== 'APROBADA') throw new RuntimeException('El administrador todavía no permitió este producto.');

        $codigo = (string)$s['codigo_barras'];
        $existente = $this->idProductoPorCodigo($codigo);
        if ($existente !== null) {
            $this->marcarUsada($idSolicitud, $existente);
            throw new RuntimeException('Ese producto ya fue registrado por otra tienda: ya aparece en tu catálogo.');
        }

        // El código lo fija la solicitud: el almacenero no puede registrar otro distinto al autorizado
        $datos['codigo_barras'] = $codigo;
        $datos['estado'] = 'ACTIVO';
        $idProducto = (new ProductoRepository())->crear($datos);
        $this->marcarUsada($idSolicitud, $idProducto);
        return $idProducto;
    }

    private function marcarUsada(int $idSolicitud, int $idProducto): void
    {
        $this->pdo->prepare(
            "UPDATE solicitud_producto SET estado = 'USADA', id_producto = ? WHERE id_solicitud_producto = ? AND estado = 'APROBADA'"
        )->execute([$idProducto, $idSolicitud]);
    }

    private function idProductoPorCodigo(string $codigo): ?int
    {
        $st = $this->pdo->prepare('SELECT id_producto FROM producto WHERE codigo_barras = ?');
        $st->execute([$codigo]);
        $id = $st->fetchColumn();
        return $id === false ? null : (int)$id;
    }
}
