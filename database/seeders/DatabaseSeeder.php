<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\ClientsSeeder;
use Database\Seeders\ResqProjectsSeeder;
use Database\Seeders\GeospatialWorkspacesSeeder;
use Database\Seeders\ReferenceRoutesSeeder;
use Database\Seeders\CorridorMonitoringsSeeder;
use Database\Seeders\UsersSeeder;
use Database\Seeders\MonitoringStationsSeeder;
use Database\Seeders\DataLoggersSeeder;
use Database\Seeders\MstPrefixesSeeder;
use Database\Seeders\WarningStationsSeeder;
use Database\Seeders\SensorsSeeder;
use Database\Seeders\CanonicalParametersSeeder;
use Database\Seeders\SensorMappingProfilesSeeder;
use Database\Seeders\ConnectivityConfigsSeeder;
use Database\Seeders\DataLoggerDiscoveriesSeeder;
use Database\Seeders\HydrometEwsRelationshipsSeeder;
use Database\Seeders\HydrometHazardClassificationsSeeder;
use Database\Seeders\HydrometWdamConfigsSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\ModelHasRolesSeeder;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\ProvincesSeeder;
use Database\Seeders\ReferencePointsSeeder;
use Database\Seeders\RoleHasPermissionsSeeder;
use Database\Seeders\SensorMappingPresetsSeeder;
use Database\Seeders\SensorMappingPresetItemsSeeder;
use Database\Seeders\SentinelNotificationsSeeder;
use Database\Seeders\SpatialInformationLayersSeeder;
use Database\Seeders\StationFunctionConfigurationsSeeder;
use Database\Seeders\StationSpatialReferencesSeeder;
use Database\Seeders\UserHasProjectsSeeder;
use Database\Seeders\WarningStationDevicesSeeder;
use Database\Seeders\WarningStationDeviceHeartbeatsSeeder;
use Database\Seeders\WarningStationTelemetryConfigsSeeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ClientsSeeder::class);
        $this->call(ResqProjectsSeeder::class);
        $this->call(GeospatialWorkspacesSeeder::class);
        $this->call(ReferenceRoutesSeeder::class);
        $this->call(CorridorMonitoringsSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(MonitoringStationsSeeder::class);
        $this->call(DataLoggersSeeder::class);
        $this->call(MstPrefixesSeeder::class);
        $this->call(WarningStationsSeeder::class);
        $this->call(SensorsSeeder::class);
        $this->call(CanonicalParametersSeeder::class);
        $this->call(SensorMappingProfilesSeeder::class);
        $this->call(ConnectivityConfigsSeeder::class);
        $this->call(DataLoggerDiscoveriesSeeder::class);
        $this->call(HydrometEwsRelationshipsSeeder::class);
        $this->call(HydrometHazardClassificationsSeeder::class);
        $this->call(HydrometWdamConfigsSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(ModelHasRolesSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(ProvincesSeeder::class);
        $this->call(ReferencePointsSeeder::class);
        $this->call(RoleHasPermissionsSeeder::class);
        $this->call(SensorMappingPresetsSeeder::class);
        $this->call(SensorMappingPresetItemsSeeder::class);
        $this->call(SentinelNotificationsSeeder::class);
        $this->call(SpatialInformationLayersSeeder::class);
        $this->call(StationFunctionConfigurationsSeeder::class);
        $this->call(StationSpatialReferencesSeeder::class);
        $this->call(UserHasProjectsSeeder::class);
        $this->call(WarningStationDevicesSeeder::class);
        $this->call(WarningStationDeviceHeartbeatsSeeder::class);
        $this->call(WarningStationTelemetryConfigsSeeder::class);
    }
}