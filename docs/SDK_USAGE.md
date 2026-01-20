# Data2Rest Official SDKs Usage Guide

Data2Rest provides official client libraries for JavaScript and Python to simplify API integration. These SDKs handle authentication, request formatting, and error handling for you.

## 1. JavaScript SDK

### Installation
Download the `data2rest.js` file from your dashboard (`/admin/api`) or reference it via CDN/local path.

```html
<script src="/sdk/javascript/data2rest.js"></script>
```
Or for Node.js:
```javascript
const Data2RestClient = require('./path/to/data2rest.js');
```

### Initialization
```javascript
const client = new Data2RestClient({
    baseUrl: 'http://localhost/data2rest/public/api',
    apiKey: 'your_api_key_here',
    version: 'v2' // optional, defaults to v2
});
```

### Basic CRUD Operations

```javascript
// Function specific scope for async/await
async function main() {
    const users = client.database(1).table('users');

    // 1️⃣ Create
    const newUser = await users.create({ 
        name: 'John Doe', 
        email: 'john@example.com' 
    });
    console.log('Created:', newUser);

    // 2️⃣ Read (List with filters)
    const list = await users.get({ 
        limit: 10, 
        sort: '-id' 
    });
    console.log('List:', list);

    // 3️⃣ Update
    const updated = await users.update(newUser.id, { 
        name: 'John Smith' 
    });

    // 4️⃣ Delete
    await users.delete(newUser.id);
}
```

---

## 2. Python SDK

### Installation
Requires `requests` library.
```bash
pip install requests
```
Download `data2rest.py` to your project folder.

### Initialization
```python
from data2rest import Data2RestClient

client = Data2RestClient(
    base_url='http://localhost/data2rest/public/api',
    api_key='your_api_key_here'
)
```

### Basic CRUD Operations

```python
# Access the table resource
users = client.database(1).table('users')

try:
    # 1️⃣ Create
    new_user = users.create({
        "name": "Jane Doe",
        "email": "jane@example.com"
    })
    print(f"Created User ID: {new_user['id']}")

    # 2️⃣ Read (Get list)
    all_users = users.get(limit=5, sort='-created_at')
    
    # 3️⃣ Update
    users.update(new_user['id'], {"status": "active"})

    # 4️⃣ Delete
    users.delete(new_user['id'])

except Exception as e:
    print(f"Error: {e}")
```

## 3. Advanced Features (Both SDKs)

### Bulk Operations
Perform multiple writes in a single HTTP request for efficiency.

**JavaScript:**
```javascript
await client.database(1).table('products').bulk([
    { create: { name: 'Product A', price: 10 } },
    { create: { name: 'Product B', price: 20 } },
    { delete: 55 }
]);
```

**Python:**
```python
client.database(1).table('products').bulk([
    {"create": {"name": "Product A", "price": 10}},
    {"create": {"name": "Product B", "price": 20}}
])
```
