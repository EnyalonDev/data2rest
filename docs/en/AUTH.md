# 🔐 Authentication Module

[← Back to Main README](../../README.en.md)

## 📋 Description

The **Authentication Module** provides a complete system for login, user management, roles, and permissions based on RBAC (Role-Based Access Control).

---

## 📁 Module Structure

```
src/Modules/Auth/
├── LoginController.php     # Login/logout management
├── UserController.php      # User CRUD
└── RoleController.php      # Role and permission management
```

---

## ✨ Features

### 🔑 Login System
- Secure authentication with PHP sessions
- Credential validation
- Feedback via flash messages

### 👥 User Management
- Create, edit, and delete users
- Role assignment
- Granular permissions per database
- User listing and search

### 🛡️ Access Control (RBAC) - Policy Architect
- Customizable roles (admin, user, etc.)
- **Policy Architect**: Visual interface to define granular permissions.
- Specific permissions per resource (CRUD per table).
- Validation on every action.
- User groups.

---

## 🚀 Usage

### 1. Login
Access `/login` and enter your credentials:
```
Username: admin
Password: admin123
```

### 2. Implementation Examples

#### Permission Verification in PHP
```php
use App\Core\Auth;

// Require user to be logged in
Auth::requireLogin();

// Require specific permission for a database
Auth::requireDatabaseAccess($db_id);

// Check if they have manage permission for a module
if (Auth::hasPermission("module:api", "manage")) {
    // Perform administrative action
}
```

#### JSON Policy Structure (Policy Architect)
```json
{
  "all": false,
  "modules": {
    "databases": ["view", "manage"],
    "api": ["view"]
  },
  "databases": {
    "1": ["read", "insert", "update"],
    "2": ["view"]
  }
}
```

---

## 🔒 Security

### Sessions
Sessions are handled with native PHP and stored securely.

### Permissions
The system verifies permissions for every action:
```php
Auth::requirePermission("db:1", "write");
```

### Password Hashing
Passwords are hashed using PHP's `password_hash()`.

---

[← Back to Main README](../../README.en.md)
