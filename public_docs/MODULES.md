# Data2Rest Modules Documentation

Data2Rest is a powerful backend-as-a-service and system management platform. Below is a summary of the core modules included in this repository.

---

## 🔐 Auth Module
Handles user authentication, session management, and role-based access control (RBAC).
- **Features**: 
  - JWT System Authentication.
  - Project-based user isolation.
  - Google OAuth 2.0 Integration.
  - Email Verification flow.
  - Profile management.

## 🗄️ Database Module
The core engine for managing multiple database connections (MySQL, PostgreSQL, SQLite).
- **Features**:
  - **CRUD GUI**: Automatic interface generation for any connected table.
  - **Dynamic Paginator**: Efficiently browse large datasets with configurable page sizes.
  - **Schema Explorer**: Visualize and manage table structures.
  - **History/Versions**: Track changes made to records (audit logs).
  - **Recycle Bin**: Soft-delete system for record recovery.

## 🔌 API Module
Automatically exposes your databases as RESTful APIs.
- **Features**:
  - **API Keys**: Manage access tokens with rate limiting.
  - **Auto-Docs**: Interactive Swagger/OpenAPI documentation generated on the fly.
  - **Permissions**: Fine-grained control over which API keys can access which tables/methods.

## 💳 Billing Module
Infrastructure for project billing and service management.
- **Features**:
  - Service catalogs and templates.
  - Project management and resource quotas.
  - Installment generation and payment tracking.

## 📦 System & Maintenance
Core tools for keeping the system healthy and up-to-date.
- **Features**:
  - **Backups**: Database and file system backup management.
  - **Logs**: Centralized system logging for diagnostics.
  - **Media**: Secure file storage and management.
  - **Webhooks**: Event-driven notifications for third-party integrations.

---

## 🛠️ Usage
For more information on how to integrate these modules, refer to the [API Guide](docs/API_GUIDE.md) (Internal) or use the built-in API Explorer in the Admin Dashboard.

*Desarrollado por [portafoliocreativo.com](https://portafoliocreativo.com)*
