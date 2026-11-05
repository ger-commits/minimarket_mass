# Instalación de Minimarket MASS en Laragon

## 1. Copiar el proyecto

1. Descomprime `minimarket_mass` dentro de `C:\laragon\www\`.
2. Inicia **Apache** y **MySQL** desde Laragon.
3. Comprueba que exista `C:\laragon\www\minimarket_mass\public\index.php`.

## 2. Preparar MySQL

Abre HeidiSQL o phpMyAdmin desde Laragon.

### Si vas a crear la base desde cero

Ejecuta, en este orden:

1. `database/schema.sql`
2. `database/datos.sql`
3. `database/consultas.sql` (opcional)
4. `database/boleta.sql` (opcional)

La versión actual de `schema.sql` ya incluye `ingreso_mercaderia`, el tipo de movimiento 7 y la relación de ingresos en `movimiento_stock`. No ejecutes la migración sobre una base recién creada con este esquema.

### Si ya tienes la base anterior y no quieres borrarla

No ejecutes `schema.sql`, porque ese archivo recrea la base y elimina la anterior. Ejecuta **una sola vez** `database/migracion_001_ingresos_almacen.sql` sobre `minimarket_mass`. Esta migración agrega el registro de ingresos y el vínculo al Kardex sin borrar tus datos.

## 3. Configurar la conexión

1. Copia `.env.example` y ponle el nombre `.env`.
2. Abre `.env` y configura `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` según tu Laragon.
3. En la instalación típica de Laragon, MySQL usa `127.0.0.1`, puerto `3306`, usuario `root` y contraseña vacía.

## 4. Abrir el sistema

Con Apache iniciado, abre:

`http://localhost/minimarket_mass/public/`

Ingresa con una cuenta cuyo rol sea **ALMACENERO**. El acceso de almacén se abre desde `index.php?r=stock`; el archivo `almacenamiento__.html` redirige a esa pantalla protegida.

## 5. Qué se conecta a MySQL en Almacenamiento

- **Ingresos:** crea el registro de ingreso, suma existencias, actualiza/crea el lote si se indicó y registra el movimiento tipo 7 en el Kardex.
- **Mermas:** valida el stock, descuenta existencias, consume lotes por fecha de vencimiento y registra el tipo de movimiento 5.
- **Lotes:** consulta los lotes reales de la tienda, sus fechas y cantidades. La baja desde una alerta afecta exactamente al lote elegido.
- **Kardex:** muestra movimientos almacenados en `movimiento_stock`, con su saldo y origen.
- **Stock mínimo:** los cambios se guardan en la tabla `stock`.

Todas las operaciones de almacén se limitan a la tienda y al empleado de la sesión. Las peticiones de escritura validan el token CSRF.

## 6. Prueba recomendada

1. Entra como almacenero.
2. Abre **Stock** y apunta el stock actual de un producto de prueba.
3. Registra un ingreso pequeño y vuelve a Stock: debe aumentar.
4. Revisa **Kardex**: debe aparecer el ingreso con su saldo nuevo.
5. Registra una merma de una unidad (o una fracción si el producto se vende por peso): el stock debe bajar.
6. Revisa **Mermas**, **Lotes** y **Kardex**.

> Importante: no se ha podido probar esta copia contra tu instancia local de MySQL. La sintaxis PHP/JavaScript se valida en el paquete, pero debes ejecutar la instalación y las pruebas anteriores en tu Laragon.
