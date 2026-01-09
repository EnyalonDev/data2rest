# 🚀 Data2Rest - Database Management System & REST APIs

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

**Data2Rest** was born from a real need: to accelerate backend development for web and mobile applications. My goal was that the moment I designed the database, the necessary endpoints would be ready immediately. From that vision, this simple and practical system emerged, ideal for independent developers, students, and small teams who need to launch MVPs or production prototypes in minutes.

With Data2Rest, the backend adapts to your idea and not the other way around. Forget about searching for generic API examples that don't fit your project; here you design your data structure, and the system automatically generates REST endpoints ready to be consumed from any client.

### 🎯 Project Goal
To provide an open-source tool that eliminates initial friction when creating backends, reducing repetitive errors and allowing developers to focus on what really matters: their data design and business logic.

**Ideal for:**
*   👨‍💻 **Independent Developers**: Create prototypes and full apps without writing boilerplate.
*   🎓 **Students**: Learn about data structures and APIs by seeing immediate results.
*   🚀 **MVPs and Startups**: Validate your business ideas with a functional backend in record time.
*   👥 **Small Teams**: Improve productivity by sharing a unified data core.

---

## 📋 Table of Contents

- [Main Features](#-main-features)
- [System Requirements](#-system-requirements)
- [Installation](#-installation)
- [System Architecture](#-system-architecture)
- [Modules](#-modules)
- [Configuration](#-configuration)
- [Basic Usage](#-basic-usage)
- [Security](#-security)
- [Contributing](#-contributing)
- [License](#-license)
- [Credits](#-credits)

---

## ✨ Main Features

### 🗄️ Database Management
- **Dynamic creation** of SQLite databases
- **Visual management** of tables and fields
- **Full CRUD** with intuitive interface
- **Field configuration** with custom data types
- **Integrated file** and media management

### 🔌 Automatic REST API
- **Automatic generation** of REST endpoints for each table
- **Interactive documentation** Swagger-style
- **Authentication via API Keys**
- **Full support** for GET, POST, PUT, PATCH, DELETE
- **Filtering and pagination** of results

### 🔐 Authentication and Authorization System
- **Secure login** with PHP sessions
- **Role-Based Access Control** (RBAC) with inheritance
- **Team Isolation**: Strict visibility of users by Group
- **Policy Architect**: Visual definition of permissions (`delete_users`, `crud_create`, etc.)
- **Flash message system** with elegant modals

### 🎨 Modern Interface
- **Dark mode design** with glassmorphism effects
- **Responsive design** optimized for mobile
- **Smooth animations** and micro-interactions
- **Tailwind CSS** for consistent styling
- **Premium typography** with Google Fonts (Outfit)

---

## 💻 System Requirements

- **PHP**: 8.0 or higher
- **SQLite**: 3.x
- **Apache**: 2.4+ with mod_rewrite enabled
- **Required PHP Extensions**:
  - `pdo_sqlite`
  - `session`
  - `json`

---

## 🚀 Installation

### Automatic Installation (Recommended)

1. **Clone or download** the project to your web server:
   ```bash
   cd /path/to/webserver/
   git clone <repository-url> data2rest
   ```

2. **Configure Apache** to allow `.htaccess`:
   ```apache
   <Directory "/path/to/webserver/data2rest">
       AllowOverride All
       Require all granted
   </Directory>
   ```

3. **Restart Apache**:
   ```bash
   # Example for brew on Mac
   brew services restart httpd
   ```

4. **Access the application** in your browser:
   ```
   http://localhost/data2rest/
   ```

5. **Automatic Installation**: The system will detect it's the first time and automatically create:
   - System database (`data/system.sqlite`)
   - Default administrator user
   - Necessary table structure

### Default Credentials

After the automatic installation is complete, you can access with the following credentials:

```
Username: admin
Password: admin123
```

⚠️ **SECURITY NOTICE**: Even though it seems like an obvious step, it is **strongly recommended to change the password** immediately after your first login to protect the integrity of your system and your data.

---

## 🏗️ System Architecture

```
data2rest/
├── public/                 # Public entry point
│   ├── index.php          # Main router
│   └── uploads/           # Uploaded files
├── src/
│   ├── Core/              # Core system
│   │   ├── Auth.php       # Authentication and authorization
│   │   ├── Config.php     # Global configuration
│   │   ├── Database.php   # DB Connection
│   │   ├── Installer.php  # Automatic Installer
│   │   └── Router.php     # Routing system
│   ├── Modules/           # Functional modules
│   │   ├── Api/           # → See docs/API.md
│   │   ├── Auth/          # → See docs/AUTH.md
│   │   └── Database/      # → See docs/DATABASE.md
│   └── Views/             # Views and templates
│       ├── admin/         # Control panel
│       ├── auth/          # Authentication views
│       └── partials/      # Reusable components
├── data/                  # System databases
│   └── system.sqlite      # Main DB
└── docs/                  # Detailed documentation
```

---

## 📦 Modules

The system is organized into independent and well-documented modules:

### 1. [REST API Module](docs/API.md)
Automatic generation of REST endpoints with interactive documentation and multi-platform examples.
- REST Controllers (GET, POST, PUT, DELETE)
- API Key management with security validation
- Dynamic documentation with practical examples
- **Examples included**: cURL, JavaScript, Python

### 2. [Authentication Module](docs/AUTH.md)
Complete system for login, users, roles, and granular permissions.
- User profile management
- Policy Architect (Permissions per table and action)
- Workgroups and hierarchies
- **Use cases**: Creation of restricted roles, team management

### 3. [Database Module](docs/DATABASE.md)
Comprehensive visual management of SQLite databases and data flows.
- Schema design (Tables and Columns)
- Advanced data types and upload interfaces
- Dynamic CRUD with validations
- **Tutorials**: Relationship configuration, multimedia file management

---

## ⚙️ Configuration

### Configuration File

The file `src/Core/Config.php` contains the main configuration:

```php
private static $config = [
    'db_path' => __DIR__ . '/../../data/system.sqlite',
    'app_name' => 'Data2Rest',
    'base_url' => '',
    'upload_dir' => __DIR__ . '/../../public/uploads/',
    'allowed_roles' => ['admin', 'user'],
];
```

### Configurable Variables

- **db_path**: Path to the system database
- **app_name**: Application name
- **upload_dir**: Directory for uploaded files
- **allowed_roles**: Roles allowed in the system

---

## 📖 Basic Usage

### 1. Create a Database

1. Access **Databases** from the main menu
2. Fill out the "Initialize New Node" form
3. Enter name and description
4. Click "Create Database"

### 2. Create Tables

1. Select a database
2. Click "View Tables"
3. Enter the table name
4. Click "Create Table"

### 3. Configure Fields

1. Click on the configuration icon (⚙️) for the table
2. Add fields with their data types
3. Configure special options (file upload, textarea, etc.)

### 4. Manage Data (CRUD)

1. Click "Enter Segment" on a table
2. Use the "New Entry" button to create records
3. Edit or delete existing records

### 5. Generate REST API

APIs are automatically generated for each table:

```
GET    /api/v1/{database}/{table}        # List all
GET    /api/v1/{database}/{table}/{id}   # Get one
POST   /api/v1/{database}/{table}        # Create
PUT    /api/v1/{database}/{table}/{id}   # Full update
PATCH  /api/v1/{database}/{table}/{id}   # Partial update
DELETE /api/v1/{database}/{table}/{id}   # Delete
```

### 6. View API Documentation

1. Access **API Docs** from the menu
2. Select a database
3. Consult endpoints and usage examples

---

## 🔒 Security

### Implemented Best Practices

✅ **Session authentication** with native PHP
✅ **SQL Prepared Statements**
✅ **HTML escaping** on all outputs
✅ **Permission validation** for every action
✅ **API Keys** for REST endpoint access
✅ **Role-Based Access Control** (RBAC)

---

## 🤝 Contributing

Contributions are welcome. Please:

1. Fork the project
2. Create a branch for your feature (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is under the MIT License. See the `LICENSE` file for more details.

---

## 👨‍💻 Credits

**Developed by:** **EnyalonDev - Néstor Ovallos Cañas**

- 🌐 Website: [nestorovallos.com](https://nestorovallos.com)
- 📧 Email: contacto@nestorovallos.com
- 💼 LinkedIn: [Néstor Ovallos](https://linkedin.com/in/nestorovallos)

---

## 🆘 Support

If you find any issues or have questions:

1. Review the [module documentation](docs/)
2. Open an [Issue](https://github.com/your-user/data2rest/issues)
3. Contact the developer

---

**Thank you for using Data2Rest!** 🚀

---

## 🚧 TODOs and Proposed Improvements

### 🎯 High Priority

- [ ] **Multi-Database Engine Support**
  - Drivers for **MySQL, PostgreSQL, and MariaDB**
  - Transparent migration between engines
  - Remote database support
  - External connection settings panel

- [ ] **Automatic Backup System**
  - Scheduled database backups
  - SQL/JSON export
  - Restore from backups
  - Cloud storage (S3, Google Cloud)

### 🔧 Medium Priority

- [ ] **Data Export**
  - Export tables to CSV/Excel
  - Export full databases
  - Bulk import from files

... (Other TODOs can remain as they are or be simplified)

---

## 💬 Contributions

Ideas to improve the project?

1. Review the TODOs list
2. Open an Issue to discuss the improvement
3. Create a Pull Request with your implementation

---
