# 🔌 REST API Module

[← Back to Main README](../../README.en.md)

## 📋 Description

The **REST API Module** provides automatic generation of RESTful endpoints for all database tables managed by the system. It includes API Key authentication, interactive documentation, and full support for CRUD operations.

---

## 📁 Module Structure

```
src/Modules/Api/
├── RestController.php      # Main REST API controller
└── ApiDocsController.php   # Documentation generator
```

---

## ✨ Features

### 🔄 Automatic Endpoints
- **GET** `/api/v1/{database}/{table}` - List all records
- **GET** `/api/v1/{database}/{table}/{id}` - Get a specific record
- **POST** `/api/v1/{database}/{table}` - Create new record
- **PUT** `/api/v1/{database}/{table}/{id}` - Full record update
- **PATCH** `/api/v1/{database}/{table}/{id}` - Partial record update
- **DELETE** `/api/v1/{database}/{table}/{id}` - Delete record

### 🔐 Authentication
- API Keys stored in the system database
- Validation on every request
- Key management from the admin panel

### 📖 Automatic Documentation
- Dynamic Swagger-style documentation generation
- cURL usage examples
- List of all available endpoints

---

## 🚀 Usage

### 1. Generate API Key

1. Access the admin panel
2. Go to **API Management**
3. Click "Generate New Key"
4. Copy and save the generated API Key

### 2. Make Requests

All requests must include the `X-API-Key` header:

```bash
curl -H "X-API-Key: your-api-key-here" \
     http://localhost/data2rest/api/v1/mydb/users
```

### 3. Usage Examples

#### List All Records

```bash
GET /api/v1/mydb/users

curl -H "X-API-Key: abc123..." \
     http://localhost/data2rest/api/v1/mydb/users
```

**Response:**
```json
[
  {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
]
```

#### JavaScript (Fetch API)
```javascript
const response = await fetch('http://localhost/data2rest/api/v1/mydb/users', {
    method: 'POST',
    headers: {
        'X-API-Key': 'your-api-key-here',
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        name: 'Peter Smith',
        email: 'peter@example.com'
    })
});
const data = await response.json();
console.log(data);
```

#### Python (Requests)
```python
import requests

url = "http://localhost/data2rest/api/v1/mydb/users"
headers = {
    "X-API-Key": "your-api-key-here",
    "Content-Type": "application/json"
}
data = {
    "name": "Peter Smith",
    "email": "peter@example.com"
}

response = requests.post(url, json=data, headers=headers)
print(response.json())
```

---

## 🔒 Security

### API Keys
API Keys are stored in the `api_keys` table of the system database.

### Validation
Every request undergoes:
1. **API Key Validation** - Verifies it exists and is active
2. **Database Validation** - Verifies the DB exists
3. **Table Validation** - Verifies the table exists
4. **Data Validation** - Sanitizes inputs before executing queries

---

## 📊 Error Responses

### 401 Unauthorized
```json
{
  "error": "Invalid or missing API key"
}
```

### 404 Not Found
```json
{
  "error": "Record not found"
}
```

---

[← Back to Main README](../../README.en.md)
