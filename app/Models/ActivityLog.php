<?php

namespace App\Models;

use Core\Database\Model;

class ActivityLog extends Model
{
    protected ?string $table = 'activity_logs';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Ambil objek User pembuat aktivitas.
     */
    public function user(): ?User
    {
        if (empty($this->user_id)) {
            return null;
        }
        return User::find((int) $this->user_id);
    }
}
