<?php
// M: escáner de celular del almacén. Un enlace (QR) con token vincula el celular con la sesión de un almacenero.
require_once __DIR__ . '/Database.php';

class EscanerRepository
{
    private const HORAS_VALIDEZ      = 12;
    private const SEGUNDOS_CONECTADO = 60;

    // Crea un enlace nuevo. 'desde' es el último código que ya existe, para no mostrar códigos viejos.
    public function vincular(int $idEmpleado): array
    {
        $pdo = Database::conectar();
        $pdo->exec('DELETE FROM escaner_vinculo WHERE expira < NOW()');
        $token = bin2hex(random_bytes(16));
        $pdo->prepare(
            'INSERT INTO escaner_vinculo (token, id_empleado, creado, expira)
             VALUES (?, ?, NOW(), NOW() + INTERVAL ' . self::HORAS_VALIDEZ . ' HOUR)'
        )->execute([$token, $idEmpleado]);

        $st = $pdo->prepare(
            'SELECT COALESCE(MAX(c.id_codigo), 0)
               FROM escaner_codigo c JOIN escaner_vinculo v ON v.token = c.token
              WHERE v.id_empleado = ?'
        );
        $st->execute([$idEmpleado]);
        return ['token' => $token, 'desde' => (int)$st->fetchColumn()];
    }

    public function tokenValido(string $token): bool
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) return false;
        $st = Database::conectar()->prepare('SELECT 1 FROM escaner_vinculo WHERE token = ? AND expira > NOW()');
        $st->execute([$token]);
        return (bool)$st->fetchColumn();
    }

    // El celular avisa que está conectado (sin código)
    public function latido(string $token): void
    {
        if (!$this->tokenValido($token)) {
            throw new UnexpectedValueException('El enlace del celular venció. Genera un QR nuevo desde la computadora.');
        }
        Database::conectar()->prepare('UPDATE escaner_vinculo SET ultimo_uso = NOW() WHERE token = ?')->execute([$token]);
    }

    public function registrarCodigo(string $token, string $codigo): void
    {
        $codigo = trim($codigo);
        if (!preg_match('/^[0-9A-Za-z\-._]{3,48}$/', $codigo)) {
            throw new InvalidArgumentException('El código no es válido.');
        }
        if (!$this->tokenValido($token)) {
            throw new UnexpectedValueException('El enlace del celular venció. Genera un QR nuevo desde la computadora.');
        }
        $pdo = Database::conectar();
        $pdo->prepare('UPDATE escaner_vinculo SET ultimo_uso = NOW() WHERE token = ?')->execute([$token]);
        $pdo->prepare('INSERT INTO escaner_codigo (token, codigo, creado) VALUES (?, ?, NOW(3))')->execute([$token, $codigo]);
    }

    // Códigos nuevos de los celulares de este almacenero y si alguno sigue conectado
    public function pendientes(int $idEmpleado, int $desde): array
    {
        $pdo = Database::conectar();
        $st = $pdo->prepare(
            "SELECT c.id_codigo, c.codigo, DATE_FORMAT(c.creado, '%H:%i:%s') AS hora
               FROM escaner_codigo c JOIN escaner_vinculo v ON v.token = c.token
              WHERE v.id_empleado = ? AND c.id_codigo > ?
              ORDER BY c.id_codigo LIMIT 50"
        );
        $st->execute([$idEmpleado, $desde]);
        $codigos = array_map(
            fn($f) => ['id' => (int)$f['id_codigo'], 'codigo' => $f['codigo'], 'hora' => $f['hora']],
            $st->fetchAll()
        );

        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM escaner_vinculo
              WHERE id_empleado = ? AND expira > NOW()
                AND ultimo_uso >= NOW() - INTERVAL ' . self::SEGUNDOS_CONECTADO . ' SECOND'
        );
        $st->execute([$idEmpleado]);
        return ['codigos' => $codigos, 'celular' => (int)$st->fetchColumn() > 0];
    }

    // Escaneos de los celulares de la tienda que el administrador todavía no atendió (últimas 24 horas)
    public function pendientesTienda(int $idTienda): array
    {
        $st = Database::conectar()->prepare(
            "SELECT c.id_codigo, c.codigo, DATE_FORMAT(c.creado, '%H:%i:%s') AS hora,
                    CONCAT(e.nombres, ' ', e.apellidos) AS almacenero
               FROM escaner_codigo c
               JOIN escaner_vinculo v ON v.token = c.token
               JOIN empleado e ON e.id_empleado = v.id_empleado
              WHERE e.id_tienda = ? AND c.procesado = 0 AND c.creado >= NOW() - INTERVAL 1 DAY
              ORDER BY c.id_codigo LIMIT 50"
        );
        $st->execute([$idTienda]);
        return $st->fetchAll();
    }

    // Código de un escaneo pendiente de esta tienda (null si no existe o ya se atendió)
    public function codigoPendiente(int $idCodigo, int $idTienda): ?string
    {
        $st = Database::conectar()->prepare(
            'SELECT c.codigo FROM escaner_codigo c
               JOIN escaner_vinculo v ON v.token = c.token
               JOIN empleado e ON e.id_empleado = v.id_empleado
              WHERE c.id_codigo = ? AND e.id_tienda = ? AND c.procesado = 0'
        );
        $st->execute([$idCodigo, $idTienda]);
        $codigo = $st->fetchColumn();
        return $codigo === false ? null : (string)$codigo;
    }

    public function marcarProcesado(int $idCodigo, int $idTienda): void
    {
        Database::conectar()->prepare(
            'UPDATE escaner_codigo c
               JOIN escaner_vinculo v ON v.token = c.token
               JOIN empleado e ON e.id_empleado = v.id_empleado
                SET c.procesado = 1
              WHERE c.id_codigo = ? AND e.id_tienda = ?'
        )->execute([$idCodigo, $idTienda]);
    }
}
