<?php

namespace App\Controllers\Admin;

use App\Services\AuthService;
use Core\Controller\Controller;
use Core\Http\Request;
use Core\Http\Response;

class AuthController extends Controller
{
    public function __construct(protected AuthService $auth)
    {
    }

    /**
     * Show the admin login form.
     */
    public function showLogin(): Response
    {
        return $this->view('admin.auth.login');
    }

    /**
     * Process an admin login attempt.
     */
    public function login(Request $request): Response
    {
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        // Basic validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            return $this->view('admin.auth.login', [
                'error' => 'Invalid credentials.',
                'oldEmail' => $email,
            ], 422);
        }

        if (!$this->auth->attempt($email, $password)) {
            return $this->view('admin.auth.login', [
                'error' => 'Invalid credentials.',
                'oldEmail' => $email,
            ], 422);
        }

        return $this->redirect('/admin');
    }

    /**
     * Log the admin out and redirect to login.
     */
    public function logout(): Response
    {
        $this->auth->logout();
        return $this->redirect('/admin/login');
    }
}
