<?php

namespace Core\Database;

use JsonSerializable;
use ReflectionClass;

abstract class Model implements JsonSerializable
{
    protected ?string $table = null;
    protected string $primaryKey = 'id';
    protected ?string $connection = null;
    protected array $attributes = [];
    protected array $original = [];

    protected array $fillable = [];
    protected static array $allowedOperators = [
        '=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE', 'ILIKE', 'IS', 'IS NOT'
    ];
    protected static array $allowedDirections = ['ASC', 'DESC'];

    public static function validateDirection(string $direction): string
    {
        $dir = strtoupper(trim($direction));
        if (!in_array($dir, static::$allowedDirections, true)) {
            throw new \InvalidArgumentException("Invalid sort direction: [{$direction}]. Only ASC or DESC allowed.");
        }
        return $dir;
    }

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function getTable(): string
    {
        if (!is_null($this->table)) {
            return $this->table;
        }

        $shortName = (new ReflectionClass($this))->getShortName();
        return strtolower($shortName) . 's';
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (!empty($this->fillable) && !in_array($key, $this->fillable, true)) {
                continue;
            }
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    public function forceFill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    public static function escapeIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid database identifier: [{$identifier}]");
        }
        return "`{$identifier}`";
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function all(): array
    {
        $instance = new static();
        $table = static::escapeIdentifier($instance->getTable());
        $rows = Connection::select("SELECT * FROM {$table}", [], $instance->connection);

        return array_map(function ($row) {
            $model = new static();
            $model->forceFill($row);
            $model->original = $row;
            return $model;
        }, $rows);
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $table = static::escapeIdentifier($instance->getTable());
        $pk = static::escapeIdentifier($instance->getPrimaryKey());

        $row = Connection::selectOne("SELECT * FROM {$table} WHERE {$pk} = ? LIMIT 1", [$id], $instance->connection);

        if (!$row) {
            return null;
        }

        $model = new static();
        $model->forceFill($row);
        $model->original = $row;
        return $model;
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): array
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $operator = strtoupper(trim((string) $operator));
        if (!in_array($operator, static::$allowedOperators, true)) {
            throw new \InvalidArgumentException("Unsupported SQL operator: [{$operator}]");
        }

        $instance = new static();
        $table = static::escapeIdentifier($instance->getTable());
        $col = static::escapeIdentifier($column);
        $sql = "SELECT * FROM {$table} WHERE {$col} {$operator} ?";
        $rows = Connection::select($sql, [$value], $instance->connection);

        return array_map(function ($row) {
            $model = new static();
            $model->forceFill($row);
            $model->original = $row;
            return $model;
        }, $rows);
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public function save(): bool
    {
        $table = static::escapeIdentifier($this->getTable());
        $pk = $this->getPrimaryKey();
        $escapedPk = static::escapeIdentifier($pk);
        $pdo = Connection::get($this->connection);

        if (isset($this->attributes[$pk]) && !empty($this->original)) {
            // Update
            $fields = array_keys($this->attributes);
            $setClauses = implode(', ', array_map(fn($f) => static::escapeIdentifier($f) . " = ?", $fields));
            $sql = "UPDATE {$table} SET {$setClauses} WHERE {$escapedPk} = ?";
            $values = array_values($this->attributes);
            $values[] = $this->attributes[$pk];

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($values);
            if ($result) {
                $this->original = $this->attributes;
            }
            return $result;
        } else {
            // Insert
            $fields = array_keys($this->attributes);
            $columns = implode(', ', array_map([static::class, 'escapeIdentifier'], $fields));
            $placeholders = implode(', ', array_fill(0, count($fields), '?'));
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute(array_values($this->attributes));

            if ($result) {
                $lastId = $pdo->lastInsertId();
                if ($lastId) {
                    $this->attributes[$pk] = $lastId;
                }
                $this->original = $this->attributes;
            }
            return $result;
        }
    }

    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function delete(): bool
    {
        $pk = $this->getPrimaryKey();
        if (!isset($this->attributes[$pk])) {
            return false;
        }

        $table = static::escapeIdentifier($this->getTable());
        $escapedPk = static::escapeIdentifier($pk);
        $sql = "DELETE FROM {$table} WHERE {$escapedPk} = ?";
        return Connection::statement($sql, [$this->attributes[$pk]], $this->connection);
    }
}
