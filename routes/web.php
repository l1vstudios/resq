<?php

use App\Http\Controllers\CanonicalCatalogController;
use App\Http\Controllers\CanonicalIngressRolloutController;
use App\Http\Controllers\CanonicalTraceReplayController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceSetupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MappingWorkbenchController;
use App\Http\Controllers\ProjectSetupController;
use App\Http\Controllers\RegisteredDataController;
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
    Route::get('/project-configuration', [DashboardController::class, 'index'])->name('project-configuration');
    Route::get('/projects', [ProjectSetupController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectSetupController::class, 'storeProject'])->name('projects.store');
    Route::post('/project-workspaces', [ProjectSetupController::class, 'storeWorkspace'])->name('project-workspaces.store');
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
    Route::get('/warning-stations', [RegisteredDataController::class, 'warningStations'])->name('warning-stations.index');
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
    Route::get('/rednode-status', [DeviceSetupController::class, 'rednodeStatus'])->name('rednode-status.show');
    Route::get('/data-loggers', [RegisteredDataController::class, 'dataLoggers'])->name('data-loggers.index');
    Route::post('/data-loggers', [DeviceSetupController::class, 'storeDataLogger'])->name('data-loggers.store');
    Route::post('/data-loggers/test-remote', [DeviceSetupController::class, 'testDataLoggerRemote'])->name('data-loggers.test-remote');
    Route::get('/connectivity', [RegisteredDataController::class, 'connectivity'])->name('connectivity.index');
    Route::post('/connectivity', [DeviceSetupController::class, 'storeConnectivity'])->name('connectivity.store');
    Route::get('/credentials', [RegisteredDataController::class, 'credentials'])->name('credentials.index');
    Route::post('/credentials', [DeviceSetupController::class, 'storeCredential'])->name('credentials.store');
    Route::get('/telemetry', [RegisteredDataController::class, 'telemetry'])->name('telemetry.index');
    Route::get('/canonical-catalog', [CanonicalCatalogController::class, 'index'])->name('canonical-catalog.index');
    Route::prefix('mapping-workbench')->name('mapping-workbench.')->middleware('can:manage-canonical-mappings')->group(function () {
        Route::get('/', [MappingWorkbenchController::class, 'index'])->name('index');
        Route::post('/profiles', [MappingWorkbenchController::class, 'storeProfile'])->name('profiles.store');
        Route::get('/versions/{version}', [MappingWorkbenchController::class, 'show'])->name('show');
        Route::post('/versions/{version}/rules', [MappingWorkbenchController::class, 'saveRule'])->name('rules.save');
        Route::delete('/versions/{version}/rules/{rule}', [MappingWorkbenchController::class, 'destroyRule'])->name('rules.destroy');
        Route::post('/versions/{version}/validate', [MappingWorkbenchController::class, 'validateVersion'])->name('validate');
        Route::post('/versions/{version}/preview', [MappingWorkbenchController::class, 'preview'])->name('preview');
        Route::post('/versions/{version}/publish', [MappingWorkbenchController::class, 'publish'])->name('publish');
        Route::post('/versions/{version}/clone', [MappingWorkbenchController::class, 'clone'])->name('clone');
        Route::post('/versions/{version}/activate', [MappingWorkbenchController::class, 'activate'])->name('activate');
        Route::post('/assignments/{assignment}/rollback', [MappingWorkbenchController::class, 'rollback'])->name('rollback');
    });
    Route::prefix('canonical-ingress-rollout')->name('canonical-ingress-rollout.')->middleware('can:manage-canonical-mappings')->group(function () {
        Route::get('/', [CanonicalIngressRolloutController::class, 'index'])->name('index');
        Route::post('/transition', [CanonicalIngressRolloutController::class, 'transition'])->name('transition');
    });
    Route::prefix('canonical-trace')->name('canonical-trace.')->middleware('can:manage-canonical-mappings')->group(function () {
        Route::get('/', [CanonicalTraceReplayController::class, 'index'])->name('index');
        Route::get('/raw/{event}', [CanonicalTraceReplayController::class, 'raw'])->name('raw');
        Route::get('/values/{value}', [CanonicalTraceReplayController::class, 'value'])->name('value');
        Route::post('/replays', [CanonicalTraceReplayController::class, 'create'])->name('replays.create');
        Route::get('/replays/{batch}', [CanonicalTraceReplayController::class, 'batch'])->name('replays.show');
        Route::post('/replays/{batch}/dry-run', [CanonicalTraceReplayController::class, 'dryRun'])->name('replays.dry-run');
        Route::post('/replays/{batch}/execute', [CanonicalTraceReplayController::class, 'execute'])->name('replays.execute');
    });
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
