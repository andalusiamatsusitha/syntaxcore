<?php

/**
 * SyntaxCore Database Seeder
 * Run via CLI: php database/seed.php
 */

$baseDir = dirname(__DIR__);

if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
}

$app = require_once $baseDir . '/bootstrap/app.php';

use App\Models\User;

echo "--- SyntaxCore Database Seeder ---\n";

try {
    $email = 'admin@syntaxcore.com';
    $user = User::findByEmail($email);

    if (!$user) {
        $user = new User([
            'name' => 'Administrator',
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $user->setPassword('admin123');
        $user->save();

        echo "[SUCCESS] Default administrator seeded successfully!\n";
        echo "  Email   : {$email}\n";
        echo "  Password: admin123\n";
    } else {
        echo "[INFO] Administrator '{$email}' already exists.\n";
    }
} catch (\Throwable $e) {
    echo "[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
