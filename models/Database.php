<?php
// M: conexión PDO a la base minimarket_mass (MySQL 8)
class Database
{
    private static ?PDO $pdo = null;

    public static function conectar(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                'mysql:host=localhost;dbname=minimarket_mass;charset=utf8mb4',
                'root', '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        }
        return self::$pdo;
    }
}
