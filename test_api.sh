#!/bin/bash

# Test Admin Login
echo "Testing Admin Login..."
curl -c cookies.txt -X POST -H "Content-Type: application/json" -d '{"email":"admin@example.com","password":"admin123"}' http://127.0.0.1:8080/api/auth/login.php
echo -e "\n"

# Test Admin Dashboard Stats
echo "Testing Admin Dashboard Stats..."
curl -b cookies.txt http://127.0.0.1:8080/api/admin/dashboard_stats.php
echo -e "\n"

# Test Admin Get Users
echo "Testing Admin Get Users..."
curl -b cookies.txt http://127.0.0.1:8080/api/admin/get_users.php
echo -e "\n"

# Test Seller Login
echo "Testing Seller Login..."
curl -c cookies_seller.txt -X POST -H "Content-Type: application/json" -d '{"email":"seller@example.com","password":"seller123"}' http://127.0.0.1:8080/api/auth/login.php
echo -e "\n"

# Test Seller Dashboard Stats
echo "Testing Seller Dashboard Stats..."
curl -b cookies_seller.txt http://127.0.0.1:8080/api/seller/dashboard_stats.php
echo -e "\n"
