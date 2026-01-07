<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\BaseController;

class LoginController extends BaseController
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $this->redirect('');
        }
        $this->view('auth/login', ['title' => 'Login'], null);
    }

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (Auth::login($username, $password)) {
            $this->redirect('');
        }

        $this->view('auth/login', [
            'title' => 'Login',
            'error' => "Invalid username or password"
        ], null);
    }

    public function logout()
    {
        Auth::logout();
        $this->redirect('login');
    }
}
