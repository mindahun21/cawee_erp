#!/bin/bash

#######################################################################
# Elisoft ERP — Production Update Script
#
# This script updates the application on production servers
# Usage: bash update.sh
#######################################################################

set -e  # Exit on any error

echo "========================================"
echo "  Elisoft ERP — Production Update"
echo "========================================"
echo ""

# Install/update composer dependencies (production mode)
echo "▶ Installing composer dependencies..."
composer install --no-dev --optimize-autoloader

# Run database migrations
echo "▶ Running database migrations..."
php artisan migrate --force

# Clear all caches
echo "▶ Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Install npm dependencies
echo "▶ Installing npm dependencies..."
npm install

# Build frontend assets
echo "▶ Building frontend assets..."
npm run build

echo ""
echo "========================================"
echo "  ✅ Update completed successfully!"
echo "========================================"
echo ""
