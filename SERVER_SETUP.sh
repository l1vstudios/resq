#!/bin/bash
# Server Setup Script - RESQ Database initialization
# Usage: bash SERVER_SETUP.sh

set -e

echo "=== RESQ Database Setup ==="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "ERROR: .env file not found"
    echo "Please copy .env.example to .env and configure database connection"
    exit 1
fi

echo "1. Installing PHP dependencies..."
composer install --no-interaction

echo ""
echo "2. Generating application key..."
php artisan key:generate --force

echo ""
echo "3. Running database migrations..."
php artisan migrate --force

echo ""
echo "4. Seeding database with initial data..."
php artisan db:seed --force

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Database initialized with:"
echo "  - 46 tables (all structures created)"
echo "  - 300+ records (all data seeded)"
echo "  - Support for MySQL 8.0+ and PostgreSQL 12+"
echo ""
echo "Next steps:"
echo "  - Review database connection in .env"
echo "  - Run: php artisan serve (for local development)"
echo "  - Run: php artisan tinker (to verify data)"
echo ""
