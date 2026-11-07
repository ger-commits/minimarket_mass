<?php
// M: tabla caja. Cada terminal tiene una caja fija (id_caja en el .env); aquí se lee su número y su estado.
require_once __DIR__ . '/Database.php';

class CajaRepository
{
    // devuelve la caja con su estado y si tiene un turno ABIERTO, o null si no existe
    public function buscar(int $idCaja): ?array
    {
        $st = Database::conectar()->prepare(
            "SELECT c.id_caja, c.numero_caja, c.estado, c.id_tienda,
                    EXISTS (SELECT 1 FROM caja_turno ct
                             WHERE ct.id_caja = c.id_caja AND ct.estado = 'ABIERTO') AS turno_abierto
               FROM caja c
              WHERE c.id_caja = ?"
        );
        $st->execute([$idCaja]);
        return $st->fetch() ?: null;
    }
}
