<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    protected ?User $currentUser = null;

    /**
     * Ensure PHP session is active.
     */
    public function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Attempt to authenticate a user using credentials.
     */
    public function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail(trim($email));

        if (!$user || !$user->verifyPassword($password)) {
            return false;
        }

        $this->login($user);
        return true;
    }

    /**
     * Log a user into the session.
     */
    public function login(User $user): void
    {
        $this->ensureSessionStarted();

        // Regenerate session ID to prevent session fixation attacks
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['auth']['user_id'] = $user->id;
        $this->currentUser = $user;
    }

    /**
     * Check if a user is currently authenticated.
     */
    public function check(): bool
    {
        $this->ensureSessionStarted();
        return !empty($_SESSION['auth']['user_id']);
    }

    /**
     * Check if the visitor is a guest.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the authenticated user ID.
     */
    public function id(): ?int
    {
        $this->ensureSessionStarted();
        return isset($_SESSION['auth']['user_id']) ? (int) $_SESSION['auth']['user_id'] : null;
    }

    /**
     * Get the currently authenticated user model.
     */
    public function user(): ?User
    {
        if ($this->currentUser) {
            return $this->currentUser;
        }

        $id = $this->id();
        if (!$id) {
            return null;
        }

        return $this->currentUser = User::find($id);
    }

    /**
     * Log the user out and clean session safely.
     */
    public function logout(): void
    {
        $this->ensureSessionStarted();

        if (isset($_SESSION['auth'])) {
            unset($_SESSION['auth']['user_id']);
            if (empty($_SESSION['auth'])) {
                unset($_SESSION['auth']);
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
        $this->currentUser = null;
    }
}
