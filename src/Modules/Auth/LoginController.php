<?php

namespace App\Modules\Auth;

use App\Core\Auth;

class LoginController {
    public function showLoginForm() {
        if (Auth::check()) {
            header('Location: ' . Auth::getBaseUrl());
            exit;
        }
        require_once __DIR__ . '/../../Views/auth/login.php';
    }

    public function login() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (Auth::login($username, $password)) {
            header('Location: ' . Auth::getBaseUrl());
            exit;
        }

        $error = "Invalid username or password";
        require_once __DIR__ . '/../../Views/auth/login.php';
    }

    public function logout() {
        Auth::logout();
    }
}
