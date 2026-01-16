<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use App\Core\Lang;
use PDO;

/**
 * Profile Controller
 * 
 * Handles user profile management including viewing and updating
 * personal information and password changes.
 * 
 * Core Features:
 * - View user profile
 * - Update personal information
 * - Change password
 * - Email and contact management
 * - Session-based user identification
 * 
 * User Fields:
 * - public_name (display name)
 * - email (contact email)
 * - phone (contact phone)
 * - address (physical address)
 * - password (optional update)
 * 
 * Security:
 * - Login required for all operations
 * - Password hashing with PASSWORD_DEFAULT
 * - Session-based user identification
 * - Users can only edit their own profile
 * 
 * @package App\Modules\Auth
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
/**
 * ProfileController Controller
 *
 * Core Features: TODO
 *
 * Security: Requires login, permission checks as implemented.
 *
 * @package App\Modules\
 * @author DATA2REST Development Team
 * @version 1.0.0
 */
class ProfileController extends BaseController
{
    /**
     * Constructor - Requires user authentication
     * 
     * Ensures that only authenticated users can access
     * profile management functionality.
     */
/**
 * __construct method
 *
 * @return void
 */
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Display user profile form
     * 
     * Shows the profile editing form with current user information
     * loaded from the database.
     * 
     * Features:
     * - Loads current user data
     * - Displays editable profile fields
     * - Internationalized interface
     * 
     * @return void Renders profile view
     * 
     * @example
     * GET /admin/profile
     */
/**
 * index method
 *
 * @return void
 */
    public function index()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        $this->view('admin/profile/index', [
            'user' => $user,
            'title' => Lang::get('profile.title', 'Mi Perfil'),
            'breadcrumbs' => [
                Lang::get('common.home', 'Inicio') => 'admin/dashboard',
                Lang::get('profile.title', 'Mi Perfil') => null
            ]
        ]);
    }

    /**
     * Save user profile information
     * 
     * Updates user profile data including optional password change.
     * Only updates password if new password is provided.
     * 
     * Features:
     * - Update personal information
     * - Optional password change
     * - Password hashing for security
     * - Success/error flash messages
     * 
     * Security:
     * - Session-based user identification
     * - Password hashing with PASSWORD_DEFAULT
     * - Users can only update their own profile
     * 
     * @return void Redirects to profile page with status message
     * 
     * @example
     * POST /admin/profile/save
     * Body: public_name=John&email=john@example.com&new_password=secret
     */
/**
 * save method
 *
 * @return void
 */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return;

        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];

        $public_name = $_POST['public_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        try {
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET public_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                $stmt->execute([$public_name, $email, $phone, $address, $password_hash, $userId]);
            } else {
                $stmt = $db->prepare("UPDATE users SET public_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$public_name, $email, $phone, $address, $userId]);
            }

            // Update session if needed (e.g., if we display public_name in UI)
            // $_SESSION['public_name'] = $public_name;

            Auth::setFlashError("Perfil actualizado correctamente.", 'success');
        } catch (\PDOException $e) {
            Auth::setFlashError("Error al actualizar el perfil: " . $e->getMessage());
        }

        header('Location: ' . Auth::getBaseUrl() . 'admin/profile');
        exit;
    }
}
