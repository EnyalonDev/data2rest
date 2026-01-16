<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\BaseController;


/**
 * Login Controller
 * 
 * Handles user authentication including login, logout, and login form display.
 * 
 * Core Features:
 * - Login form rendering
 * - User authentication
 * - Session management
 * - Logout functionality
 * - Automatic redirect for authenticated users
 * 
 * Security:
 * - Password verification via Auth service
 * - Session-based authentication
 * - Redirect prevention for logged-in users
 * - Error message display on failed login
 * 
 * @package App\Modules\Auth
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * LoginController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class LoginController extends BaseController
{
    /**
     * Display login form
     * 
     * Shows the login page. If user is already authenticated,
     * redirects to dashboard.
     * 
     * @return void Renders login view or redirects
     * 
     * @example
     * GET /login
     */
    /**
     * showLoginForm method
     *
     * @return void
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            $this->redirect('');
        }
        $this->view('auth/login', ['title' => 'Login']);
    }

    /**
     * Process login attempt
     * 
     * Validates credentials and creates user session on success.
     * Shows error message on failure.
     * 
     * @return void Redirects to dashboard on success, shows error on failure
     * 
     * @example
     * POST /login
     * Body: username=admin&password=secret
     */
    /**
     * login method
     *
     * @return void
     */
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
        ]);
    }

    /**
     * Logout user
     * 
     * Destroys user session and redirects to login page.
     * 
     * @return void Redirects to login page
     * 
     * @example
     * GET /logout
     */
    /**
     * logout method
     *
     * @return void
     */
    public function logout()
    {
        Auth::logout();
        $this->redirect('login');
    }
}
