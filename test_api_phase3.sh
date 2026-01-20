#!/bin/bash

# Data2Rest API Phase 3 - Test Script
# Analytics, Exports & Webhook Retry

echo "🚀 Data2Rest API - Phase 3 Feature Tests"
echo "=========================================="
echo ""

# Configuration
API_BASE_URL="http://localhost/api"
API_KEY="your_api_key_here"
DB_ID="1"
TABLE="products" 

echo "🧪 Generating Traffic for Analytics..."
# Generate some standard requests
for i in {1..5}; do
    curl -s -o /dev/null -H "X-API-KEY: $API_KEY" "$API_BASE_URL/db/$DB_ID/$TABLE?limit=1"
done
echo "✅ Traffic generated."
echo ""

# Test 1: CSV Export
echo "Test 1: CSV Export Check"
echo "------------------------"
RESPONSE=$(curl -s -I -H "X-API-KEY: $API_KEY" "$API_BASE_URL/db/$DB_ID/$TABLE?format=csv")
if echo "$RESPONSE" | grep -q "Content-Type: text/csv"; then
    echo "✅ CSV Header Received (Content-Type: text/csv)"
else
    echo "❌ Failed to get CSV header"
fi
echo ""

# Test 2: Excel Export (Basic XML)
echo "Test 2: Excel Export Check"
echo "--------------------------"
RESPONSE=$(curl -s -I -H "X-API-KEY: $API_KEY" "$API_BASE_URL/db/$DB_ID/$TABLE?format=xlsx")
if echo "$RESPONSE" | grep -q "Content-Type: application/vnd.ms-excel"; then
    echo "✅ Excel Header Received (Content-Type: application/vnd.ms-excel)"
else
    echo "❌ Failed to get Excel header"
fi
echo ""

# Test 3: Analytics Endpoints (SDK Check simulation)
echo "Test 3: SDK Files Existence"
echo "---------------------------"
if [ -f "public/sdk/javascript/data2rest.js" ]; then
    echo "✅ JS SDK found"
else 
    echo "❌ JS SDK missing"
fi

if [ -f "public/sdk/python/data2rest.py" ]; then
    echo "✅ Python SDK found"
else 
    echo "❌ Python SDK missing"
fi
echo ""

echo "✅ Phase 3 Test Suite Complete!"
echo "Please visit /admin/api/analytics to view the dashboard."
