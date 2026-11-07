# Kanban · Minimarket Mass

Tablero de seguimiento del proyecto. Mover una tarea entre columnas cambiando su sección.

## 📋 Por hacer

- [ ] Revisar y ajustar el flujo de login de almacenero (`public/login_almacenero.html`)
- [ ] Completar el panel del administrador (`public/panel_administrador_mass.html`)
- [ ] Implementar el módulo de caja y turnos (`CajaTurno`, `MovimientoCaja`, `TurnoTrabajo`)
- [ ] Implementar devoluciones y anulaciones de venta (`SolicitudAnulacion`, `DevolucionVenta`)
- [ ] Documentar los procedimientos de traslado de mercadería y mermas

## 🚧 En progreso

- [ ] Pantallas de ventas y boleta (`sp_boleta`, `vw_comprobante`)

## ✅ Hecho

- [x] Diseño de la base de datos y diagrama ER (`docs/diseno_bd.md`)
- [x] Scripts SQL de esquema, datos de prueba y consultas (`database/`)
- [x] Autenticación de empleados con PDO (`EmpleadoRepository`)
- [x] Gestión de stock y lotes (`StockRepository`, `LoteProducto`)
