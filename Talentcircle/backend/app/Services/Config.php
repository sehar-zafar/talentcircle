<?php

class Config
{
    public static function env(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false || $value === null) return $default;
        return $value;
    }

    public static function getDbDsn(): string
    {
        $host = self::env('DB_HOST', '127.0.0.1');
        $db = self::env('DB_DATABASE', 'talentcircle');
        $port = self::env('DB_PORT', null);
        // MySQL DSN. Port is optional.
        return $port ? "mysql:host={$host};port={$port};dbname={$db}" : "mysql:host={$host};dbname={$db}";
    }

    public static function getDbUser(): string
    {
        return (string) self::env('DB_USERNAME', 'root');
    }

    public static function getDbPass(): string
    {
        $p = self::env('DB_PASSWORD', '');
        return $p === null ? '' : (string)$p;
    }
}

