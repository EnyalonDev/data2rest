# 🗄️ Database Module

[← Back to Main README](../../README.en.md)

## 📋 Description

The **Database Module** allows you to visually create and manage SQLite databases, with full support for CRUD operations, field configuration, and table management.

---

## 📁 Module Structure

```
src/Modules/Database/
├── DatabaseController.php  # Database and table management
└── CrudController.php      # CRUD operations on records
```

---

## ✨ Features

### 📦 Database Management
- Create new SQLite databases
- List existing databases
- Delete databases
- View detailed information

### 📋 Table Management
- Create tables dynamically
- Configure fields with data types
- Delete tables
- View table structure

### ✏️ CRUD Operations
- Create records
- Read/List records
- Update records
- Delete records
- Search and filtering

### 🎨 Field Configuration
- Data types: TEXT, INTEGER, REAL, BLOB
- Special fields: file, textarea, checkbox, date, time
- Custom validations
- Default values

---

## 🚀 Usage

### 1. Create a Database
1. Go to **Databases**
2. Fill out the "Initialize New Node" form
3. Enter name and description
4. Click "Create Database"

### 2. Create Tables
1. Select a database
2. Click "View Tables"
3. Enter the table name
4. Click "Create Table"

### 3. Configure Fields
1. Click on the ⚙️ icon for the table
2. Add fields:
   - **Field Name**: name of the field
   - **Type**: data type (TEXT, INTEGER, etc.)
   - **Special**: special options (file, textarea)
3. Save the configuration

### 4. Manage Data
1. Click "Enter Segment"
2. Use "New Entry" to create records
3. Edit with the "Edit" button
4. Delete with the "Kill" button

---

## 🔧 Controllers

### DatabaseController.php
**Main methods:**
- `index()` - List all databases
- `create()` - Create new database
- `delete()` - Delete database
- `viewTables()` - Show tables of a DB
- `createTable()` - Create new table
- `deleteTable()` - Delete table
- `fields()` - Manage table fields

### CrudController.php
**Main methods:**
- `list()` - List records of a table
- `form()` - Create/edit form
- `save()` - Save record
- `delete()` - Delete record
- `mediaList()` - Manage uploaded files

---

## 📚 Examples

### Relationship Configuration (Foreign Keys)
The system allows linking tables to create complex relational structures:

1. **Target Table**: `categories` (id, name)
2. **Source Table**: `products`
3. **Field Configuration in `products`**:
   - **Name**: `category_id`
   - **Type**: `INTEGER`
   - **Relationship**: Select table `categories`
   - **Display Field**: Select `name`

This will allow the system to show a selector with the names of the categories when inserting a product.

---

## 🔒 Security

### Permission Validation
Every operation validates that the user has permissions:
```php
Auth::requireDatabaseAccess($db_id);
Auth::requirePermission("db:$db_id", "write");
```

### Prepared Statements
All queries use prepared statements.

### Sanitization
Data is sanitized before being displayed using `htmlspecialchars()`.

---

[← Back to Main README](../../README.en.md)
