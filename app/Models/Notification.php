<?php

namespace App\Models;

use Core\Database\Model;

class Notification extends Model
{
    protected ?string $table = 'notifications';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'created_at',
    ];

    /**
     * Ambil objek User penerima notifikasi (null jika global).
     */
    public function user(): ?User
    {
        if (empty($this->user_id)) {
            return null;
        }
        return User::find((int) $this->user_id);
    }

    /**
     * Tandai notifikasi sebagai telah dibaca.
     */
    public function markAsRead(): bool
    {
        $this->is_read = 1;
        return $this->save();
    }
}
