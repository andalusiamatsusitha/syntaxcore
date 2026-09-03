<?php

namespace App\Services;

use App\Models\User;
use Core\Security\Csrf;
use Core\Session\Session;

class AuthService
{
    protected ?User $currentUser = null;

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
        // 1. Regenerate session ID to prevent session fixation attacks
        Session::regenerate(true);

        // 2. Regenerate CSRF token on authentication state transition
        Csrf::regenerateToken();

        // 3. Store authentication identifier
        Session::set('auth.user_id', $user->id);
        $this->currentUser = $user;
    }

    /**
     * Check if a user is currently authenticated.
     */
    public function check(): bool
    {
        return !empty(Session::get('auth.user_id'));
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
        $id = Session::get('auth.user_id');
        return $id ? (int) $id : null;
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
     * Log the user out, clean authentication state, and reset CSRF token.
     */
    public function logout(): void
    {
        // 1. Remove authentication identity
        Session::remove('auth.user_id');

        // 2. Regenerate session ID
        Session::regenerate(true);

        // 3. Regenerate CSRF token for new anonymous session
        Csrf::regenerateToken();

        $this->currentUser = null;
    }
}
