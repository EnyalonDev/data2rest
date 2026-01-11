<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Database;
use App\Core\BaseController;
use App\Core\Lang;
use PDO;

/**
 * User Profile Controller
 * Handles user profile viewing and updating.
 */
class ProfileController extends BaseController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Display the user profile form.
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
     * Save the user profile information.
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
