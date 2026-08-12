-- MySQL dump 10.13  Distrib 9.6.0, for macos14.8 (x86_64)
--
-- Host: 127.0.0.1    Database: resq
-- ------------------------------------------------------
-- Server version	8.0.33

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `canonical_observations`
--

DROP TABLE IF EXISTS `canonical_observations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `canonical_observations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `canonical_observation_uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monitoring_station_id` bigint unsigned DEFAULT NULL,
  `data_logger_id` bigint unsigned DEFAULT NULL,
  `sensor_id` bigint unsigned DEFAULT NULL,
  `domain` enum('meteorology','hydrology','geotechnical') COLLATE utf8mb4_unicode_ci NOT NULL,
  `observed_at` timestamp NOT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `field_values` json DEFAULT NULL,
  `field_units` json DEFAULT NULL,
  `field_origins` json DEFAULT NULL,
  `field_quality` json DEFAULT NULL,
  `processing_statuses` json DEFAULT NULL,
  `quality_status` enum('valid','suspect','invalid','limited','not_available') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid',
  `completeness_status` enum('complete','partial','missing_required') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'partial',
  `processing_status` enum('mapped','processed','late','calculation_failed','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mapped',
  `raw_data_ingestion_id` bigint unsigned DEFAULT NULL,
  `sensor_mapping_profile_id` bigint unsigned DEFAULT NULL,
  `traceability` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `canonical_observations_canonical_observation_uid_unique` (`canonical_observation_uid`),
  UNIQUE KEY `canonical_observation_unique_scope` (`monitoring_station_id`,`sensor_id`,`domain`,`observed_at`),
  KEY `canonical_observations_data_logger_id_foreign` (`data_logger_id`),
  KEY `canonical_observations_sensor_id_foreign` (`sensor_id`),
  KEY `canonical_observations_raw_data_ingestion_id_foreign` (`raw_data_ingestion_id`),
  KEY `canonical_observations_sensor_mapping_profile_id_foreign` (`sensor_mapping_profile_id`),
  KEY `canonical_observations_domain_observed_at_index` (`domain`,`observed_at`),
  CONSTRAINT `canonical_observations_data_logger_id_foreign` FOREIGN KEY (`data_logger_id`) REFERENCES `data_loggers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `canonical_observations_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `canonical_observations_raw_data_ingestion_id_foreign` FOREIGN KEY (`raw_data_ingestion_id`) REFERENCES `raw_data_ingestions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `canonical_observations_sensor_id_foreign` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `canonical_observations_sensor_mapping_profile_id_foreign` FOREIGN KEY (`sensor_mapping_profile_id`) REFERENCES `sensor_mapping_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `canonical_observations`
--

LOCK TABLES `canonical_observations` WRITE;
/*!40000 ALTER TABLE `canonical_observations` DISABLE KEYS */;
/*!40000 ALTER TABLE `canonical_observations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `canonical_parameter_values`
--

DROP TABLE IF EXISTS `canonical_parameter_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `canonical_parameter_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `canonical_observation_id` bigint unsigned NOT NULL,
  `canonical_parameter_id` bigint unsigned NOT NULL,
  `numeric_value` decimal(24,8) DEFAULT NULL,
  `string_value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canonical_unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_origin` enum('reidentified_direct_measurement','reidentified_device_processed','platform_processed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quality_status` enum('valid','suspect','invalid','limited','not_available') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid',
  `raw_data_ingestion_id` bigint unsigned DEFAULT NULL,
  `sensor_mapping_profile_id` bigint unsigned DEFAULT NULL,
  `traceability` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `canonical_value_unique_parameter` (`canonical_observation_id`,`canonical_parameter_id`),
  KEY `canonical_parameter_values_raw_data_ingestion_id_foreign` (`raw_data_ingestion_id`),
  KEY `canonical_parameter_values_sensor_mapping_profile_id_foreign` (`sensor_mapping_profile_id`),
  KEY `idx_can_param_origin` (`canonical_parameter_id`,`value_origin`),
  CONSTRAINT `canonical_parameter_values_canonical_observation_id_foreign` FOREIGN KEY (`canonical_observation_id`) REFERENCES `canonical_observations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `canonical_parameter_values_canonical_parameter_id_foreign` FOREIGN KEY (`canonical_parameter_id`) REFERENCES `canonical_parameters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `canonical_parameter_values_raw_data_ingestion_id_foreign` FOREIGN KEY (`raw_data_ingestion_id`) REFERENCES `raw_data_ingestions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `canonical_parameter_values_sensor_mapping_profile_id_foreign` FOREIGN KEY (`sensor_mapping_profile_id`) REFERENCES `sensor_mapping_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `canonical_parameter_values`
--

LOCK TABLES `canonical_parameter_values` WRITE;
/*!40000 ALTER TABLE `canonical_parameter_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `canonical_parameter_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `canonical_parameters`
--

DROP TABLE IF EXISTS `canonical_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `canonical_parameters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `field_identity` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `definition` text COLLATE utf8mb4_unicode_ci,
  `domain` enum('meteorology','hydrology','geotechnical') COLLATE utf8mb4_unicode_ci NOT NULL,
  `canonical_unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'decimal',
  `measurement_characteristic` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_platform_processed` tinyint(1) NOT NULL DEFAULT '0',
  `source_fields` json DEFAULT NULL,
  `formula` text COLLATE utf8mb4_unicode_ci,
  `input_requirements` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `canonical_parameters_field_identity_unique` (`field_identity`),
  KEY `canonical_parameters_domain_status_index` (`domain`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `canonical_parameters`
--

LOCK TABLES `canonical_parameters` WRITE;
/*!40000 ALTER TABLE `canonical_parameters` DISABLE KEYS */;
INSERT INTO `canonical_parameters` (`id`, `field_identity`, `definition`, `domain`, `canonical_unit`, `data_type`, `measurement_characteristic`, `is_platform_processed`, `source_fields`, `formula`, `input_requirements`, `status`, `created_at`, `updated_at`) VALUES (1,'WindSpeed','Ultrasonic wind speed measurement from RK900-11','meteorology','m/s','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±5%\", \"max_value\": 40, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Wind speed row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(2,'WindDirection','Ultrasonic wind direction measurement from RK900-11','meteorology','°','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±3°\", \"max_value\": 359, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Wind direction row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(3,'Temperature','Atmospheric temperature from RK900-11','meteorology','°C','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±1°C\", \"max_value\": 80, \"min_value\": -40, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric temperature row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(4,'Humidity','Atmospheric relative humidity from RK900-11','meteorology','%RH','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±3%\", \"max_value\": 100, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric humidity row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(5,'Pressure','Atmospheric pressure from RK900-11','meteorology','hPa','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±2hPa\", \"max_value\": 1100, \"min_value\": 300, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric pressure row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(6,'Rainfall','Rainfall rate (hourly) from RK900-11, valid at wind speed ≤5 m/s','meteorology','mm/hr','decimal','accumulated',0,NULL,NULL,'{\"accuracy\": \"±8% (at wind speed ≤5 m/s)\", \"max_value\": 200, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Rainfall row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(7,'Altitude','Altitude/elevation measurement from RK900-11 barometer','meteorology','m','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±8%\", \"max_value\": 9000, \"min_value\": -500, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Altitude row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(8,'Irradiance','Solar irradiance (pyranometer) from RK900-11 at vertical light incidence','meteorology','W/m²','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±5% (at vertical light)\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Irradiance row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(9,'Illumination','Illuminance (lux) from RK900-11 at vertical light incidence','meteorology','lux','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±5% (at vertical light)\", \"max_value\": 200000, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Illumination row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(10,'PM25','Particulate matter ≤2.5 μm from RK900-11 air quality sensor','meteorology','μg/m³','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±5%\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - PM2.5 row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(11,'PM10','Particulate matter ≤10 μm from RK900-11 air quality sensor','meteorology','μg/m³','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±8%\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - PM10 row\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(12,'SoilMoisture','Volumetric soil moisture content measured by RK510-01 FDR soil moisture sensor','geotechnical','%','decimal','instantaneous',0,NULL,NULL,'{\"accuracy\": \"±2% (0-50%)\", \"max_value\": 100, \"min_value\": 0, \"resolution\": null, \"source_url\": \"https://www.rikasensor.com/perfect-soil-moisture-sensor-manufacturer-for-soil-monitoring.html\", \"source_note\": \"RK510-01 official Rika Sensor product page; ranges available: 0-100%, 0-50%, 0-30%\", \"source_reference\": \"SPECIFICATIONS table - Range / Accuracy rows\"}','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(13,'WaterLevel','WaterLevel','hydrology','m','numeric','measured',0,NULL,NULL,NULL,'active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(14,'WaterVelocity','WaterVelocity','hydrology','m/s','numeric','measured',0,NULL,NULL,NULL,'active','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `canonical_parameters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','suspended','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `max_users` int unsigned NOT NULL DEFAULT '10',
  `max_projects` int unsigned NOT NULL DEFAULT '5',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_client_code_unique` (`client_code`),
  KEY `clients_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` (`id`, `client_code`, `name`, `contact_name`, `contact_email`, `contact_phone`, `status`, `max_users`, `max_projects`, `created_at`, `updated_at`, `deleted_at`) VALUES (4,'DEMO-CLIENT','Demo Client Sentinel EMP','Demo PIC','client.demo@resq.local','+628000000001','active',5,3,'2026-08-11 05:02:27','2026-08-11 05:02:27',NULL);
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `connectivity_configs`
--

DROP TABLE IF EXISTS `connectivity_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `connectivity_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_logger_id` bigint unsigned NOT NULL,
  `connectivity_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `communication_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protocol` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host_or_endpoint` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int unsigned DEFAULT NULL,
  `topic_or_api_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_port` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baud_rate` int unsigned DEFAULT NULL,
  `data_bits` tinyint unsigned DEFAULT NULL,
  `stop_bits` tinyint unsigned DEFAULT NULL,
  `parity` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timeout_ms` int unsigned DEFAULT NULL,
  `pin_mapping` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monitored_sensor_ids` json DEFAULT NULL,
  `rednode_host` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rednode_ssh_port` int unsigned DEFAULT NULL,
  `rednode_ssh_user` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rednode_ssh_password` text COLLATE utf8mb4_unicode_ci,
  `rednode_gateway_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rednode_poll_interval_ms` int unsigned DEFAULT NULL,
  `sim_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imei` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apn` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connectivity_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Online',
  `connection_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `uplink_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `serial_settings` json DEFAULT NULL,
  `runtime_state` json DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_connected_at` timestamp NULL DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `last_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `connectivity_configs_connectivity_code_unique` (`connectivity_code`),
  KEY `connectivity_configs_connection_state_uplink_state_index` (`connection_state`,`uplink_state`),
  KEY `connectivity_logger_last_seen_idx` (`data_logger_id`,`last_seen_at`),
  CONSTRAINT `connectivity_configs_data_logger_id_foreign` FOREIGN KEY (`data_logger_id`) REFERENCES `data_loggers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `connectivity_configs`
--

LOCK TABLES `connectivity_configs` WRITE;
/*!40000 ALTER TABLE `connectivity_configs` DISABLE KEYS */;
INSERT INTO `connectivity_configs` (`id`, `data_logger_id`, `connectivity_code`, `communication_type`, `protocol`, `host_or_endpoint`, `port`, `topic_or_api_path`, `gateway_id`, `serial_port`, `baud_rate`, `data_bits`, `stop_bits`, `parity`, `timeout_ms`, `pin_mapping`, `monitored_sensor_ids`, `rednode_host`, `rednode_ssh_port`, `rednode_ssh_user`, `rednode_ssh_password`, `rednode_gateway_path`, `rednode_poll_interval_ms`, `sim_number`, `imei`, `apn`, `connectivity_status`, `connection_state`, `uplink_state`, `serial_settings`, `runtime_state`, `last_seen_at`, `last_connected_at`, `last_error`, `last_payload`, `created_at`, `updated_at`) VALUES (5,5,'SERIAL-REDNODE-BLIIOT-011','Serial','Modbus RTU','/dev/ttyAS2',NULL,'Pin 5-6 / /dev/ttyAS2','REDNODE-BLIIOT-011','/dev/ttyAS2',9600,8,1,'none',1500,'Pin 5-6 / /dev/ttyAS2','[7, 8]',NULL,NULL,NULL,NULL,NULL,1000,NULL,NULL,NULL,'Online','connected','uplink',NULL,'{\"source\": \"rika-demo-seeder\", \"monitoring_enabled\": true}','2026-08-11 05:12:08','2026-08-11 05:12:08',NULL,'{\"device\": {\"vendor\": \"Bliiot\", \"hostname\": \"BL118-bliiot\", \"platform\": \"linux arm 5.4.61\", \"device_uid\": \"rn-b16752f7e8cf1c81\", \"logger_code\": \"REDNODE-BLIIOT-011\", \"device_label\": \"BL118-bliiot\", \"logger_model\": \"RedNode Bliiot\", \"mac_addresses\": [\"00:e0:9a:24:8a:d0\"], \"gateway_version\": \"1.0.0\", \"firmware_version\": \"Ubuntu 20.04.5 LTS\"}, \"sensors\": [{\"raw\": 340, \"rows\": [{\"hex\": \"0x0097\", \"raw\": 151, \"int16\": 151, \"binary\": \"00000000 10010111\", \"uint16\": 151, \"address\": 0, \"function_code\": \"FC03\"}, {\"hex\": \"0x0154\", \"raw\": 340, \"int16\": 340, \"binary\": \"00000001 01010100\", \"uint16\": 340, \"address\": 1, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 2, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 3, \"function_code\": \"FC03\"}, {\"hex\": \"0x3020\", \"raw\": 12320, \"int16\": 12320, \"binary\": \"00110000 00100000\", \"uint16\": 12320, \"address\": 4, \"function_code\": \"FC03\"}, {\"hex\": \"0x4200\", \"raw\": 16896, \"int16\": 16896, \"binary\": \"01000010 00000000\", \"uint16\": 16896, \"address\": 5, \"function_code\": \"FC03\"}, {\"hex\": \"0x9C96\", \"raw\": 40086, \"int16\": -25450, \"binary\": \"10011100 10010110\", \"uint16\": 40086, \"address\": 6, \"function_code\": \"FC03\"}, {\"hex\": \"0x4265\", \"raw\": 16997, \"int16\": 16997, \"binary\": \"01000010 01100101\", \"uint16\": 16997, \"address\": 7, \"function_code\": \"FC03\"}, {\"hex\": \"0xEB4C\", \"raw\": 60236, \"int16\": -5300, \"binary\": \"11101011 01001100\", \"uint16\": 60236, \"address\": 8, \"function_code\": \"FC03\"}, {\"hex\": \"0x447A\", \"raw\": 17530, \"int16\": 17530, \"binary\": \"01000100 01111010\", \"uint16\": 17530, \"address\": 9, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 10, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 11, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 12, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 13, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 14, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 15, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 16, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 17, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 18, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 19, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 20, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 21, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 22, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 23, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 24, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 25, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 26, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 27, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 28, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 29, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 30, \"function_code\": \"FC03\"}, {\"hex\": \"0x66D6\", \"raw\": 26326, \"int16\": 26326, \"binary\": \"01100110 11010110\", \"uint16\": 26326, \"address\": 31, \"function_code\": \"FC03\"}, {\"hex\": \"0x3EFD\", \"raw\": 16125, \"int16\": 16125, \"binary\": \"00111110 11111101\", \"uint16\": 16125, \"address\": 32, \"function_code\": \"FC03\"}, {\"hex\": \"0xE11E\", \"raw\": 57630, \"int16\": -7906, \"binary\": \"11100001 00011110\", \"uint16\": 57630, \"address\": 33, \"function_code\": \"FC03\"}, {\"hex\": \"0x3D34\", \"raw\": 15668, \"int16\": 15668, \"binary\": \"00111101 00110100\", \"uint16\": 15668, \"address\": 34, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 35, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 36, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 37, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 38, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 39, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 40, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 41, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 42, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 43, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 44, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 45, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 46, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 47, \"function_code\": \"FC03\"}, {\"hex\": \"0x0000\", \"raw\": 0, \"int16\": 0, \"binary\": \"00000000 00000000\", \"uint16\": 0, \"address\": 48, \"function_code\": \"FC03\"}], \"error\": null, \"value\": \"WindDirection 340.00 °, WindSpeed 0.00 m/s, Temperature 32.05 °C, Humidity 57.40 %RH, Pressure 1003.68 hPa, Rainfall 0.00 mm/hr, PM25 0.00 μg/m³, Illumination 0.00 lux, Irradiance 0.04 W/m², Altitude 0.00 m, PM10 0.00 μg/m³\", \"address\": 0, \"quantity\": 49, \"parameter\": \"Weather Station\", \"registers\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], \"threshold\": null, \"pin_mapping\": \"Pin 5-6 / /dev/ttyAS2\", \"received_at\": \"2026-08-11T12:12:08.034Z\", \"sensor_code\": \"RIKA-CUACA\", \"sensor_type\": \"weather_station\", \"serial_port\": \"/dev/ttyAS2\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"sensor_label\": \"Weather Station - WindDirection, WindSpeed, Temperature, Humidity, Pressure, Rainfall, PM25, Illumination, Irradiance, Altitude, PM10\", \"function_code\": \"FC03\", \"numeric_value\": 340, \"parameter_values\": [{\"raw\": 340, \"unit\": \"°\", \"label\": \"WindDirection\", \"value\": 340, \"parameter\": \"WindDirection\", \"registers\": [340], \"value_text\": \"340.00 °\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±3°\", \"max_value\": 359, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Wind direction row\"}, \"register_index\": 1, \"register_address\": \"40002\", \"source_parameter\": \"Wind direction\", \"datasheet_reference\": \"SPECIFICATIONS table - Wind direction row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 0, \"unit\": \"m/s\", \"label\": \"WindSpeed\", \"value\": 0, \"parameter\": \"WindSpeed\", \"registers\": [0, 0], \"value_text\": \"0.00 m/s\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±5%\", \"max_value\": 40, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Wind speed row\"}, \"register_index\": 2, \"register_address\": \"40003\", \"source_parameter\": \"Wind speed\", \"datasheet_reference\": \"SPECIFICATIONS table - Wind speed row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 32.0469970703125, \"unit\": \"°C\", \"label\": \"Temperature\", \"value\": 32.0469970703125, \"parameter\": \"Temperature\", \"registers\": [12320, 16896], \"value_text\": \"32.05 °C\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±1°C\", \"max_value\": 80, \"min_value\": -40, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric temperature row\"}, \"register_index\": 4, \"register_address\": \"40005\", \"source_parameter\": \"Atmospheric temperature\", \"datasheet_reference\": \"SPECIFICATIONS table - Atmospheric temperature row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 57.40291595458984, \"unit\": \"%RH\", \"label\": \"Humidity\", \"value\": 57.40291595458984, \"parameter\": \"Humidity\", \"registers\": [40086, 16997], \"value_text\": \"57.40 %RH\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±3%\", \"max_value\": 100, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric humidity row\"}, \"register_index\": 6, \"register_address\": \"40007\", \"source_parameter\": \"Atmospheric humidity\", \"datasheet_reference\": \"SPECIFICATIONS table - Atmospheric humidity row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 1003.676513671875, \"unit\": \"hPa\", \"label\": \"Pressure\", \"value\": 1003.676513671875, \"parameter\": \"Pressure\", \"registers\": [60236, 17530], \"value_text\": \"1003.68 hPa\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±2hPa\", \"max_value\": 1100, \"min_value\": 300, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric pressure row\"}, \"register_index\": 8, \"register_address\": \"40009\", \"source_parameter\": \"Atmospheric pressure\", \"datasheet_reference\": \"SPECIFICATIONS table - Atmospheric pressure row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 0, \"unit\": \"mm/hr\", \"label\": \"Rainfall\", \"value\": 0, \"parameter\": \"Rainfall\", \"registers\": [0, 0], \"value_text\": \"0.00 mm/hr\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±8% (at wind speed ≤5 m/s)\", \"max_value\": 200, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Rainfall row\"}, \"register_index\": 12, \"register_address\": \"40013\", \"source_parameter\": \"Rainfall\", \"datasheet_reference\": \"SPECIFICATIONS table - Rainfall row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 0, \"unit\": \"μg/m³\", \"label\": \"PM25\", \"value\": 0, \"parameter\": \"PM25\", \"registers\": [0, 0], \"value_text\": \"0.00 μg/m³\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±5%\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - PM2.5 row\"}, \"register_index\": 25, \"register_address\": \"40026\", \"source_parameter\": \"Dust concentration (PM2.5)\", \"datasheet_reference\": \"SPECIFICATIONS table - PM2.5 row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 0, \"unit\": \"lux\", \"label\": \"Illumination\", \"value\": 0, \"parameter\": \"Illumination\", \"registers\": [0, 0], \"value_text\": \"0.00 lux\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±5% (at vertical light)\", \"max_value\": 200000, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Illumination row\"}, \"register_index\": 29, \"register_address\": \"40030\", \"source_parameter\": \"Illumination\", \"datasheet_reference\": \"SPECIFICATIONS table - Illumination row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 0.04416000097990036, \"unit\": \"W/m²\", \"label\": \"Irradiance\", \"value\": 0.04416000097990036, \"parameter\": \"Irradiance\", \"registers\": [57630, 15668], \"value_text\": \"0.04 W/m²\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±5% (at vertical light)\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Irradiance row\"}, \"register_index\": 33, \"register_address\": \"40034\", \"source_parameter\": \"Radiation\", \"datasheet_reference\": \"SPECIFICATIONS table - Irradiance row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 0, \"unit\": \"m\", \"label\": \"Altitude\", \"value\": 0, \"parameter\": \"Altitude\", \"registers\": [0, 0], \"value_text\": \"0.00 m\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±8%\", \"max_value\": 9000, \"min_value\": -500, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Altitude row\"}, \"register_index\": 37, \"register_address\": \"40038\", \"source_parameter\": \"Altitude\", \"datasheet_reference\": \"SPECIFICATIONS table - Altitude row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"raw\": 0, \"unit\": \"μg/m³\", \"label\": \"PM10\", \"value\": 0, \"parameter\": \"PM10\", \"registers\": [0, 0], \"value_text\": \"0.00 μg/m³\", \"modbus_frame\": {\"rx\": \"01 03 62 00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 28 5B\", \"tx\": \"01 03 00 00 00 31 84 1E\", \"note\": \"Reconstructed from successful Modbus RTU request/response data captured by the gateway.\", \"address\": 0, \"quantity\": 49, \"slave_id\": 1, \"function_code\": \"FC03\", \"register_bytes\": \"00 97 01 54 00 00 00 00 30 20 42 00 9C 96 42 65 EB 4C 44 7A 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 66 D6 3E FD E1 1E 3D 34 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00\", \"register_words\": [151, 340, 0, 0, 12320, 16896, 40086, 16997, 60236, 17530, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 26326, 16125, 57630, 15668, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]}, \"requirements\": {\"accuracy\": \"±8%\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - PM10 row\"}, \"register_index\": 47, \"register_address\": \"40048\", \"source_parameter\": \"PM10\", \"datasheet_reference\": \"SPECIFICATIONS table - PM10 row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}], \"mapped_parameters\": [{\"label\": \"WindDirection\", \"offset\": 0, \"data_type\": \"uint16\", \"parameter\": \"WindDirection\", \"byte_order\": null, \"profile_id\": 5, \"data_length\": 1, \"source_unit\": \"deg\", \"profile_code\": \"MAP-RIKA-CUACA-WINDDIRECTION\", \"requirements\": {\"accuracy\": \"±3°\", \"max_value\": 359, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Wind direction row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"°\", \"register_index\": 1, \"canonical_field\": \"WindDirection\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40002\", \"source_parameter\": \"Wind direction\", \"datasheet_reference\": \"SPECIFICATIONS table - Wind direction row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"WindSpeed\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"WindSpeed\", \"byte_order\": \"CDAB\", \"profile_id\": 6, \"data_length\": 2, \"source_unit\": \"m/s\", \"profile_code\": \"MAP-RIKA-CUACA-WINDSPEED\", \"requirements\": {\"accuracy\": \"±5%\", \"max_value\": 40, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Wind speed row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"m/s\", \"register_index\": 2, \"canonical_field\": \"WindSpeed\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40003\", \"source_parameter\": \"Wind speed\", \"datasheet_reference\": \"SPECIFICATIONS table - Wind speed row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"Temperature\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"Temperature\", \"byte_order\": \"CDAB\", \"profile_id\": 7, \"data_length\": 2, \"source_unit\": \"C\", \"profile_code\": \"MAP-RIKA-CUACA-TEMPERATURE\", \"requirements\": {\"accuracy\": \"±1°C\", \"max_value\": 80, \"min_value\": -40, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric temperature row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"°C\", \"register_index\": 4, \"canonical_field\": \"Temperature\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40005\", \"source_parameter\": \"Atmospheric temperature\", \"datasheet_reference\": \"SPECIFICATIONS table - Atmospheric temperature row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"Humidity\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"Humidity\", \"byte_order\": \"CDAB\", \"profile_id\": 8, \"data_length\": 2, \"source_unit\": \"%\", \"profile_code\": \"MAP-RIKA-CUACA-HUMIDITY\", \"requirements\": {\"accuracy\": \"±3%\", \"max_value\": 100, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric humidity row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"%RH\", \"register_index\": 6, \"canonical_field\": \"Humidity\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40007\", \"source_parameter\": \"Atmospheric humidity\", \"datasheet_reference\": \"SPECIFICATIONS table - Atmospheric humidity row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"Pressure\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"Pressure\", \"byte_order\": \"CDAB\", \"profile_id\": 9, \"data_length\": 2, \"source_unit\": \"hPa\", \"profile_code\": \"MAP-RIKA-CUACA-PRESSURE\", \"requirements\": {\"accuracy\": \"±2hPa\", \"max_value\": 1100, \"min_value\": 300, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Atmospheric pressure row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"hPa\", \"register_index\": 8, \"canonical_field\": \"Pressure\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40009\", \"source_parameter\": \"Atmospheric pressure\", \"datasheet_reference\": \"SPECIFICATIONS table - Atmospheric pressure row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"Rainfall\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"Rainfall\", \"byte_order\": \"CDAB\", \"profile_id\": 10, \"data_length\": 2, \"source_unit\": \"mm\", \"profile_code\": \"MAP-RIKA-CUACA-RAINFALL\", \"requirements\": {\"accuracy\": \"±8% (at wind speed ≤5 m/s)\", \"max_value\": 200, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Rainfall row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"mm/hr\", \"register_index\": 12, \"canonical_field\": \"Rainfall\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40013\", \"source_parameter\": \"Rainfall\", \"datasheet_reference\": \"SPECIFICATIONS table - Rainfall row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"PM25\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"PM25\", \"byte_order\": \"CDAB\", \"profile_id\": 11, \"data_length\": 2, \"source_unit\": \"ug/m3\", \"profile_code\": \"MAP-RIKA-CUACA-PM25\", \"requirements\": {\"accuracy\": \"±5%\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - PM2.5 row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"μg/m³\", \"register_index\": 25, \"canonical_field\": \"PM25\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40026\", \"source_parameter\": \"Dust concentration (PM2.5)\", \"datasheet_reference\": \"SPECIFICATIONS table - PM2.5 row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"Illumination\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"Illumination\", \"byte_order\": \"CDAB\", \"profile_id\": 12, \"data_length\": 2, \"source_unit\": \"lux\", \"profile_code\": \"MAP-RIKA-CUACA-ILLUMINATION\", \"requirements\": {\"accuracy\": \"±5% (at vertical light)\", \"max_value\": 200000, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Illumination row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"lux\", \"register_index\": 29, \"canonical_field\": \"Illumination\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40030\", \"source_parameter\": \"Illumination\", \"datasheet_reference\": \"SPECIFICATIONS table - Illumination row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"Irradiance\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"Irradiance\", \"byte_order\": \"CDAB\", \"profile_id\": 13, \"data_length\": 2, \"source_unit\": \"W/m2\", \"profile_code\": \"MAP-RIKA-CUACA-IRRADIANCE\", \"requirements\": {\"accuracy\": \"±5% (at vertical light)\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 0.1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Irradiance row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"W/m²\", \"register_index\": 33, \"canonical_field\": \"Irradiance\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40034\", \"source_parameter\": \"Radiation\", \"datasheet_reference\": \"SPECIFICATIONS table - Irradiance row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"Altitude\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"Altitude\", \"byte_order\": \"CDAB\", \"profile_id\": 14, \"data_length\": 2, \"source_unit\": \"m\", \"profile_code\": \"MAP-RIKA-CUACA-ALTITUDE\", \"requirements\": {\"accuracy\": \"±8%\", \"max_value\": 9000, \"min_value\": -500, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - Altitude row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"m\", \"register_index\": 37, \"canonical_field\": \"Altitude\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40038\", \"source_parameter\": \"Altitude\", \"datasheet_reference\": \"SPECIFICATIONS table - Altitude row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}, {\"label\": \"PM10\", \"offset\": 0, \"data_type\": \"float32\", \"parameter\": \"PM10\", \"byte_order\": \"CDAB\", \"profile_id\": 15, \"data_length\": 2, \"source_unit\": \"ug/m3\", \"profile_code\": \"MAP-RIKA-CUACA-PM10\", \"requirements\": {\"accuracy\": \"±8%\", \"max_value\": 2000, \"min_value\": 0, \"resolution\": 1, \"source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\", \"source_note\": \"RK900-11 official Rika Sensor product page\", \"source_reference\": \"SPECIFICATIONS table - PM10 row\"}, \"scale_factor\": 1, \"value_origin\": \"direct_measurement\", \"function_code\": \"FC03\", \"canonical_unit\": \"μg/m³\", \"register_index\": 47, \"canonical_field\": \"PM10\", \"canonical_domain\": \"meteorology\", \"register_address\": \"40048\", \"source_parameter\": \"PM10\", \"datasheet_reference\": \"SPECIFICATIONS table - PM10 row\", \"datasheet_source_url\": \"https://www.rikasensor.com/rk900-11-ultrasonic-automatic-weather-instrument.html\"}], \"threshold_exceeded\": null, \"weather_parameters\": [\"WindDirection\", \"WindSpeed\", \"Temperature\", \"Humidity\", \"Pressure\", \"Rainfall\", \"PM25\", \"Illumination\", \"Irradiance\", \"Altitude\", \"PM10\"]}], \"reported_at\": \"2026-08-11T12:12:07.892684Z\"}','2026-08-11 05:02:27','2026-08-11 05:12:08');
/*!40000 ALTER TABLE `connectivity_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `corridor_monitorings`
--

DROP TABLE IF EXISTS `corridor_monitorings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `corridor_monitorings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `workspace_id` bigint unsigned NOT NULL,
  `reference_route_id` bigint unsigned DEFAULT NULL,
  `corridor_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path_coordinates` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Planned',
  `status_metadata` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `corridor_monitorings_corridor_code_unique` (`corridor_code`),
  KEY `corridor_monitorings_workspace_id_foreign` (`workspace_id`),
  KEY `corridor_monitorings_reference_route_id_foreign` (`reference_route_id`),
  KEY `corridor_monitorings_project_id_workspace_id_index` (`project_id`,`workspace_id`),
  KEY `corridor_monitorings_project_id_status_index` (`project_id`,`status`),
  CONSTRAINT `corridor_monitorings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `corridor_monitorings_reference_route_id_foreign` FOREIGN KEY (`reference_route_id`) REFERENCES `reference_routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `corridor_monitorings_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `corridor_monitorings`
--

LOCK TABLES `corridor_monitorings` WRITE;
/*!40000 ALTER TABLE `corridor_monitorings` DISABLE KEYS */;
INSERT INTO `corridor_monitorings` (`id`, `project_id`, `workspace_id`, `reference_route_id`, `corridor_code`, `name`, `path_coordinates`, `status`, `status_metadata`, `notes`, `created_at`, `updated_at`) VALUES (4,4,4,1,'COR-DEMO-01','Semeru Lahar Corridor','[{\"lat\": -8.108, \"lng\": 112.922}, {\"lat\": -8.1378, \"lng\": 112.9467}, {\"lat\": -8.1724, \"lng\": 112.9716}, {\"lat\": -8.2052, \"lng\": 112.994}, {\"lat\": -8.239, \"lng\": 113.0185}]','Active','{\"hazard_state\": \"WASPADA\"}','Operational corridor from Semeru source area to downstream warning response points.','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `corridor_monitorings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` double DEFAULT NULL,
  `balance` double DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_logger_discoveries`
--

DROP TABLE IF EXISTS `data_logger_discoveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_logger_discoveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `matched_data_logger_id` bigint unsigned DEFAULT NULL,
  `device_uid` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logger_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logger_model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firmware_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hostname` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_ip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_addresses` json DEFAULT NULL,
  `last_payload` json DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Detected',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `data_logger_discoveries_matched_data_logger_id_foreign` (`matched_data_logger_id`),
  KEY `data_logger_discoveries_device_uid_index` (`device_uid`),
  KEY `data_logger_discoveries_logger_code_index` (`logger_code`),
  KEY `data_logger_discoveries_serial_number_index` (`serial_number`),
  KEY `data_logger_discoveries_request_ip_index` (`request_ip`),
  CONSTRAINT `data_logger_discoveries_matched_data_logger_id_foreign` FOREIGN KEY (`matched_data_logger_id`) REFERENCES `data_loggers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_logger_discoveries`
--

LOCK TABLES `data_logger_discoveries` WRITE;
/*!40000 ALTER TABLE `data_logger_discoveries` DISABLE KEYS */;
INSERT INTO `data_logger_discoveries` (`id`, `matched_data_logger_id`, `device_uid`, `logger_code`, `serial_number`, `logger_model`, `vendor`, `firmware_version`, `device_label`, `hostname`, `request_ip`, `mac_addresses`, `last_payload`, `last_seen_at`, `status`, `created_at`, `updated_at`) VALUES (1,5,'rn-b16752f7e8cf1c81','REDNODE-BLIIOT-011',NULL,'RedNode Bliiot','Bliiot','Ubuntu 20.04.5 LTS','BL118-bliiot','BL118-bliiot','192.168.3.1','[\"00:e0:9a:24:8a:d0\"]','{\"vendor\": \"Bliiot\", \"hostname\": \"BL118-bliiot\", \"platform\": \"linux arm 5.4.61\", \"device_uid\": \"rn-b16752f7e8cf1c81\", \"logger_code\": \"REDNODE-BLIIOT-011\", \"device_label\": \"BL118-bliiot\", \"logger_model\": \"RedNode Bliiot\", \"mac_addresses\": [\"00:e0:9a:24:8a:d0\"], \"gateway_version\": \"1.0.0\", \"firmware_version\": \"Ubuntu 20.04.5 LTS\"}','2026-08-11 05:12:07','Matched','2026-08-11 05:02:16','2026-08-11 05:12:07');
/*!40000 ALTER TABLE `data_logger_discoveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_loggers`
--

DROP TABLE IF EXISTS `data_loggers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_loggers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `monitoring_station_id` bigint unsigned DEFAULT NULL,
  `logger_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serial_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logger_model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firmware_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remote_host` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remote_ssh_port` int unsigned DEFAULT NULL,
  `remote_ssh_user` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remote_ssh_password` text COLLATE utf8mb4_unicode_ci,
  `remote_gateway_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remote_last_tested_at` timestamp NULL DEFAULT NULL,
  `remote_last_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remote_last_message` text COLLATE utf8mb4_unicode_ci,
  `logger_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data_loggers_logger_code_unique` (`logger_code`),
  KEY `data_loggers_monitoring_station_id_foreign` (`monitoring_station_id`),
  CONSTRAINT `data_loggers_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_loggers`
--

LOCK TABLES `data_loggers` WRITE;
/*!40000 ALTER TABLE `data_loggers` DISABLE KEYS */;
INSERT INTO `data_loggers` (`id`, `monitoring_station_id`, `logger_code`, `serial_number`, `logger_model`, `vendor`, `firmware_version`, `device_label`, `remote_host`, `remote_ssh_port`, `remote_ssh_user`, `remote_ssh_password`, `remote_gateway_path`, `remote_last_tested_at`, `remote_last_status`, `remote_last_message`, `logger_status`, `created_at`, `updated_at`) VALUES (5,4,'REDNODE-BLIIOT-011',NULL,'RedNode Bliiot','Bliiot','Ubuntu 20.04.5 LTS','BL118-bliiot','192.168.3.1',22,'root',NULL,'/root/rednode-gateway','2026-08-11 05:12:07','Success','Gateway heartbeat/config dari 192.168.3.1','Active','2026-08-11 05:02:27','2026-08-11 05:12:07');
/*!40000 ALTER TABLE `data_loggers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_credentials`
--

DROP TABLE IF EXISTS `device_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `device_credentials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_logger_id` bigint unsigned NOT NULL,
  `credential_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mqtt_username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mqtt_password_hash` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_ref` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credential_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_credentials_credential_code_unique` (`credential_code`),
  KEY `device_credentials_data_logger_id_foreign` (`data_logger_id`),
  CONSTRAINT `device_credentials_data_logger_id_foreign` FOREIGN KEY (`data_logger_id`) REFERENCES `data_loggers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device_credentials`
--

LOCK TABLES `device_credentials` WRITE;
/*!40000 ALTER TABLE `device_credentials` DISABLE KEYS */;
/*!40000 ALTER TABLE `device_credentials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `geospatial_workspaces`
--

DROP TABLE IF EXISTS `geospatial_workspaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `geospatial_workspaces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `workspace_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hazard` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beneficiaries` int unsigned NOT NULL DEFAULT '0',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `basemap_provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OpenStreetMap',
  `basemap_tile_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_zoom` tinyint unsigned NOT NULL DEFAULT '5',
  `map_bounds` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `geospatial_workspaces_workspace_code_unique` (`workspace_code`),
  KEY `geospatial_workspaces_project_id_foreign` (`project_id`),
  CONSTRAINT `geospatial_workspaces_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `geospatial_workspaces`
--

LOCK TABLES `geospatial_workspaces` WRITE;
/*!40000 ALTER TABLE `geospatial_workspaces` DISABLE KEYS */;
INSERT INTO `geospatial_workspaces` (`id`, `project_id`, `workspace_code`, `name`, `hazard`, `province`, `city`, `beneficiaries`, `latitude`, `longitude`, `status`, `basemap_provider`, `basemap_tile_url`, `default_zoom`, `map_bounds`, `created_at`, `updated_at`) VALUES (4,4,'GWS-EMP-DEMO','Semeru Geospatial Workspace','Hydromet','Jawa Timur','Lumajang',12000,-8.1724000,112.9716000,'Normal','OpenStreetMap',NULL,12,NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `geospatial_workspaces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hydromet_ews_relationships`
--

DROP TABLE IF EXISTS `hydromet_ews_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hydromet_ews_relationships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `corridor_id` bigint unsigned NOT NULL,
  `monitoring_station_id` bigint unsigned NOT NULL,
  `warning_station_id` bigint unsigned DEFAULT NULL,
  `relationship_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hydromet_ews_relationships_relationship_code_unique` (`relationship_code`),
  UNIQUE KEY `hydromet_ews_relationship_scope_unique` (`project_id`,`corridor_id`,`monitoring_station_id`,`warning_station_id`),
  KEY `hydromet_ews_relationships_corridor_id_foreign` (`corridor_id`),
  KEY `hydromet_ews_relationships_monitoring_station_id_foreign` (`monitoring_station_id`),
  KEY `hydromet_ews_relationships_warning_station_id_foreign` (`warning_station_id`),
  KEY `hews_project_status_idx` (`project_id`,`status`),
  CONSTRAINT `hydromet_ews_relationships_corridor_id_foreign` FOREIGN KEY (`corridor_id`) REFERENCES `corridor_monitorings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_ews_relationships_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_ews_relationships_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_ews_relationships_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hydromet_ews_relationships`
--

LOCK TABLES `hydromet_ews_relationships` WRITE;
/*!40000 ALTER TABLE `hydromet_ews_relationships` DISABLE KEYS */;
INSERT INTO `hydromet_ews_relationships` (`id`, `project_id`, `corridor_id`, `monitoring_station_id`, `warning_station_id`, `relationship_code`, `name`, `status`, `notes`, `created_at`, `updated_at`) VALUES (4,4,4,4,4,'EWS-DEMO-01','Demo Hydromet EWS Relationship','active','Corridor + Monitoring Station + Warning Station demo relation.','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `hydromet_ews_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hydromet_hazard_classifications`
--

DROP TABLE IF EXISTS `hydromet_hazard_classifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hydromet_hazard_classifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `hydromet_ews_relationship_id` bigint unsigned NOT NULL,
  `corridor_id` bigint unsigned NOT NULL,
  `monitoring_station_id` bigint unsigned NOT NULL,
  `sensor_id` bigint unsigned DEFAULT NULL,
  `canonical_parameter_id` bigint unsigned DEFAULT NULL,
  `classification_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameter` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reading_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `threshold_config` json DEFAULT NULL,
  `hazard_levels` json NOT NULL,
  `unresolved_business_rules` json DEFAULT NULL,
  `evaluation_engine` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'configuration_only',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hydromet_hazard_classifications_classification_code_unique` (`classification_code`),
  KEY `hhc_relationship_fk` (`hydromet_ews_relationship_id`),
  KEY `hydromet_hazard_classifications_corridor_id_foreign` (`corridor_id`),
  KEY `hydromet_hazard_classifications_monitoring_station_id_foreign` (`monitoring_station_id`),
  KEY `hydromet_hazard_classifications_sensor_id_foreign` (`sensor_id`),
  KEY `hydromet_hazard_classifications_canonical_parameter_id_foreign` (`canonical_parameter_id`),
  KEY `hhc_project_station_idx` (`project_id`,`monitoring_station_id`),
  KEY `hhc_method_status_idx` (`reading_method`,`status`),
  CONSTRAINT `hhc_relationship_fk` FOREIGN KEY (`hydromet_ews_relationship_id`) REFERENCES `hydromet_ews_relationships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_hazard_classifications_canonical_parameter_id_foreign` FOREIGN KEY (`canonical_parameter_id`) REFERENCES `canonical_parameters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hydromet_hazard_classifications_corridor_id_foreign` FOREIGN KEY (`corridor_id`) REFERENCES `corridor_monitorings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_hazard_classifications_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_hazard_classifications_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_hazard_classifications_sensor_id_foreign` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hydromet_hazard_classifications`
--

LOCK TABLES `hydromet_hazard_classifications` WRITE;
/*!40000 ALTER TABLE `hydromet_hazard_classifications` DISABLE KEYS */;
INSERT INTO `hydromet_hazard_classifications` (`id`, `project_id`, `hydromet_ews_relationship_id`, `corridor_id`, `monitoring_station_id`, `sensor_id`, `canonical_parameter_id`, `classification_code`, `parameter`, `reading_method`, `threshold_config`, `hazard_levels`, `unresolved_business_rules`, `evaluation_engine`, `status`, `created_at`, `updated_at`) VALUES (1,4,4,4,4,7,6,'HZ-DEMO-WL-01','Rainfall','Absolute','{\"comparison\": \"configuration_only\"}','{\"AWAS\": {\"level\": \"AWAS\", \"threshold\": 4}, \"SIAGA\": {\"level\": \"SIAGA\", \"threshold\": 3}, \"WASPADA\": {\"level\": \"WASPADA\", \"threshold\": 2}}','[\"Comparison direction and persistence rules must be confirmed by hydromet authority.\"]','configuration_only','active','2026-08-11 05:02:27','2026-08-11 05:08:02'),(2,4,4,4,4,8,12,'HZ-DEMO-SOILMOISTURE-01','SoilMoisture','Absolute','{\"basis\": \"Mapped SoilMoisture value from raw register multiplied by 0.1.\", \"comparison\": \">=\"}','{\"AWAS\": {\"level\": \"AWAS\", \"threshold\": 35}, \"SIAGA\": {\"level\": \"SIAGA\", \"threshold\": 30}, \"WASPADA\": {\"level\": \"WASPADA\", \"threshold\": 25}}','[\"Persistence and de-escalation windows must be confirmed by hydromet authority.\"]','configuration_only','active','2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `hydromet_hazard_classifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hydromet_wdam_configs`
--

DROP TABLE IF EXISTS `hydromet_wdam_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hydromet_wdam_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `hydromet_ews_relationship_id` bigint unsigned NOT NULL,
  `warning_station_id` bigint unsigned DEFAULT NULL,
  `wdam_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dashboard_notification_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `registered_recipients` json DEFAULT NULL,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `sms_provider_ref` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `whatsapp_provider_ref` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warning_station_assignment_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `automatic_activation_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `authority_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual_authority',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hydromet_wdam_configs_wdam_code_unique` (`wdam_code`),
  KEY `hwdam_relationship_fk` (`hydromet_ews_relationship_id`),
  KEY `hwdam_project_status_idx` (`project_id`,`status`),
  KEY `hydromet_wdam_warning_station_idx` (`warning_station_id`,`warning_station_assignment_enabled`),
  CONSTRAINT `hwdam_relationship_fk` FOREIGN KEY (`hydromet_ews_relationship_id`) REFERENCES `hydromet_ews_relationships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_wdam_configs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hydromet_wdam_configs_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hydromet_wdam_configs`
--

LOCK TABLES `hydromet_wdam_configs` WRITE;
/*!40000 ALTER TABLE `hydromet_wdam_configs` DISABLE KEYS */;
INSERT INTO `hydromet_wdam_configs` (`id`, `project_id`, `hydromet_ews_relationship_id`, `warning_station_id`, `wdam_code`, `dashboard_notification_enabled`, `registered_recipients`, `sms_enabled`, `sms_provider_ref`, `whatsapp_enabled`, `whatsapp_provider_ref`, `warning_station_assignment_enabled`, `automatic_activation_enabled`, `authority_method`, `status`, `notes`, `created_at`, `updated_at`) VALUES (4,4,4,4,'WDAM-DEMO-01',1,'[{\"name\": \"Demo Operator\", \"channel\": \"dashboard\"}]',0,NULL,0,NULL,1,0,'manual_authority','active','Provider credentials not selected in demo.','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `hydromet_wdam_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_resets_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2023_07_10_112535_create_customers_table',1),(6,'2026_07_08_000000_create_resq_configuration_tables',1),(7,'2026_07_08_010000_create_provinces_table',1),(8,'2026_07_08_020000_add_coordinates_to_provinces_table',1),(9,'2026_07_08_030000_create_device_setup_tables',1),(10,'2026_07_08_040000_create_mst_prefixes_and_add_sensor_addressing',1),(11,'2026_07_08_050000_add_unique_sensor_prefix_address',1),(12,'2026_07_08_060000_add_modbus_polling_config_to_sensors',1),(13,'2026_07_17_000000_add_rednode_serial_fields_to_connectivity_configs',1),(14,'2026_07_17_010000_add_rednode_heartbeat_fields_to_connectivity_configs',1),(15,'2026_07_17_020000_add_monitored_sensor_ids_to_connectivity_configs',1),(16,'2026_07_17_030000_add_rednode_control_fields_to_connectivity_configs',1),(17,'2026_07_22_000000_add_remote_access_and_weather_parameters',1),(18,'2026_07_22_010000_add_parameter_values_to_telemetry_readings',1),(19,'2026_07_31_000000_create_canonical_data_database_tables',1),(20,'2026_07_31_010000_add_data_logger_id_to_sensors_table',1),(21,'2026_07_31_020000_add_rednode_state_to_connectivity_configs_table',1),(22,'2026_08_03_000000_align_runtime_sensor_schema',1),(23,'2026_08_03_030000_prune_realtime_observation_duplicates',1),(24,'2026_08_06_010000_create_data_logger_discoveries_table',1),(25,'2026_08_10_000000_add_raw_values_to_telemetry_readings',1),(26,'2026_08_10_120000_seed_rk900_11_master_parameters',1),(27,'2026_08_10_130000_create_sensor_mapping_presets',1),(28,'2026_08_10_140000_update_rk900_11_preset_modbus_registers',1),(29,'2026_08_10_150000_expand_sensor_and_telemetry_value_columns',1),(30,'2026_08_10_160000_set_rk900_11_float_byte_order',1),(31,'2026_08_11_000000_create_client_and_rbac_foundation',1),(32,'2026_08_11_010000_extend_users_and_projects_for_client_authority',1),(33,'2026_08_11_020000_create_project_spatial_workspace_tables',1),(34,'2026_08_11_030000_complete_monitoring_station_domain',1),(35,'2026_08_11_040000_complete_warning_station_domain',1),(36,'2026_08_11_050000_create_hydromet_ews_configuration_tables',1),(37,'2026_08_11_060000_prepare_runtime_read_models',1),(38,'2026_08_11_070000_create_station_function_configurations',1),(39,'2026_08_11_080000_create_sentinel_notifications',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_has_roles_role_id_model_id_model_type_unique` (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_type_model_id_index` (`model_type`,`model_id`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` (`id`, `role_id`, `model_type`, `model_id`, `created_at`, `updated_at`) VALUES (5,10,'App\\Models\\User',1,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(6,10,'App\\Models\\User',6,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(7,13,'App\\Models\\User',7,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(8,14,'App\\Models\\User',8,'2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monitoring_stations`
--

DROP TABLE IF EXISTS `monitoring_stations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monitoring_stations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `corridor_id` bigint unsigned DEFAULT NULL,
  `station_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `station_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'environmental_monitoring',
  `coordinate` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `logger_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logger_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `connectivity_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Online',
  `registration_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `registered_at` timestamp NULL DEFAULT NULL,
  `registered_by_user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `service_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_period_start` date DEFAULT NULL,
  `service_period_end` date DEFAULT NULL,
  `entitlement` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `administrative_attention` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monitoring_stations_station_code_unique` (`station_code`),
  KEY `monitoring_stations_workspace_id_foreign` (`workspace_id`),
  KEY `monitoring_stations_corridor_id_foreign` (`corridor_id`),
  KEY `monitoring_stations_registered_by_user_id_foreign` (`registered_by_user_id`),
  KEY `monitoring_stations_project_id_station_type_index` (`project_id`,`station_type`),
  KEY `monitoring_stations_project_id_registration_status_index` (`project_id`,`registration_status`),
  KEY `monitoring_project_service_idx` (`project_id`,`service_status`),
  CONSTRAINT `monitoring_stations_corridor_id_foreign` FOREIGN KEY (`corridor_id`) REFERENCES `corridor_monitorings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `monitoring_stations_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `monitoring_stations_registered_by_user_id_foreign` FOREIGN KEY (`registered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `monitoring_stations_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monitoring_stations`
--

LOCK TABLES `monitoring_stations` WRITE;
/*!40000 ALTER TABLE `monitoring_stations` DISABLE KEYS */;
INSERT INTO `monitoring_stations` (`id`, `workspace_id`, `project_id`, `corridor_id`, `station_code`, `name`, `station_type`, `coordinate`, `latitude`, `longitude`, `logger_id`, `logger_status`, `connectivity_status`, `registration_status`, `registered_at`, `registered_by_user_id`, `status`, `service_status`, `service_period_start`, `service_period_end`, `entitlement`, `package_status`, `administrative_attention`, `created_at`, `updated_at`) VALUES (4,4,4,4,'MS-DEMO-01','Demo Monitoring Station 01','hydromet_monitoring','-8.1724,112.9716',-8.1724000,112.9716000,NULL,'Active','Online','registered','2026-07-22 01:00:00',1,'Normal','Active','2026-07-11','2027-08-11','standard','Active',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02'),(5,4,4,4,'MS-SEMERU-UPPER','Semeru Upper Monitoring Station','hydromet_monitoring','-8.1378,112.9467',-8.1378000,112.9467000,NULL,'Registered','Pending','registered','2026-07-24 01:00:00',1,'Normal','Active','2026-07-11','2027-08-11','standard','Active',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02'),(6,4,4,4,'MS-SEMERU-DOWN','Semeru Downstream Monitoring Station','hydromet_monitoring','-8.2390,113.0185',-8.2390000,113.0185000,NULL,'Registered','Pending','registered','2026-07-24 01:00:00',1,'Normal','Active','2026-07-11','2027-08-11','standard','Active',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `monitoring_stations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mst_prefixes`
--

DROP TABLE IF EXISTS `mst_prefixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_prefixes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prefix_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_prefixes_prefix_code_unique` (`prefix_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mst_prefixes`
--

LOCK TABLES `mst_prefixes` WRITE;
/*!40000 ALTER TABLE `mst_prefixes` DISABLE KEYS */;
INSERT INTO `mst_prefixes` (`id`, `prefix_code`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES (4,'DEMO','Demo Modbus Prefix',NULL,'Active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(5,'RIKA','Rika Modbus Sensor',NULL,'Active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(6,'LEMBAB','Soil Moisture Sensor',NULL,'Active','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `mst_prefixes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `resource` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`),
  KEY `permissions_resource_action_index` (`resource`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `resource`, `action`, `created_at`, `updated_at`) VALUES (10,'project.create','Create Project','Can create new projects','project','create','2026-08-11 05:02:27','2026-08-11 05:02:27'),(11,'project.edit','Edit Project','Can edit existing projects','project','edit','2026-08-11 05:02:27','2026-08-11 05:02:27'),(12,'project.delete','Delete Project','Can delete projects','project','delete','2026-08-11 05:02:27','2026-08-11 05:02:27'),(13,'operational-state.access','Access Operational State','Can view operational state monitoring','operational-state','access','2026-08-11 05:02:27','2026-08-11 05:02:27'),(14,'operational-integrity.access','Access Operational Integrity','Can view operational integrity status','operational-integrity','access','2026-08-11 05:02:27','2026-08-11 05:02:27'),(15,'reporting.access','Access Reporting','Can access reporting and analytics','reporting','access','2026-08-11 05:02:27','2026-08-11 05:02:27'),(16,'administrative-monitoring.access','Access Administrative Monitoring','Can view administrative monitoring state','administrative-monitoring','access','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_recovery_accounts`
--

DROP TABLE IF EXISTS `project_recovery_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_recovery_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `recovery_username` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recovery_password_hash` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','unused','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unused',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_recovery_accounts_project_id_unique` (`project_id`),
  UNIQUE KEY `project_recovery_accounts_recovery_username_unique` (`recovery_username`),
  KEY `project_recovery_accounts_user_id_foreign` (`user_id`),
  KEY `project_recovery_accounts_project_id_status_index` (`project_id`,`status`),
  KEY `project_recovery_accounts_status_index` (`status`),
  CONSTRAINT `project_recovery_accounts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_recovery_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_recovery_accounts`
--

LOCK TABLES `project_recovery_accounts` WRITE;
/*!40000 ALTER TABLE `project_recovery_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_recovery_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provinces`
--

DROP TABLE IF EXISTS `provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provinces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provinces_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provinces`
--

LOCK TABLES `provinces` WRITE;
/*!40000 ALTER TABLE `provinces` DISABLE KEYS */;
INSERT INTO `provinces` (`id`, `name`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES (1,'Nanggroe Aceh Darussalam',5.5483000,95.3238000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(2,'Sumatera Utara',3.5952000,98.6722000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(3,'Sumatera Selatan',-2.9761000,104.7754000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(4,'Sumatera Barat',-0.9471000,100.4172000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(5,'Bengkulu',-3.8004000,102.2655000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(6,'Riau',0.5071000,101.4478000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(7,'Kepulauan Riau',0.9186000,104.4665000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(8,'Jambi',-1.6101000,103.6131000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(9,'Lampung',-5.3971000,105.2668000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(10,'Bangka Belitung',-2.1291000,106.1138000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(11,'Kalimantan Barat',-0.0263000,109.3425000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(12,'Kalimantan Timur',-0.5022000,117.1536000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(13,'Kalimantan Selatan',-3.4424000,114.8324000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(14,'Kalimantan Tengah',-2.2096000,113.9213000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(15,'Kalimantan Utara',2.8375000,117.3653000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(16,'Banten',-6.1201000,106.1503000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(17,'DKI Jakarta',-6.2088000,106.8456000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(18,'Jawa Barat',-6.9175000,107.6191000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(19,'Jawa Tengah',-6.9667000,110.4167000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(20,'Daerah Istimewa Yogyakarta',-7.7956000,110.3695000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(21,'Jawa Timur',-7.2575000,112.7521000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(22,'Bali',-8.6500000,115.2167000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(23,'Nusa Tenggara Timur',-10.1772000,123.6070000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(24,'Nusa Tenggara Barat',-8.5833000,116.1167000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(25,'Gorontalo',0.5435000,123.0568000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(26,'Sulawesi Barat',-2.6748000,118.8945000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(27,'Sulawesi Tengah',-0.9003000,119.8780000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(28,'Sulawesi Utara',1.4748000,124.8421000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(29,'Sulawesi Tenggara',-3.9985000,122.5120000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(30,'Sulawesi Selatan',-5.1477000,119.4327000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(31,'Maluku Utara',0.7324000,127.5625000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(32,'Maluku',-3.6954000,128.1814000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(33,'Papua Barat',-0.8615000,134.0620000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(34,'Papua',-2.5337000,140.7181000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(35,'Papua Tengah',-3.3639000,135.5000000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(36,'Papua Pegunungan',-4.0836000,138.9481000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(37,'Papua Selatan',-8.4991000,140.4040000,'2026-08-12 02:11:12','2026-08-12 02:11:12'),(38,'Papua Barat Daya',-0.8762000,131.2558000,'2026-08-12 02:11:12','2026-08-12 02:11:12');
/*!40000 ALTER TABLE `provinces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raw_data_ingestions`
--

DROP TABLE IF EXISTS `raw_data_ingestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `raw_data_ingestions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `monitoring_station_id` bigint unsigned DEFAULT NULL,
  `data_logger_id` bigint unsigned DEFAULT NULL,
  `sensor_id` bigint unsigned DEFAULT NULL,
  `source_device_identity` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_parameter` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `register_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `function_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_length` int unsigned DEFAULT NULL,
  `byte_order` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scale_factor` decimal(18,8) NOT NULL DEFAULT '1.00000000',
  `offset` decimal(18,8) NOT NULL DEFAULT '0.00000000',
  `source_unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `raw_data_classification` enum('direct_measurement','device_processed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct_measurement',
  `observed_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `reception_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `raw_data_ingestions_data_logger_id_foreign` (`data_logger_id`),
  KEY `raw_data_ingestions_monitoring_station_id_observed_at_index` (`monitoring_station_id`,`observed_at`),
  KEY `raw_data_ingestions_sensor_id_source_parameter_index` (`sensor_id`,`source_parameter`),
  CONSTRAINT `raw_data_ingestions_data_logger_id_foreign` FOREIGN KEY (`data_logger_id`) REFERENCES `data_loggers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `raw_data_ingestions_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `raw_data_ingestions_sensor_id_foreign` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raw_data_ingestions`
--

LOCK TABLES `raw_data_ingestions` WRITE;
/*!40000 ALTER TABLE `raw_data_ingestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `raw_data_ingestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reference_points`
--

DROP TABLE IF EXISTS `reference_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reference_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `workspace_id` bigint unsigned DEFAULT NULL,
  `corridor_id` bigint unsigned DEFAULT NULL,
  `reference_route_id` bigint unsigned DEFAULT NULL,
  `point_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `point_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reference',
  `coordinate` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_points_point_code_unique` (`point_code`),
  KEY `reference_points_workspace_id_foreign` (`workspace_id`),
  KEY `reference_points_corridor_id_foreign` (`corridor_id`),
  KEY `reference_points_reference_route_id_foreign` (`reference_route_id`),
  KEY `reference_points_project_id_workspace_id_index` (`project_id`,`workspace_id`),
  KEY `reference_points_project_id_point_type_index` (`project_id`,`point_type`),
  CONSTRAINT `reference_points_corridor_id_foreign` FOREIGN KEY (`corridor_id`) REFERENCES `corridor_monitorings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reference_points_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reference_points_reference_route_id_foreign` FOREIGN KEY (`reference_route_id`) REFERENCES `reference_routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reference_points_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reference_points`
--

LOCK TABLES `reference_points` WRITE;
/*!40000 ALTER TABLE `reference_points` DISABLE KEYS */;
INSERT INTO `reference_points` (`id`, `project_id`, `workspace_id`, `corridor_id`, `reference_route_id`, `point_code`, `name`, `point_type`, `coordinate`, `latitude`, `longitude`, `status`, `notes`, `created_at`, `updated_at`) VALUES (1,4,4,4,1,'BM-DEMO-01','Semeru Reference BM 01','BM','-8.1080,112.9220',-8.1080000,112.9220000,'Active','Reference BM near Semeru source area for CFPE demo.','2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `reference_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reference_routes`
--

DROP TABLE IF EXISTS `reference_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reference_routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `workspace_id` bigint unsigned DEFAULT NULL,
  `route_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reference',
  `path_coordinates` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_routes_route_code_unique` (`route_code`),
  KEY `reference_routes_workspace_id_foreign` (`workspace_id`),
  KEY `reference_routes_project_id_workspace_id_index` (`project_id`,`workspace_id`),
  KEY `reference_routes_project_id_status_index` (`project_id`,`status`),
  CONSTRAINT `reference_routes_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reference_routes_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reference_routes`
--

LOCK TABLES `reference_routes` WRITE;
/*!40000 ALTER TABLE `reference_routes` DISABLE KEYS */;
INSERT INTO `reference_routes` (`id`, `project_id`, `workspace_id`, `route_code`, `name`, `route_type`, `path_coordinates`, `status`, `notes`, `created_at`, `updated_at`) VALUES (1,4,4,'RR-DEMO-RIVER','Semeru Lahar Reference Route','lahar_corridor','[{\"lat\": -8.108, \"lng\": 112.922}, {\"lat\": -8.1378, \"lng\": 112.9467}, {\"lat\": -8.1724, \"lng\": 112.9716}, {\"lat\": -8.2052, \"lng\": 112.994}, {\"lat\": -8.239, \"lng\": 113.0185}]','Active','Demo reference route from Semeru summit toward downstream monitoring points for CFPE binding.','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `reference_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `response_plans`
--

DROP TABLE IF EXISTS `response_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `response_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint unsigned DEFAULT NULL,
  `sensor_id` bigint unsigned DEFAULT NULL,
  `warning_station_id` bigint unsigned DEFAULT NULL,
  `dashboard_notif` tinyint(1) NOT NULL DEFAULT '1',
  `sms_blasting` tinyint(1) NOT NULL DEFAULT '0',
  `warning_station_act` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `response_plans_workspace_id_foreign` (`workspace_id`),
  KEY `response_plans_sensor_id_foreign` (`sensor_id`),
  KEY `response_plans_warning_station_id_foreign` (`warning_station_id`),
  CONSTRAINT `response_plans_sensor_id_foreign` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `response_plans_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `response_plans_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `response_plans`
--

LOCK TABLES `response_plans` WRITE;
/*!40000 ALTER TABLE `response_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `response_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resq_projects`
--

DROP TABLE IF EXISTS `resq_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resq_projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_date` date DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resq_projects_project_code_unique` (`project_code`),
  KEY `resq_projects_client_id_foreign` (`client_id`),
  CONSTRAINT `resq_projects_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resq_projects`
--

LOCK TABLES `resq_projects` WRITE;
/*!40000 ALTER TABLE `resq_projects` DISABLE KEYS */;
INSERT INTO `resq_projects` (`id`, `project_code`, `name`, `owner`, `project_date`, `status`, `created_at`, `updated_at`, `client_id`) VALUES (4,'EMP-DEMO-001','Sentinal Project','Sentinel Demo','2026-08-11','Active','2026-08-11 05:02:27','2026-08-11 05:08:02',4);
/*!40000 ALTER TABLE `resq_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_has_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `role_has_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` (`id`, `role_id`, `permission_id`, `created_at`, `updated_at`) VALUES (13,10,10,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(14,10,11,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(15,10,12,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(16,10,13,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(17,10,14,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(18,10,16,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(19,10,15,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(20,11,11,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(21,12,13,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(22,12,14,'2026-08-11 05:02:27','2026-08-11 05:02:27'),(23,12,16,'2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  KEY `roles_type_index` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `type`, `created_at`, `updated_at`) VALUES (10,'SentinelAdmin','Sentinel Administrator','Full administrative access to Sentinel Console','system','2026-08-11 05:02:27','2026-08-11 05:02:27'),(11,'ProjectManager','Project Manager','Can manage project configurations','system','2026-08-11 05:02:27','2026-08-11 05:02:27'),(12,'PlatformOperator','Platform Operator','Can monitor operational state and integrity','system','2026-08-11 05:02:27','2026-08-11 05:02:27'),(13,'ClientAdmin','Client Administrator','Administrator for a client organization','system','2026-08-11 05:02:27','2026-08-11 05:02:27'),(14,'ClientOperator','Client Operator','Operator for client projects','system','2026-08-11 05:02:27','2026-08-11 05:02:27'),(15,'ClientViewer','Client Viewer','Read-only access for client projects','system','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor_mapping_preset_items`
--

DROP TABLE IF EXISTS `sensor_mapping_preset_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sensor_mapping_preset_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sensor_mapping_preset_id` bigint unsigned NOT NULL,
  `canonical_parameter_id` bigint unsigned NOT NULL,
  `source_parameter` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `register_offset` int NOT NULL DEFAULT '0',
  `function_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_length` int unsigned DEFAULT NULL,
  `byte_order` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `preset_item_unique_parameter` (`sensor_mapping_preset_id`,`canonical_parameter_id`),
  KEY `sensor_mapping_preset_items_canonical_parameter_id_foreign` (`canonical_parameter_id`),
  CONSTRAINT `sensor_mapping_preset_items_canonical_parameter_id_foreign` FOREIGN KEY (`canonical_parameter_id`) REFERENCES `canonical_parameters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sensor_mapping_preset_items_sensor_mapping_preset_id_foreign` FOREIGN KEY (`sensor_mapping_preset_id`) REFERENCES `sensor_mapping_presets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_mapping_preset_items`
--

LOCK TABLES `sensor_mapping_preset_items` WRITE;
/*!40000 ALTER TABLE `sensor_mapping_preset_items` DISABLE KEYS */;
INSERT INTO `sensor_mapping_preset_items` (`id`, `sensor_mapping_preset_id`, `canonical_parameter_id`, `source_parameter`, `source_unit`, `register_offset`, `function_code`, `value_type`, `data_length`, `byte_order`, `sort_order`, `created_at`, `updated_at`) VALUES (1,1,2,'Wind direction','°',1,'FC03','uint16',1,NULL,0,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(2,1,1,'Wind speed','m/s',2,'FC03','float32',2,'CDAB',1,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(3,1,3,'Atmospheric temperature','°C',4,'FC03','float32',2,'CDAB',2,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(4,1,4,'Atmospheric humidity','%RH',6,'FC03','float32',2,'CDAB',3,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(5,1,5,'Atmospheric pressure','hPa',8,'FC03','float32',2,'CDAB',4,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(6,1,6,'Rainfall','mm/hr',12,'FC03','float32',2,'CDAB',5,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(7,1,10,'Dust concentration (PM2.5)','μg/m³',25,'FC03','float32',2,'CDAB',6,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(8,1,9,'Illumination','lux',29,'FC03','float32',2,'CDAB',7,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(9,1,8,'Radiation','W/m²',33,'FC03','float32',2,'CDAB',8,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(10,1,7,'Altitude','m',37,'FC03','float32',2,'CDAB',9,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(11,1,11,'PM10','μg/m³',47,'FC03','float32',2,'CDAB',10,'2026-08-12 02:11:13','2026-08-12 02:11:13'),(12,2,12,'Soil moisture','%',0,'FC03','float32',2,NULL,0,'2026-08-12 02:11:13','2026-08-12 02:11:13');
/*!40000 ALTER TABLE `sensor_mapping_preset_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor_mapping_presets`
--

DROP TABLE IF EXISTS `sensor_mapping_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sensor_mapping_presets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `preset_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacturer` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `communication_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sensor_mapping_presets_preset_key_unique` (`preset_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_mapping_presets`
--

LOCK TABLES `sensor_mapping_presets` WRITE;
/*!40000 ALTER TABLE `sensor_mapping_presets` DISABLE KEYS */;
INSERT INTO `sensor_mapping_presets` (`id`, `preset_key`, `label`, `manufacturer`, `device_model`, `communication_path`, `description`, `status`, `created_at`, `updated_at`) VALUES (1,'rika-rk900-11','RK900-11 Weather Station','Rika Sensor','RK900-11','RS485 Modbus RTU','Preset parameter RK900-11 berdasarkan User Manual V5.0 bagian Communication Protocol MODBUS-RTU. Offset memakai Modbus address 0-based dari read block address 0.','active','2026-08-12 02:11:13','2026-08-12 02:11:13'),(2,'rika-rk510-01','RK510-01 Soil Moisture','Rika Sensor','RK510-01','RS485 Modbus RTU','Preset parameter RK510-01 soil moisture berdasarkan tabel spesifikasi Rika.','active','2026-08-12 02:11:13','2026-08-12 02:11:13');
/*!40000 ALTER TABLE `sensor_mapping_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor_mapping_profiles`
--

DROP TABLE IF EXISTS `sensor_mapping_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sensor_mapping_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sensor_id` bigint unsigned DEFAULT NULL,
  `profile_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `manufacturer` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `communication_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slave_id` int unsigned DEFAULT NULL,
  `source_parameter` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `register_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `function_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_length` int unsigned DEFAULT NULL,
  `byte_order` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scale_factor` decimal(18,8) NOT NULL DEFAULT '1.00000000',
  `offset` decimal(18,8) NOT NULL DEFAULT '0.00000000',
  `value_interpretation` text COLLATE utf8mb4_unicode_ci,
  `canonical_parameter_id` bigint unsigned NOT NULL,
  `value_origin` enum('direct_measurement','device_processed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct_measurement',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sensor_mapping_profiles_profile_code_unique` (`profile_code`),
  KEY `sensor_mapping_profiles_canonical_parameter_id_foreign` (`canonical_parameter_id`),
  KEY `sensor_mapping_profiles_sensor_id_source_parameter_index` (`sensor_id`,`source_parameter`),
  CONSTRAINT `sensor_mapping_profiles_canonical_parameter_id_foreign` FOREIGN KEY (`canonical_parameter_id`) REFERENCES `canonical_parameters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sensor_mapping_profiles_sensor_id_foreign` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_mapping_profiles`
--

LOCK TABLES `sensor_mapping_profiles` WRITE;
/*!40000 ALTER TABLE `sensor_mapping_profiles` DISABLE KEYS */;
INSERT INTO `sensor_mapping_profiles` (`id`, `sensor_id`, `profile_code`, `manufacturer`, `device_model`, `communication_path`, `slave_id`, `source_parameter`, `source_unit`, `register_address`, `function_code`, `value_type`, `data_length`, `byte_order`, `scale_factor`, `offset`, `value_interpretation`, `canonical_parameter_id`, `value_origin`, `status`, `created_at`, `updated_at`) VALUES (4,8,'MAP-SENSOR-LEMBAB-SOILMOISTURE','Rika Sensor','Soil Moisture Demo','RS485 Modbus RTU',4,'Soil moisture raw register','raw','0','FC03','uint16',1,NULL,0.10000000,0.00000000,NULL,12,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(5,7,'MAP-RIKA-CUACA-WINDDIRECTION','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Wind direction','deg','40002','FC03','uint16',1,NULL,1.00000000,0.00000000,NULL,2,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(6,7,'MAP-RIKA-CUACA-WINDSPEED','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Wind speed','m/s','40003','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,1,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(7,7,'MAP-RIKA-CUACA-TEMPERATURE','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Atmospheric temperature','C','40005','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,3,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(8,7,'MAP-RIKA-CUACA-HUMIDITY','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Atmospheric humidity','%','40007','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,4,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(9,7,'MAP-RIKA-CUACA-PRESSURE','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Atmospheric pressure','hPa','40009','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,5,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(10,7,'MAP-RIKA-CUACA-RAINFALL','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Rainfall','mm','40013','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,6,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(11,7,'MAP-RIKA-CUACA-PM25','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Dust concentration (PM2.5)','ug/m3','40026','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,10,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(12,7,'MAP-RIKA-CUACA-ILLUMINATION','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Illumination','lux','40030','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,9,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(13,7,'MAP-RIKA-CUACA-IRRADIANCE','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Radiation','W/m2','40034','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,8,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(14,7,'MAP-RIKA-CUACA-ALTITUDE','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'Altitude','m','40038','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,7,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(15,7,'MAP-RIKA-CUACA-PM10','Rika Sensor','RK900-11','RS485 Modbus RTU',1,'PM10','ug/m3','40048','FC03','float32',2,'CDAB',1.00000000,0.00000000,NULL,11,'direct_measurement','active','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `sensor_mapping_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensors`
--

DROP TABLE IF EXISTS `sensors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sensors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint unsigned NOT NULL,
  `monitoring_station_id` bigint unsigned NOT NULL,
  `data_logger_id` bigint unsigned DEFAULT NULL,
  `warning_station_id` bigint unsigned DEFAULT NULL,
  `mst_prefix_id` bigint unsigned DEFAULT NULL,
  `slave_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `function_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FC03',
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `poll_interval_ms` int unsigned NOT NULL DEFAULT '1000',
  `sensor_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameter` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weather_parameters` json DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `threshold` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scale_factor` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `offset` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reading_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Absolute',
  `alert_level` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `rule` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sensors_sensor_code_unique` (`sensor_code`),
  UNIQUE KEY `sensors_prefix_slave_address_unique` (`mst_prefix_id`,`slave_id`,`address`),
  KEY `sensors_warning_station_id_foreign` (`warning_station_id`),
  KEY `sensors_data_logger_id_foreign` (`data_logger_id`),
  KEY `sensors_station_last_seen_idx` (`monitoring_station_id`,`last_seen_at`),
  KEY `sensors_workspace_status_idx` (`workspace_id`,`status`),
  CONSTRAINT `sensors_data_logger_id_foreign` FOREIGN KEY (`data_logger_id`) REFERENCES `data_loggers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sensors_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sensors_mst_prefix_id_foreign` FOREIGN KEY (`mst_prefix_id`) REFERENCES `mst_prefixes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sensors_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sensors_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensors`
--

LOCK TABLES `sensors` WRITE;
/*!40000 ALTER TABLE `sensors` DISABLE KEYS */;
INSERT INTO `sensors` (`id`, `workspace_id`, `monitoring_station_id`, `data_logger_id`, `warning_station_id`, `mst_prefix_id`, `slave_id`, `address`, `function_code`, `quantity`, `poll_interval_ms`, `sensor_code`, `type`, `parameter`, `weather_parameters`, `value`, `threshold`, `data_type`, `scale_factor`, `offset`, `unit`, `reading_method`, `alert_level`, `rule`, `status`, `last_seen_at`, `created_at`, `updated_at`) VALUES (7,4,4,5,NULL,5,'1','0','FC03',49,1000,'RIKA-CUACA','weather_station','Weather Station',NULL,'WindDirection 340.00 °, WindSpeed 0.00 m/s, Temperature 32.05 °C +8 parameter',NULL,'float32',1.0000,0.0000,'','Absolute','Normal',NULL,'Normal','2026-08-11 05:12:08','2026-08-11 05:02:27','2026-08-11 05:12:07'),(8,4,4,5,NULL,6,'4','0','FC03',1,1000,'SENSOR-LEMBAB','soil_moisture','Soil Moisture',NULL,'SoilMoisture 39.40 %','35','uint16',0.1000,0.0000,'%','Absolute','Awas','AWAS when mapped SoilMoisture >= 35%','Awas','2026-08-11 05:12:08','2026-08-11 05:02:27','2026-08-11 05:12:08');
/*!40000 ALTER TABLE `sensors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sentinel_notifications`
--

DROP TABLE IF EXISTS `sentinel_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sentinel_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `corridor_id` bigint unsigned DEFAULT NULL,
  `monitoring_station_id` bigint unsigned DEFAULT NULL,
  `warning_station_id` bigint unsigned DEFAULT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `source_context` json DEFAULT NULL,
  `occurred_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sentinel_notifications_project_id_foreign` (`project_id`),
  KEY `sentinel_notifications_corridor_id_foreign` (`corridor_id`),
  KEY `sentinel_notifications_monitoring_station_id_foreign` (`monitoring_station_id`),
  KEY `sentinel_notifications_warning_station_id_foreign` (`warning_station_id`),
  KEY `notifications_user_read_idx` (`user_id`,`read_at`,`occurred_at`),
  KEY `notifications_client_project_idx` (`client_id`,`project_id`,`occurred_at`),
  KEY `notifications_category_event_idx` (`category`,`event_type`),
  CONSTRAINT `sentinel_notifications_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sentinel_notifications_corridor_id_foreign` FOREIGN KEY (`corridor_id`) REFERENCES `corridor_monitorings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sentinel_notifications_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sentinel_notifications_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sentinel_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sentinel_notifications_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sentinel_notifications`
--

LOCK TABLES `sentinel_notifications` WRITE;
/*!40000 ALTER TABLE `sentinel_notifications` DISABLE KEYS */;
INSERT INTO `sentinel_notifications` (`id`, `user_id`, `client_id`, `project_id`, `corridor_id`, `monitoring_station_id`, `warning_station_id`, `category`, `event_type`, `title`, `body`, `source_context`, `occurred_at`, `read_at`, `created_at`, `updated_at`) VALUES (1,NULL,4,4,4,4,4,'Operational','Waspada','Demo Waspada WaterLevel','Demo notification for WaterLevel Waspada state.','{\"value\": 2.4, \"parameter\": \"WaterLevel\"}','2026-08-11 00:40:00',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `sentinel_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spatial_information_layers`
--

DROP TABLE IF EXISTS `spatial_information_layers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spatial_information_layers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `workspace_id` bigint unsigned DEFAULT NULL,
  `layer_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `layer_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'overlay',
  `source_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `layer_payload` json DEFAULT NULL,
  `style_color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visible_by_default` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spatial_information_layers_layer_code_unique` (`layer_code`),
  KEY `spatial_information_layers_workspace_id_foreign` (`workspace_id`),
  KEY `spatial_information_layers_project_id_workspace_id_index` (`project_id`,`workspace_id`),
  KEY `spatial_information_layers_project_id_status_index` (`project_id`,`status`),
  CONSTRAINT `spatial_information_layers_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spatial_information_layers_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spatial_information_layers`
--

LOCK TABLES `spatial_information_layers` WRITE;
/*!40000 ALTER TABLE `spatial_information_layers` DISABLE KEYS */;
INSERT INTO `spatial_information_layers` (`id`, `project_id`, `workspace_id`, `layer_code`, `name`, `layer_type`, `source_url`, `layer_payload`, `style_color`, `visible_by_default`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (1,4,4,'LAYER-DEMO-FLOODPLAIN','Semeru Lahar Information Layer','GeoJSON',NULL,'{\"type\": \"FeatureCollection\", \"features\": []}','#2563eb',1,1,'Active','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `spatial_information_layers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `station_function_configurations`
--

DROP TABLE IF EXISTS `station_function_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `station_function_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `monitoring_station_id` bigint unsigned NOT NULL,
  `function_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reading_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `configuration` json DEFAULT NULL,
  `validation_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_validated',
  `validated_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `unresolved_analytical_rules` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `station_function_unique` (`monitoring_station_id`,`function_name`),
  KEY `station_function_project_status_idx` (`project_id`,`function_name`,`status`),
  CONSTRAINT `station_function_configurations_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `station_function_configurations_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `station_function_configurations`
--

LOCK TABLES `station_function_configurations` WRITE;
/*!40000 ALTER TABLE `station_function_configurations` DISABLE KEYS */;
INSERT INTO `station_function_configurations` (`id`, `project_id`, `monitoring_station_id`, `function_name`, `reading_method`, `configuration`, `validation_state`, `validated_at`, `activated_at`, `unresolved_analytical_rules`, `status`, `created_at`, `updated_at`) VALUES (1,4,4,'TDE','Moving Average','{\"data_window\": {\"unit\": \"hours\", \"value\": 6}}','not_validated',NULL,NULL,'[\"TDE calculation formula is intentionally not implemented in demo seed.\"]','draft','2026-08-11 05:02:27','2026-08-11 05:08:02'),(2,4,4,'Discharge','Absolute','{\"unit\": \"m3/s\", \"manning_n\": 0.031, \"coefficient_cd\": 0.72, \"calculation_runtime\": \"backend_only\", \"cross_sectional_area\": 12.5}','not_validated',NULL,NULL,'[\"Discharge calculation formula is intentionally not implemented in demo seed.\"]','draft','2026-08-11 05:02:27','2026-08-11 05:08:02'),(3,4,4,'CFPE','Probability','{\"offset_distance\": 3.5, \"reference_bm_id\": 1, \"offset_direction\": \"centerline\", \"reference_route_id\": 1, \"uncertainty_factor\": 0.2, \"calculation_runtime\": \"backend_only\", \"station_ground_zero_chainage\": 125.75}','validated','2026-08-11 01:00:00','2026-08-11 01:00:00','[\"CFPE calculation formula is intentionally not implemented in demo seed.\"]','active','2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `station_function_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `station_spatial_references`
--

DROP TABLE IF EXISTS `station_spatial_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `station_spatial_references` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `workspace_id` bigint unsigned NOT NULL,
  `corridor_id` bigint unsigned DEFAULT NULL,
  `reference_route_id` bigint unsigned DEFAULT NULL,
  `reference_point_id` bigint unsigned DEFAULT NULL,
  `monitoring_station_id` bigint unsigned DEFAULT NULL,
  `warning_station_id` bigint unsigned DEFAULT NULL,
  `placement_role` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'corridor_reference',
  `station_offset` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `station_spatial_references_workspace_id_foreign` (`workspace_id`),
  KEY `station_spatial_references_reference_route_id_foreign` (`reference_route_id`),
  KEY `station_spatial_references_reference_point_id_foreign` (`reference_point_id`),
  KEY `station_spatial_references_monitoring_station_id_foreign` (`monitoring_station_id`),
  KEY `station_spatial_references_warning_station_id_foreign` (`warning_station_id`),
  KEY `station_spatial_references_project_id_workspace_id_index` (`project_id`,`workspace_id`),
  KEY `station_spatial_references_corridor_id_placement_role_index` (`corridor_id`,`placement_role`),
  CONSTRAINT `station_spatial_references_corridor_id_foreign` FOREIGN KEY (`corridor_id`) REFERENCES `corridor_monitorings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `station_spatial_references_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `station_spatial_references_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `station_spatial_references_reference_point_id_foreign` FOREIGN KEY (`reference_point_id`) REFERENCES `reference_points` (`id`) ON DELETE SET NULL,
  CONSTRAINT `station_spatial_references_reference_route_id_foreign` FOREIGN KEY (`reference_route_id`) REFERENCES `reference_routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `station_spatial_references_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `station_spatial_references_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `station_spatial_references`
--

LOCK TABLES `station_spatial_references` WRITE;
/*!40000 ALTER TABLE `station_spatial_references` DISABLE KEYS */;
INSERT INTO `station_spatial_references` (`id`, `project_id`, `workspace_id`, `corridor_id`, `reference_route_id`, `reference_point_id`, `monitoring_station_id`, `warning_station_id`, `placement_role`, `station_offset`, `status`, `created_at`, `updated_at`) VALUES (1,4,4,4,1,1,4,NULL,'primary_monitoring','chainage:125.75;offset:3.5m centerline','Active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(2,4,4,4,1,1,5,NULL,'corridor_monitoring','chainage:1400;upper corridor monitoring point','Active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(3,4,4,4,1,1,6,NULL,'corridor_monitoring','chainage:4600;downstream corridor monitoring point','Active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(4,4,4,4,1,1,NULL,4,'downstream_warning','chainage:3100;downstream warning response point','Active','2026-08-11 05:02:27','2026-08-11 05:02:27'),(5,4,4,4,1,1,NULL,5,'downstream_warning','chainage:4600;downstream warning response point','Active','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `station_spatial_references` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telemetry_readings`
--

DROP TABLE IF EXISTS `telemetry_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telemetry_readings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sensor_id` bigint unsigned NOT NULL,
  `data_logger_id` bigint unsigned DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `raw_value` text COLLATE utf8mb4_unicode_ci,
  `numeric_value` decimal(16,6) DEFAULT NULL,
  `registers` json DEFAULT NULL,
  `parameter_values` json DEFAULT NULL,
  `alert_level` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `telemetry_sensor_received_idx` (`sensor_id`,`received_at`,`id`),
  KEY `telemetry_logger_received_idx` (`data_logger_id`,`received_at`),
  CONSTRAINT `telemetry_readings_data_logger_id_foreign` FOREIGN KEY (`data_logger_id`) REFERENCES `data_loggers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `telemetry_readings_sensor_id_foreign` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telemetry_readings`
--

LOCK TABLES `telemetry_readings` WRITE;
/*!40000 ALTER TABLE `telemetry_readings` DISABLE KEYS */;
/*!40000 ALTER TABLE `telemetry_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_has_projects`
--

DROP TABLE IF EXISTS `user_has_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_has_projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `access_level` enum('viewer','operator','manager') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operator',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_has_projects_user_id_project_id_unique` (`user_id`,`project_id`),
  KEY `user_has_projects_project_id_access_level_index` (`project_id`,`access_level`),
  CONSTRAINT `user_has_projects_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_has_projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_has_projects`
--

LOCK TABLES `user_has_projects` WRITE;
/*!40000 ALTER TABLE `user_has_projects` DISABLE KEYS */;
INSERT INTO `user_has_projects` (`id`, `user_id`, `project_id`, `access_level`, `created_at`, `updated_at`) VALUES (1,7,4,'manager','2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `user_has_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `type` enum('sentinel','client') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sentinel',
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` date NOT NULL,
  `avatar` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `status` enum('active','suspended','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_client_id_foreign` (`client_id`),
  CONSTRAINT `users_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `type`, `password`, `dob`, `avatar`, `remember_token`, `created_at`, `updated_at`, `client_id`, `status`) VALUES (1,'Sentinal Admin','sentinaladmin@resq.com','2022-01-02 10:04:58','sentinel','$2y$10$f4.HapDTM8bY0CEVmxsX4elq1OUVe4BxJY8M9Mz2RPMvznUfL9CzO','2000-10-10','images/avatar-1.jpg',NULL,'2026-08-12 02:11:11','2026-08-12 02:11:11',NULL,'active'),(6,'Demo Sentinel Admin','sentinel.admin@resq.local','2026-08-11 01:00:00','sentinel','$2y$10$vxIo/5dQ6tLABAbjZPj6uOrUD7QFHPgyDtdNYid1VGrXYBbE4Jkp2','2000-01-01','images/avatar-1.jpg',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02',NULL,'active'),(7,'Demo Client Admin','client.admin@resq.local','2026-08-11 05:08:02','client','$2y$10$p5EzXUdwF8VmBQIU9fIkXO/nqD/5WGDvEIjjmnRFJWHqEqUf7w4me','2000-01-01','images/avatar-1.jpg',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02',4,'active'),(8,'Demo Client Operator','client.operator@resq.local','2026-08-11 05:08:02','client','$2y$10$yuB.gPCtFscQgX0t9.UGleffBn1LQVioR.v204fqL4vSIDm/ABK5.','2000-01-01','images/avatar-1.jpg',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02',4,'active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warning_station_device_heartbeats`
--

DROP TABLE IF EXISTS `warning_station_device_heartbeats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warning_station_device_heartbeats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `warning_station_id` bigint unsigned NOT NULL,
  `warning_station_device_id` bigint unsigned DEFAULT NULL,
  `device_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `availability_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `health_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `observed_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `health_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warning_station_device_heartbeats_warning_station_id_foreign` (`warning_station_id`),
  KEY `wsdh_device_fk` (`warning_station_device_id`),
  KEY `wsdh_project_station_idx` (`project_id`,`warning_station_id`),
  KEY `wsdh_device_received_idx` (`device_code`,`received_at`),
  CONSTRAINT `warning_station_device_heartbeats_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warning_station_device_heartbeats_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wsdh_device_fk` FOREIGN KEY (`warning_station_device_id`) REFERENCES `warning_station_devices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warning_station_device_heartbeats`
--

LOCK TABLES `warning_station_device_heartbeats` WRITE;
/*!40000 ALTER TABLE `warning_station_device_heartbeats` DISABLE KEYS */;
INSERT INTO `warning_station_device_heartbeats` (`id`, `project_id`, `warning_station_id`, `warning_station_device_id`, `device_code`, `device_type`, `availability_state`, `health_state`, `observed_at`, `received_at`, `health_payload`, `created_at`, `updated_at`) VALUES (1,4,4,1,'WSCP-DEMO-01','wscp','available','ok','2026-08-11 00:57:00','2026-08-11 00:58:00','{\"source\": \"demo-seeder\"}','2026-08-11 05:02:27','2026-08-11 05:02:27'),(2,4,4,2,'ASCP-DEMO-01','ascp','available','ok','2026-08-11 00:57:00','2026-08-11 00:58:00','{\"source\": \"demo-seeder\"}','2026-08-11 05:02:27','2026-08-11 05:02:27'),(3,4,4,3,'SIREN-DEMO-01','siren','available','ok','2026-08-11 00:57:00','2026-08-11 00:58:00','{\"source\": \"demo-seeder\"}','2026-08-11 05:02:27','2026-08-11 05:02:27'),(4,4,4,4,'BEACON-DEMO-01','beacon','available','ok','2026-08-11 00:57:00','2026-08-11 00:58:00','{\"source\": \"demo-seeder\"}','2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `warning_station_device_heartbeats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warning_station_devices`
--

DROP TABLE IF EXISTS `warning_station_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warning_station_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `warning_station_id` bigint unsigned NOT NULL,
  `device_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expected` tinyint(1) NOT NULL DEFAULT '1',
  `availability_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `health_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `last_heartbeat_at` timestamp NULL DEFAULT NULL,
  `health_payload` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warning_station_devices_device_code_unique` (`device_code`),
  KEY `warning_station_devices_warning_station_id_foreign` (`warning_station_id`),
  KEY `wsd_project_station_idx` (`project_id`,`warning_station_id`),
  KEY `wsd_type_expected_idx` (`device_type`,`expected`),
  KEY `wsd_availability_health_idx` (`availability_state`,`health_state`),
  CONSTRAINT `warning_station_devices_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warning_station_devices_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warning_station_devices`
--

LOCK TABLES `warning_station_devices` WRITE;
/*!40000 ALTER TABLE `warning_station_devices` DISABLE KEYS */;
INSERT INTO `warning_station_devices` (`id`, `project_id`, `warning_station_id`, `device_code`, `device_type`, `name`, `vendor`, `model`, `serial_number`, `expected`, `availability_state`, `health_state`, `last_heartbeat_at`, `health_payload`, `status`, `notes`, `created_at`, `updated_at`) VALUES (1,4,4,'WSCP-DEMO-01','wscp','Demo WSCP','RESQ','Demo Output','WSCP-DEMO-01-SN',1,'available','ok','2026-08-11 00:58:00','{\"link\": \"ok\", \"battery\": \"normal\"}','registered',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02'),(2,4,4,'ASCP-DEMO-01','ascp','Demo ASCP','RESQ','Demo Output','ASCP-DEMO-01-SN',1,'available','ok','2026-08-11 00:58:00','{\"link\": \"ok\", \"battery\": \"normal\"}','registered',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02'),(3,4,4,'SIREN-DEMO-01','siren','Demo Siren','RESQ','Demo Output','SIREN-DEMO-01-SN',1,'available','ok','2026-08-11 00:58:00','{\"link\": \"ok\", \"battery\": \"normal\"}','registered',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02'),(4,4,4,'BEACON-DEMO-01','beacon','Demo Beacon','RESQ','Demo Output','BEACON-DEMO-01-SN',1,'available','ok','2026-08-11 00:58:00','{\"link\": \"ok\", \"battery\": \"normal\"}','registered',NULL,'2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `warning_station_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warning_station_telemetry_configs`
--

DROP TABLE IF EXISTS `warning_station_telemetry_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warning_station_telemetry_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `warning_station_id` bigint unsigned NOT NULL,
  `config_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `broker_config_ref` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `protocol` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MQTT',
  `host_or_endpoint` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int unsigned DEFAULT NULL,
  `topic` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qos` tinyint unsigned NOT NULL DEFAULT '0',
  `retain` tinyint(1) NOT NULL DEFAULT '0',
  `credential_ref` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `last_connected_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warning_station_telemetry_configs_config_code_unique` (`config_code`),
  KEY `warning_station_telemetry_configs_warning_station_id_foreign` (`warning_station_id`),
  KEY `wstc_project_station_idx` (`project_id`,`warning_station_id`),
  KEY `wstc_status_seen_idx` (`connection_status`,`last_seen_at`),
  CONSTRAINT `warning_station_telemetry_configs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warning_station_telemetry_configs_warning_station_id_foreign` FOREIGN KEY (`warning_station_id`) REFERENCES `warning_stations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warning_station_telemetry_configs`
--

LOCK TABLES `warning_station_telemetry_configs` WRITE;
/*!40000 ALTER TABLE `warning_station_telemetry_configs` DISABLE KEYS */;
INSERT INTO `warning_station_telemetry_configs` (`id`, `project_id`, `warning_station_id`, `config_code`, `broker_config_ref`, `protocol`, `host_or_endpoint`, `port`, `topic`, `qos`, `retain`, `credential_ref`, `connection_status`, `last_connected_at`, `last_seen_at`, `last_error`, `created_at`, `updated_at`) VALUES (1,4,4,'WSTC-DEMO-01','shared-demo-mqtt','MQTT','mqtt://demo-broker.local',1883,'sentinel/demo/ws-demo-01/heartbeat',1,0,'secret:demo-warning-mqtt','connected','2026-08-11 00:52:00','2026-08-11 00:58:00',NULL,'2026-08-11 05:02:27','2026-08-11 05:02:27');
/*!40000 ALTER TABLE `warning_station_telemetry_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warning_stations`
--

DROP TABLE IF EXISTS `warning_stations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warning_stations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `monitoring_station_id` bigint unsigned DEFAULT NULL,
  `station_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `administrative_location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordinate` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `controller_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `controller_model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `controller_vendor` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `controller_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Standby',
  `registration_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `registered_at` timestamp NULL DEFAULT NULL,
  `registered_by_user_id` bigint unsigned DEFAULT NULL,
  `output_devices` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `service_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_period_start` date DEFAULT NULL,
  `service_period_end` date DEFAULT NULL,
  `entitlement` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `administrative_attention` text COLLATE utf8mb4_unicode_ci,
  `public_warning_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `ack_response` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warning_stations_station_code_unique` (`station_code`),
  KEY `warning_stations_workspace_id_foreign` (`workspace_id`),
  KEY `warning_stations_monitoring_station_id_foreign` (`monitoring_station_id`),
  KEY `warning_stations_registered_by_user_id_foreign` (`registered_by_user_id`),
  KEY `warning_project_registration_idx` (`project_id`,`registration_status`),
  KEY `warning_project_status_idx` (`project_id`,`status`),
  KEY `warning_project_service_idx` (`project_id`,`service_status`),
  CONSTRAINT `warning_stations_monitoring_station_id_foreign` FOREIGN KEY (`monitoring_station_id`) REFERENCES `monitoring_stations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warning_stations_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `resq_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warning_stations_registered_by_user_id_foreign` FOREIGN KEY (`registered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `warning_stations_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `geospatial_workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warning_stations`
--

LOCK TABLES `warning_stations` WRITE;
/*!40000 ALTER TABLE `warning_stations` DISABLE KEYS */;
INSERT INTO `warning_stations` (`id`, `workspace_id`, `project_id`, `monitoring_station_id`, `station_code`, `name`, `zone_id`, `administrative_location`, `coordinate`, `latitude`, `longitude`, `controller_id`, `controller_model`, `controller_vendor`, `controller_status`, `registration_status`, `registered_at`, `registered_by_user_id`, `output_devices`, `status`, `service_status`, `service_period_start`, `service_period_end`, `entitlement`, `package_status`, `administrative_attention`, `public_warning_enabled`, `ack_response`, `notes`, `created_at`, `updated_at`) VALUES (4,4,4,4,'WS-DEMO-01','Demo Warning Station 01','ZONE-DEMO-01','Lumajang Downstream Demo Zone','-8.2052,112.9940',-8.2052000,112.9940000,'WSCP-DEMO-01','Demo WSCP','RESQ','Standby','registered','2026-07-27 01:00:00',1,'[\"WSCP\", \"ASCP\", \"Siren\", \"Beacon\"]','Normal','Active','2026-07-11','2027-08-11','standard','Active',NULL,1,'manual_ack_required','Demo warning station. Low-level ASCP behavior is not configured here.','2026-08-11 05:02:27','2026-08-11 05:08:02'),(5,4,4,4,'WS-SEMERU-DOWN','Semeru Downstream Warning Station','ZONE-SEMERU-DOWN','Lumajang Downstream Warning Zone','-8.2410,113.0200',-8.2410000,113.0200000,'WSCP-SEMERU-DOWN','Demo WSCP','RESQ','Standby','registered','2026-07-27 01:00:00',1,'[\"WSCP\", \"Siren\", \"Beacon\"]','Normal','Active','2026-07-11','2027-08-11','standard','Active',NULL,1,'manual_ack_required','Demo downstream warning station. Low-level output behavior remains outside Sentinel EMP.','2026-08-11 05:02:27','2026-08-11 05:08:02');
/*!40000 ALTER TABLE `warning_stations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-12 16:20:17
