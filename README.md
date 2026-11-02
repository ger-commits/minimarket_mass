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
├── models/        # Entidades (Empleado, Producto, Venta...) y repositorios de acceso a datos
├── views/         # Pantallas: auth/, productos/, stock/, ventas/
├── public/        # Único punto de entrada web (index.php) + css/, js/, img/
├── database/      # Scripts SQL
└── docs/          # Diagrama ER y diseño de la base de datos
```

## Base de datos

La base tiene **26 tablas**. Ejecuta los scripts de `database/` en este orden:

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
3. Revisa los datos de conexión en `models/Database.php` (por defecto `root` sin contraseña en `localhost`).
4. Inicia el servidor desde la raíz del proyecto:
   ```bash
   php -S localhost:8000 -t public
   ```
5. Abre `http://localhost:8000`.

## Rutas disponibles

| Ruta | Qué muestra |
|------|-------------|
| `index.php?r=login` | Inicio de sesión con el DNI del empleado |
| `index.php?r=productos` | Lista de productos |
| `index.php?r=stock&tienda=1` | Stock de una tienda |
| `index.php?r=boleta&serie=B001&n=1` | Boleta de una venta |

Para probar el login usa un DNI de `datos.sql`, por ejemplo `70000001`.

## Roles

Administrador, Cajero y Almacenero.

## Autor

ger-commits
