<?php
// M: catálogo de productos. Precio único para las 3 tiendas.
// Permite escribir una categoría o marca nueva; si no existe, se crea en la misma transacción.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/MermaRepository.php';
require_once __DIR__ . '/Administrador.php';

class ProductoRepository
{
    // Lista para el cajero (sin cambios)
    public function listar(): array
    {
        return Database::conectar()->query(
            "SELECT p.id_producto, p.nombre_producto, c.nombre_categoria, m.nombre_marca,
                    u.abreviatura, p.precio_venta, p.estado
               FROM producto p
               JOIN categoria c     ON c.id_categoria = p.id_categoria
               JOIN marca m         ON m.id_marca = p.id_marca
               JOIN unidad_medida u ON u.id_unidad = p.id_unidad
              ORDER BY c.nombre_categoria, p.nombre_producto"
        )->fetchAll();
    }

    public function buscar(int $idProducto): ?array
    {
        $st = Database::conectar()->prepare('SELECT * FROM producto WHERE id_producto = ?');
        $st->execute([$idProducto]);
        return $st->fetch() ?: null;
    }

    // Lista completa para el administrador. El estado mostrado es el de SU tienda: un producto descontinuado
    // en otra tienda sigue ACTIVO aquí.
    public function listarAdmin(int $idTienda): array
    {
        $st = Database::conectar()->prepare(
            "SELECT p.id_producto, p.nombre_producto, p.codigo_barras, p.precio_venta, p.se_vende_por_peso,
                    CASE WHEN p.estado <> 'ACTIVO' THEN p.estado
                         WHEN s.descontinuado = 'S' THEN 'DESCONTINUADO' ELSE 'ACTIVO' END AS estado,
                    p.id_categoria, p.id_marca, p.id_unidad,
                    c.nombre_categoria, m.nombre_marca, u.abreviatura
               FROM producto p
               JOIN categoria c     ON c.id_categoria = p.id_categoria
               JOIN marca m         ON m.id_marca = p.id_marca
               JOIN unidad_medida u ON u.id_unidad = p.id_unidad
               LEFT JOIN stock s    ON s.id_producto = p.id_producto AND s.id_tienda = ?
              ORDER BY estado, c.nombre_categoria, p.nombre_producto"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    // Opciones de los desplegables del formulario
    public function catalogos(): array
    {
        $pdo = Database::conectar();
        return [
            'categorias' => $pdo->query('SELECT id_categoria, nombre_categoria FROM categoria ORDER BY nombre_categoria')->fetchAll(),
            'marcas'     => $pdo->query('SELECT id_marca, nombre_marca FROM marca ORDER BY nombre_marca')->fetchAll(),
            'unidades'   => $pdo->query('SELECT id_unidad, abreviatura FROM unidad_medida ORDER BY id_unidad')->fetchAll(),
        ];
    }

    // Crea el producto (y categoría/marca nuevas si se escribieron) y su stock en 0 en cada tienda
    public function crear(array $datos): int
    {
        $d = $this->validar($datos, null);
        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $idCategoria = $d['nueva_categoria'] !== null ? $this->asegurarCategoria($pdo, $d['nueva_categoria']) : $d['categoria'];
            $idMarca     = $d['nueva_marca'] !== null ? $this->asegurarMarca($pdo, $d['nueva_marca']) : $d['marca'];

            $st = $pdo->prepare(
                'INSERT INTO producto (nombre_producto, codigo_barras, precio_venta, se_vende_por_peso, estado,
                                       id_categoria, id_marca, id_unidad)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $st->execute([$d['nombre'], $d['codigo'], $d['precio'], $d['peso'], $d['estado'],
                          $idCategoria, $idMarca, $d['unidad']]);
            $id = (int)$pdo->lastInsertId();
            $pdo->exec("INSERT INTO stock (id_tienda, id_producto, cantidad_actual, stock_minimo)
                        SELECT id_tienda, $id, 0, 0 FROM tienda");
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function actualizar(int $id, array $datos): void
    {
        if ($this->buscar($id) === null) {
            throw new RuntimeException('El producto no existe.');
        }
        $d = $this->validar($datos, $id);
        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $idCategoria = $d['nueva_categoria'] !== null ? $this->asegurarCategoria($pdo, $d['nueva_categoria']) : $d['categoria'];
            $idMarca     = $d['nueva_marca'] !== null ? $this->asegurarMarca($pdo, $d['nueva_marca']) : $d['marca'];
            $pdo->prepare(
                'UPDATE producto SET nombre_producto = ?, codigo_barras = ?, precio_venta = ?, se_vende_por_peso = ?,
                                     id_categoria = ?, id_marca = ?, id_unidad = ?
                  WHERE id_producto = ?'
            )->execute([$d['nombre'], $d['codigo'], $d['precio'], $d['peso'],
                        $idCategoria, $idMarca, $d['unidad'], $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Cambia solo el precio (es único para todas las tiendas)
    public function actualizarPrecio(int $id, string $precioTexto): void
    {
        $precio = filter_var(str_replace(',', '.', trim($precioTexto)), FILTER_VALIDATE_FLOAT);
        if ($precio === false || $precio <= 0 || $precio > 99999999.99) {
            throw new InvalidArgumentException('El precio debe ser un número mayor que cero.');
        }
        Database::conectar()->prepare('UPDATE producto SET precio_venta = ? WHERE id_producto = ?')
            ->execute([round((float)$precio, 2), $id]);
    }

    // Descontinuar SOLO en la tienda del administrador: deja de venderse allí y conserva su stock.
    // En las otras tiendas el producto sigue igual.
    public function descontinuar(int $id, Administrador $admin): void
    {
        $this->cambiarEstadoEnTienda($id, $admin, 'S',
            'Producto descontinuado en tu tienda. Ya no se vende aquí; su stock se conserva.');
    }

    // Vuelve a vender el producto en la tienda del administrador
    public function reactivar(int $id, Administrador $admin): void
    {
        $this->cambiarEstadoEnTienda($id, $admin, 'N', 'Producto reactivado en tu tienda. Ya se puede vender de nuevo.');
    }

    private function cambiarEstadoEnTienda(int $id, Administrador $admin, string $descontinuado, string $aviso): void
    {
        $p = $this->buscar($id);
        if ($p === null || $p['estado'] === 'ELIMINADO') throw new RuntimeException('El producto no existe.');
        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT IGNORE INTO stock (id_tienda, id_producto, cantidad_actual, stock_minimo) VALUES (?, ?, 0, 0)')
                ->execute([$admin->idTienda, $id]);
            $st = $pdo->prepare('SELECT descontinuado FROM stock WHERE id_tienda = ? AND id_producto = ? FOR UPDATE');
            $st->execute([$admin->idTienda, $id]);
            if ($st->fetchColumn() === $descontinuado) {
                throw new RuntimeException($descontinuado === 'S' ? 'Ese producto ya está descontinuado en tu tienda.' : 'Ese producto ya está activo en tu tienda.');
            }
            $pdo->prepare('UPDATE stock SET descontinuado = ? WHERE id_tienda = ? AND id_producto = ?')
                ->execute([$descontinuado, $admin->idTienda, $id]);
            // El aviso llega solo al almacén de esta tienda
            $pdo->prepare('INSERT INTO notificacion_almacen (id_tienda, tipo, mensaje, creado) VALUES (?, ?, ?, NOW())')
                ->execute([$admin->idTienda, $descontinuado === 'S' ? 'PRODUCTO_DESCONTINUADO' : 'PRODUCTO_REACTIVADO',
                           mb_substr($p['nombre_producto'] . ': ' . $aviso, 0, 255)]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    // Valida los datos del formulario antes de guardar
    private function validar(array $d, ?int $idActual): array
    {
        $nombre = trim((string)($d['nombre'] ?? ''));
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
            throw new InvalidArgumentException('El nombre debe tener entre 2 y 100 caracteres.');
        }

        $precio = filter_var(str_replace(',', '.', (string)($d['precio'] ?? '')), FILTER_VALIDATE_FLOAT);
        if ($precio === false || $precio <= 0 || $precio > 99999999.99) {
            throw new InvalidArgumentException('El precio debe ser un número mayor que cero.');
        }

        $codigo = trim((string)($d['codigo_barras'] ?? ''));
        $codigo = $codigo === '' ? null : $codigo;
        if ($codigo !== null) {
            if (!preg_match('/^[0-9]{8,14}$/', $codigo)) {
                throw new InvalidArgumentException('El código de barras debe tener entre 8 y 14 dígitos.');
            }
            $st = Database::conectar()->prepare('SELECT id_producto FROM producto WHERE codigo_barras = ?');
            $st->execute([$codigo]);
            $otro = $st->fetchColumn();
            if ($otro !== false && (int)$otro !== (int)$idActual) {
                throw new InvalidArgumentException('Ya existe un producto con ese código de barras.');
            }
        }

        // Categoría: se elige una existente o se escribe una nueva
        $nuevaCategoria = trim((string)($d['nueva_categoria'] ?? ''));
        $categoria = (int)($d['id_categoria'] ?? 0);
        if ($nuevaCategoria !== '') {
            if (mb_strlen($nuevaCategoria) > 50) throw new InvalidArgumentException('La categoría nueva no puede pasar de 50 caracteres.');
        } elseif (!$this->existe('categoria', 'id_categoria', $categoria)) {
            throw new InvalidArgumentException('Elige una categoría o escribe una nueva.');
        }

        // Marca: se elige una existente o se escribe una nueva
        $nuevaMarca = trim((string)($d['nueva_marca'] ?? ''));
        $marca = (int)($d['id_marca'] ?? 0);
        if ($nuevaMarca !== '') {
            if (mb_strlen($nuevaMarca) > 50) throw new InvalidArgumentException('La marca nueva no puede pasar de 50 caracteres.');
        } elseif (!$this->existe('marca', 'id_marca', $marca)) {
            throw new InvalidArgumentException('Elige una marca o escribe una nueva.');
        }

        $unidad = (int)($d['id_unidad'] ?? 0);
        if (!$this->existe('unidad_medida', 'id_unidad', $unidad)) {
            throw new InvalidArgumentException('Elige una unidad válida.');
        }

        return [
            'nombre'           => $nombre,
            'codigo'           => $codigo,
            'precio'           => round((float)$precio, 2),
            'peso'             => ($d['se_vende_por_peso'] ?? 'N') === 'S' ? 'S' : 'N',
            'estado'           => ($d['estado'] ?? 'ACTIVO') === 'DESCONTINUADO' ? 'DESCONTINUADO' : 'ACTIVO',
            'categoria'        => $categoria,
            'nueva_categoria'  => $nuevaCategoria !== '' ? $nuevaCategoria : null,
            'marca'            => $marca,
            'nueva_marca'      => $nuevaMarca !== '' ? $nuevaMarca : null,
            'unidad'           => $unidad,
        ];
    }

    // Devuelve el id de la categoría; si no existe, la crea
    private function asegurarCategoria(PDO $pdo, string $nombre): int
    {
        $st = $pdo->prepare('SELECT id_categoria FROM categoria WHERE nombre_categoria = ?');
        $st->execute([$nombre]);
        $id = $st->fetchColumn();
        if ($id !== false) return (int)$id;
        $pdo->prepare('INSERT INTO categoria (nombre_categoria) VALUES (?)')->execute([$nombre]);
        return (int)$pdo->lastInsertId();
    }

    // Devuelve el id de la marca; si no existe, la crea (no es marca propia)
    private function asegurarMarca(PDO $pdo, string $nombre): int
    {
        $st = $pdo->prepare('SELECT id_marca FROM marca WHERE nombre_marca = ?');
        $st->execute([$nombre]);
        $id = $st->fetchColumn();
        if ($id !== false) return (int)$id;
        $pdo->prepare("INSERT INTO marca (nombre_marca, es_marca_propia) VALUES (?, 'N')")->execute([$nombre]);
        return (int)$pdo->lastInsertId();
    }

    private function existe(string $tabla, string $columna, int $id): bool
    {
        $st = Database::conectar()->prepare("SELECT COUNT(*) FROM $tabla WHERE $columna = ?");
        $st->execute([$id]);
        return (int)$st->fetchColumn() > 0;
    }
}