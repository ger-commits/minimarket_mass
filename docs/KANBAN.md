# Kanban de Minimarket MASS

Tablero del sistema completo (caja, almacén, administrador, base de datos y escáner de celular), con el estado según el código de la versión más reciente del proyecto.

**Leyenda de columnas**

- **Por hacer**: no iniciado o no existe en el código.
- **En progreso**: hay parte implementada (modelo, tablas o pantalla) pero falta algo para que funcione de punta a punta.
- **Hecho**: implementado en el código y en la base de datos.

Cada tarea indica su área y, entre paréntesis, el archivo donde se ve la evidencia.

---

## Por hacer

### Seguridad

- **SEG-1** Límite de intentos de login por DNI e IP. El README lo describe (5 fallos por DNI, 20 por IP, bloqueo de 15 min en `storage/intentos/`), pero no hay código que lo haga. (`controllers/AuthController.php`)
- **SEG-2** Revisar que `.env` no salga del repositorio y, si las credenciales se compartieron, cambiarlas. El zip de trabajo contiene `.env` (`.gitignore` lo excluye). Confirmar también que `schema.sql` no tenga contraseñas reales.
- **SEG-3** Servir Three.js y las fuentes desde `public/` en vez de cdnjs y Google Fonts, como pide el README (`public/js/vendor/` aún no existe). (`views/auth/login.php`, `views/stock/app.php`)

### Repositorio y configuración

- **REP-1** Sincronizar `main` con la versión de trabajo. Faltan en el repo unos 30 archivos: controladores de escáner, caja y turnos; migraciones 002 a 006; modelos de escáner, ingresos, mermas, reportes, traslados y sesión; vistas de escáner y de login logística.
- **REP-2** Ajustar `.gitignore` para `platform-tools/` (binarios de adb para Windows) y `storage/`. Ninguno de los dos está ignorado.
- **REP-3** Quitar `almacenamiento__.html` de la raíz del repo, o moverlo a `public/` como dice el README.

### Base de datos

- **BD-1** `schema.sql` empieza con `DROP DATABASE IF EXISTS minimarket_mass`. Agregar una advertencia destacada o un script que no borre nada. (`database/schema.sql`, línea 19)
- **BD-2** Quitar valores fijos en las consultas. La consulta 40 de `consultas.sql` usa `id_tienda = 2` y hay que cambiarlo a mano por cada tienda. Convertirla en consulta parametrizada o en vista.
- **BD-3** Resolver los pendientes con el profesor (`docs/diseno_bd.md`, sección 8): qué consultas concretas debe tener `consultas.sql` y si la boleta debe ser consulta, vista o procedimiento. Hoy hay una vista y un procedimiento.
- **BD-4** Unificar el número de tablas. El README dice 30 y el diseño dice 31 (`database/schema.sql`).

### Documentación

- **DOC-1** Corregir el README. Menciona archivos que ya no están (`public/panel_administrador_mass.html`, `public/login_almacenero.html`) y marca como «por crear» los CSS y JS de los logins de administrador y almacenero, que sí existen.
- **DOC-2** Probar la instalación completa en Laragon. `INSTALACION_LARAGON.md` aclara que la copia no se probó contra una instancia real de MySQL.
- **DOC-3** Documentar las rutas nuevas en el README: `admin_api_escaneos`, `caja_api_escaner_*`, `escaner_*`, `stock_notificaciones`. Hoy no aparecen en la tabla de rutas.

### Calidad

- **QA-1** No hay pruebas automatizadas. Empezar por los repositorios de ventas (`VentaRepository`) y de stock (`StockRepository`): cobro mixto, anulación y descuento de stock.

### Mejoras funcionales

- **MEJ-1** Traslados con estado «en tránsito» y botón de recepción en la tienda destino. Hoy el traslado descuenta y suma stock de inmediato (README, sección de actualización de almacén).

---

## En progreso

- **DEV-1** Devoluciones de venta. Existen la tabla `devolucion_venta`, `detalle_devolucion` y el modelo `DevolucionVenta.php`. En la caja solo aparece el tipo «Devolución» en `pos.js`. Falta el controlador, el endpoint en `public/index.php`, la interfaz de caja y la vuelta del stock.
- **BD-5** Consultas y boleta: el diseño está completo, pero falta cerrar las preguntas del profesor (ver BD-3).
- **DOC-4** Alinear `diseno_bd.md` con `schema.sql` y las migraciones (las migraciones 002 a 006 no aparecen en el diseño).

---

## Hecho

### Base de datos

- Esquema con 31 tablas, claves foráneas y restricciones `CHECK` (`database/schema.sql`).
- Datos de prueba para 3 tiendas, empleados, productos, ventas, anulaciones y pagos mixtos (`database/datos.sql`).
- Vista `vw_comprobante` y procedimiento `sp_boleta` (`database/boleta.sql`).
- Migración 001: ingresos de almacén (`database/migracion_001_ingresos_almacen.sql`).
- Migración 002: escáner de celular del almacén (`database/migracion_002_escaner.sql`).
- Migración 003: escaneos pendientes hasta que el administrador los atiende (`database/migracion_003_escaner_procesado.sql`).
- Migración 004: notificaciones para el almacén, como «producto eliminado» (`database/migracion_004_eliminar_producto.sql`).
- Migración 005: cuadre de caja guardado al cerrar el turno (`database/migracion_005_cuadre_caja.sql`).
- Migración 006: escáner de celular del cajero por USB, guardado en base de datos (`database/migracion_006_escaner_caja.sql`).

### Acceso y seguridad

- Login de cajero con DNI y contraseña en la caja fija de la terminal, con contraseña cifrada con bcrypt (`controllers/AuthController.php`, `models/EmpleadoRepository.php`).
- Login de almacenero y de administrador (`views/auth/login_logistica.php`, `views/auth/login_administrador.php`).
- Selección de perfil (`views/auth/seleccion.php`).
- Token CSRF en formularios y en los endpoints que cambian datos (caja, almacén, administrador).
- Id de sesión nuevo al ingresar y verificación del empleado en cada petición (`models/Sesion.php`).

### Caja (POS)

- Abrir y cerrar turno de caja (`controllers/CajaController.php`, `controllers/TurnoController.php`).
- Buscar productos y registrar ventas (`caja_api_productos`, `caja_api_vender`).
- Cobro con varios métodos: efectivo, tarjeta y billetera digital Sip (`models/PagoVenta.php`).
- Boleta y factura con serie y correlativo por tienda (`models/SerieComprobante.php`).
- Solicitud de anulación que aprueba o rechaza el administrador (`models/SolicitudAnulacion.php`).
- Movimientos de caja: retiros, gastos, ingresos de sencillo (`models/MovimientoCaja.php`).
- Cuadre de caja al cerrar el turno (`views/caja/pos.php`).
- Ver la boleta de una venta (`index.php?r=boleta`, `views/ventas/boleta.php`).
- Escáner de celular del cajero por USB (`controllers/EscanerCajaController.php`, `public/js/caja/qr.js`, `conectar_celular_usb.bat`).

### Almacén (stock)

- Pantalla de stock por tienda, con la tienda tomada de la sesión (`controllers/StockController.php`, `views/stock/app.php`).
- Ingresos de mercadería, que actualizan stock y registran movimiento en una transacción (`models/IngresoRepository.php`).
- Mermas, también por lote (`models/MermaRepository.php`).
- Lotes con fecha de vencimiento (`models/LoteProducto.php`).
- Stock mínimo y alertas (`StockController`, acción `stock_minimo`).
- Kardex de movimientos (`models/MovimientoStock.php`).
- Escáner de celular del almacén: vincular, enviar y ver pendientes (`controllers/EscanerController.php`).
- Avisos para el almacén (`controllers/NotificacionController.php`).

### Administrador

- Panel con anulaciones pendientes, aprobar o rechazar (`controllers/AdminController.php`).
- Programación de turnos de trabajo de la tienda (`models/TurnoTrabajoRepository.php`).
- Traslados de mercadería entre tiendas (`models/TrasladoRepository.php`).
- Historial de mermas y reportes filtrados por tienda (`models/ReporteRepository.php`).
- Cancelar turnos programados (`index.php?r=admin_turno_cancelar`).
- Escaneos del almacén atendidos desde el panel (`controllers/EscaneoAdminController.php`).

### Productos

- Lista de productos (`controllers/ProductoController.php`, `views/productos/lista.php`).
- Eliminar producto, con aviso al almacén (`database/migracion_004_eliminar_producto.sql`).
