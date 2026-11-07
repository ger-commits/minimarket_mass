<?php
// Escáner de celular del CAJERO (cable USB). El enlace y los códigos se guardan en la base de datos.
// Es independiente del escáner del almacén (EscanerRepository), que usa otras tablas.
require_once __DIR__ . '/Database.php';

class EscanerCajaRepository
{
    private const HORAS_VALIDEZ      = 12;   // el enlace dura 12 horas
    private const SEGUNDOS_CONECTADO = 25;   // el celular cuenta como conectado si avisó hace menos de 25 s
    private const MAX_PENDIENTES     = 50;   // códigos guardados por caja

    // Genera un enlace nuevo; el anterior (y su QR) deja de funcionar.
    public function vincular(int $idCaja, int $idEmpleado): string
    {
        $pdo = Database::conectar();
        $token = bin2hex(random_bytes(16));
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO escaner_caja_vinculo (id_caja, token, id_empleado, creado, visto, seq)
                 VALUES (?, ?, ?, NOW(), NULL, 0)
                 ON DUPLICATE KEY UPDATE token = VALUES(token), id_empleado = VALUES(id_empleado),
                                         creado = NOW(), visto = NULL, seq = 0'
            )->execute([$idCaja, $token, $idEmpleado]);
            $pdo->prepare('DELETE FROM escaner_caja_codigo WHERE id_caja = ?')->execute([$idCaja]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $token;
    }

    public function desvincular(int $idCaja): void
    {
        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM escaner_caja_codigo WHERE id_caja = ?')->execute([$idCaja]);
            $pdo->prepare('DELETE FROM escaner_caja_vinculo WHERE id_caja = ?')->execute([$idCaja]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Enlace de la caja si todavía está vigente (o null).
    private function vigente(PDO $pdo, int $idCaja): ?array
    {
        $st = $pdo->prepare(
            'SELECT token, seq, TIMESTAMPDIFF(SECOND, visto, NOW()) AS hace
               FROM escaner_caja_vinculo
              WHERE id_caja = ? AND creado > NOW() - INTERVAL ' . self::HORAS_VALIDEZ . ' HOUR'
        );
        $st->execute([$idCaja]);
        $f = $st->fetch(PDO::FETCH_ASSOC);
        return $f ?: null;
    }

    public function valido(int $idCaja, string $token): bool
    {
        $f = $this->vigente(Database::conectar(), $idCaja);
        return $f !== null && hash_equals((string)$f['token'], $token);
    }

    // Lo que la caja consulta cada segundo: si hay enlace, si el celular está conectado y los códigos nuevos (seq > $desde).
    public function estado(int $idCaja, int $desde): array
    {
        $pdo = Database::conectar();
        $f = $this->vigente($pdo, $idCaja);
        if ($f === null) {
            return ['vinculado' => false, 'conectado' => false, 'seq' => 0, 'codigos' => []];
        }
        $st = $pdo->prepare('SELECT seq, codigo FROM escaner_caja_codigo WHERE id_caja = ? AND seq > ? ORDER BY seq');
        $st->execute([$idCaja, $desde]);
        $codigos = array_map(
            fn($r) => ['seq' => (int)$r['seq'], 'codigo' => (string)$r['codigo']],
            $st->fetchAll(PDO::FETCH_ASSOC)
        );
        return [
            'vinculado' => true,
            'conectado' => $f['hace'] !== null && (int)$f['hace'] < self::SEGUNDOS_CONECTADO,
            'seq'       => (int)$f['seq'],
            'codigos'   => $codigos,
        ];
    }

    // Lo que manda el celular: un código leído, o solo un aviso de que sigue conectado ($soloAviso).
    public function enviar(int $idCaja, string $token, string $codigo, bool $soloAviso): void
    {
        $pdo = Database::conectar();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'SELECT token, seq FROM escaner_caja_vinculo
                  WHERE id_caja = ? AND creado > NOW() - INTERVAL ' . self::HORAS_VALIDEZ . ' HOUR FOR UPDATE'
            );
            $st->execute([$idCaja]);
            $f = $st->fetch(PDO::FETCH_ASSOC);
            if (!$f || !hash_equals((string)$f['token'], $token)) {
                throw new RuntimeException('El enlace con la caja venció. Vuelva a vincular el celular desde la caja.', 410);
            }
            $pdo->prepare('UPDATE escaner_caja_vinculo SET visto = NOW() WHERE id_caja = ?')->execute([$idCaja]);
            if (!$soloAviso) {
                if (!preg_match('/^[0-9A-Za-z.\-]{3,40}$/', $codigo)) {
                    throw new InvalidArgumentException('Código no válido.');
                }
                $seq = (int)$f['seq'] + 1;
                $pdo->prepare('UPDATE escaner_caja_vinculo SET seq = ? WHERE id_caja = ?')->execute([$seq, $idCaja]);
                $pdo->prepare('INSERT INTO escaner_caja_codigo (id_caja, seq, codigo, hora) VALUES (?, ?, ?, NOW())')
                    ->execute([$idCaja, $seq, $codigo]);
                $pdo->prepare('DELETE FROM escaner_caja_codigo WHERE id_caja = ? AND seq <= ?')
                    ->execute([$idCaja, $seq - self::MAX_PENDIENTES]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
