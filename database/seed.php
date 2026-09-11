<?php

/**
 * SyntaxCore Database Seeder
 * Run via CLI: php database/seed.php
 * 
 * Supports environment variables:
 * - SEED_ADMIN_EMAIL: Administrator email address
 * - SEED_ADMIN_PASSWORD: Administrator password
 * - SEED_ADMIN_NAME: Administrator display name
 */

$baseDir = dirname(__DIR__);

if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
}

$app = require_once $baseDir . '/bootstrap/app.php';

use App\Models\User;

echo "--- SyntaxCore Database Seeder ---\n";

try {
    $email = getenv('SEED_ADMIN_EMAIL') ?: 'admin@syntaxcore.com';
    $password = getenv('SEED_ADMIN_PASSWORD') ?: 'admin123';
    $name = getenv('SEED_ADMIN_NAME') ?: 'Administrator';

    $user = User::findByEmail($email);

    if (!$user) {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $user->setPassword($password);
        $user->save();

        echo "[SUCCESS] Administrator seeded successfully!\n";
        echo "  Name    : {$name}\n";
        echo "  Email   : {$email}\n";
        echo "  Password: [PROTECTED / CONFIGURABLE VIA SEED_ADMIN_PASSWORD]\n";
    } else {
        echo "[INFO] Administrator '{$email}' already exists.\n";
    }
} catch (\Throwable $e) {
    echo "[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
