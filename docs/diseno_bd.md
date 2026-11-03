# Minimarket Mass: documento de diseno de la base de datos

> **Fuente unica del proyecto.** De este documento salen `database/schema.sql` y el `README`.
> Nada se crea en el schema, en los datos ni en las consultas que no este descrito aqui.
> Base de datos: `minimarket_mass` · Motor: MySQL 8.0.16 o superior · Juego de caracteres: `utf8mb4`.

## 1. El sistema

**Nombre:** Minimarket Mass (Tienda Mass, grupo Intercorp), 3 tiendas en Arequipa: Cayma, Cerro Colorado y Yanahuara.

**Descripcion.** Es la base de datos que respalda la operacion diaria de un supermercado que atiende solo de forma presencial, con **hasta 3 sucursales** (hoy las tres de Arequipa):

- el **catalogo** de productos (categoria, marca, unidad de medida y precio unico para todas las tiendas);
- el **stock de cada producto en cada tienda**, con su historial (kardex), los traslados de mercaderia entre tiendas y las mermas;
- la **venta en caja**: turnos de caja, boletas y facturas con serie y correlativo por tienda, detalle de productos y solicitudes de anulacion que autoriza el administrador;
- el **cobro**: una venta puede pagarse con varios metodos (efectivo, tarjeta y billetera digital Sip);
- las **devoluciones** de productos de una venta, que regresan al stock y sacan el dinero de la caja;
- los **movimientos de caja** del turno (ingresos de sencillo, retiros de seguridad, gastos y devoluciones de dinero), para cuadrar el efectivo;
- los **lotes con fecha de vencimiento** de los productos perecibles (leche, yogurt), para vender primero lo que vence antes;
- el **modulo del administrador**: autoriza o rechaza las anulaciones que pide el cajero, programa los turnos de trabajo de su tienda y registra los traslados de mercaderia;
- los **empleados** (administrador, cajero y almacenero) y los **clientes** (solo DNI y RUC).

**Fuera de alcance:** compras a proveedores (por eso no existe la entidad proveedor), guias de remision, facturacion electronica, puntos y promociones. No hay un nombre de usuario aparte: el empleado ingresa al sistema con su DNI y su contrasena, que se guarda cifrada.

**Tamano del modelo:** 31 tablas, 46 relaciones (46 claves foraneas), 163 columnas.

## 2. Archivos del repositorio y orden de ejecucion

| Orden | Archivo | Para que sirve |
|---|---|---|
| 1 | `docs/diseno_bd.md` | Este documento. |
| 2 | `database/schema.sql` | Borra y crea la base `minimarket_mass` con todas las tablas. Sin datos. |
| 3 | `database/datos.sql` | Datos de ejemplo (ficticios) que respetan las reglas de negocio. |
| 4 | `database/consultas.sql` | Consultas del negocio con `JOIN` y consultas de control de reglas. |
| 5 | `database/boleta.sql` | Vista y procedimiento que muestran una boleta o factura a partir de su serie y correlativo. |

Ejecucion: `schema.sql` → `datos.sql` → `consultas.sql` → `boleta.sql`.

## 3. Convenciones de tipos

| Dato | Tipo | Por que |
|---|---|---|
| Identificadores (llaves primarias) | `TINYINT`, `SMALLINT`, `MEDIUMINT`, `INT` o `BIGINT`, siempre `UNSIGNED` y `AUTO_INCREMENT` | Se elige el entero mas chico que cubre el volumen esperado (3 distritos caben en `SMALLINT`; las ventas, que crecen sin parar, usan `BIGINT`). |
| Llaves foraneas | **El mismo tipo** que la llave primaria a la que apuntan | MySQL lo exige para crear la relacion. |
| Dinero | `DECIMAL(10,2)` | Exacto, sin errores de redondeo como `FLOAT`. |
| Kilos y cantidades | `DECIMAL(10,3)` | Los productos por peso se venden con gramos (0.800 kg). |
| Contrasena del empleado | `VARCHAR(255)` | Guarda el hash (bcrypt o argon2), no la contrasena. 255 deja espacio al algoritmo que se use. |
| DNI y RUC | `CHAR(8)` y `CHAR(11)` | Longitud fija, solo digitos (lo verifica un `CHECK`). Son texto: pueden empezar con cero y no se suman. |
| Fecha y hora | `DATETIME` | Fecha y hora de la operacion. |
| Fecha de vencimiento | `DATE` | Solo importa el dia, no la hora. |
| Estados y motivos | `VARCHAR` con `CHECK (... IN (...))` | Legible y facil de cambiar. |
| Motor de tablas | `InnoDB` | Es el que soporta claves foraneas y transacciones. |

**Opcional** significa que la columna acepta `NULL`. Si no se indica, la columna es `NOT NULL`.

## 4. Las tablas

Las tablas estan en el **mismo orden en que se crean** en `schema.sql`: primero las independientes y al final las que tienen claves foraneas.

### Nivel 1: Tablas independientes (no tienen claves foraneas)

#### `distrito`

**Para que sirve:** Distritos de Arequipa donde hay tiendas (Cayma, Cerro Colorado, Yanahuara).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_distrito` | `SMALLINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del distrito. |
| `nombre_distrito` | `VARCHAR(40)` | No |  | Nombre del distrito (Cayma, Cerro Colorado, Yanahuara). |

**Clave primaria:** `id_distrito`.

**Valores unicos:** `nombre_distrito`.

#### `rol`

**Para que sirve:** Roles de los empleados: ADMINISTRADOR, CAJERO, ALMACENERO.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_rol` | `TINYINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del rol. |
| `nombre_rol` | `VARCHAR(20)` | No |  | ADMINISTRADOR, CAJERO o ALMACENERO. |

**Clave primaria:** `id_rol`.

**Valores unicos:** `nombre_rol`.

#### `cliente`

**Para que sirve:** Clientes: solo DNI y/o RUC. Se pide uno de los dos; la factura exige RUC.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_cliente` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador interno del cliente. |
| `dni` | `CHAR(8)` | Si |  | DNI de 8 digitos. Opcional: el DNI es ocasional, no siempre se pide. |
| `ruc` | `CHAR(11)` | Si |  | RUC de 11 digitos. Opcional: solo lo tienen las empresas o personas con negocio. |

**Clave primaria:** `id_cliente`.

**Valores unicos:** `dni`; `ruc`.

**Reglas de la tabla (`CHECK`):**

- `dni IS NULL OR dni REGEXP '^[0-9]{8}$'`
- `ruc IS NULL OR ruc REGEXP '^[0-9]{11}$'`
- `dni IS NOT NULL OR ruc IS NOT NULL`

#### `categoria`

**Para que sirve:** Categorias de producto (abarrotes, lacteos, bebidas...).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_categoria` | `SMALLINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la categoria. |
| `nombre_categoria` | `VARCHAR(50)` | No |  | Nombre de la categoria. |

**Clave primaria:** `id_categoria`.

**Valores unicos:** `nombre_categoria`.

#### `marca`

**Para que sirve:** Marcas de los productos.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_marca` | `SMALLINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la marca. |
| `nombre_marca` | `VARCHAR(50)` | No |  | Nombre de la marca. |
| `es_marca_propia` | `CHAR(1)` | No |  | S si es marca propia de la tienda, N si no. Por defecto: `'N'`. |

**Clave primaria:** `id_marca`.

**Valores unicos:** `nombre_marca`.

**Reglas de la tabla (`CHECK`):**

- `es_marca_propia IN ('S','N')`

#### `unidad_medida`

**Para que sirve:** Unidades de medida: unidad, kilogramo, litro.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_unidad` | `TINYINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la unidad. |
| `abreviatura` | `VARCHAR(5)` | No |  | Abreviatura que se imprime (und, kg, lt). |
| `nombre_unidad` | `VARCHAR(30)` | No |  | Nombre completo de la unidad. |

**Clave primaria:** `id_unidad`.

**Valores unicos:** `abreviatura`; `nombre_unidad`.

#### `tipo_comprobante`

**Para que sirve:** Tipos de comprobante: BOLETA (serie B) y FACTURA (serie F).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_tipo_comprobante` | `TINYINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del tipo. |
| `nombre_comprobante` | `VARCHAR(20)` | No |  | BOLETA o FACTURA. |
| `letra_serie` | `CHAR(1)` | No |  | Letra con que empieza la serie: B (boleta) o F (factura). |

**Clave primaria:** `id_tipo_comprobante`.

**Valores unicos:** `nombre_comprobante`; `letra_serie`.

**Reglas de la tabla (`CHECK`):**

- `letra_serie IN ('B','F')`

#### `metodo_pago`

**Para que sirve:** Metodos de pago aceptados: EFECTIVO, TARJETA, BILLETERA DIGITAL SIP.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_metodo_pago` | `TINYINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del metodo. |
| `nombre_metodo` | `VARCHAR(30)` | No |  | EFECTIVO, TARJETA o BILLETERA DIGITAL SIP. |

**Clave primaria:** `id_metodo_pago`.

**Valores unicos:** `nombre_metodo`.

#### `tipo_movimiento`

**Para que sirve:** Tipos de movimiento del kardex y su efecto sobre el stock (E = entrada, S = salida).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_tipo_movimiento` | `TINYINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del tipo de movimiento. |
| `nombre_movimiento` | `VARCHAR(30)` | No |  | VENTA, ANULACION DE VENTA, TRASLADO DE SALIDA, TRASLADO DE ENTRADA o MERMA. |
| `efecto` | `CHAR(1)` | No |  | E si el movimiento suma stock (entrada), S si lo resta (salida). |

**Clave primaria:** `id_tipo_movimiento`.

**Valores unicos:** `nombre_movimiento`.

**Reglas de la tabla (`CHECK`):**

- `efecto IN ('E','S')`

### Nivel 2: Dependen solo de tablas del nivel 1

#### `tienda`

**Para que sirve:** Las 3 tiendas de Tienda Mass en Arequipa.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_tienda` | `SMALLINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la tienda. |
| `nombre_tienda` | `VARCHAR(60)` | No |  | Nombre de la tienda (unico). |
| `direccion` | `VARCHAR(120)` | No |  | Direccion de la tienda. |
| `telefono` | `VARCHAR(15)` | Si |  | Telefono. Opcional. |
| `estado` | `VARCHAR(10)` | No |  | ACTIVA o INACTIVA. Por defecto: `'ACTIVA'`. |
| `id_distrito` | `SMALLINT UNSIGNED` | No | FK → `distrito` | Distrito donde esta la tienda. |

**Clave primaria:** `id_tienda`.

**Claves foraneas:** `id_distrito` → `distrito(id_distrito)`.

**Valores unicos:** `nombre_tienda`.

**Reglas de la tabla (`CHECK`):**

- `estado IN ('ACTIVA','INACTIVA')`

#### `producto`

**Para que sirve:** Catalogo de productos. El precio es unico para todas las tiendas.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_producto` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del producto. |
| `nombre_producto` | `VARCHAR(100)` | No |  | Nombre completo con marca y presentacion. |
| `codigo_barras` | `VARCHAR(14)` | Si |  | Codigo de barras (8 a 14 digitos). Opcional: los productos al peso no lo tienen. |
| `precio_venta` | `DECIMAL(10,2)` | No |  | Precio de venta al publico; es unico para las 3 tiendas. |
| `se_vende_por_peso` | `CHAR(1)` | No |  | S si se vende por kilo (admite decimales), N si se vende por unidad entera. Por defecto: `'N'`. |
| `estado` | `VARCHAR(10)` | No |  | ACTIVO o DESCONTINUADO. Por defecto: `'ACTIVO'`. |
| `id_categoria` | `SMALLINT UNSIGNED` | No | FK → `categoria` | Categoria del producto. |
| `id_marca` | `SMALLINT UNSIGNED` | No | FK → `marca` | Marca del producto. |
| `id_unidad` | `TINYINT UNSIGNED` | No | FK → `unidad_medida` | Unidad en que se mide el producto. |

**Clave primaria:** `id_producto`.

**Claves foraneas:** `id_categoria` → `categoria(id_categoria)`; `id_marca` → `marca(id_marca)`; `id_unidad` → `unidad_medida(id_unidad)`.

**Valores unicos:** `codigo_barras`.

**Reglas de la tabla (`CHECK`):**

- `precio_venta > 0`
- `se_vende_por_peso IN ('S','N')`
- `estado IN ('ACTIVO','DESCONTINUADO')`
- `codigo_barras IS NULL OR codigo_barras REGEXP '^[0-9]{8,14}$'`

### Nivel 3: Dependen de tablas de los niveles 1 y 2

#### `empleado`

**Para que sirve:** Empleados: cada uno pertenece a una tienda, tiene un rol y entra al sistema con su DNI y su contrasena.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_empleado` | `MEDIUMINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del empleado. |
| `nombres` | `VARCHAR(60)` | No |  | Nombres. |
| `apellidos` | `VARCHAR(60)` | No |  | Apellidos. |
| `dni` | `CHAR(8)` | No |  | DNI de 8 digitos; es el usuario con el que ingresa al sistema, junto con su contrasena. Unico. |
| `contrasena` | `VARCHAR(255)` | No |  | Hash de la contrasena (bcrypt o argon2). Nunca se guarda la contrasena en texto; mide 60 caracteres o mas. |
| `estado` | `VARCHAR(10)` | No |  | ACTIVO o INACTIVO. Por defecto: `'ACTIVO'`. |
| `id_tienda` | `SMALLINT UNSIGNED` | No | FK → `tienda` | Tienda donde trabaja (una sola). |
| `id_rol` | `TINYINT UNSIGNED` | No | FK → `rol` | Rol del empleado (uno solo). |

**Clave primaria:** `id_empleado`.

**Claves foraneas:** `id_tienda` → `tienda(id_tienda)`; `id_rol` → `rol(id_rol)`.

**Valores unicos:** `dni`.

**Reglas de la tabla (`CHECK`):**

- `dni REGEXP '^[0-9]{8}$'`
- `CHAR_LENGTH(contrasena) >= 60`
- `estado IN ('ACTIVO','INACTIVO')`

#### `caja`

**Para que sirve:** Cajas registradoras de cada tienda.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_caja` | `SMALLINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la caja. |
| `numero_caja` | `TINYINT UNSIGNED` | No |  | Numero de la caja dentro de su tienda (1, 2...). |
| `estado` | `VARCHAR(10)` | No |  | ACTIVA o INACTIVA. Por defecto: `'ACTIVA'`. |
| `id_tienda` | `SMALLINT UNSIGNED` | No | FK → `tienda` | Tienda a la que pertenece la caja. |

**Clave primaria:** `id_caja`.

**Claves foraneas:** `id_tienda` → `tienda(id_tienda)`.

**Valores unicos:** `id_tienda, numero_caja`.

**Reglas de la tabla (`CHECK`):**

- `estado IN ('ACTIVA','INACTIVA')`

#### `serie_comprobante`

**Para que sirve:** Series de comprobantes por tienda (B001, F001...) y el ultimo correlativo emitido.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_serie` | `SMALLINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la serie. |
| `serie` | `CHAR(4)` | No |  | Serie de 4 caracteres: letra B o F y tres digitos (B001, F001). |
| `ultimo_numero` | `INT UNSIGNED` | No |  | Ultimo correlativo emitido con esta serie (0 si aun no se emitio ninguno). Por defecto: `0`. |
| `id_tienda` | `SMALLINT UNSIGNED` | No | FK → `tienda` | Tienda a la que pertenece la serie. |
| `id_tipo_comprobante` | `TINYINT UNSIGNED` | No | FK → `tipo_comprobante` | Boleta o factura. |

**Clave primaria:** `id_serie`.

**Claves foraneas:** `id_tienda` → `tienda(id_tienda)`; `id_tipo_comprobante` → `tipo_comprobante(id_tipo_comprobante)`.

**Valores unicos:** `id_tienda, serie`.

**Reglas de la tabla (`CHECK`):**

- `serie REGEXP '^[BF][0-9]{3}$'`
- `ultimo_numero <= 99999999`

#### `stock`

**Para que sirve:** Stock de cada producto en cada tienda (llave compuesta tienda + producto).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_tienda` | `SMALLINT UNSIGNED` | No | PK + FK → `tienda` | Tienda (parte de la llave compuesta). |
| `id_producto` | `INT UNSIGNED` | No | PK + FK → `producto` | Producto (parte de la llave compuesta). |
| `cantidad_actual` | `DECIMAL(10,3)` | No |  | Cantidad disponible ahora en esa tienda; nunca negativa. Por defecto: `0`. |
| `stock_minimo` | `DECIMAL(10,3)` | No |  | Cantidad minima; si el stock llega a ella o baja, hay que reponer. Por defecto: `0`. |

**Clave primaria:** `id_tienda, id_producto`  (llave **compuesta**).

**Claves foraneas:** `id_tienda` → `tienda(id_tienda)`; `id_producto` → `producto(id_producto)`.

**Reglas de la tabla (`CHECK`):**

- `cantidad_actual >= 0`
- `stock_minimo >= 0`

### Nivel 4: Turnos, horarios, traslados, mermas y lotes

#### `caja_turno`

**Para que sirve:** Turno de caja: un cajero abre una caja con un monto inicial y la cierra al terminar.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_caja_turno` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del turno. |
| `fecha_hora_apertura` | `DATETIME` | No |  | Cuando el cajero abrio la caja. Por defecto: `CURRENT_TIMESTAMP`. |
| `monto_inicial` | `DECIMAL(10,2)` | No |  | Dinero con que se abre la caja. |
| `fecha_hora_cierre` | `DATETIME` | Si |  | Cuando se cerro. NULL mientras el turno esta abierto. |
| `monto_cierre` | `DECIMAL(10,2)` | Si |  | Dinero contado al cerrar. NULL mientras el turno esta abierto. |
| `estado` | `VARCHAR(10)` | No |  | ABIERTO o CERRADO. Por defecto: `'ABIERTO'`. |
| `id_caja` | `SMALLINT UNSIGNED` | No | FK → `caja` | Caja que se abre. |
| `id_empleado` | `MEDIUMINT UNSIGNED` | No | FK → `empleado` | Cajero responsable del turno. |

**Clave primaria:** `id_caja_turno`.

**Claves foraneas:** `id_caja` → `caja(id_caja)`; `id_empleado` → `empleado(id_empleado)`.

**Reglas de la tabla (`CHECK`):**

- `monto_inicial >= 0`
- `(estado = 'ABIERTO' AND fecha_hora_cierre IS NULL AND monto_cierre IS NULL) OR (estado = 'CERRADO' AND fecha_hora_cierre IS NOT NULL AND monto_cierre IS NOT NULL AND monto_cierre >= 0 AND fecha_hora_cierre >= fecha_hora_apertura)`

#### `turno_trabajo`

**Para que sirve:** Horario de trabajo que el **administrador programa** a los empleados de su tienda (dia y hora de entrada y salida). No es el turno de caja (`caja_turno`), que registra el dinero.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_turno_trabajo` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del turno de trabajo. |
| `fecha` | `DATE` | No |  | Dia en que se trabaja. |
| `hora_inicio` | `TIME` | No |  | Hora de entrada. |
| `hora_fin` | `TIME` | No |  | Hora de salida. |
| `id_empleado` | `MEDIUMINT UNSIGNED` | No | FK → `empleado` | Empleado que trabaja. |
| `id_empleado_programa` | `MEDIUMINT UNSIGNED` | No | FK → `empleado` | Administrador que programa el turno. |

**Clave primaria:** `id_turno_trabajo`.

**Claves foraneas:** `id_empleado` → `empleado(id_empleado)`; `id_empleado_programa` → `empleado(id_empleado)`.

**Valores unicos:** `(id_empleado, fecha)` (un turno por empleado y dia).

**Restricciones `CHECK`:** `hora_fin > hora_inicio`.

#### `traslado_mercaderia`

**Para que sirve:** Cabecera del traslado de mercaderia entre dos tiendas (origen y destino).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_traslado` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del traslado. |
| `fecha_hora` | `DATETIME` | No |  | Cuando se registro el traslado. Por defecto: `CURRENT_TIMESTAMP`. |
| `observacion` | `VARCHAR(200)` | Si |  | Comentario. Opcional. |
| `id_tienda_origen` | `SMALLINT UNSIGNED` | No | FK → `tienda` | Tienda que envia la mercaderia. |
| `id_tienda_destino` | `SMALLINT UNSIGNED` | No | FK → `tienda` | Tienda que la recibe (distinta del origen). |
| `id_empleado` | `MEDIUMINT UNSIGNED` | No | FK → `empleado` | Almacenero o administrador que lo registra. |

**Clave primaria:** `id_traslado`.

**Claves foraneas:** `id_tienda_origen` → `tienda(id_tienda)`; `id_tienda_destino` → `tienda(id_tienda)`; `id_empleado` → `empleado(id_empleado)`.

**Reglas de la tabla (`CHECK`):**

- `id_tienda_origen <> id_tienda_destino`

#### `merma`

**Para que sirve:** Cabecera de la merma (perdida de mercaderia); su tienda sale del empleado que la registra.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_merma` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la merma. |
| `fecha_hora` | `DATETIME` | No |  | Cuando se registro la merma. Por defecto: `CURRENT_TIMESTAMP`. |
| `motivo` | `VARCHAR(20)` | No |  | VENCIDO, DAÑADO, ROBO u OTRO. |
| `observacion` | `VARCHAR(200)` | Si |  | Comentario. Opcional. |
| `id_empleado` | `MEDIUMINT UNSIGNED` | No | FK → `empleado` | Empleado que la registra; su tienda es la tienda afectada. |

**Clave primaria:** `id_merma`.

**Claves foraneas:** `id_empleado` → `empleado(id_empleado)`.

**Reglas de la tabla (`CHECK`):**

- `motivo IN ('VENCIDO','DAÑADO','ROBO','OTRO')`

#### `lote_producto`

**Para que sirve:** Lotes de productos perecibles en cada tienda: codigo de lote, fecha de vencimiento y cantidad que queda.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_lote` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del lote. |
| `codigo_lote` | `VARCHAR(20)` | No |  | Codigo del lote impreso en el empaque (unico por tienda y producto). |
| `fecha_vencimiento` | `DATE` | No |  | Fecha hasta la que el lote se puede vender. |
| `cantidad_inicial` | `DECIMAL(10,3)` | No |  | Cantidad que llego en el lote. |
| `cantidad_actual` | `DECIMAL(10,3)` | No |  | Cantidad que queda del lote en la tienda (nunca mayor que la inicial). |
| `id_tienda` | `SMALLINT UNSIGNED` | No | FK → `stock` | Tienda donde esta el lote (junto con id_producto apunta a stock). |
| `id_producto` | `INT UNSIGNED` | No | FK → `stock` | Producto al que pertenece el lote. |

**Clave primaria:** `id_lote`.

**Claves foraneas:** `id_tienda, id_producto` → `stock(id_tienda, id_producto)`.

**Valores unicos:** `id_tienda, id_producto, codigo_lote`.

**Reglas de la tabla (`CHECK`):**

- `cantidad_inicial > 0`
- `cantidad_actual >= 0 AND cantidad_actual <= cantidad_inicial`

### Nivel 5: Ventas y movimientos de caja

#### `venta`

**Para que sirve:** Cabecera de la venta: comprobante (serie + correlativo), turno de caja y cliente opcional.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_venta` | `BIGINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador interno de la venta. |
| `numero_correlativo` | `INT UNSIGNED` | No |  | Numero del comprobante dentro de su serie (1, 2, 3...). |
| `fecha_hora` | `DATETIME` | No |  | Fecha y hora de emision. Por defecto: `CURRENT_TIMESTAMP`. |
| `total` | `DECIMAL(10,2)` | No |  | Total a pagar: suma de los importes del detalle. |
| `estado` | `VARCHAR(10)` | No |  | EMITIDA o ANULADA (las ventas nunca se borran). Por defecto: `'EMITIDA'`. |
| `id_serie` | `SMALLINT UNSIGNED` | No | FK → `serie_comprobante` | Serie con la que se emitio; de ella se obtienen la tienda y el tipo de comprobante. |
| `id_caja_turno` | `INT UNSIGNED` | No | FK → `caja_turno` | Turno de caja en que se vendio; de el se obtienen la caja y el cajero. |
| `id_cliente` | `INT UNSIGNED` | Si | FK → `cliente` | Cliente. Opcional: la boleta puede ir sin cliente; la factura lo exige con RUC. |

**Clave primaria:** `id_venta`.

**Claves foraneas:** `id_serie` → `serie_comprobante(id_serie)`; `id_caja_turno` → `caja_turno(id_caja_turno)`; `id_cliente` → `cliente(id_cliente)`.

**Valores unicos:** `id_serie, numero_correlativo`.

**Reglas de la tabla (`CHECK`):**

- `numero_correlativo BETWEEN 1 AND 99999999`
- `total > 0`
- `estado IN ('EMITIDA','ANULADA')`

#### `movimiento_caja`

**Para que sirve:** Movimientos de efectivo de un turno de caja que no son ventas: ingresos, retiros, gastos y devoluciones de dinero.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_movimiento_caja` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del movimiento de caja. |
| `fecha_hora` | `DATETIME` | No |  | Cuando ocurrio. Por defecto: `CURRENT_TIMESTAMP`. |
| `tipo` | `VARCHAR(10)` | No |  | INGRESO (sencillo), RETIRO (de seguridad), GASTO o DEVOLUCION (dinero que se devuelve al cliente). |
| `monto` | `DECIMAL(10,2)` | No |  | Dinero del movimiento (siempre positivo; el tipo dice si entra o sale). |
| `motivo` | `VARCHAR(100)` | No |  | Explicacion del movimiento. |
| `id_caja_turno` | `INT UNSIGNED` | No | FK → `caja_turno` | Turno de caja en el que ocurrio. |
| `id_venta` | `BIGINT UNSIGNED` | Si | FK → `venta` | Venta de la que se devuelve el dinero. Opcional; solo en DEVOLUCION. |

**Clave primaria:** `id_movimiento_caja`.

**Claves foraneas:** `id_caja_turno` → `caja_turno(id_caja_turno)`; `id_venta` → `venta(id_venta)`.

**Reglas de la tabla (`CHECK`):**

- `tipo IN ('INGRESO','RETIRO','GASTO','DEVOLUCION')`
- `monto > 0`
- `(tipo = 'DEVOLUCION' AND id_venta IS NOT NULL) OR (tipo <> 'DEVOLUCION' AND id_venta IS NULL)`

### Nivel 6: Detalle, solicitudes de anulacion, pagos y devoluciones de la venta

#### `detalle_venta`

**Para que sirve:** Lineas de la venta: producto, cantidad, precio al momento de vender e importe.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_detalle_venta` | `BIGINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la linea. |
| `cantidad` | `DECIMAL(10,3)` | No |  | Cantidad vendida (decimales solo en productos por peso). |
| `precio_unitario` | `DECIMAL(10,2)` | No |  | Copia del precio del producto al momento de vender. |
| `importe` | `DECIMAL(10,2)` | No |  | cantidad x precio_unitario, redondeado a 2 decimales. |
| `id_venta` | `BIGINT UNSIGNED` | No | FK → `venta` | Venta a la que pertenece la linea. |
| `id_producto` | `INT UNSIGNED` | No | FK → `producto` | Producto vendido. |

**Clave primaria:** `id_detalle_venta`.

**Claves foraneas:** `id_venta` → `venta(id_venta)`; `id_producto` → `producto(id_producto)`.

**Reglas de la tabla (`CHECK`):**

- `cantidad > 0`
- `precio_unitario > 0`
- `importe = ROUND(cantidad * precio_unitario, 2)`

#### `solicitud_anulacion`

**Para que sirve:** El cajero **pide** anular una venta y el administrador de la tienda la **autoriza o rechaza**. Reemplaza a la antigua tabla `anulacion_venta`: ahora queda registrado quien la pidio, quien la resolvio y el resultado.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_solicitud` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la solicitud. |
| `fecha_hora_solicitud` | `DATETIME` | No |  | Cuando la pidio el cajero. Por defecto: `CURRENT_TIMESTAMP`. |
| `motivo` | `VARCHAR(150)` | No |  | Por que se pide anular la venta. |
| `estado` | `VARCHAR(10)` | No |  | PENDIENTE (por defecto), APROBADA o RECHAZADA. |
| `fecha_hora_resolucion` | `DATETIME` | Si |  | Cuando la resolvio el administrador. NULL mientras esta PENDIENTE. |
| `motivo_rechazo` | `VARCHAR(150)` | Si |  | Por que se rechazo. Solo si el estado es RECHAZADA. |
| `id_venta` | `BIGINT UNSIGNED` | No | FK → `venta` | Venta cuya anulacion se solicita. |
| `id_empleado_solicita` | `MEDIUMINT UNSIGNED` | No | FK → `empleado` | Cajero que la pide. |
| `id_empleado_resuelve` | `MEDIUMINT UNSIGNED` | Si | FK → `empleado` | Administrador que la autoriza o rechaza. NULL mientras esta PENDIENTE. |
| `anulacion_aprobada` | `BIGINT UNSIGNED` (columna generada) | Si |  | Copia de `id_venta` solo cuando el estado es APROBADA; con su `UNIQUE` impide aprobar dos veces la anulacion de una misma venta. |

**Clave primaria:** `id_solicitud`.

**Claves foraneas:** `id_venta` → `venta(id_venta)`; `id_empleado_solicita` → `empleado(id_empleado)`; `id_empleado_resuelve` → `empleado(id_empleado)`.

**Valores unicos:** `anulacion_aprobada` (una sola solicitud APROBADA por venta; una rechazada puede volver a pedirse).

**Restricciones `CHECK`:**
- `estado` es PENDIENTE, APROBADA o RECHAZADA, y los campos de resolucion son coherentes con el estado: PENDIENTE no tiene resolucion; APROBADA tiene administrador y fecha; RECHAZADA ademas tiene motivo de rechazo.
- La resolucion no puede ser anterior a la solicitud.
- Quien resuelve es distinto de quien solicita.

#### `pago_venta`

**Para que sirve:** Pagos de una venta. Puede haber varios y deben sumar el total.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_pago` | `BIGINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del pago. |
| `monto_pagado` | `DECIMAL(10,2)` | No |  | Monto de este pago. |
| `fecha_hora` | `DATETIME` | No |  | Cuando se cobro. Por defecto: `CURRENT_TIMESTAMP`. |
| `estado` | `VARCHAR(10)` | No |  | VALIDO o ANULADO (pasa a ANULADO cuando se anula la venta). Por defecto: `'VALIDO'`. |
| `id_venta` | `BIGINT UNSIGNED` | No | FK → `venta` | Venta que se paga. |
| `id_metodo_pago` | `TINYINT UNSIGNED` | No | FK → `metodo_pago` | Metodo con que se pago. |

**Clave primaria:** `id_pago`.

**Claves foraneas:** `id_venta` → `venta(id_venta)`; `id_metodo_pago` → `metodo_pago(id_metodo_pago)`.

**Reglas de la tabla (`CHECK`):**

- `monto_pagado > 0`
- `estado IN ('VALIDO','ANULADO')`

#### `devolucion_venta`

**Para que sirve:** Devolucion parcial de productos de una venta emitida. El dinero sale de la caja como un movimiento_caja de tipo DEVOLUCION.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_devolucion` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la devolucion. |
| `fecha_hora` | `DATETIME` | No |  | Cuando se hizo la devolucion. Por defecto: `CURRENT_TIMESTAMP`. |
| `motivo` | `VARCHAR(150)` | No |  | Por que se devuelve. |
| `monto_devuelto` | `DECIMAL(10,2)` | No |  | Dinero devuelto (suma de los importes de su detalle). |
| `id_venta` | `BIGINT UNSIGNED` | No | FK → `venta` | Venta emitida sobre la que se devuelve. |

**Clave primaria:** `id_devolucion`.

**Claves foraneas:** `id_venta` → `venta(id_venta)`.

**Reglas de la tabla (`CHECK`):**

- `monto_devuelto > 0`

### Nivel 7: Subtipos de pago, detalle de devolucion y kardex

#### `efectivo`

**Para que sirve:** Detalle del pago en efectivo (subtipo de pago_venta; su llave primaria es tambien foranea).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_pago` | `BIGINT UNSIGNED` | No | PK + FK → `pago_venta` | Pago al que pertenece (es llave primaria y foranea a la vez). |
| `monto_recibido` | `DECIMAL(10,2)` | No |  | Billetes y monedas que entrego el cliente. |
| `vuelto` | `DECIMAL(10,2)` | No |  | Cambio devuelto: monto_recibido - monto_pagado. Por defecto: `0`. |

**Clave primaria:** `id_pago`  (tambien es llave foranea: subtipo de `pago_venta`).

**Claves foraneas:** `id_pago` → `pago_venta(id_pago)`.

**Reglas de la tabla (`CHECK`):**

- `monto_recibido > 0`
- `vuelto >= 0 AND vuelto < monto_recibido`

#### `tarjeta`

**Para que sirve:** Detalle del pago con tarjeta (subtipo de pago_venta). Nunca se guarda el numero completo.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_pago` | `BIGINT UNSIGNED` | No | PK + FK → `pago_venta` | Pago al que pertenece (llave primaria y foranea a la vez). |
| `tipo_tarjeta` | `VARCHAR(10)` | No |  | CREDITO o DEBITO. |
| `marca_tarjeta` | `VARCHAR(20)` | No |  | Marca de la tarjeta (VISA, MASTERCARD...). |
| `ultimos_4_digitos` | `CHAR(4)` | No |  | Ultimos 4 digitos; nunca se guarda el numero completo. |
| `codigo_autorizacion` | `VARCHAR(10)` | No |  | Codigo de autorizacion del pago con tarjeta. |

**Clave primaria:** `id_pago`  (tambien es llave foranea: subtipo de `pago_venta`).

**Claves foraneas:** `id_pago` → `pago_venta(id_pago)`.

**Reglas de la tabla (`CHECK`):**

- `tipo_tarjeta IN ('CREDITO','DEBITO')`
- `ultimos_4_digitos REGEXP '^[0-9]{4}$'`

#### `billetera_digital_sip`

**Para que sirve:** Detalle del pago con la billetera digital Sip (subtipo de pago_venta).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_pago` | `BIGINT UNSIGNED` | No | PK + FK → `pago_venta` | Pago al que pertenece (llave primaria y foranea a la vez). |
| `modalidad_pago` | `VARCHAR(15)` | No |  | QR o TARJETA_SIP. |
| `codigo_operacion` | `VARCHAR(30)` | No |  | Codigo de la operacion en Sip (unico). |

**Clave primaria:** `id_pago`  (tambien es llave foranea: subtipo de `pago_venta`).

**Claves foraneas:** `id_pago` → `pago_venta(id_pago)`.

**Valores unicos:** `codigo_operacion`.

**Reglas de la tabla (`CHECK`):**

- `modalidad_pago IN ('QR','TARJETA_SIP')`

#### `detalle_devolucion`

**Para que sirve:** Lineas de la devolucion: que linea de la venta se devuelve y cuanto.

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_detalle_devolucion` | `INT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador de la linea devuelta. |
| `cantidad` | `DECIMAL(10,3)` | No |  | Cantidad que se devuelve (no mayor que la vendida). |
| `importe` | `DECIMAL(10,2)` | No |  | Cantidad devuelta x precio_unitario de la linea vendida. |
| `id_devolucion` | `INT UNSIGNED` | No | FK → `devolucion_venta` | Devolucion a la que pertenece. |
| `id_detalle_venta` | `BIGINT UNSIGNED` | No | FK → `detalle_venta` | Linea de la venta que se devuelve. |

**Clave primaria:** `id_detalle_devolucion`.

**Claves foraneas:** `id_devolucion` → `devolucion_venta(id_devolucion)`; `id_detalle_venta` → `detalle_venta(id_detalle_venta)`.

**Valores unicos:** `id_devolucion, id_detalle_venta`.

**Reglas de la tabla (`CHECK`):**

- `cantidad > 0`
- `importe > 0`

#### `movimiento_stock`

**Para que sirve:** Kardex: cada cambio de stock, con un unico origen (venta, traslado, merma o devolucion).

| Columna | Tipo | Nulo | Llave | Descripcion |
|---|---|---|---|---|
| `id_movimiento` | `BIGINT UNSIGNED AUTO_INCREMENT` | No | PK | Identificador del movimiento. |
| `fecha_hora` | `DATETIME` | No |  | Cuando ocurrio. Por defecto: `CURRENT_TIMESTAMP`. |
| `cantidad` | `DECIMAL(10,3)` | No |  | Cantidad que entra o sale (siempre positiva; el efecto lo da el tipo de movimiento). |
| `stock_nuevo` | `DECIMAL(10,3)` | No |  | Saldo del stock despues del movimiento. |
| `observacion` | `VARCHAR(200)` | Si |  | Comentario. Opcional. |
| `id_tienda` | `SMALLINT UNSIGNED` | No | FK → `stock` | Tienda cuyo stock cambia (junto con id_producto apunta a stock). |
| `id_producto` | `INT UNSIGNED` | No | FK → `stock` | Producto cuyo stock cambia. |
| `id_tipo_movimiento` | `TINYINT UNSIGNED` | No | FK → `tipo_movimiento` | Tipo de movimiento (define si entra o sale). |
| `id_empleado` | `MEDIUMINT UNSIGNED` | No | FK → `empleado` | Empleado que lo origino. |
| `id_venta` | `BIGINT UNSIGNED` | Si | FK → `venta` | Origen: venta (o su anulacion). Opcional; solo uno de los cuatro origenes. |
| `id_traslado` | `INT UNSIGNED` | Si | FK → `traslado_mercaderia` | Origen: traslado. Opcional; solo uno de los cuatro origenes. |
| `id_merma` | `INT UNSIGNED` | Si | FK → `merma` | Origen: merma. Opcional; solo uno de los cuatro origenes. |
| `id_devolucion` | `INT UNSIGNED` | Si | FK → `devolucion_venta` | Origen: devolucion de un cliente. Opcional; solo uno de los cuatro origenes. |
| `id_lote` | `INT UNSIGNED` | Si | FK → `lote_producto` | Lote afectado. Opcional; solo en productos con lote. |

**Clave primaria:** `id_movimiento`.

**Claves foraneas:** `id_tienda, id_producto` → `stock(id_tienda, id_producto)`; `id_tipo_movimiento` → `tipo_movimiento(id_tipo_movimiento)`; `id_empleado` → `empleado(id_empleado)`; `id_venta` → `venta(id_venta)`; `id_traslado` → `traslado_mercaderia(id_traslado)`; `id_merma` → `merma(id_merma)`; `id_devolucion` → `devolucion_venta(id_devolucion)`; `id_lote` → `lote_producto(id_lote)`.

**Reglas de la tabla (`CHECK`):**

- `cantidad > 0`
- `stock_nuevo >= 0`
- `(id_venta IS NOT NULL) + (id_traslado IS NOT NULL) + (id_merma IS NOT NULL) + (id_devolucion IS NOT NULL) = 1`

## 5. Relaciones (43)

Cardinalidad leida desde el padre hacia el hijo. `1` = exactamente uno, `0..1` = cero o uno, `0..N` = cero o muchos, `1..N` = uno o muchos. Una relacion **identificadora** es aquella cuya llave foranea forma parte de la llave primaria del hijo.

| # | Padre | Hijo | Padre : Hijo | Clave foranea | Significado |
|---|---|---|---|---|---|
| 1 | `distrito` | `tienda` | 1 : 0..N | `id_distrito` | distrito ubica a tienda |
| 2 | `tienda` | `empleado` | 1 : 0..N | `id_tienda` | tienda emplea a empleado |
| 3 | `rol` | `empleado` | 1 : 0..N | `id_rol` | rol define el rol de empleado |
| 4 | `tienda` | `caja` | 1 : 0..N | `id_tienda` | tienda tiene caja |
| 5 | `caja` | `caja_turno` | 1 : 0..N | `id_caja` | caja se abre en caja_turno |
| 6 | `empleado` | `caja_turno` | 1 : 0..N | `id_empleado` | empleado abre caja_turno |
| 7 | `tienda` | `serie_comprobante` | 1 : 0..N | `id_tienda` | tienda numera con serie_comprobante |
| 8 | `tipo_comprobante` | `serie_comprobante` | 1 : 0..N | `id_tipo_comprobante` | tipo_comprobante clasifica serie_comprobante |
| 9 | `serie_comprobante` | `venta` | 1 : 0..N | `id_serie` | serie_comprobante emite venta |
| 10 | `caja_turno` | `venta` | 1 : 0..N | `id_caja_turno` | caja_turno registra venta |
| 11 | `cliente` | `venta` | 0..1 : 0..N | `id_cliente` | cliente compra en venta |
| 12 | `venta` | `detalle_venta` | 1 : 1..N | `id_venta` | venta contiene detalle_venta |
| 13 | `producto` | `detalle_venta` | 1 : 0..N | `id_producto` | producto se vende en detalle_venta |
| 14 | `venta` | `solicitud_anulacion` | 1 : 0..N | `id_venta` | venta se anula con solicitud_anulacion |
| 15 | `empleado` | `solicitud_anulacion` | 1 : 0..N | `id_empleado_solicita` | empleado (cajero) solicita solicitud_anulacion |
| 16 | `empleado` | `solicitud_anulacion` | 1 : 0..N | `id_empleado_resuelve` | empleado (administrador) resuelve solicitud_anulacion |
| 17 | `venta` | `pago_venta` | 1 : 1..N | `id_venta` | venta se cobra con pago_venta |
| 18 | `metodo_pago` | `pago_venta` | 1 : 0..N | `id_metodo_pago` | metodo_pago es el medio de pago_venta |
| 19 | `pago_venta` | `efectivo` | 1 : 0..1 (identificadora) | `id_pago` | pago_venta detalla efectivo |
| 20 | `pago_venta` | `tarjeta` | 1 : 0..1 (identificadora) | `id_pago` | pago_venta detalla tarjeta |
| 21 | `pago_venta` | `billetera_digital_sip` | 1 : 0..1 (identificadora) | `id_pago` | pago_venta detalla billetera_digital_sip |
| 22 | `categoria` | `producto` | 1 : 0..N | `id_categoria` | categoria clasifica producto |
| 23 | `marca` | `producto` | 1 : 0..N | `id_marca` | marca identifica producto |
| 24 | `unidad_medida` | `producto` | 1 : 0..N | `id_unidad` | unidad_medida mide producto |
| 25 | `tienda` | `stock` | 1 : 0..N (identificadora) | `id_tienda` | tienda guarda stock |
| 26 | `producto` | `stock` | 1 : 0..N (identificadora) | `id_producto` | producto se guarda en stock |
| 27 | `stock` | `movimiento_stock` | 1 : 0..N | `id_tienda, id_producto` | stock cambia con movimiento_stock |
| 28 | `tipo_movimiento` | `movimiento_stock` | 1 : 0..N | `id_tipo_movimiento` | tipo_movimiento tipifica movimiento_stock |
| 29 | `empleado` | `movimiento_stock` | 1 : 0..N | `id_empleado` | empleado ejecuta movimiento_stock |
| 30 | `venta` | `movimiento_stock` | 0..1 : 1..N | `id_venta` | venta origina movimiento_stock |
| 31 | `traslado_mercaderia` | `movimiento_stock` | 0..1 : 1..N | `id_traslado` | traslado_mercaderia origina movimiento_stock |
| 32 | `merma` | `movimiento_stock` | 0..1 : 1..N | `id_merma` | merma origina movimiento_stock |
| 33 | `tienda` | `traslado_mercaderia` | 1 : 0..N | `id_tienda_origen` | tienda envía (origen) traslado_mercaderia |
| 34 | `tienda` | `traslado_mercaderia` | 1 : 0..N | `id_tienda_destino` | tienda recibe (destino) traslado_mercaderia |
| 35 | `empleado` | `traslado_mercaderia` | 1 : 0..N | `id_empleado` | empleado registra traslado_mercaderia |
| 36 | `empleado` | `merma` | 1 : 0..N | `id_empleado` | empleado registra merma |
| 37 | `stock` | `lote_producto` | 1 : 0..N | `id_tienda, id_producto` | stock se divide en lote_producto |
| 38 | `lote_producto` | `movimiento_stock` | 0..1 : 0..N | `id_lote` | lote_producto afecta a movimiento_stock |
| 39 | `caja_turno` | `movimiento_caja` | 1 : 0..N | `id_caja_turno` | caja_turno registra movimiento_caja |
| 40 | `venta` | `devolucion_venta` | 1 : 0..N | `id_venta` | venta se devuelve con devolucion_venta |
| 41 | `venta` | `movimiento_caja` | 0..1 : 0..N | `id_venta` | venta se reembolsa con movimiento_caja |
| 42 | `devolucion_venta` | `detalle_devolucion` | 1 : 1..N | `id_devolucion` | devolucion_venta contiene detalle_devolucion |
| 43 | `detalle_venta` | `detalle_devolucion` | 1 : 0..N | `id_detalle_venta` | detalle_venta se devuelve en detalle_devolucion |
| 44 | `devolucion_venta` | `movimiento_stock` | 0..1 : 1..N | `id_devolucion` | devolucion_venta origina movimiento_stock |
| 45 | `empleado` | `turno_trabajo` | 1 : 0..N | `id_empleado` | empleado trabaja en turno_trabajo |
| 46 | `empleado` | `turno_trabajo` | 1 : 0..N | `id_empleado_programa` | empleado (administrador) programa turno_trabajo |

## 6. Reglas de negocio

Cada regla indica **donde se garantiza**: **BD** si la base de datos la impide con `PRIMARY KEY`, `UNIQUE`, `FOREIGN KEY`, `NOT NULL` o `CHECK`; **Proceso** si depende del procedimiento de carga (la aplicacion o el script que registra la venta) y la base solo la deja verificar. Las reglas marcadas como Proceso se comprueban con las consultas de control 24 a 39 de `consultas.sql`, que deben devolver 0 filas; `datos.sql` las cumple todas.

| # | Regla | Donde se garantiza |
|---|---|---|
| 1 | La cadena tiene 3 tiendas en Arequipa; cada una esta en un distrito. | BD: `tienda.id_distrito` (FK) |
| 2 | Los metodos de pago son **efectivo, tarjeta y billetera digital Sip**. No hay otros (no hay Yape ni Plin). | BD: contenido de `metodo_pago` y las 3 tablas de subtipo |
| 3 | Boleta y factura tienen **serie y correlativo por tienda**. La serie empieza con `B` (boleta) o `F` (factura) y tiene 4 caracteres (`B001`). | BD: `serie_comprobante` (`CHECK` de formato, `UNIQUE(id_tienda, serie)`) |
| 4 | **Serie + correlativo no se repiten:** no pueden existir dos ventas con la misma serie y el mismo numero. | BD: `UNIQUE (id_serie, numero_correlativo)` en `venta` |
| 5 | El correlativo de cada serie avanza **de uno en uno** y `serie_comprobante.ultimo_numero` guarda el ultimo emitido. | Proceso (consulta 27) |
| 6 | La **factura exige un cliente con RUC**; la boleta puede emitirse sin cliente. | Proceso (consulta 26) |
| 7 | El DNI tiene 8 digitos y el RUC 11, solo numeros. Un cliente tiene DNI, RUC o ambos (al menos uno). | BD: `CHECK` en `cliente` y `empleado` |
| 8 | El **stock es por tienda** (un producto tiene una fila de stock en cada tienda) y **nunca es negativo**. | BD: llave compuesta de `stock`, `CHECK (cantidad_actual >= 0)` |
| 9 | Cada cambio de stock deja un **movimiento en el kardex** con el saldo resultante; el stock actual es el ultimo saldo. | Proceso (consulta 28) |
| 10 | Cada movimiento del kardex tiene **un solo origen**: una venta, un traslado, una merma o una devolucion. | BD: `CHECK` de origen en `movimiento_stock` |
| 11 | Las **ventas nunca se borran**: estan EMITIDAS o ANULADAS. | BD: `CHECK` de estado (y el usuario de la aplicacion no debe tener permiso `DELETE` sobre `venta`) |
| 12 | La anulacion de una venta la **solicita el cajero de esa venta** y la **autoriza o rechaza el administrador de la misma tienda**. Solo puede aprobarse **una vez** por venta. Al aprobarse, la venta pasa a ANULADA, el stock vuelve al inventario y los pagos pasan a ANULADO. | BD: `UNIQUE` sobre la solicitud aprobada, `CHECK` de estado y de que quien resuelve sea distinto de quien solicita. Proceso: quien solicita y quien resuelve (consulta 31) |
| 13 | Una venta puede tener **varios pagos** y los pagos validos **suman el total**. | Proceso (consulta 24) |
| 14 | Cada pago tiene el detalle de **su** metodo y de ningun otro (efectivo, tarjeta o Sip). | BD: subtipos con `PK = FK`. Proceso: que el subtipo coincida con el metodo (consulta 32) |
| 15 | El **cajero vende solo con un turno de caja abierto**, en una caja de **su** tienda. | Proceso (consultas 29 y 30) |
| 16 | Una caja tiene un solo turno abierto a la vez y un cajero tambien. Un turno abierto no tiene cierre; uno cerrado tiene fecha y monto de cierre. | BD: `CHECK` de estado en `caja_turno`. Proceso: un turno abierto por caja y por cajero |
| 17 | Un empleado pertenece a **una tienda** y tiene **un rol** (administrador, cajero o almacenero). Ingresa con su DNI, que es unico, y con su contrasena, que se guarda cifrada (hash). | BD: FK a `tienda` y `rol`, `UNIQUE (dni)`, `CHECK` de longitud de la contrasena |
| 18 | Un traslado va de una tienda **origen** a otra **destino distinta**. Lo registra el **administrador** de la tienda de origen y mueve stock en ambas tiendas. | BD: `CHECK (id_tienda_origen <> id_tienda_destino)`. Proceso: quien lo registra (consulta 45) |
| 19 | La merma la registra un almacenero o administrador y descuenta de **su propia tienda**. | Proceso |
| 20 | El **precio es unico** para todas las tiendas. La linea de venta guarda una **copia del precio** vigente al vender. | BD: `precio_venta` solo en `producto`; `precio_unitario` en `detalle_venta` |
| 21 | El **importe de cada linea** es `cantidad x precio_unitario` y el **total** de la venta es la suma de sus importes. | BD: `CHECK` del importe. Proceso: el total (consulta 25) |
| 22 | Los productos **por unidad** se venden en cantidades enteras; los productos **por peso** admiten decimales (hasta gramos). | Proceso |
| 23 | De la tarjeta solo se guardan los **ultimos 4 digitos**, nunca el numero completo. | BD: `CHECK` de 4 digitos y no existe columna para el numero |
| 24 | Los productos **perecibles** tienen **lotes** con fecha de vencimiento. El stock de un producto con lote es la **suma de sus lotes**, y la cantidad de un lote nunca supera la que llego. | BD: `CHECK` de cantidades y `UNIQUE (tienda, producto, codigo_lote)`. Proceso: suma de lotes (consulta 33) |
| 25 | La venta de un producto con lote sale del **lote que vence primero** y **nunca de un lote vencido**; un lote vencido solo sale como merma. Cada movimiento del kardex de estos productos indica su lote. | Proceso (consultas 34 y 35) |
| 26 | La **devolucion** es parcial, sobre una venta **emitida**, por lineas de esa venta y sin superar la cantidad vendida. Su monto es la suma de su detalle, el producto vuelve al stock y el dinero se devuelve en efectivo desde la caja. | BD: `UNIQUE (devolucion, linea)` y `CHECK` de montos. Proceso: el resto (consulta 36) |
| 27 | Todo **movimiento de caja** ocurre dentro de su turno. Solo el movimiento de tipo DEVOLUCION lleva venta. | BD: `CHECK` de tipo y venta. Proceso: horario del turno y tienda de la venta (consulta 37) |
| 28 | El **monto de cierre** de un turno es: monto inicial + efectivo cobrado + ingresos - retiros - gastos - devoluciones. | Proceso (consultas 38 y 39) |
| 29 | El **administrador programa los turnos de trabajo** de los empleados de **su** tienda: un turno por empleado y dia, con hora de salida posterior a la de entrada. | BD: `UNIQUE (id_empleado, fecha)` y `CHECK` de horas. Proceso: que lo programe un administrador de la misma tienda (consulta 43) |
| 30 | Un cajero abre su turno de caja **solo un dia en que tiene turno de trabajo** y dentro de su horario. | Proceso (consulta 44) |

## 7. Decisiones de diseno y por que se tomaron

1. **La venta no guarda su tienda, su caja ni su cajero.** Se obtienen de `id_serie` (la serie pertenece a una tienda y a un tipo) y de `id_caja_turno` (el turno tiene la caja y el cajero). Asi no puede haber una venta con una tienda y una caja de otra.
2. **Serie y correlativo estan separados.** `serie_comprobante` guarda la serie por tienda y su `ultimo_numero`; `venta` guarda solo el correlativo. El `UNIQUE (id_serie, numero_correlativo)` impide repetir un comprobante.
3. **`total` se guarda en la venta aunque se pueda calcular.** Es el monto que se cobra y contra el que se comparan los pagos. La consulta 25 comprueba que siempre sea igual a la suma del detalle. No se guardan subtotales ni impuestos aparte: el precio y el total son los finales.
4. **`precio_unitario` se copia al detalle.** Si manana cambia el precio del producto, las ventas pasadas no cambian.
5. **Pagos con subtipos exclusivos.** `pago_venta` guarda lo comun (monto, fecha, estado, metodo) y cada metodo tiene su tabla con sus propios datos (`efectivo`, `tarjeta`, `billetera_digital_sip`). La llave primaria del subtipo es tambien llave foranea del pago, asi un pago tiene a lo mas un detalle por tabla y no hay columnas vacias por metodo.
6. **Varios pagos por venta.** Por eso el pago es una tabla aparte y no una columna de la venta; permite pagar parte con tarjeta y parte en efectivo.
7. **Kardex unico (`movimiento_stock`).** Una sola tabla registra todo cambio de stock (venta, anulacion, traslado, merma, devolucion). Un `CHECK` obliga a que tenga exactamente un origen. Las lineas de un traslado o de una merma son los propios movimientos que apuntan a ellos, por eso `traslado_mercaderia` y `merma` son solo cabeceras. La devolucion si tiene detalle propio (`detalle_devolucion`) porque cada linea debe apuntar a la linea de venta que se devuelve.
8. **El traslado genera dos movimientos por producto:** una salida en la tienda de origen y una entrada en la de destino.
9. **`stock` con llave compuesta (tienda + producto).** Es una tabla puente entre dos entidades y no necesita un identificador propio. El kardex apunta a esa pareja.
10. **La merma no guarda su tienda.** Sale del empleado que la registra (cada empleado pertenece a una sola tienda).
11. **Anulacion con autorizacion (`solicitud_anulacion`).** Anular una venta tiene dos actores: el cajero la pide y el administrador la autoriza o la rechaza. Por eso se guarda la solicitud con su estado, quien la pidio y quien la resolvio, sin ensuciar `venta`. La relacion es 1 a 0..N: una solicitud rechazada puede repetirse, pero solo una puede quedar APROBADA (columna generada con `UNIQUE`; en MySQL los `NULL` no cuentan como repetidos).
12. **Cliente minimo.** Es un supermercado: no se registra nombre, direccion ni correo; solo DNI y RUC, ambos opcionales pero al menos uno.
13. **DNI como usuario y contrasena cifrada.** No hay un nombre de usuario aparte: el DNI es unico y es el usuario. La contrasena se guarda como **hash** (bcrypt o argon2), nunca en texto, en `VARCHAR(255)`: un hash bcrypt mide 60 caracteres, pero argon2 mide cerca de 100 y el algoritmo puede cambiar con los anos. Un `CHECK` exige al menos 60 caracteres, asi que una clave escrita por error en texto (casi siempre mas corta) no entra. La aplicacion crea el hash con `password_hash()` y lo compara con `password_verify()`; la contrasena nunca se muestra ni se devuelve en una consulta.
14. **Nada se borra.** No se usan `ON DELETE CASCADE`: ventas, pagos y movimientos son historial. Lo que ya no se usa cambia de estado (INACTIVO, DESCONTINUADO, ANULADA).
15. **Estados con `VARCHAR` + `CHECK` en lugar de `ENUM`.** Se leen mejor en los resultados y se cambian sin alterar el tipo de la columna.
16. **Las reglas que dependen de varias tablas** (pagos que suman el total, factura con RUC, correlativo consecutivo, quien anula) no caben en un `CHECK`. Se dejan como reglas de proceso y se verifican con consultas de control que deben devolver 0 filas.
17. **MySQL 8.0.16 o superior**, porque desde esa version los `CHECK` se aplican (antes se aceptaban y se ignoraban).
18. **Lotes (`lote_producto`).** El stock de una tienda es un numero por producto, pero un yogurt vence y una lata no. El lote guarda codigo, vencimiento y cantidad que queda, y apunta a `stock` (tienda + producto). Solo se usan en perecibles; los demas productos no necesitan lote. El kardex indica el lote afectado.
19. **Devolucion con cabecera y detalle.** Anular una venta devuelve todo; la devolucion devuelve solo algunas lineas. Cada linea apunta a la linea vendida, de modo que se puede comprobar que no se devuelve mas de lo vendido y a que precio. La venta original sigue EMITIDA.
20. **Movimientos de caja.** Hasta ahora el cuadre del turno solo consideraba el sencillo inicial y las ventas en efectivo. Ahora se registran los ingresos, retiros, gastos y devoluciones de dinero, y el cierre de caja se puede comprobar con una consulta.
21. **Sin proveedor ni compras.** No se modelan proveedores. El stock entra por traslados entre tiendas y por los lotes cargados al abrir el inventario. El modelo no limita el numero de tiendas, pero esta pensado para un maximo de 3 sucursales.
22. **Turnos de trabajo (`turno_trabajo`) separados del turno de caja.** `caja_turno` registra el dinero de una caja; `turno_trabajo` es el horario que el administrador programa a cualquier empleado (cajero, almacenero o el propio administrador). Se separan porque un almacenero trabaja sin abrir caja y un turno de caja siempre cae dentro de un turno de trabajo.
23. **Tablas del administrador.** Del analisis de los diagramas de referencia solo se tomo lo que hace el administrador: autorizar anulaciones, gestionar inventario y generar reportes (se resuelve con consultas sobre `stock` y el kardex, sin tabla nueva), programar turnos y registrar traslados. Por eso solo se agregaron `solicitud_anulacion` y `turno_trabajo`, y el traslado queda registrado por el administrador. No se creo una tabla `administrador`: es un `rol` de `empleado`, y duplicarlo crearia datos repetidos.

## 8. Pendientes por confirmar con el profesor

- Que **consultas concretas** debe incluir `consultas.sql` (ahora hay 26 preguntas de negocio y 19 consultas de control).
- Si la **boleta** debe ser una consulta, una vista o un procedimiento almacenado, y que columnas debe mostrar. Hoy `boleta.sql` incluye una vista (`vw_comprobante`) y un procedimiento (`sp_boleta`).
