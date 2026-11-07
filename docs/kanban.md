# Kanban · Minimarket Mass

Tablero de seguimiento del proyecto. Mover una tarea entre columnas cambiando su sección.

## 📋 Por hacer

- [ ] Traslados con estado «en tránsito» y botón de recepción en la tienda destino (hoy el traslado es inmediato)
- [ ] Cargar `three.min.js` en `public/js/vendor/` para no depender de cdnjs (ver README, sección de logins)
- [ ] Revisar el límite de intentos de login detrás de proxy (se usa `REMOTE_ADDR`)
- [ ] Verificar que `.env` no se suba al repositorio y que solo quede `.env.example`
- [ ] Probar el escáner de almacén por celular con túnel https (`iniciar_tunel_almacen.bat`)

## 🚧 En progreso

_Sin tareas por ahora._

## ✅ Hecho

- [x] Diseño de la base de datos y diagrama ER (`docs/diseno_bd.md`)
- [x] Scripts SQL de esquema, datos de prueba y consultas (`database/`)
- [x] Autenticación por rol: administrador, cajero y almacenero (`AuthController`, `EmpleadoRepository`)
- [x] Ingresos de almacén, mermas, lotes y Kardex (`migracion_001_ingresos_almacen.sql`)
- [x] Escáner de almacén por celular sin cable (`LEEME_ESCANER_ALMACEN.md`)
- [x] Escáner de caja (`migracion_002_escaner.sql`, `migracion_006_escaner_caja.sql`)
- [x] Anulación de ventas (`anulacion.sql`) y boleta con `sp_boleta` (`boleta.sql`)
- [x] Cuadre de caja y turnos de trabajo (`migracion_005_cuadre_caja.sql`, `TurnoController`)
- [x] Producto nuevo y traslado en dos pasos (`migracion_007_producto_nuevo_y_traslado_2_pasos.sql`)
- [x] Descontinuar producto por tienda y anular envío (`migracion_008_descontinuar_por_tienda_y_anular_envio.sql`)
- [x] Reportes de mermas y ventas filtrados por tienda para el administrador
- [x] Eliminación de productos (`migracion_004_eliminar_producto.sql`)
