-- PostgreSQL: Truncate all tables in dependency order (reverse)
-- Run this BEFORE importing data if tables already have seeder data
-- WARNING: This will DELETE ALL DATA from tables

-- Disable foreign key checks
SET CONSTRAINTS ALL DEFERRED;

-- Truncate in reverse dependency order (child tables first)
TRUNCATE TABLE "warning_station_telemetry_configs" CASCADE;
TRUNCATE TABLE "warning_station_device_heartbeats" CASCADE;
TRUNCATE TABLE "warning_station_devices" CASCADE;
TRUNCATE TABLE "user_has_projects" CASCADE;
TRUNCATE TABLE "role_has_permissions" CASCADE;
TRUNCATE TABLE "model_has_roles" CASCADE;
TRUNCATE TABLE "sensor_mapping_profiles" CASCADE;
TRUNCATE TABLE "sensor_mapping_preset_items" CASCADE;
TRUNCATE TABLE "sensor_mapping_presets" CASCADE;
TRUNCATE TABLE "canonical_parameters" CASCADE;
TRUNCATE TABLE "connectivity_configs" CASCADE;
TRUNCATE TABLE "sensors" CASCADE;
TRUNCATE TABLE "data_logger_discoveries" CASCADE;
TRUNCATE TABLE "data_loggers" CASCADE;
TRUNCATE TABLE "sentinel_notifications" CASCADE;
TRUNCATE TABLE "station_spatial_references" CASCADE;
TRUNCATE TABLE "station_function_configurations" CASCADE;
TRUNCATE TABLE "spatial_information_layers" CASCADE;
TRUNCATE TABLE "hydromet_wdam_configs" CASCADE;
TRUNCATE TABLE "hydromet_hazard_classifications" CASCADE;
TRUNCATE TABLE "hydromet_ews_relationships" CASCADE;
TRUNCATE TABLE "corridor_monitorings" CASCADE;
TRUNCATE TABLE "warning_stations" CASCADE;
TRUNCATE TABLE "monitoring_stations" CASCADE;
TRUNCATE TABLE "reference_points" CASCADE;
TRUNCATE TABLE "reference_routes" CASCADE;
TRUNCATE TABLE "geospatial_workspaces" CASCADE;
TRUNCATE TABLE "resq_projects" CASCADE;
TRUNCATE TABLE "mst_prefixes" CASCADE;
TRUNCATE TABLE "provinces" CASCADE;
TRUNCATE TABLE "users" CASCADE;
TRUNCATE TABLE "clients" CASCADE;
TRUNCATE TABLE "permissions" CASCADE;
TRUNCATE TABLE "roles" CASCADE;

-- Re-enable foreign key checks
SET CONSTRAINTS ALL IMMEDIATE;

-- Reset sequences (auto-increment) after truncate
-- Uncomment and adjust as needed:
-- ALTER SEQUENCE roles_id_seq RESTART WITH 1;
-- ALTER SEQUENCE users_id_seq RESTART WITH 1;
-- etc.

-- Done!
SELECT 'All tables truncated successfully' as status;
