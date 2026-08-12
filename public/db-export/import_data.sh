#!/bin/bash

###############################################################################
# PostgreSQL Data Import Script for Sentinel RESQ
#
# This script safely imports the seeder data by:
# 1. Backing up existing data
# 2. Truncating tables in reverse dependency order
# 3. Importing data in correct foreign-key dependency order
# 4. Resetting auto-increment sequences
#
# Usage: ./import_data.sh [--no-backup] [--force]
#        --no-backup   Skip backup step (not recommended)
#        --force       Skip confirmation prompt
###############################################################################

set -euo pipefail

# Configuration
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_USER="${DB_USER:-postgres}"
DB_NAME="${DB_NAME:-sentinal_resq}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Flags
SKIP_BACKUP=false
FORCE_MODE=false

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --no-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --force)
            FORCE_MODE=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            echo "Usage: $0 [--no-backup] [--force]"
            exit 1
            ;;
    esac
done

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "\n${BLUE}=== $1 ===${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

confirm() {
    if [ "$FORCE_MODE" = true ]; then
        return 0
    fi

    local prompt="$1"
    local response
    read -p "$(echo -e ${YELLOW}$prompt${NC} ' (yes/no): ')" response

    [[ "$response" =~ ^[Yy][Ee][Ss]?$ ]]
}

check_psql() {
    if ! command -v psql &> /dev/null; then
        print_error "psql not found. Install PostgreSQL client."
        exit 1
    fi
    print_success "psql found"
}

check_files() {
    local files=(
        "$SCRIPT_DIR/truncate_all_tables.sql"
        "$SCRIPT_DIR/resq_postgres_data_ordered.sql"
    )

    for file in "${files[@]}"; do
        if [ ! -f "$file" ]; then
            print_error "Missing file: $file"
            exit 1
        fi
    done
    print_success "All SQL files found"
}

check_connection() {
    print_info "Testing connection to $DB_HOST:$DB_PORT/$DB_NAME as user '$DB_USER'..."

    if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "SELECT 1" &>/dev/null; then
        print_success "Database connection successful"
    else
        print_error "Cannot connect to database. Check host, port, user, and password."
        exit 1
    fi
}

backup_database() {
    if [ "$SKIP_BACKUP" = true ]; then
        print_warning "Skipping backup (--no-backup flag used)"
        return
    fi

    print_header "Step 1: Backing up existing data"

    local backup_file="sentinal_resq_backup_$(date +%Y%m%d_%H%M%S).dump"

    print_info "Creating backup: $backup_file"

    if pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
        -F c -f "$backup_file" 2>/dev/null; then
        print_success "Backup created: $backup_file"
    else
        print_error "Backup failed"
        exit 1
    fi
}

truncate_tables() {
    print_header "Step 2: Truncating existing data"

    if confirm "This will DELETE ALL data from all tables. Continue?"; then
        print_info "Executing truncate_all_tables.sql..."

        if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
            -X -v ON_ERROR_STOP=1 -f "$SCRIPT_DIR/truncate_all_tables.sql" 2>&1 | grep -E "(TRUNCATE|successfully|error|ERROR)" || true; then
            print_success "Tables truncated"
        else
            print_error "Truncate failed"
            exit 1
        fi
    else
        print_error "Cancelled by user"
        exit 1
    fi
}

import_data() {
    print_header "Step 3: Importing data in correct order"

    print_info "Executing resq_postgres_data_ordered.sql..."
    print_warning "This may take a minute..."

    if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
        -X -v ON_ERROR_STOP=1 --single-transaction \
        -f "$SCRIPT_DIR/resq_postgres_data_ordered.sql" &>/dev/null; then
        print_success "Data imported successfully"
    else
        print_error "Data import failed. Check SQL file and try again."
        exit 1
    fi
}

reset_sequences() {
    print_header "Step 4: Resetting auto-increment sequences"

    print_info "Resetting sequences..."

    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" << 'SQL' 2>/dev/null
DO $$
DECLARE
    r record;
    sequence_count int := 0;
BEGIN
    FOR r IN
        SELECT
            format('%I.%I', n.nspname, c.relname) AS table_name,
            a.attname AS column_name,
            pg_get_serial_sequence(
                format('%I.%I', n.nspname, c.relname),
                a.attname
            ) AS sequence_name
        FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        JOIN pg_attribute a ON a.attrelid = c.oid
        WHERE c.relkind = 'r'
          AND n.nspname = 'public'
          AND a.attname = 'id'
          AND a.attnum > 0
          AND NOT a.attisdropped
    LOOP
        IF r.sequence_name IS NOT NULL THEN
            EXECUTE format(
                'SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM %s), 1), (SELECT COUNT(*) > 0 FROM %s))',
                r.sequence_name,
                r.column_name,
                r.table_name,
                r.table_name
            );
            sequence_count := sequence_count + 1;
        END IF;
    END LOOP;
    RAISE NOTICE 'Reset % sequences', sequence_count;
END $$;
SQL

    print_success "Sequences reset"
}

verify_import() {
    print_header "Step 5: Verifying import"

    local counts
    counts=$(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c \
        "SELECT
         (SELECT count(*) FROM users) AS users,
         (SELECT count(*) FROM resq_projects) AS projects,
         (SELECT count(*) FROM data_loggers) AS data_loggers,
         (SELECT count(*) FROM sensors) AS sensors,
         (SELECT count(*) FROM canonical_parameters) AS canonical_parameters;" 2>/dev/null)

    if [ -n "$counts" ]; then
        echo "$counts" | while read -r line; do
            print_info "$line"
        done
        print_success "Import verification complete"
    else
        print_warning "Could not verify import"
    fi
}

# Main execution
main() {
    print_header "PostgreSQL Data Import for Sentinel RESQ"

    print_info "Configuration:"
    print_info "  Host:     $DB_HOST:$DB_PORT"
    print_info "  Database: $DB_NAME"
    print_info "  User:     $DB_USER"
    echo ""

    check_psql
    check_files
    check_connection

    backup_database
    truncate_tables
    import_data
    reset_sequences
    verify_import

    print_header "Import completed successfully! ✓"
    echo -e "\n${GREEN}Next steps:${NC}"
    echo "1. Clear Laravel cache: php artisan optimize:clear"
    echo "2. Verify application: Open your browser to check the platform"
    echo ""
}

# Run main function
main "$@"
