# PostgreSQL Data Import Instructions

## Problem Summary

The original `resq_postgres_data.sql` is alphabetically ordered, which violates foreign key constraints. The database may also contain existing data causing duplicate key errors.

## Solution

Use `resq_postgres_data_ordered.sql` which has tables in correct foreign-key dependency order.

## Prerequisites

- PostgreSQL client (`psql`) installed and accessible
- Database `sentinal_resq` already created
- PostgreSQL user `postgres` with appropriate permissions and password configured

## Step-by-Step Import

### 1. Back up existing data (optional but recommended)

```bash
cd /var/www/html/sentinalplatform

pg_dump -h 127.0.0.1 -U postgres -d sentinal_resq \
  -F c -f "sentinal_resq_backup_$(date +%Y%m%d_%H%M%S).dump"
```

### 2. Clear existing data

```bash
psql -X -v ON_ERROR_STOP=1 \
  -h 127.0.0.1 -U postgres -d sentinal_resq \
  -f public/db-export/truncate_all_tables.sql
```

This will:
- Truncate all tables in reverse dependency order
- Preserve table structure
- Reset foreign key constraints

### 3. Import data in correct order

```bash
psql -X -v ON_ERROR_STOP=1 --single-transaction \
  -h 127.0.0.1 -U postgres -d sentinal_resq \
  -f public/db-export/resq_postgres_data_ordered.sql
```

The flags mean:
- `-X`: No startup file
- `-v ON_ERROR_STOP=1`: Stop immediately on first error
- `--single-transaction`: Rollback everything if any error occurs
- `-f`: Execute SQL from file

### 4. Reset auto-increment sequences

```bash
psql -h 127.0.0.1 -U postgres -d sentinal_resq << 'SQL'
DO $$
DECLARE
    r record;
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
        END IF;
    END LOOP;
END $$;
SQL
```

### 5. Verify import

```bash
psql -h 127.0.0.1 -U postgres -d sentinal_resq -c \
"SELECT
 (SELECT count(*) FROM users) AS users,
 (SELECT count(*) FROM resq_projects) AS projects,
 (SELECT count(*) FROM data_loggers) AS data_loggers,
 (SELECT count(*) FROM sensors) AS sensors,
 (SELECT count(*) FROM canonical_parameters) AS canonical_parameters;"
```

## Troubleshooting

### Authentication Failed for "root"

PostgreSQL does not have a user named "root" by default. Use `postgres` instead.

Edit `.env`:
```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentinal_resq
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password
```

Then clear Laravel cache:
```bash
php artisan optimize:clear
```

### Foreign Key Constraint Errors

If you see foreign key errors during import, it means:
1. Tables are still in wrong order (shouldn't happen with `resq_postgres_data_ordered.sql`)
2. Referenced IDs don't exist in parent tables

Always use `resq_postgres_data_ordered.sql`, not `resq_postgres_data.sql`.

### Duplicate Key Errors

If you see duplicate key errors (e.g., "Key (id)=(1) already exists"):
1. Run step 2 (truncate) first to clear existing data
2. Ensure the truncate completed successfully

### Sequence Issues

If auto-increment fails after import, run step 4 to reset sequences.

## Files Used

- `truncate_all_tables.sql` - Clears all data in dependency order
- `resq_postgres_data_ordered.sql` - Imports data in correct foreign-key dependency order
- `resq_postgres_data.sql` - **DO NOT USE** (alphabetically ordered, violates constraints)
- `resq_data_only.sql` - Data-only backup (not for fresh import)
- `resq_full.sql` - Full dump with schema (not for fresh import)

## Summary Table Order

**Import order (dependencies satisfied before use):**
1. roles, permissions
2. clients, users, provinces, mst_prefixes
3. resq_projects
4. geospatial_workspaces, reference_routes, reference_points
5. monitoring_stations, warning_stations
6. corridor_monitorings, hydromet_ews_relationships
7. hydromet_hazard_classifications, hydromet_wdam_configs
8. spatial_information_layers, station_function_configurations, station_spatial_references
9. sentinel_notifications
10. data_loggers, data_logger_discoveries, sensors
11. connectivity_configs, canonical_parameters
12. sensor_mapping_presets, sensor_mapping_preset_items, sensor_mapping_profiles
13. model_has_roles, role_has_permissions, user_has_projects
14. warning_station_devices, warning_station_device_heartbeats, warning_station_telemetry_configs
