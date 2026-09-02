<?php

namespace Core\Database;

use Core\Application\Application;
use Core\Application\Container;
use PDO;
use Exception;

class Connection
{
    protected static array $connections = [];

    /**
     * Get or create a PDO connection instance.
     */
    public static function get(?string $name = null): PDO
    {
        /** @var Application $app */
        $app = Container::getInstance();
        $dbConfig = $app->config('database', []);

        $connectionName = $name ?? $dbConfig['default'] ?? 'mysql';

        if (isset(static::$connections[$connectionName])) {
            return static::$connections[$connectionName];
        }

        $config = $dbConfig['connections'][$connectionName] ?? null;

        if (!$config) {
            throw new Exception("Database connection [{$connectionName}] is not configured.");
        }

        $driver = $config['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $dsn = "sqlite:{$config['database']}";
            $pdo = new PDO($dsn, null, null, $config['options'] ?? [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } elseif ($driver === 'mysql') {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } else {
            throw new Exception("Database driver [{$driver}] is not supported.");
        }

        static::$connections[$connectionName] = $pdo;
        return $pdo;
    }

    /**
     * Execute a raw SQL statement with bindings.
     */
    public static function statement(string $sql, array $bindings = [], ?string $connection = null): bool
    {
        $stmt = static::get($connection)->prepare($sql);
        return $stmt->execute($bindings);
    }

    /**
     * Run a select query and return all results.
     */
    public static function select(string $sql, array $bindings = [], ?string $connection = null): array
    {
        $stmt = static::get($connection)->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Run a select query and return a single row.
     */
    public static function selectOne(string $sql, array $bindings = [], ?string $connection = null): ?array
    {
        $stmt = static::get($connection)->prepare($sql);
        $stmt->execute($bindings);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
