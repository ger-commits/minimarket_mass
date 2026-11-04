<?php
// M: consultas de SOLO LECTURA para el panel del administrador (ventas y personal de su tienda). No modifica datos.
require_once __DIR__ . '/Database.php';

class AdminPanelRepository
{
    // Últimas ventas de la tienda: comprobante, fecha, cajero, medio de pago (el primero válido), total y estado
    public function ventas(int $idTienda, int $limite = 20): array
    {
        $st = Database::conectar()->prepare(
            "SELECT s.serie, v.numero_correlativo, v.fecha_hora, v.total, v.estado, t.nombre_tienda,
                    CONCAT(LEFT(e.nombres, 1), '. ', SUBSTRING_INDEX(e.apellidos, ' ', 1)) AS cajero,
                    (SELECT mp.nombre_metodo
                       FROM pago_venta pv
                       JOIN metodo_pago mp ON mp.id_metodo_pago = pv.id_metodo_pago
                      WHERE pv.id_venta = v.id_venta AND pv.estado = 'VALIDO'
                      ORDER BY pv.id_pago LIMIT 1) AS medio
               FROM venta v
               JOIN serie_comprobante s ON s.id_serie = v.id_serie
               JOIN tienda t            ON t.id_tienda = s.id_tienda
               JOIN caja_turno ct       ON ct.id_caja_turno = v.id_caja_turno
               JOIN empleado e          ON e.id_empleado = ct.id_empleado
              WHERE s.id_tienda = ?
              ORDER BY v.fecha_hora DESC, v.id_venta DESC
              LIMIT " . (int)$limite
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    // Personal de la tienda con su rol, estado y su turno más reciente
    public function personal(int $idTienda): array
    {
        $st = Database::conectar()->prepare(
            "SELECT e.nombres, e.apellidos, e.dni, e.estado, r.nombre_rol, t.nombre_tienda,
                    (SELECT CONCAT(TIME_FORMAT(tt.hora_inicio, '%H:%i'), ' – ', TIME_FORMAT(tt.hora_fin, '%H:%i'))
                       FROM turno_trabajo tt
                      WHERE tt.id_empleado = e.id_empleado
                      ORDER BY tt.fecha DESC, tt.hora_inicio DESC
                      LIMIT 1) AS turno
               FROM empleado e
               JOIN rol r    ON r.id_rol = e.id_rol
               JOIN tienda t ON t.id_tienda = e.id_tienda
              WHERE e.id_tienda = ?
              ORDER BY r.nombre_rol, e.apellidos"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    // Totales de hoy y de ayer de la tienda (solo ventas emitidas, sin anuladas)
    public function ventasDelDia(int $idTienda, string $cuando): array
    {
        $fecha = $cuando === 'ayer' ? 'DATE_SUB(CURDATE(), INTERVAL 1 DAY)' : 'CURDATE()';
        $st = Database::conectar()->prepare(
            "SELECT COALESCE(SUM(v.total), 0) AS total, COUNT(*) AS transacciones
               FROM venta v
               JOIN serie_comprobante s ON s.id_serie = v.id_serie
              WHERE s.id_tienda = ? AND v.estado = 'EMITIDA' AND DATE(v.fecha_hora) = $fecha"
        );
        $st->execute([$idTienda]);
        return $st->fetch();
    }

    // Conteos generales: productos activos y productos con stock bajo o agotado en la tienda
    public function conteosResumen(int $idTienda): array
    {
        $pdo = Database::conectar();
        $productos = (int)$pdo->query("SELECT COUNT(*) FROM producto WHERE estado = 'ACTIVO'")->fetchColumn();
        $st = $pdo->prepare("SELECT COUNT(*) FROM stock WHERE id_tienda = ? AND cantidad_actual <= stock_minimo");
        $st->execute([$idTienda]);
        return ['productos' => $productos, 'alertas' => (int)$st->fetchColumn()];
    }

    // Las tres tiendas con su estado y su distrito
    public function tiendas(): array
    {
        return Database::conectar()->query(
            "SELECT t.nombre_tienda, t.estado, t.direccion, d.nombre_distrito
               FROM tienda t
               JOIN distrito d ON d.id_distrito = t.id_distrito
              ORDER BY t.id_tienda"
        )->fetchAll();
    }
}
