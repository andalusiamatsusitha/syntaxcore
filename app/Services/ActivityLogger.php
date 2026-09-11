<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Notification;
use Core\Application\Container;
use Core\Http\Request;

class ActivityLogger
{
    /**
     * Catat entri log aktivitas baru ke database.
     */
    public static function log(
        string $action,
        string $description,
        ?int $userId = null,
        ?Request $request = null
    ): ActivityLog {
        if ($userId === null) {
            try {
                /** @var AuthService $auth */
                $auth = Container::getInstance()->make(AuthService::class);
                $userId = $auth->id();
            } catch (\Throwable) {
                $userId = null;
            }
        }

        $ip = null;
        $userAgent = null;

        if ($request) {
            $ip = $request->header('X-Forwarded-For') 
                ?? $request->server['REMOTE_ADDR'] 
                ?? '127.0.0.1';
            $userAgent = $request->header('User-Agent') 
                ?? $request->server['HTTP_USER_AGENT'] 
                ?? null;
        } else {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] 
                ?? $_SERVER['REMOTE_ADDR'] 
                ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        }

        $log = new ActivityLog([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => substr((string) $ip, 0, 45),
            'user_agent' => substr((string) $userAgent, 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $log->save();

        return $log;
    }

    /**
     * Kirim notifikasi sistem baru.
     */
    public static function notify(
        string $title,
        string $message,
        string $type = 'info',
        ?int $userId = null
    ): Notification {
        $notif = new Notification([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        return $notif;
    }
}
