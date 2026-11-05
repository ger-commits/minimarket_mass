<?php
// M: catálogo de productos (producto + categoría, marca y unidad). Precio único para las 3 tiendas.
require_once __DIR__ . '/Database.php';

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

    // Lista completa para el administrador (incluye los ids para editar)
    public function listarAdmin(): array
    {
        return Database::conectar()->query(
            "SELECT p.id_producto, p.nombre_producto, p.codigo_barras, p.precio_venta, p.se_vende_por_peso,
                    p.estado, p.id_categoria, p.id_marca, p.id_unidad,
                    c.nombre_categoria, m.nombre_marca, u.abreviatura
               FROM producto p
               JOIN categoria c     ON c.id_categoria = p.id_categoria
               JOIN marca m         ON m.id_marca = p.id_marca
               JOIN unidad_medida u ON u.id_unidad = p.id_unidad
              ORDER BY p.estado, c.nombre_categoria, p.nombre_producto"
        )->fetchAll();
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

    // Crea el producto y, en la misma transacción, su fila de stock en 0 para cada tienda
    public function crear(array $datos): int
    {
        $d = $this->validar($datos, null);
        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'INSERT INTO producto (nombre_producto, codigo_barras, precio_venta, se_vende_por_peso, estado,
                                       id_categoria, id_marca, id_unidad)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $st->execute([$d['nombre'], $d['codigo'], $d['precio'], $d['peso'], $d['estado'],
                          $d['categoria'], $d['marca'], $d['unidad']]);
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
        Database::conectar()->prepare(
            'UPDATE producto SET nombre_producto = ?, codigo_barras = ?, precio_venta = ?, se_vende_por_peso = ?,
                                 estado = ?, id_categoria = ?, id_marca = ?, id_unidad = ?
              WHERE id_producto = ?'
        )->execute([$d['nombre'], $d['codigo'], $d['precio'], $d['peso'], $d['estado'],
                    $d['categoria'], $d['marca'], $d['unidad'], $id]);
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

        $categoria = (int)($d['id_categoria'] ?? 0);
        $marca     = (int)($d['id_marca'] ?? 0);
        $unidad    = (int)($d['id_unidad'] ?? 0);
        if (!$this->existe('categoria', 'id_categoria', $categoria)
            || !$this->existe('marca', 'id_marca', $marca)
            || !$this->existe('unidad_medida', 'id_unidad', $unidad)) {
            throw new InvalidArgumentException('Elige una categoría, marca y unidad válidas.');
        }

        return [
            'nombre'    => $nombre,
            'codigo'    => $codigo,
            'precio'    => round((float)$precio, 2),
            'peso'      => ($d['se_vende_por_peso'] ?? 'N') === 'S' ? 'S' : 'N',
            'estado'    => ($d['estado'] ?? 'ACTIVO') === 'DESCONTINUADO' ? 'DESCONTINUADO' : 'ACTIVO',
            'categoria' => $categoria,
            'marca'     => $marca,
            'unidad'    => $unidad,
        ];
    }

    private function existe(string $tabla, string $columna, int $id): bool
    {
        $st = Database::conectar()->prepare("SELECT COUNT(*) FROM $tabla WHERE $columna = ?");
        $st->execute([$id]);
        return (int)$st->fetchColumn() > 0;
    }
}