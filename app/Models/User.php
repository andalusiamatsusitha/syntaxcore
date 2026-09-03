<?php

namespace App\Models;

use Core\Database\Model;

class User extends Model
{
    protected ?string $table = 'users';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'created_at',
        'updated_at',
    ];

    /**
     * Find a user by their email address.
     */
    public static function findByEmail(string $email): ?static
    {
        $results = static::where('email', '=', $email);
        return $results[0] ?? null;
    }

    /**
     * Set a hashed password.
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify if the provided password matches the stored hash.
     */
    public function verifyPassword(string $password): bool
    {
        if (empty($this->password)) {
            return false;
        }

        return password_verify($password, $this->password);
    }

    /**
     * Hide sensitive attributes when serializing to array or JSON.
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();
        unset($attributes['password']);
        return $attributes;
    }
}
