/* =====================================================================
   MINIMARKET MASS - consultas.sql
   Consultas que responden preguntas del negocio usando JOIN entre las tablas.
   Ejecutar DESPUES de schema.sql y datos.sql.
   Solo se vende lo que esta EMITIDO: las ventas ANULADAS no cuentan en los totales.
   ===================================================================== */

USE minimarket_mass;
SET NAMES utf8mb4;

/* ---------------------------------------------------------------------
   A. CATALOGO Y PERSONAL
   --------------------------------------------------------------------- */

-- 1. Que productos vendemos, de que categoria, marca y unidad, y a que precio.
SELECT p.id_producto, p.nombre_producto, c.nombre_categoria AS categoria, m.nombre_marca AS marca,
       u.abreviatura AS unidad, p.precio_venta
  FROM producto p
  JOIN categoria c     ON c.id_categoria = p.id_categoria
  JOIN marca m         ON m.id_marca = p.id_marca
  JOIN unidad_medida u ON u.id_unidad = p.id_unidad
 ORDER BY c.nombre_categoria, p.nombre_producto;

-- 2. Quienes trabajan en cada tienda y con que rol.
SELECT t.nombre_tienda AS tienda, d.nombre_distrito AS distrito, r.nombre_rol AS rol,
       CONCAT(e.nombres, ' ', e.apellidos) AS empleado, e.dni
  FROM empleado e
  JOIN tienda t   ON t.id_tienda = e.id_tienda
  JOIN distrito d ON d.id_distrito = t.id_distrito
  JOIN rol r      ON r.id_rol = e.id_rol
 ORDER BY t.nombre_tienda, r.nombre_rol;

/* ---------------------------------------------------------------------
   B. INVENTARIO
   --------------------------------------------------------------------- */

-- 3. Cual es el stock de cada producto en cada tienda (REPONER cuando llega al minimo o menos).
SELECT t.nombre_tienda AS tienda, p.nombre_producto AS producto, u.abreviatura AS unidad,
       s.cantidad_actual, s.stock_minimo,
       CASE WHEN s.cantidad_actual <= s.stock_minimo THEN 'REPONER' ELSE 'NORMAL' END AS situacion
  FROM stock s
  JOIN tienda t        ON t.id_tienda = s.id_tienda
  JOIN producto p      ON p.id_producto = s.id_producto
  JOIN unidad_medida u ON u.id_unidad = p.id_unidad
 ORDER BY t.nombre_tienda, p.nombre_producto;

-- 4. Que productos hay que reponer ahora mismo y cuanto falta para llegar al doble del minimo.
SELECT t.nombre_tienda AS tienda, p.nombre_producto AS producto, s.cantidad_actual, s.stock_minimo,
       (s.stock_minimo * 2 - s.cantidad_actual) AS sugerido_reponer
  FROM stock s
  JOIN tienda t   ON t.id_tienda = s.id_tienda
  JOIN producto p ON p.id_producto = s.id_producto
 WHERE s.cantidad_actual <= s.stock_minimo
 ORDER BY t.nombre_tienda, p.nombre_producto;

-- 5. Cuanto stock tiene la cadena de cada producto sumando las 3 tiendas.
SELECT p.nombre_producto AS producto, u.abreviatura AS unidad, SUM(s.cantidad_actual) AS stock_total
  FROM stock s
  JOIN producto p      ON p.id_producto = s.id_producto
  JOIN unidad_medida u ON u.id_unidad = p.id_unidad
 GROUP BY p.id_producto, p.nombre_producto, u.abreviatura
 ORDER BY p.nombre_producto;

-- 6. Kardex: historial de movimientos de la papa blanca en Mass Cayma (saldo despues de cada movimiento).
SELECT m.fecha_hora, tm.nombre_movimiento AS movimiento, m.cantidad, m.stock_nuevo AS saldo,
       CASE WHEN m.id_venta IS NOT NULL    THEN CONCAT('Venta ', s.serie, '-', LPAD(v.numero_correlativo, 8, '0'))
            WHEN m.id_traslado IS NOT NULL THEN CONCAT('Traslado N° ', m.id_traslado)
            ELSE CONCAT('Merma N° ', m.id_merma) END AS origen,
       CONCAT(e.nombres, ' ', e.apellidos) AS registrado_por
  FROM movimiento_stock m
  JOIN tienda t            ON t.id_tienda = m.id_tienda
  JOIN producto p          ON p.id_producto = m.id_producto
  JOIN tipo_movimiento tm  ON tm.id_tipo_movimiento = m.id_tipo_movimiento
  JOIN empleado e          ON e.id_empleado = m.id_empleado
  LEFT JOIN venta v        ON v.id_venta = m.id_venta
  LEFT JOIN serie_comprobante s ON s.id_serie = v.id_serie
 WHERE t.nombre_tienda = 'Mass Cayma' AND p.nombre_producto = 'Papa blanca (por kg)'
 ORDER BY m.fecha_hora, m.id_movimiento;

-- 7. Que mercaderia se traslado entre tiendas: origen, destino, producto y cantidad.
SELECT tr.id_traslado, tr.fecha_hora, o.nombre_tienda AS origen, d.nombre_tienda AS destino,
       p.nombre_producto AS producto, m.cantidad, CONCAT(e.nombres, ' ', e.apellidos) AS registrado_por
  FROM traslado_mercaderia tr
  JOIN tienda o   ON o.id_tienda = tr.id_tienda_origen
  JOIN tienda d   ON d.id_tienda = tr.id_tienda_destino
  JOIN empleado e ON e.id_empleado = tr.id_empleado
  JOIN movimiento_stock m ON m.id_traslado = tr.id_traslado AND m.id_tienda = tr.id_tienda_origen
  JOIN producto p ON p.id_producto = m.id_producto
 ORDER BY tr.id_traslado, p.nombre_producto;

-- 8. Que se perdio por merma, por que motivo y en que tienda.
SELECT mr.id_merma, mr.fecha_hora, t.nombre_tienda AS tienda, mr.motivo, p.nombre_producto AS producto, m.cantidad
  FROM merma mr
  JOIN movimiento_stock m ON m.id_merma = mr.id_merma
  JOIN tienda t           ON t.id_tienda = m.id_tienda
  JOIN producto p         ON p.id_producto = m.id_producto
 ORDER BY mr.fecha_hora;

/* ---------------------------------------------------------------------
   C. VENTAS
   --------------------------------------------------------------------- */

-- 9. Que comprobantes se emitieron (serie + correlativo), a quien y por cuanto.
SELECT CONCAT(s.serie, '-', LPAD(v.numero_correlativo, 8, '0')) AS comprobante,
       tc.nombre_comprobante AS tipo, t.nombre_tienda AS tienda, v.fecha_hora,
       COALESCE(cl.ruc, cl.dni, 'SIN CLIENTE') AS cliente, v.total, v.estado
  FROM venta v
  JOIN serie_comprobante s  ON s.id_serie = v.id_serie
  JOIN tipo_comprobante tc  ON tc.id_tipo_comprobante = s.id_tipo_comprobante
  JOIN tienda t             ON t.id_tienda = s.id_tienda
  LEFT JOIN cliente cl      ON cl.id_cliente = v.id_cliente
 ORDER BY s.serie, v.numero_correlativo;

-- 10. Cuanto vendio cada dia la cadena (comprobantes y total).
SELECT DATE(v.fecha_hora) AS dia, COUNT(*) AS comprobantes, SUM(v.total) AS total_vendido
  FROM venta v
 WHERE v.estado = 'EMITIDA'
 GROUP BY DATE(v.fecha_hora)
 ORDER BY dia;

-- 11. Cuanto vendio cada tienda cada dia.
SELECT DATE(v.fecha_hora) AS dia, t.nombre_tienda AS tienda, COUNT(*) AS comprobantes, SUM(v.total) AS total_vendido
  FROM venta v
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN tienda t            ON t.id_tienda = s.id_tienda
 WHERE v.estado = 'EMITIDA'
 GROUP BY DATE(v.fecha_hora), t.id_tienda, t.nombre_tienda
 ORDER BY dia, t.nombre_tienda;

-- 12. Cuanto se vendio en boletas y cuanto en facturas por tienda.
SELECT t.nombre_tienda AS tienda, tc.nombre_comprobante AS tipo, COUNT(*) AS comprobantes, SUM(v.total) AS total_vendido
  FROM venta v
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN tipo_comprobante tc ON tc.id_tipo_comprobante = s.id_tipo_comprobante
  JOIN tienda t            ON t.id_tienda = s.id_tienda
 WHERE v.estado = 'EMITIDA'
 GROUP BY t.id_tienda, t.nombre_tienda, tc.id_tipo_comprobante, tc.nombre_comprobante
 ORDER BY t.nombre_tienda, tc.nombre_comprobante;

-- 13. Que se vendio: productos mas vendidos por cantidad e importe.
SELECT p.nombre_producto AS producto, u.abreviatura AS unidad, SUM(d.cantidad) AS cantidad_vendida, SUM(d.importe) AS importe_vendido
  FROM detalle_venta d
  JOIN venta v         ON v.id_venta = d.id_venta
  JOIN producto p      ON p.id_producto = d.id_producto
  JOIN unidad_medida u ON u.id_unidad = p.id_unidad
 WHERE v.estado = 'EMITIDA'
 GROUP BY p.id_producto, p.nombre_producto, u.abreviatura
 ORDER BY importe_vendido DESC;

-- 14. Cuanto se vendio por categoria de producto.
SELECT c.nombre_categoria AS categoria, SUM(d.importe) AS importe_vendido
  FROM detalle_venta d
  JOIN venta v     ON v.id_venta = d.id_venta
  JOIN producto p  ON p.id_producto = d.id_producto
  JOIN categoria c ON c.id_categoria = p.id_categoria
 WHERE v.estado = 'EMITIDA'
 GROUP BY c.id_categoria, c.nombre_categoria
 ORDER BY importe_vendido DESC;

-- 15. Cuanto vendio cada cajero (el cajero de una venta es el responsable de su turno de caja).
SELECT CONCAT(e.nombres, ' ', e.apellidos) AS cajero, t.nombre_tienda AS tienda, COUNT(*) AS comprobantes, SUM(v.total) AS total_vendido
  FROM venta v
  JOIN caja_turno ct ON ct.id_caja_turno = v.id_caja_turno
  JOIN empleado e    ON e.id_empleado = ct.id_empleado
  JOIN tienda t      ON t.id_tienda = e.id_tienda
 WHERE v.estado = 'EMITIDA'
 GROUP BY e.id_empleado, e.nombres, e.apellidos, t.nombre_tienda
 ORDER BY total_vendido DESC;

-- 16. Cuanto se cobro con cada metodo de pago (efectivo, tarjeta, billetera digital Sip).
SELECT mp.nombre_metodo AS metodo_pago, COUNT(*) AS pagos, SUM(pv.monto_pagado) AS monto_cobrado
  FROM pago_venta pv
  JOIN metodo_pago mp ON mp.id_metodo_pago = pv.id_metodo_pago
 WHERE pv.estado = 'VALIDO'
 GROUP BY mp.id_metodo_pago, mp.nombre_metodo
 ORDER BY monto_cobrado DESC;

-- 17. Que ventas se pagaron con mas de un metodo (pago mixto).
SELECT CONCAT(s.serie, '-', LPAD(v.numero_correlativo, 8, '0')) AS comprobante, v.total,
       COUNT(*) AS cantidad_de_pagos, SUM(pv.monto_pagado) AS suma_de_pagos
  FROM venta v
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN pago_venta pv       ON pv.id_venta = v.id_venta AND pv.estado = 'VALIDO'
 GROUP BY v.id_venta, s.serie, v.numero_correlativo, v.total
HAVING COUNT(*) > 1;

-- 18. Que ventas se anularon: quien lo pidio, quien lo autorizo y por que motivo.
SELECT CONCAT(s.serie, '-', LPAD(v.numero_correlativo, 8, '0')) AS comprobante, v.total,
       sa.fecha_hora_resolucion AS fecha_anulacion, sa.motivo,
       CONCAT(ec.nombres, ' ', ec.apellidos) AS pedido_por, CONCAT(ea.nombres, ' ', ea.apellidos) AS autorizado_por
  FROM solicitud_anulacion sa
  JOIN venta v             ON v.id_venta = sa.id_venta
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN empleado ec         ON ec.id_empleado = sa.id_empleado_solicita
  JOIN empleado ea         ON ea.id_empleado = sa.id_empleado_resuelve
 WHERE sa.estado = 'APROBADA'
 ORDER BY sa.fecha_hora_resolucion;

/* ---------------------------------------------------------------------
   D. CAJA
   --------------------------------------------------------------------- */

-- 19. Cuadre de cada turno: monto de cierre contra lo esperado
--     (monto inicial + efectivo cobrado + ingresos - retiros, gastos y devoluciones de dinero).
SELECT ct.id_caja_turno AS turno, t.nombre_tienda AS tienda, c.numero_caja AS caja,
       CONCAT(e.nombres, ' ', e.apellidos) AS cajero, ct.estado, ct.monto_inicial,
       COALESCE(ef.efectivo_cobrado, 0)  AS efectivo_cobrado,
       COALESCE(mc.ingresos, 0)          AS ingresos,
       COALESCE(mc.retiros_y_gastos, 0)  AS retiros_y_gastos,
       COALESCE(mc.devoluciones, 0)      AS devoluciones,
       ct.monto_inicial + COALESCE(ef.efectivo_cobrado, 0) + COALESCE(mc.ingresos, 0)
                        - COALESCE(mc.retiros_y_gastos, 0) - COALESCE(mc.devoluciones, 0) AS monto_esperado,
       ct.monto_cierre,
       ct.monto_cierre - (ct.monto_inicial + COALESCE(ef.efectivo_cobrado, 0) + COALESCE(mc.ingresos, 0)
                        - COALESCE(mc.retiros_y_gastos, 0) - COALESCE(mc.devoluciones, 0)) AS diferencia
  FROM caja_turno ct
  JOIN caja c     ON c.id_caja = ct.id_caja
  JOIN tienda t   ON t.id_tienda = c.id_tienda
  JOIN empleado e ON e.id_empleado = ct.id_empleado
  LEFT JOIN (SELECT v.id_caja_turno, SUM(pv.monto_pagado) AS efectivo_cobrado
               FROM venta v
               JOIN pago_venta pv ON pv.id_venta = v.id_venta AND pv.estado = 'VALIDO' AND pv.id_metodo_pago = 1
              GROUP BY v.id_caja_turno) ef ON ef.id_caja_turno = ct.id_caja_turno
  LEFT JOIN (SELECT id_caja_turno,
                    SUM(CASE WHEN tipo = 'INGRESO' THEN monto ELSE 0 END) AS ingresos,
                    SUM(CASE WHEN tipo IN ('RETIRO','GASTO') THEN monto ELSE 0 END) AS retiros_y_gastos,
                    SUM(CASE WHEN tipo = 'DEVOLUCION' THEN monto ELSE 0 END) AS devoluciones
               FROM movimiento_caja GROUP BY id_caja_turno) mc ON mc.id_caja_turno = ct.id_caja_turno
 ORDER BY ct.id_caja_turno;

-- 20. Que ingresos, retiros, gastos y devoluciones de dinero hubo en cada turno de caja.
SELECT mc.id_movimiento_caja, mc.fecha_hora, t.nombre_tienda AS tienda, ct.id_caja_turno AS turno,
       CONCAT(e.nombres, ' ', e.apellidos) AS cajero, mc.tipo, mc.monto, mc.motivo
  FROM movimiento_caja mc
  JOIN caja_turno ct ON ct.id_caja_turno = mc.id_caja_turno
  JOIN caja c        ON c.id_caja = ct.id_caja
  JOIN tienda t      ON t.id_tienda = c.id_tienda
  JOIN empleado e    ON e.id_empleado = ct.id_empleado
 ORDER BY mc.fecha_hora;

/* ---------------------------------------------------------------------
   E. DEVOLUCIONES Y LOTES
   --------------------------------------------------------------------- */

-- 21. Que productos se devolvieron, de que comprobante, por que motivo y cuanto dinero se devolvio.
SELECT dv.id_devolucion, dv.fecha_hora,
       CONCAT(s.serie, '-', LPAD(v.numero_correlativo, 8, '0')) AS comprobante,
       p.nombre_producto AS producto, dd.cantidad, dd.importe, dv.motivo, t.nombre_tienda AS tienda
  FROM devolucion_venta dv
  JOIN venta v             ON v.id_venta = dv.id_venta
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN detalle_devolucion dd ON dd.id_devolucion = dv.id_devolucion
  JOIN detalle_venta d     ON d.id_detalle_venta = dd.id_detalle_venta
  JOIN producto p          ON p.id_producto = d.id_producto
  JOIN tienda t            ON t.id_tienda = s.id_tienda
 ORDER BY dv.fecha_hora;

-- 22. Ventas netas por dia: lo vendido (comprobantes emitidos) menos lo devuelto.
SELECT d.dia, d.vendido, COALESCE(r.devuelto, 0) AS devuelto, d.vendido - COALESCE(r.devuelto, 0) AS venta_neta
  FROM (SELECT DATE(fecha_hora) AS dia, SUM(total) AS vendido
          FROM venta WHERE estado = 'EMITIDA' GROUP BY DATE(fecha_hora)) d
  LEFT JOIN (SELECT DATE(fecha_hora) AS dia, SUM(monto_devuelto) AS devuelto
               FROM devolucion_venta GROUP BY DATE(fecha_hora)) r ON r.dia = d.dia
 ORDER BY d.dia;

-- 23. Que lotes hay en cada tienda, cuanto queda y cuando vencen.
--     Fecha de referencia: 2026-10-19 (el dia de los datos de ejemplo); para usar la fecha de hoy, cambiala por CURDATE().
SELECT t.nombre_tienda AS tienda, p.nombre_producto AS producto, l.codigo_lote, l.fecha_vencimiento,
       DATEDIFF(l.fecha_vencimiento, '2026-10-19') AS dias_para_vencer, l.cantidad_actual,
       CASE WHEN l.fecha_vencimiento < '2026-10-19' THEN 'VENCIDO'
            WHEN DATEDIFF(l.fecha_vencimiento, '2026-10-19') <= 30 THEN 'POR VENCER'
            ELSE 'VIGENTE' END AS situacion
  FROM lote_producto l
  JOIN tienda t   ON t.id_tienda = l.id_tienda
  JOIN producto p ON p.id_producto = l.id_producto
 ORDER BY l.fecha_vencimiento, t.nombre_tienda;

/* ---------------------------------------------------------------------
   F. CONTROL DE REGLAS DE NEGOCIO
   Cada consulta debe devolver 0 FILAS: si devuelve algo, hay un dato que rompe la regla.
   --------------------------------------------------------------------- */

-- 24. Ventas emitidas cuyos pagos validos no suman el total.
SELECT v.id_venta, v.total
  FROM venta v
 WHERE v.estado = 'EMITIDA'
   AND ROUND(v.total - COALESCE((SELECT SUM(pv.monto_pagado) FROM pago_venta pv
                                  WHERE pv.id_venta = v.id_venta AND pv.estado = 'VALIDO'), 0), 2) <> 0;

-- 25. Ventas cuyo detalle no suma el total.
SELECT v.id_venta, v.total
  FROM venta v
 WHERE ROUND(v.total - (SELECT SUM(d.importe) FROM detalle_venta d WHERE d.id_venta = v.id_venta), 2) <> 0;

-- 26. Facturas cuyo cliente no tiene RUC.
SELECT v.id_venta
  FROM venta v
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN tipo_comprobante tc ON tc.id_tipo_comprobante = s.id_tipo_comprobante
  LEFT JOIN cliente cl     ON cl.id_cliente = v.id_cliente
 WHERE tc.letra_serie = 'F' AND cl.ruc IS NULL;

-- 27. Series con huecos o correlativos repetidos, o cuyo ultimo_numero no coincide con la ultima venta.
SELECT s.id_serie, s.serie
  FROM serie_comprobante s
  LEFT JOIN venta v ON v.id_serie = s.id_serie
 GROUP BY s.id_serie, s.serie, s.ultimo_numero
HAVING COUNT(v.id_venta) <> s.ultimo_numero
    OR COALESCE(MAX(v.numero_correlativo), 0) <> s.ultimo_numero;

-- 28. Stock que no coincide con el ultimo saldo del kardex.
SELECT s.id_tienda, s.id_producto, s.cantidad_actual, m.stock_nuevo
  FROM stock s
  JOIN movimiento_stock m ON m.id_movimiento = (SELECT MAX(x.id_movimiento) FROM movimiento_stock x
                                                 WHERE x.id_tienda = s.id_tienda AND x.id_producto = s.id_producto)
 WHERE ROUND(s.cantidad_actual - m.stock_nuevo, 3) <> 0;

-- 29. Ventas hechas por un empleado que no es cajero, o en una tienda distinta a la de su caja.
SELECT v.id_venta
  FROM venta v
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN caja_turno ct       ON ct.id_caja_turno = v.id_caja_turno
  JOIN caja c              ON c.id_caja = ct.id_caja
  JOIN empleado e          ON e.id_empleado = ct.id_empleado
 WHERE e.id_rol <> 2 OR c.id_tienda <> s.id_tienda OR e.id_tienda <> c.id_tienda;

-- 30. Ventas fuera del horario de su turno de caja.
SELECT v.id_venta
  FROM venta v
  JOIN caja_turno ct ON ct.id_caja_turno = v.id_caja_turno
 WHERE v.fecha_hora < ct.fecha_hora_apertura
    OR (ct.fecha_hora_cierre IS NOT NULL AND v.fecha_hora > ct.fecha_hora_cierre);

-- 31. Anulaciones incoherentes: venta ANULADA sin solicitud APROBADA (o al reves), solicitud de un empleado que no es el cajero de la venta,
--     o resuelta por alguien que no es administrador de la tienda de la venta.
SELECT v.id_venta
  FROM venta v
  LEFT JOIN solicitud_anulacion sa ON sa.id_venta = v.id_venta AND sa.estado = 'APROBADA'
 WHERE (v.estado = 'ANULADA') <> (sa.id_solicitud IS NOT NULL)
UNION
SELECT sa.id_venta
  FROM solicitud_anulacion sa
  JOIN venta v             ON v.id_venta = sa.id_venta
  JOIN caja_turno ct       ON ct.id_caja_turno = v.id_caja_turno
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  LEFT JOIN empleado e     ON e.id_empleado = sa.id_empleado_resuelve
 WHERE sa.id_empleado_solicita <> ct.id_empleado
    OR (sa.id_empleado_resuelve IS NOT NULL AND (e.id_rol <> 1 OR e.id_tienda <> s.id_tienda));

-- 32. Pagos sin el detalle de su metodo (efectivo, tarjeta o Sip), o con mas de uno.
SELECT pv.id_pago
  FROM pago_venta pv
 WHERE (SELECT COUNT(*) FROM efectivo e WHERE e.id_pago = pv.id_pago)
     + (SELECT COUNT(*) FROM tarjeta t WHERE t.id_pago = pv.id_pago)
     + (SELECT COUNT(*) FROM billetera_digital_sip b WHERE b.id_pago = pv.id_pago) <> 1
    OR (pv.id_metodo_pago = 1 AND NOT EXISTS (SELECT 1 FROM efectivo e WHERE e.id_pago = pv.id_pago))
    OR (pv.id_metodo_pago = 2 AND NOT EXISTS (SELECT 1 FROM tarjeta t WHERE t.id_pago = pv.id_pago))
    OR (pv.id_metodo_pago = 3 AND NOT EXISTS (SELECT 1 FROM billetera_digital_sip b WHERE b.id_pago = pv.id_pago));

-- 33. Productos con lote cuyo stock no es la suma de sus lotes.
SELECT s.id_tienda, s.id_producto, s.cantidad_actual, SUM(l.cantidad_actual) AS suma_lotes
  FROM stock s
  JOIN lote_producto l ON l.id_tienda = s.id_tienda AND l.id_producto = s.id_producto
 GROUP BY s.id_tienda, s.id_producto, s.cantidad_actual
HAVING ROUND(s.cantidad_actual - SUM(l.cantidad_actual), 3) <> 0;

-- 34. Movimientos de productos con lote que no indican lote, o que indican un lote de otro producto o tienda.
SELECT m.id_movimiento
  FROM movimiento_stock m
  LEFT JOIN lote_producto l ON l.id_lote = m.id_lote
 WHERE (m.id_lote IS NULL AND EXISTS (SELECT 1 FROM lote_producto x
                                       WHERE x.id_tienda = m.id_tienda AND x.id_producto = m.id_producto))
    OR (m.id_lote IS NOT NULL AND (l.id_tienda <> m.id_tienda OR l.id_producto <> m.id_producto));

-- 35. Ventas que sacaron stock de un lote ya vencido.
SELECT m.id_movimiento, m.id_venta, l.codigo_lote
  FROM movimiento_stock m
  JOIN lote_producto l ON l.id_lote = m.id_lote
 WHERE m.id_tipo_movimiento = 1 AND DATE(m.fecha_hora) > l.fecha_vencimiento;

-- 36. Devoluciones incoherentes: venta no emitida, monto distinto de su detalle, importe mal calculado
--     o mas cantidad devuelta que la vendida.
SELECT dv.id_devolucion
  FROM devolucion_venta dv
  JOIN venta v ON v.id_venta = dv.id_venta
 WHERE v.estado <> 'EMITIDA'
    OR ROUND(dv.monto_devuelto - (SELECT SUM(dd.importe) FROM detalle_devolucion dd
                                   WHERE dd.id_devolucion = dv.id_devolucion), 2) <> 0
    OR EXISTS (SELECT 1
                 FROM detalle_devolucion dd
                 JOIN detalle_venta d ON d.id_detalle_venta = dd.id_detalle_venta
                WHERE dd.id_devolucion = dv.id_devolucion
                  AND (d.id_venta <> dv.id_venta
                       OR dd.importe <> ROUND(dd.cantidad * d.precio_unitario, 2)
                       OR (SELECT SUM(x.cantidad) FROM detalle_devolucion x
                            WHERE x.id_detalle_venta = dd.id_detalle_venta) > d.cantidad));

-- 37. Movimientos de caja fuera del horario de su turno, o devoluciones de dinero de una venta de otra tienda.
SELECT mc.id_movimiento_caja
  FROM movimiento_caja mc
  JOIN caja_turno ct ON ct.id_caja_turno = mc.id_caja_turno
  JOIN caja c        ON c.id_caja = ct.id_caja
  LEFT JOIN venta v             ON v.id_venta = mc.id_venta
  LEFT JOIN serie_comprobante s ON s.id_serie = v.id_serie
 WHERE mc.fecha_hora < ct.fecha_hora_apertura
    OR (ct.fecha_hora_cierre IS NOT NULL AND mc.fecha_hora > ct.fecha_hora_cierre)
    OR (mc.id_venta IS NOT NULL AND s.id_tienda <> c.id_tienda);

-- 38. Turnos cerrados cuyo monto de cierre no es: inicial + efectivo cobrado + ingresos - retiros, gastos y devoluciones.
SELECT ct.id_caja_turno, ct.monto_cierre
  FROM caja_turno ct
 WHERE ct.estado = 'CERRADO'
   AND ROUND(ct.monto_cierre - (ct.monto_inicial
        + COALESCE((SELECT SUM(pv.monto_pagado)
                      FROM venta v
                      JOIN pago_venta pv ON pv.id_venta = v.id_venta AND pv.estado = 'VALIDO' AND pv.id_metodo_pago = 1
                     WHERE v.id_caja_turno = ct.id_caja_turno), 0)
        + COALESCE((SELECT SUM(CASE WHEN mc.tipo = 'INGRESO' THEN mc.monto ELSE -mc.monto END)
                      FROM movimiento_caja mc WHERE mc.id_caja_turno = ct.id_caja_turno), 0)), 2) <> 0;

-- 39. Ventas cuyo dinero devuelto de caja (movimientos DEVOLUCION) no es igual a lo registrado en sus devoluciones.
SELECT v.id_venta
  FROM venta v
 WHERE ROUND(COALESCE((SELECT SUM(mc.monto) FROM movimiento_caja mc
                        WHERE mc.id_venta = v.id_venta AND mc.tipo = 'DEVOLUCION'), 0)
           - COALESCE((SELECT SUM(dv.monto_devuelto) FROM devolucion_venta dv
                        WHERE dv.id_venta = v.id_venta), 0), 2) <> 0;


/* ---------------------------------------------------------------------
   H. MODULO DEL ADMINISTRADOR (solicitudes, turnos de trabajo y traslados)
   --------------------------------------------------------------------- */

-- 40. Bandeja del administrador: solicitudes de anulacion PENDIENTES de su tienda (cambiar el 2 por la tienda del administrador).
SELECT sa.id_solicitud, sa.fecha_hora_solicitud, CONCAT(s.serie, '-', LPAD(v.numero_correlativo, 8, '0')) AS comprobante,
       v.total, sa.motivo, CONCAT(e.nombres, ' ', e.apellidos) AS pedido_por
  FROM solicitud_anulacion sa
  JOIN venta v             ON v.id_venta = sa.id_venta
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN empleado e          ON e.id_empleado = sa.id_empleado_solicita
 WHERE sa.estado = 'PENDIENTE' AND s.id_tienda = 2
 ORDER BY sa.fecha_hora_solicitud;

-- 41. Rol de turnos programados: quien trabaja, que dia, en que horario y quien lo programo.
SELECT t.nombre_tienda, tt.fecha, CONCAT(e.nombres, ' ', e.apellidos) AS empleado, r.nombre_rol,
       tt.hora_inicio, tt.hora_fin, CONCAT(p.nombres, ' ', p.apellidos) AS programado_por
  FROM turno_trabajo tt
  JOIN empleado e ON e.id_empleado = tt.id_empleado
  JOIN rol r      ON r.id_rol = e.id_rol
  JOIN tienda t   ON t.id_tienda = e.id_tienda
  JOIN empleado p ON p.id_empleado = tt.id_empleado_programa
 ORDER BY t.nombre_tienda, tt.fecha, tt.hora_inicio, e.apellidos;

-- 42. Solicitudes de anulacion por estado y tienda (cuantas se aprobaron, rechazaron o siguen pendientes).
SELECT t.nombre_tienda, sa.estado, COUNT(*) AS solicitudes
  FROM solicitud_anulacion sa
  JOIN venta v             ON v.id_venta = sa.id_venta
  JOIN serie_comprobante s ON s.id_serie = v.id_serie
  JOIN tienda t            ON t.id_tienda = s.id_tienda
 GROUP BY t.nombre_tienda, sa.estado
 ORDER BY t.nombre_tienda, sa.estado;

-- 43. Turnos de trabajo incoherentes: programados por alguien que no es administrador de la tienda del empleado.
SELECT tt.id_turno_trabajo
  FROM turno_trabajo tt
  JOIN empleado e ON e.id_empleado = tt.id_empleado
  JOIN empleado p ON p.id_empleado = tt.id_empleado_programa
 WHERE p.id_rol <> 1 OR p.id_tienda <> e.id_tienda;

-- 44. Cajeros con turno de caja un dia en que no tenian turno de trabajo programado (o fuera de su horario).
SELECT ct.id_caja_turno
  FROM caja_turno ct
  LEFT JOIN turno_trabajo tt ON tt.id_empleado = ct.id_empleado AND tt.fecha = DATE(ct.fecha_hora_apertura)
 WHERE tt.id_turno_trabajo IS NULL
    OR TIME(ct.fecha_hora_apertura) < tt.hora_inicio
    OR (ct.fecha_hora_cierre IS NOT NULL AND TIME(ct.fecha_hora_cierre) > tt.hora_fin);

-- 45. Traslados incoherentes: no los registro un administrador de la tienda de origen.
SELECT tr.id_traslado
  FROM traslado_mercaderia tr
  JOIN empleado e ON e.id_empleado = tr.id_empleado
 WHERE e.id_rol <> 1 OR e.id_tienda <> tr.id_tienda_origen;
