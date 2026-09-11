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
        'role_id',
        'created_at',
        'updated_at',
    ];

    protected ?Role $cachedRole = null;

    /**
     * Find a user by their email address.
     */
    public static function findByEmail(string $email): ?static
    {
        $results = static::where('email', '=', $email);
        return $results[0] ?? null;
    }

    /**
     * Get the associated Role model.
     */
    public function role(): ?Role
    {
        if ($this->cachedRole !== null) {
            return $this->cachedRole;
        }

        if (empty($this->role_id)) {
            return null;
        }

        return $this->cachedRole = Role::find($this->role_id);
    }

    /**
     * Get the role slug string (e.g. 'superadmin', 'admin', 'user').
     */
    public function roleSlug(): string
    {
        return $this->role()?->slug ?? 'user';
    }

    /**
     * Get numeric user level (higher number = higher permission).
     */
    public function roleLevel(): int
    {
        return (int) ($this->role()?->level ?? 1);
    }

    /**
     * Check if the user matches one or more role slugs.
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->roleSlug(), $roles, true);
    }

    /**
     * Check if user meets or exceeds a minimum numeric level.
     */
    public function hasMinLevel(int $minLevel): bool
    {
        return $this->roleLevel() >= $minLevel;
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
        $attributes['role'] = $this->role()?->toArray();
        $attributes['role_slug'] = $this->roleSlug();
        $attributes['role_name'] = $this->role()?->name ?? 'User';
        return $attributes;
    }
}
