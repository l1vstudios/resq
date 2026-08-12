<?php

use App\Http\Controllers\CanonicalDatabaseController;
use App\Http\Controllers\ClientFunctionConfigurationController;
use App\Http\Controllers\ClientInboxController;
use App\Http\Controllers\ClientPlatformOperationsController;
use App\Http\Controllers\ClientReportingController;
use App\Http\Controllers\ClientUserManagementController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceSetupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HydrometEwsConfigurationController;
use App\Http\Controllers\PlatformOperationsController;
use App\Http\Controllers\ProjectSetupController;
use App\Http\Controllers\RegisteredDataController;
use App\Http\Controllers\SentinelRuntimeController;
use App\Http\Controllers\WarningStationDomainController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes(['verify' => true]);

Route::get('/', [DashboardController::class, 'index'])->name('root')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/map-data', [DashboardController::class, 'mapData'])->name('dashboard.map-data');
    Route::get('/monitoring', [ProjectSetupController::class, 'monitoring'])->name('monitoring.index');
    Route::get('/platform-operations', [PlatformOperationsController::class, 'index'])->name('platform-operations.index');
    Route::get('/platform-operations/projects/{project}', [PlatformOperationsController::class, 'project'])->name('platform-operations.projects.show');
    Route::get('/platform-operations/projects/{project}/corridors/{corridor}', [PlatformOperationsController::class, 'corridor'])->name('platform-operations.corridors.show');
    Route::get('/platform-operations/stations/{station}', [PlatformOperationsController::class, 'station'])->name('platform-operations.stations.show');
    Route::get('/platform-operations/integrity/stations', [PlatformOperationsController::class, 'integrity'])->name('platform-operations.integrity.index');
    Route::get('/platform-operations/administrative/stations', [PlatformOperationsController::class, 'administrative'])->name('platform-operations.administrative.index');
    Route::get('/client-operations', [ClientPlatformOperationsController::class, 'index'])->name('client-operations.index');
    Route::get('/client-operations/projects/{project}', [ClientPlatformOperationsController::class, 'project'])->name('client-operations.projects.show');
    Route::get('/client-operations/projects/{project}/corridors/{corridor}', [ClientPlatformOperationsController::class, 'corridor'])->name('client-operations.corridors.show');
    Route::get('/client-operations/projects/{project}/stations', [ClientPlatformOperationsController::class, 'stations'])->name('client-operations.projects.stations');
    Route::get('/client-operations/stations/{station}', [ClientPlatformOperationsController::class, 'station'])->name('client-operations.stations.show');
    Route::get('/client-operations/function-configuration', [ClientFunctionConfigurationController::class, 'index'])->name('client-operations.function-configuration.index');
    Route::get('/client-operations/stations/{station}/function-configuration', [ClientFunctionConfigurationController::class, 'station'])->name('client-operations.function-configuration.stations.show');
    Route::post('/client-operations/stations/{station}/function-configuration/{function}', [ClientFunctionConfigurationController::class, 'store'])->name('client-operations.function-configuration.store');
    Route::get('/client-operations/reporting', [ClientReportingController::class, 'index'])->name('client-operations.reporting.index');
    Route::get('/client-operations/reporting/generate', [ClientReportingController::class, 'generate'])->name('client-operations.reporting.generate');
    Route::get('/client-operations/inbox', [ClientInboxController::class, 'index'])->name('client-operations.inbox.index');
    Route::get('/client-operations/inbox/{notification}', [ClientInboxController::class, 'show'])->name('client-operations.inbox.show');
    Route::post('/client-operations/inbox/{notification}/read', [ClientInboxController::class, 'markRead'])->name('client-operations.inbox.read');
    Route::get('/client-operations/users', [ClientUserManagementController::class, 'index'])->name('client-operations.users.index');
    Route::post('/client-operations/users', [ClientUserManagementController::class, 'store'])->name('client-operations.users.store');
    Route::put('/client-operations/users/{user}', [ClientUserManagementController::class, 'update'])->name('client-operations.users.update');
    Route::get('/project-configuration', [ProjectSetupController::class, 'index'])->name('project-configuration');
    Route::get('/projects', [ProjectSetupController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}/spatial-resources', [ProjectSetupController::class, 'spatialResources'])->name('projects.spatial-resources');
    Route::get('/projects/{project}/corridors/{corridor}', [ProjectSetupController::class, 'projectCorridor'])->name('projects.corridors.show');
    Route::post('/projects', [ProjectSetupController::class, 'storeProject'])->name('projects.store');
    Route::get('/canonical-database', [CanonicalDatabaseController::class, 'index'])->name('canonical-database.index');
    Route::post('/canonical-parameters', [CanonicalDatabaseController::class, 'storeParameter'])->name('canonical-parameters.store');
    Route::delete('/canonical-parameters/{parameter}', [CanonicalDatabaseController::class, 'destroyParameter'])->name('canonical-parameters.destroy');
    Route::post('/sensor-mapping-presets', [CanonicalDatabaseController::class, 'storePreset'])->name('sensor-mapping-presets.store');
    Route::delete('/sensor-mapping-presets/{preset}', [CanonicalDatabaseController::class, 'destroyPreset'])->name('sensor-mapping-presets.destroy');
    Route::post('/canonical-mapping', [CanonicalDatabaseController::class, 'storeMapping'])->name('canonical-mapping.store');
    Route::delete('/canonical-mapping/{profile}', [CanonicalDatabaseController::class, 'destroyMapping'])->name('canonical-mapping.destroy');
    Route::post('/project-workspaces', [ProjectSetupController::class, 'storeWorkspace'])->name('project-workspaces.store');
    Route::post('/project-information-layers', [ProjectSetupController::class, 'storeInformationLayer'])->name('project-information-layers.store');
    Route::post('/project-reference-routes', [ProjectSetupController::class, 'storeReferenceRoute'])->name('project-reference-routes.store');
    Route::post('/project-corridors', [ProjectSetupController::class, 'storeCorridor'])->name('project-corridors.store');
    Route::post('/project-reference-points', [ProjectSetupController::class, 'storeReferencePoint'])->name('project-reference-points.store');
    Route::post('/project-station-spatial-references', [ProjectSetupController::class, 'storeStationSpatialReference'])->name('project-station-spatial-references.store');
    Route::post('/project-monitoring-stations', [ProjectSetupController::class, 'storeMonitoringStation'])->name('project-monitoring-stations.store');
    Route::post('/project-warning-stations', [ProjectSetupController::class, 'storeWarningStation'])->name('project-warning-stations.store');
    Route::post('/project-sensors', [ProjectSetupController::class, 'storeSensor'])->name('project-sensors.store');
    Route::post('/project-response-plans', [ProjectSetupController::class, 'storeResponsePlan'])->name('project-response-plans.store');
    Route::post('/projects/start-monitoring', [DeviceSetupController::class, 'startProjectMonitoring'])->name('projects.start-monitoring');
    Route::post('/projects/stop-monitoring', [DeviceSetupController::class, 'stopProjectMonitoring'])->name('projects.stop-monitoring');
    Route::get('/projects/live-monitoring', [DeviceSetupController::class, 'projectMonitoringLiveData'])->name('projects.live-monitoring');
    Route::delete('/project-setup/{type}/{id}', [ProjectSetupController::class, 'destroy'])->name('project-setup.destroy');
    Route::get('/clusters', [RegisteredDataController::class, 'clusters'])->name('clusters.index');
    Route::get('/monitoring-stations', [RegisteredDataController::class, 'monitoringStations'])->name('monitoring-stations.index');
    Route::get('/monitoring-stations/{station}/domain', [ProjectSetupController::class, 'monitoringStationDomain'])->name('monitoring-stations.domain');
    Route::get('/warning-stations', [RegisteredDataController::class, 'warningStations'])->name('warning-stations.index');
    Route::get('/warning-stations/{station}/domain', [WarningStationDomainController::class, 'show'])->name('warning-stations.domain');
    Route::post('/warning-station-telemetry-configs', [WarningStationDomainController::class, 'storeTelemetryConfig'])->name('warning-station-telemetry-configs.store');
    Route::post('/warning-station-devices', [WarningStationDomainController::class, 'storeDevice'])->name('warning-station-devices.store');
    Route::get('/hydromet-ews/{relationship}/configuration', [HydrometEwsConfigurationController::class, 'show'])->name('hydromet-ews.configuration.show');
    Route::post('/hydromet-ews/relationships', [HydrometEwsConfigurationController::class, 'storeRelationship'])->name('hydromet-ews.relationships.store');
    Route::post('/hydromet-ews/hazard-classifications', [HydrometEwsConfigurationController::class, 'storeHazardClassification'])->name('hydromet-ews.hazard-classifications.store');
    Route::post('/hydromet-ews/wdam-configs', [HydrometEwsConfigurationController::class, 'storeWdamConfig'])->name('hydromet-ews.wdam-configs.store');
    Route::get('/runtime/projects/{project}', [SentinelRuntimeController::class, 'project'])->name('runtime.projects.show');
    Route::get('/runtime/monitoring-stations/{station}', [SentinelRuntimeController::class, 'station'])->name('runtime.monitoring-stations.show');
    Route::get('/runtime/monitoring-stations/{station}/latest', [SentinelRuntimeController::class, 'latest'])->name('runtime.monitoring-stations.latest');
    Route::get('/runtime/monitoring-stations/{station}/time-series', [SentinelRuntimeController::class, 'timeSeries'])->name('runtime.monitoring-stations.time-series');
    Route::get('/sensors', [RegisteredDataController::class, 'sensors'])->name('sensors.index');
    Route::get('/mst-prefixes', [RegisteredDataController::class, 'mstPrefixes'])->name('mst-prefixes.index');
    Route::post('/mst-prefixes', [DeviceSetupController::class, 'storeMstPrefix'])->name('mst-prefixes.store');
    Route::get('/modbus-configuration', [RegisteredDataController::class, 'modbusConfiguration'])->name('modbus-configuration.index');
    Route::post('/modbus-configuration/realtime-sensor-status', [DeviceSetupController::class, 'updateRealtimeSensorStatus'])->name('modbus-configuration.realtime-sensor-status');
    Route::post('/rednode-serial-config', [DeviceSetupController::class, 'storeRednodeSerialConfig'])->name('rednode-serial-config.store');
    Route::post('/rednode-control', [DeviceSetupController::class, 'rednodeControl'])->name('rednode-control.store');
    Route::post('/rednode-port-test', [DeviceSetupController::class, 'rednodePortTest'])->name('rednode-port-test.store');
    Route::get('/rednode-pin-scan', [RegisteredDataController::class, 'rednodePinScan'])->name('rednode-pin-scan.index');
    Route::post('/rednode-pin-scan', [DeviceSetupController::class, 'rednodePinScan'])->name('rednode-pin-scan.store');
    Route::get('/rednode-status', [DeviceSetupController::class, 'rednodeStatus'])->name('rednode-status');
    Route::get('/rednode-status/show', [DeviceSetupController::class, 'rednodeStatus'])->name('rednode-status.show');
    Route::get('/mini-server', [DeviceSetupController::class, 'miniServer'])->name('mini-server.index');
    Route::post('/mini-server/scan', [DeviceSetupController::class, 'miniServerScan'])->name('mini-server.scan');
    Route::get('/data-loggers', [RegisteredDataController::class, 'dataLoggers'])->name('data-loggers.index');
    Route::post('/data-loggers', [DeviceSetupController::class, 'storeDataLogger'])->name('data-loggers.store');
    Route::post('/data-loggers/test-remote', [DeviceSetupController::class, 'testDataLoggerRemote'])->name('data-loggers.test-remote');
    Route::post('/data-loggers/gateway-mode', [DeviceSetupController::class, 'applyDataLoggerGatewayMode'])->name('data-loggers.gateway-mode');
    Route::get('/connectivity', [RegisteredDataController::class, 'connectivity'])->name('connectivity.index');
    Route::post('/connectivity', [DeviceSetupController::class, 'storeConnectivity'])->name('connectivity.store');
    Route::get('/credentials', [RegisteredDataController::class, 'credentials'])->name('credentials.index');
    Route::post('/credentials', [DeviceSetupController::class, 'storeCredential'])->name('credentials.store');
    Route::get('/telemetry', [RegisteredDataController::class, 'telemetry'])->name('telemetry.index');
    Route::get('/telemetry/live-data', [RegisteredDataController::class, 'telemetryData'])->name('telemetry.live-data');
    Route::post('/telemetry', [DeviceSetupController::class, 'storeTelemetry'])->name('telemetry.store');
    Route::get('/command-test', [RegisteredDataController::class, 'commandTest'])->name('command-test.index');
    Route::delete('/device-setup/{type}/{id}', [DeviceSetupController::class, 'destroy'])->name('device-setup.destroy');
    Route::view('/admins', 'modules.admins.index')->name('admins.index');
});

// customers route
Route::get('/customers', [CustomerController::class, 'index'])->name('customers.list');

// Update User Details
Route::post('/update-profile/{id}', [HomeController::class, 'updateProfile'])->name('updateProfile');
Route::post('/update-password/{id}', [HomeController::class, 'updatePassword'])->name('updatePassword');

Route::get('{any}', [HomeController::class, 'index'])->name('index');

// Language Translation
Route::get('index/{locale}', [HomeController::class, 'lang']);
