#!/bin/bash

echo "=========================================="
echo "ElectroShop API Testing Suite"
echo "=========================================="
echo ""

BASE_URL="http://localhost:8000/api"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
PASSED=0
FAILED=0

test_api() {
    local name=$1
    local method=$2
    local endpoint=$3
    local data=$4
    
    echo -n "Testing $name... "
    
    if [ "$method" = "POST" ]; then
        response=$(curl -s -X POST "$BASE_URL/$endpoint" \
            -H "Content-Type: application/json" \
            -d "$data")
    else
        response=$(curl -s "$BASE_URL/$endpoint")
    fi
    
    if echo "$response" | grep -q "message.*success\|authenticated.*true\|id.*[0-9]\|\[{"; then
        echo -e "${GREEN}✓ PASSED${NC}"
        ((PASSED++))
        echo "   Response: ${response:0:100}..."
    else
        echo -e "${RED}✗ FAILED${NC}"
        ((FAILED++))
        echo "   Response: $response"
    fi
    echo ""
}

echo "1. Authentication Tests"
echo "------------------------"

# Test Registration
test_api "User Registration" "POST" "auth/register.php" \
    '{"username":"newuser","email":"newuser@test.com","password":"test123"}'

# Test Login
test_api "User Login" "POST" "auth/login.php" \
    '{"email":"admin@electroshop.com","password":"admin123"}'

# Test Session Check
test_api "Session Check" "GET" "auth/check_session.php"

echo ""
echo "2. Products Tests"
echo "------------------------"

# Test Get All Products
test_api "Get All Products" "GET" "products/read.php"

# Test Get Products by Category
test_api "Get Products by Category" "GET" "products/read.php?category_id=1"

# Test Search Products
test_api "Search Products" "GET" "products/read.php?search=iPhone"

# Test Get Single Product
test_api "Get Single Product" "GET" "products/get_product.php?id=1"

echo ""
echo "3. Orders Tests"
echo "------------------------"

# Test Get Order History
test_api "Get Order History" "GET" "orders/history.php"

echo ""
echo "=========================================="
echo "Test Summary"
echo "=========================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests passed! ✓${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed! ✗${NC}"
    exit 1
fi
