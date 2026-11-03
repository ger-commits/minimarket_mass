<?php
// M: conexión PDO a la base minimarket_mass (MySQL 8). Los datos salen del .env (ver .env.example).
require_once __DIR__ . '/Config.php';

class Database
{
    private static ?PDO $pdo = null;

    public static function conectar(): PDO
    {
        if (self::$pdo === null) {
            $host = Config::get('DB_HOST', 'localhost');
            $base = Config::get('DB_NAME', 'minimarket_mass');
            try {
                self::$pdo = new PDO(
                    "mysql:host=$host;dbname=$base;charset=utf8mb4",
                    Config::get('DB_USER', 'root'),
                    Config::get('DB_PASS', ''),
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,   // consultas preparadas de verdad en MySQL
                    ]
                );
            } catch (PDOException $e) {
                // el detalle (host, usuario) va al log del servidor, nunca al navegador
                error_log('No se pudo conectar a la base de datos: ' . $e->getMessage());
                throw new RuntimeException('No se pudo conectar con la base de datos.');
            }
        }
        return self::$pdo;
    }
}
