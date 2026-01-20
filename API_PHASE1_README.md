# Data2Rest API - Phase 1 Enhancements

## 🚀 New Features

### 1. Rate Limiting

All API requests are now subject to rate limiting to prevent abuse.

**Default Limit:** 1000 requests per hour per API key

**Response Headers:**
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 847
X-RateLimit-Reset: 1642521600
```

**Rate Limit Exceeded Response:**
```json
{
  "error": "Rate limit exceeded",
  "message": "You have exceeded the rate limit of 1000 requests per hour",
  "retry_after": 3456
}
```
**HTTP Status:** `429 Too Many Requests`

---

### 2. Granular Permissions

API keys now support fine-grained permissions at the database and table level.

**Permission Types:**
- `read` - GET requests
- `create` - POST requests
- `update` - PUT/PATCH requests
- `delete` - DELETE requests

**Example Permission Configuration:**
```json
{
  "database_id": 1,
  "table_name": "users",
  "can_read": true,
  "can_create": false,
  "can_update": false,
  "can_delete": false
}
```

**Permission Denied Response:**
```json
{
  "error": "Permission denied",
  "message": "API key does not have 'create' permission for table 'users'"
}
```
**HTTP Status:** `403 Forbidden`

---

### 3. IP Whitelisting

Restrict API key usage to specific IP addresses or CIDR ranges.

**Configuration Examples:**
```
# Single IP
192.168.1.100

# Multiple IPs (comma-separated)
192.168.1.100, 10.0.0.50

# CIDR Range
192.168.1.0/24

# Mixed
192.168.1.100, 10.0.0.0/16
```

**Blocked IP Response:**
```json
{
  "error": "Access denied",
  "message": "Your IP address is not whitelisted for this API key"
}
```
**HTTP Status:** `403 Forbidden`

---

### 4. Advanced Query Filters

Enhanced filtering capabilities with comparison operators.

#### Comparison Operators

**Greater Than:**
```http
GET /api/db/1/users?age[gt]=18
```

**Less Than or Equal:**
```http
GET /api/db/1/products?price[lte]=100
```

**Not Equal:**
```http
GET /api/db/1/users?status[ne]=inactive
```

**IN Operator (comma-separated):**
```http
GET /api/db/1/orders?status[in]=pending,processing,shipped
```

**BETWEEN Operator:**
```http
GET /api/db/1/sales?created_at[between]=2024-01-01,2024-12-31
```

**NOT NULL:**
```http
GET /api/db/1/users?email[not]=null
```

**LIKE Pattern:**
```http
GET /api/db/1/users?name[like]=%john%
```

#### All Supported Operators

| Operator | SQL Equivalent | Example |
|----------|---------------|---------|
| `eq` | `=` | `?age[eq]=25` |
| `ne` | `!=` | `?status[ne]=deleted` |
| `gt` | `>` | `?price[gt]=100` |
| `gte` | `>=` | `?age[gte]=18` |
| `lt` | `<` | `?stock[lt]=10` |
| `lte` | `<=` | `?discount[lte]=50` |
| `in` | `IN` | `?id[in]=1,2,3` |
| `between` | `BETWEEN` | `?date[between]=2024-01-01,2024-12-31` |
| `like` | `LIKE` | `?name[like]=%smith%` |
| `not` | `IS NOT` / `!=` | `?email[not]=null` |

---

### 5. Advanced Sorting

Sort results by multiple fields with ascending/descending order.

**Single Field (Ascending):**
```http
GET /api/db/1/users?sort=name
```

**Single Field (Descending - use minus prefix):**
```http
GET /api/db/1/users?sort=-created_at
```

**Multiple Fields:**
```http
GET /api/db/1/products?sort=category,-price,name
```
*Sorts by category ASC, then price DESC, then name ASC*

---

## 📚 Complete Examples

### Example 1: Complex Query with Filters and Sorting

**Request:**
```http
GET /api/db/1/products?category[in]=electronics,computers&price[lte]=1000&stock[gt]=0&sort=-created_at,name&limit=20&offset=0
X-API-KEY: your_api_key_here
```

**Response:**
```json
{
  "metadata": {
    "total_records": 156,
    "limit": 20,
    "offset": 0,
    "count": 20
  },
  "data": [
    {
      "id": 42,
      "name": "Laptop Pro",
      "category": "computers",
      "price": 899.99,
      "stock": 15,
      "created_at": "2024-01-15 10:30:00"
    }
    // ... more records
  ]
}
```

### Example 2: Date Range Query

**Request:**
```http
GET /api/db/1/orders?created_at[between]=2024-01-01,2024-01-31&status[ne]=cancelled&sort=-total
X-API-KEY: your_api_key_here
```

### Example 3: Search with Wildcards

**Request:**
```http
GET /api/db/1/users?email[like]=%@gmail.com&status=active&sort=name
X-API-KEY: your_api_key_here
```

---

## 🔧 Managing API Keys

### Creating an API Key with Custom Rate Limit

**Via Admin Panel:**
1. Navigate to **Admin → API Management**
2. Click **Create New API Key**
3. Fill in:
   - **Name:** Production App Key
   - **Description:** Main API key for production application
   - **Rate Limit:** 5000 (requests per hour)
4. Click **Create**

### Setting Permissions

**Via Admin Panel:**
1. Go to **API Management**
2. Click **Manage Permissions** on the desired key
3. Select database and table
4. Check permissions: ☑ Read ☐ Create ☐ Update ☐ Delete
5. (Optional) Add allowed IPs: `192.168.1.0/24`
6. Click **Save**

---

## 🛡️ Security Best Practices

1. **Use HTTPS Only** - Never send API keys over unencrypted connections
2. **Rotate Keys Regularly** - Generate new keys periodically
3. **Principle of Least Privilege** - Only grant necessary permissions
4. **IP Whitelisting** - Restrict keys to known IP addresses when possible
5. **Monitor Usage** - Check rate limit stats regularly for anomalies
6. **Separate Keys per Application** - Don't reuse keys across different apps

---

## 📊 Monitoring & Analytics

### View Rate Limit Statistics

Access the API key management panel to see:
- Total requests in last 24 hours
- Requests per endpoint
- Peak usage times
- Rate limit violations

### Audit Logs

All API actions are logged including:
- Permission denials
- Rate limit violations
- IP blocking events
- Permission changes

---

## 🔄 Migration Guide

### Existing API Keys

All existing API keys will:
- Have a default rate limit of **1000 req/hour**
- Have **full permissions** on all databases (backward compatible)
- Have **no IP restrictions**

### Recommended Actions

1. Review all existing API keys
2. Set appropriate rate limits based on usage
3. Configure granular permissions
4. Add IP whitelisting where applicable

---

## 🐛 Troubleshooting

### "Rate limit exceeded"
- **Solution:** Wait for the reset time or request a higher limit
- **Check:** `X-RateLimit-Reset` header for reset timestamp

### "Permission denied"
- **Solution:** Contact admin to grant required permissions
- **Check:** Ensure you're using the correct API key for this operation

### "IP address not whitelisted"
- **Solution:** Add your IP to the whitelist or use a VPN
- **Check:** Verify your current IP matches the whitelist

---

## 📞 Support

For questions or issues with the API:
- Check the full documentation at `/admin/api/docs`
- Contact your system administrator
- Review audit logs for detailed error information

---

**Version:** 1.0.0 (Phase 1)  
**Last Updated:** January 2024
