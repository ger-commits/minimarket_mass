# Minimarket Mass

Sistema de ventas e inventario para **Tienda Mass** (grupo Intercorp), con 3 tiendas en Arequipa (Cayma, Cerro Colorado y Yanahuara). Proyecto académico desarrollado en PHP con arquitectura MVC y base de datos MySQL.

## Tecnologías

- PHP 8 (PDO) con patrón MVC
- MySQL 8.0.16 o superior (necesario para que se apliquen los `CHECK`)
- Servidor local: XAMPP, Laragon o el servidor integrado de PHP

## Estructura del proyecto

```
minimarket_mass/
├── controllers/   # Lógica de cada pantalla (Auth, Producto, Stock, Venta)
├── models/           # Un modelo por tabla (31 tablas) y repositorios de acceso a datos
│   ├── Database.php             # Conexión PDO
│   ├── Empleado.php             # Clase base de los roles
│   ├── Administrador.php / Cajero.php / Almacenero.php   # Roles y sus permisos
│   ├── EmpleadoRepository.php   # Autenticación y consulta de empleados
│   ├── ProductoRepository.php / StockRepository.php / VentaRepository.php
│   └── Distrito, Tienda, Rol, Cliente, Categoria, Marca, UnidadMedida, Producto,
│       TipoComprobante, SerieComprobante, Venta, DetalleVenta, SolicitudAnulacion,
│       DevolucionVenta, DetalleDevolucion, MetodoPago, PagoVenta, Efectivo, Tarjeta,
│       BilleteraDigitalSip, Stock, LoteProducto, TipoMovimiento, MovimientoStock,
│       TrasladoMercaderia, Merma, Caja, CajaTurno, MovimientoCaja, TurnoTrabajo (.php)
├── views/         # Pantallas: auth/, productos/, stock/, ventas/
├── public/        # Único punto de entrada web (index.php) + css/, js/, img/
├── storage/       # Archivos de trabajo (intentos fallidos de login); no se sube a GitHub
├── database/      # Scripts SQL
└── docs/          # Diagrama ER y diseño de la base de datos
```

## Base de datos

La base tiene **30 tablas**. Ejecuta los scripts de `database/` en este orden:

1. `schema.sql`: crea la base y las tablas (**borra** `minimarket_mass` si ya existía)
2. `datos.sql`: datos de prueba
3. `consultas.sql`: consultas de ejemplo
4. `boleta.sql`: vista `vw_comprobante` y procedimiento `sp_boleta`

El diseño completo está en [`docs/diseno_bd.md`](docs/diseno_bd.md) y el diagrama en [`docs/diagrama_er.png`](docs/diagrama_er.png).

## Cómo ejecutarlo

1. Clona el repositorio:
   ```bash
   git clone https://github.com/ger-commits/minimarket_mass.git
   cd minimarket_mass
   ```
2. Importa los scripts de `database/` en MySQL (orden de arriba).
3. Copia `.env.example` como `.env` y completa tus datos: la conexión a MySQL (por defecto `root` sin contraseña en `localhost`) y `CAJA_ID`, el `id_caja` fijo de esa terminal (en `datos.sql`: 1 y 2 = Cayma, 3 y 4 = Cerro Colorado, 5 y 6 = Yanahuara). Sin `.env` se usan los valores por defecto de la conexión, pero el login de cajero no funciona hasta definir `CAJA_ID`.
4. Inicia el servidor desde la raíz del proyecto (la carpeta `storage/` debe poder escribirse):
   ```bash
   php -S localhost:8000 -t public
   ```
5. Abre `http://localhost:8000`.

## Rutas disponibles

Todas las rutas, salvo el login, exigen haber ingresado; sin sesión llevan al login.

| Ruta | Qué muestra |
|------|-------------|
| `index.php?r=login` | Ingreso del cajero con el DNI y la contraseña del empleado, en la caja fija de la terminal |
| `index.php?r=productos` | Lista de productos |
| `index.php?r=stock` | Stock de la tienda del empleado que ingresó (la tienda sale de la sesión, no de la URL) |
| `index.php?r=boleta&serie=B001&n=1` | Boleta de una venta, solo si es de la tienda del empleado |
| `index.php?r=logout` | Cierra la sesión (se envía por POST con el botón «Salir») |

## Login del cajero

- Pide DNI y contraseña. Los empleados de prueba están en `datos.sql` (por ejemplo, el DNI `70000002` es un cajero de Cayma); sus contraseñas se guardan como hash bcrypt, nunca en texto.
- Solo entran empleados con rol **Cajero**, y solo en una caja **ACTIVA** de **su tienda** (regla: el cajero vende en una caja de su tienda).
- La pantalla muestra el número de la caja, si está activa y si tiene un turno abierto.
- Seguridad: token CSRF en los formularios, id de sesión nuevo al ingresar, cookie `HttpOnly` y `SameSite=Lax` (y `Secure` si se usa HTTPS), la sesión solo guarda el id del empleado (en cada petición se vuelve a leer en la base, así un empleado dado de baja pierde el acceso) y mensajes de error iguales para DNI inexistente y clave incorrecta.
- Límite de intentos: 5 fallos con el mismo DNI desde la misma IP, o 20 fallos desde una misma IP, bloquean 15 minutos. Se guarda en `storage/intentos/` (no en la base de datos). Se usa `REMOTE_ADDR`; si el servidor está detrás de un proxy, todos llegarían con la IP del proxy.
- Los errores técnicos no se muestran en el navegador: van al log de errores de PHP.

### Estructura de `public/` para los logins

Cada login tiene su propia carpeta dentro de `css/`, `js/` y `img/`; lo que usan varios va en `compartido/` o `vendor/`.

```
public/
├── index.php
├── css/
│   ├── mass.css                 pantallas internas (productos, stock, boleta) y página de error
│   └── login/
│       ├── caja.css
│       ├── administrador.css    (por crear)
│       └── almacenero.css       (por crear)
├── js/
│   ├── vendor/                  librerías de terceros (three.min.js, una sola copia)
│   └── login/
│       ├── caja/                login-teclado.js, login-efectos.js
│       ├── administrador/       (por crear)
│       ├── almacenero/          (por crear)
│       └── compartido/          bolsa3d.js (y todo lo que use más de un login)
├── fonts/                       fuentes locales compartidas
└── img/
    └── login/
        ├── caja/
        ├── administrador/
        └── almacenero/
```

Reglas: cada login carga solo sus archivos; lo que se repite se mueve a `compartido/` o `vendor/` en vez de copiarse; un diseño nuevo no modifica los existentes.

### Archivos de la pantalla de login

| Archivo | Qué contiene |
|---------|--------------|
| `views/auth/login.php` | El HTML, con el número, el estado de la caja, el token CSRF y los errores que manda PHP |
| `public/css/login/caja.css` | Todos los estilos del login de caja |
| `public/js/login/caja/login-teclado.js` | Teclado numérico del DNI, validación, mostrar contraseña, reloj y «Ingresando…» |
| `public/js/login/caja/login-efectos.js` | Detalles visuales (entrada escalonada, contador del DNI, onda del teclado) |
| `public/js/login/compartido/bolsa3d.js` | Bolsa 3D con Three.js r128; si Three.js no carga, simplemente no se dibuja |

Three.js y las fuentes (Plus Jakarta Sans y Poppins) se cargan desde internet (cdnjs y Google Fonts). Para no depender de internet, guarda `three.min.js` en `public/js/vendor/` (una sola copia para todos los logins) y cambia la ruta en `views/auth/login.php`. El formulario se envía con un POST normal a `index.php?r=login`: ninguna validación de seguridad está en JavaScript, todo lo decide PHP.

## Roles

Administrador, Cajero y Almacenero.

## Autor

ger-commits
