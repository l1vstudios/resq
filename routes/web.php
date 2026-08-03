<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CanonicalDatabaseController;
use App\Http\Controllers\DeviceSetupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectSetupController;
use App\Http\Controllers\RegisteredDataController;

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
    Route::get('/project-configuration', [DashboardController::class, 'index'])->name('project-configuration');
    Route::get('/projects', [ProjectSetupController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectSetupController::class, 'storeProject'])->name('projects.store');
    Route::get('/canonical-database', [CanonicalDatabaseController::class, 'index'])->name('canonical-database.index');
    Route::post('/canonical-parameters', [CanonicalDatabaseController::class, 'storeParameter'])->name('canonical-parameters.store');
    Route::delete('/canonical-parameters/{parameter}', [CanonicalDatabaseController::class, 'destroyParameter'])->name('canonical-parameters.destroy');
    Route::post('/canonical-mapping', [CanonicalDatabaseController::class, 'storeMapping'])->name('canonical-mapping.store');
    Route::delete('/canonical-mapping/{profile}', [CanonicalDatabaseController::class, 'destroyMapping'])->name('canonical-mapping.destroy');
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
    Route::get('/rednode-status', [DeviceSetupController::class, 'rednodeStatus'])->name('rednode-status');
    Route::get('/rednode-status/show', [DeviceSetupController::class, 'rednodeStatus'])->name('rednode-status.show');
    Route::get('/data-loggers', [RegisteredDataController::class, 'dataLoggers'])->name('data-loggers.index');
    Route::post('/data-loggers', [DeviceSetupController::class, 'storeDataLogger'])->name('data-loggers.store');
    Route::post('/data-loggers/test-remote', [DeviceSetupController::class, 'testDataLoggerRemote'])->name('data-loggers.test-remote');
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
Route::get('/customers', [App\Http\Controllers\CustomerController::class, 'index'])->name('customers.list');

//Update User Details
Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');

Route::get('{any}', [App\Http\Controllers\HomeController::class, 'index'])->name('index');

//Language Translation
Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);
