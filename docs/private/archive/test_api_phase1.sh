#!/bin/bash

# Data2Rest API Phase 1 - Test Script
# This script demonstrates all new Phase 1 features

echo "🚀 Data2Rest API - Phase 1 Feature Tests"
echo "=========================================="
echo ""

# Configuration
API_BASE_URL="http://localhost/api"
API_KEY="your_api_key_here"
DB_ID="1"
TABLE="users"

echo "📝 Configuration:"
echo "  Base URL: $API_BASE_URL"
echo "  Database: $DB_ID"
echo "  Table: $TABLE"
echo ""

# Test 1: Basic GET with Rate Limit Headers
echo "Test 1: Basic GET Request (Check Rate Limit Headers)"
echo "-----------------------------------------------------"
curl -s -i -X GET \
  -H "X-API-KEY: $API_KEY" \
  "$API_BASE_URL/db/$DB_ID/$TABLE?limit=5" \
  | grep -E "(HTTP|X-RateLimit)"
echo ""
echo ""

# Test 2: Advanced Filters - Greater Than
echo "Test 2: Filter with Greater Than Operator"
echo "------------------------------------------"
echo "Query: age[gt]=18"
curl -s -X GET \
  -H "X-API-KEY: $API_KEY" \
  "$API_BASE_URL/db/$DB_ID/$TABLE?age[gt]=18&limit=3" \
  | jq '.data[] | {id, name, age}'
echo ""
echo ""

# Test 3: IN Operator
echo "Test 3: Filter with IN Operator"
echo "--------------------------------"
echo "Query: status[in]=active,pending"
curl -s -X GET \
  -H "X-API-KEY: $API_KEY" \
  "$API_BASE_URL/db/$DB_ID/$TABLE?status[in]=active,pending&limit=3" \
  | jq '.data[] | {id, name, status}'
echo ""
echo ""

# Test 4: BETWEEN Operator
echo "Test 4: Filter with BETWEEN Operator"
echo "-------------------------------------"
echo "Query: created_at[between]=2024-01-01,2024-12-31"
curl -s -X GET \
  -H "X-API-KEY: $API_KEY" \
  "$API_BASE_URL/db/$DB_ID/$TABLE?created_at[between]=2024-01-01,2024-12-31&limit=3" \
  | jq '.data[] | {id, name, created_at}'
echo ""
echo ""

# Test 5: LIKE Pattern
echo "Test 5: Filter with LIKE Pattern"
echo "---------------------------------"
echo "Query: name[like]=%john%"
curl -s -X GET \
  -H "X-API-KEY: $API_KEY" \
  "$API_BASE_URL/db/$DB_ID/$TABLE?name[like]=%john%&limit=3" \
  | jq '.data[] | {id, name, email}'
echo ""
echo ""

# Test 6: NOT NULL
echo "Test 6: Filter NOT NULL"
echo "-----------------------"
echo "Query: email[not]=null"
curl -s -X GET \
  -H "X-API-KEY: $API_KEY" \
  "$API_BASE_URL/db/$DB_ID/$TABLE?email[not]=null&limit=3" \
  | jq '.data[] | {id, name, email}'
echo ""
echo ""

# Test 7: Multi-field Sorting
echo "Test 7: Multi-field Sorting"
echo "----------------------------"
echo "Query: sort=-created_at,name (DESC by date, ASC by name)"
curl -s -X GET \
  -H "X-API-KEY: $API_KEY" \
  "$API_BASE_URL/db/$DB_ID/$TABLE?sort=-created_at,name&limit=5" \
  | jq '.data[] | {id, name, created_at}'
echo ""
echo ""

# Test 8: Complex Query
echo "Test 8: Complex Query (Multiple Filters + Sorting)"
echo "---------------------------------------------------"
echo "Query: age[gte]=18 AND status[in]=active,verified AND sort=-created_at"
curl -s -X GET \
  -H "X-API-KEY: $API_KEY" \
  "$API_BASE_URL/db/$DB_ID/$TABLE?age[gte]=18&status[in]=active,verified&sort=-created_at&limit=5" \
  | jq '{metadata, data: .data[] | {id, name, age, status, created_at}}'
echo ""
echo ""

# Test 9: Rate Limit Test (Multiple Rapid Requests)
echo "Test 9: Rate Limit Test (5 rapid requests)"
echo "-------------------------------------------"
for i in {1..5}; do
  echo "Request $i:"
  curl -s -i -X GET \
    -H "X-API-KEY: $API_KEY" \
    "$API_BASE_URL/db/$DB_ID/$TABLE?limit=1" \
    | grep "X-RateLimit-Remaining"
  sleep 0.2
done
echo ""
echo ""

# Test 10: Permission Denied (if configured)
echo "Test 10: Permission Check"
echo "-------------------------"
echo "Attempting POST (will fail if permissions not granted):"
curl -s -X POST \
  -H "X-API-KEY: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com"}' \
  "$API_BASE_URL/db/$DB_ID/$TABLE" \
  | jq '.'
echo ""
echo ""

echo "✅ Test Suite Complete!"
echo ""
echo "📊 Summary:"
echo "  - Rate limiting headers verified"
echo "  - Advanced filters tested (gt, in, between, like, not)"
echo "  - Multi-field sorting tested"
echo "  - Complex queries tested"
echo "  - Permission system tested"
echo ""
echo "📚 For full documentation, see: API_PHASE1_README.md"
