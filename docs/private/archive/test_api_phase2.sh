#!/bin/bash

# Data2Rest API Phase 2 - Test Script
# Demonstrates Cache, Versioning, and Bulk Operations

echo "🚀 Data2Rest API - Phase 2 Feature Tests"
echo "=========================================="
echo ""

# Configuration
API_BASE_URL="http://localhost/api"
API_KEY="your_api_key_here"
DB_ID="1"
TABLE="products" # Ensure this table exists or change to 'users'

echo "📝 Configuration:"
echo "  Base URL: $API_BASE_URL"
echo "  Database: $DB_ID"
echo "  Table: $TABLE"
echo ""

# Test 1: API Versioning (v2 Detection)
echo "Test 1: API Versioning (v2 Check)"
echo "----------------------------------"
echo "Requesting with Accept: application/vnd.data2rest.v2+json"
RESPONSE=$(curl -s -H "X-API-KEY: $API_KEY" \
     -H "Accept: application/vnd.data2rest.v2+json" \
     "$API_BASE_URL/db/$DB_ID/$TABLE?limit=1")
     
echo "$RESPONSE" | jq '.metadata.api_version'
echo ""

# Test 2: Cache & ETags
echo "Test 2: Caching & ETags"
echo "------------------------"
echo "First Request (Fetching ETag)..."
HEADERS=$(curl -s -i -H "X-API-KEY: $API_KEY" "$API_BASE_URL/db/$DB_ID/$TABLE?limit=1")
ETAG=$(echo "$HEADERS" | grep -i "ETag" | awk '{print $2}' | tr -d '\r')
echo "Received ETag: $ETAG"

if [ -n "$ETAG" ]; then
    echo "Sending Second Request with If-None-Match: $ETAG"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "X-API-KEY: $API_KEY" -H "If-None-Match: $ETAG" "$API_BASE_URL/db/$DB_ID/$TABLE?limit=1")
    echo "Response Code: $HTTP_CODE"
    if [ "$HTTP_CODE" == "304" ]; then
        echo "✅ Cache HIT (304 Not Modified) - Success!"
    else
        echo "❌ Cache MISS (Expected 304, got $HTTP_CODE)"
    fi
else
    echo "❌ No ETag received in first request"
fi
echo ""

# Test 3: Bulk Operations (Create)
echo "Test 3: Bulk Create"
echo "-------------------"
read -p "⚠️  This will insert data into '$TABLE'. Continue? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    PAYLOAD='{
      "operations": [
        {"method": "create", "data": {"name": "Test Item 1", "description": "Bulk Test"}},
        {"method": "create", "data": {"name": "Test Item 2", "description": "Bulk Test"}}
      ]
    }'
    
    echo "Sending Bulk Request..."
    curl -s -X POST \
         -H "X-API-KEY: $API_KEY" \
         -H "Accept: application/vnd.data2rest.v2+json" \
         -H "Content-Type: application/json" \
         -d "$PAYLOAD" \
         "$API_BASE_URL/db/$DB_ID/$TABLE/bulk" | jq '.'
fi
echo ""

# Test 4: Verify Cache Invalidation
echo "Test 4: Cache Invalidation Check"
echo "---------------------------------"
echo "Since we just wrote data, the cache should have been invalidated."
echo "Fetching new ETag..."
NEW_HEADERS=$(curl -s -i -H "X-API-KEY: $API_KEY" "$API_BASE_URL/db/$DB_ID/$TABLE?limit=1")
NEW_ETAG=$(echo "$NEW_HEADERS" | grep -i "ETag" | awk '{print $2}' | tr -d '\r')
echo "New ETag: $NEW_ETAG"

if [ "$ETAG" != "$NEW_ETAG" ]; then
    echo "✅ ETags differ - Cache Invalidated Successfully!"
else
    echo "⚠️  ETags match - Cache might not have invalidated (or data didn't impact this query)"
fi
echo ""

echo "✅ Phase 2 Test Suite Complete!"
echo "📚 documentation: API_PHASE2_README.md"
