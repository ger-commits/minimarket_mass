-- Migración 003: el escaneo del celular queda "pendiente" hasta que el administrador lo atiende.
-- Ejecutar una sola vez, después de migracion_002_escaner.sql.
ALTER TABLE escaner_codigo ADD COLUMN procesado TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE escaner_codigo ADD KEY ix_escaner_codigo_procesado (procesado, id_codigo);
