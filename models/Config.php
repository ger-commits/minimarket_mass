<?php
// M: configuración. Lee el archivo .env de la raíz del proyecto (o variables de entorno del servidor).
// Si no existe, quedan los valores por defecto que se pasan en cada llamada a Config::get().
class Config
{
    private static ?array $env = null;

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = getenv($clave);                 // una variable de entorno real tiene prioridad
        if ($valor === false) {
            self::$env ??= self::leerArchivo();
            $valor = self::$env[$clave] ?? null;
        }
        return $valor === null ? $porDefecto : (string)$valor;
    }

    private static function leerArchivo(): array
    {
        $ruta = __DIR__ . '/../.env';
        $env = [];
        if (!is_readable($ruta)) return $env;
        foreach (file($ruta, FILE_IGNORE_NEW_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) continue;
            [$k, $v] = explode('=', $linea, 2);
            $env[trim($k)] = trim($v, " \t\"'");
        }
        return $env;
    }
}
