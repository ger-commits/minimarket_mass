<?php
// M: indicadores de la tienda (solo lectura). Cada consulta filtra por la tienda del administrador.
require_once __DIR__ . '/Database.php';

class ReporteRepository
{
    public function todo(int $t): array
    {
        return [
            'ventasPorDia'      => $this->ventasPorDia($t, 30),
            'topProductos'      => $this->topProductos($t, 10),
            'anulaciones'       => $this->anulaciones($t),
            'mermasPorMotivo'   => $this->mermasPorMotivo($t),
            'turnosPorEmpleado' => $this->turnosPorEmpleado($t, 30),
            'stockCritico'      => $this->stockCritico($t),
            'traslados'         => $this->traslados($t, 30),
        ];
    }

    private function consulta(string $sql, array $params): array
    {
        $st = Database::conectar()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function ventasPorDia(int $t, int $dias): array
    {
        return $this->consulta(
            "SELECT DATE(v.fecha_hora) AS dia, COUNT(*) AS n, SUM(v.total) AS total
               FROM venta v JOIN serie_comprobante s ON s.id_serie = v.id_serie
              WHERE s.id_tienda = ? AND v.estado = 'EMITIDA'
                AND v.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL " . (int)$dias . " DAY)
              GROUP BY DATE(v.fecha_hora)
              ORDER BY dia DESC",
            [$t]
        );
    }

    public function topProductos(int $t, int $limite): array
    {
        return $this->consulta(
            "SELECT p.nombre_producto, SUM(d.cantidad) AS cantidad, SUM(d.importe) AS total
               FROM detalle_venta d
               JOIN venta v ON v.id_venta = d.id_venta
               JOIN serie_comprobante s ON s.id_serie = v.id_serie
               JOIN producto p ON p.id_producto = d.id_producto
              WHERE s.id_tienda = ? AND v.estado = 'EMITIDA'
              GROUP BY p.id_producto, p.nombre_producto
              ORDER BY total DESC
              LIMIT " . (int)$limite,
            [$t]
        );
    }

    public function anulaciones(int $t): array
    {
        return $this->consulta(
            "SELECT sa.estado, COUNT(*) AS n
               FROM solicitud_anulacion sa
               JOIN venta v ON v.id_venta = sa.id_venta
               JOIN serie_comprobante s ON s.id_serie = v.id_serie
              WHERE s.id_tienda = ?
              GROUP BY sa.estado",
            [$t]
        );
    }

    public function mermasPorMotivo(int $t): array
    {
        return $this->consulta(
            "SELECT mr.motivo, COUNT(DISTINCT mr.id_merma) AS registros, COUNT(*) AS lineas
               FROM merma mr
               JOIN movimiento_stock m ON m.id_merma = mr.id_merma
              WHERE m.id_tienda = ?
              GROUP BY mr.motivo
              ORDER BY registros DESC",
            [$t]
        );
    }

    public function turnosPorEmpleado(int $t, int $dias): array
    {
        return $this->consulta(
            "SELECT CONCAT(e.nombres, ' ', e.apellidos) AS empleado, r.nombre_rol,
                    COUNT(tt.id_turno_trabajo) AS turnos,
                    ROUND(SUM(TIME_TO_SEC(tt.hora_fin) - TIME_TO_SEC(tt.hora_inicio)) / 3600, 1) AS horas
               FROM turno_trabajo tt
               JOIN empleado e ON e.id_empleado = tt.id_empleado
               JOIN rol r ON r.id_rol = e.id_rol
              WHERE e.id_tienda = ? AND tt.fecha >= DATE_SUB(CURDATE(), INTERVAL " . (int)$dias . " DAY)
              GROUP BY e.id_empleado, e.nombres, e.apellidos, r.nombre_rol
              ORDER BY horas DESC",
            [$t]
        );
    }

    public function stockCritico(int $t): array
    {
        return $this->consulta(
            "SELECT p.nombre_producto, s.cantidad_actual, s.stock_minimo
               FROM stock s JOIN producto p ON p.id_producto = s.id_producto
              WHERE s.id_tienda = ? AND s.cantidad_actual <= s.stock_minimo
              ORDER BY s.cantidad_actual, p.nombre_producto
              LIMIT 50",
            [$t]
        );
    }

    public function traslados(int $t, int $dias): array
    {
        return $this->consulta(
            "SELECT CASE WHEN tm.id_tienda_origen = ? THEN 'ENVIADO' ELSE 'RECIBIDO' END AS sentido,
                    COUNT(*) AS n
               FROM traslado_mercaderia tm
              WHERE (tm.id_tienda_origen = ? OR tm.id_tienda_destino = ?)
                AND tm.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL " . (int)$dias . " DAY)
              GROUP BY sentido",
            [$t, $t, $t]
        );
    }
}