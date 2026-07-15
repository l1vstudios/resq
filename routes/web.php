<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
    Route::get('/project-configuration', [DashboardController::class, 'index'])->name('project-configuration');
    Route::get('/projects', [ProjectSetupController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectSetupController::class, 'storeProject'])->name('projects.store');
    Route::post('/project-workspaces', [ProjectSetupController::class, 'storeWorkspace'])->name('project-workspaces.store');
    Route::post('/project-monitoring-stations', [ProjectSetupController::class, 'storeMonitoringStation'])->name('project-monitoring-stations.store');
    Route::post('/project-warning-stations', [ProjectSetupController::class, 'storeWarningStation'])->name('project-warning-stations.store');
    Route::post('/project-sensors', [ProjectSetupController::class, 'storeSensor'])->name('project-sensors.store');
    Route::post('/project-response-plans', [ProjectSetupController::class, 'storeResponsePlan'])->name('project-response-plans.store');
    Route::delete('/project-setup/{type}/{id}', [ProjectSetupController::class, 'destroy'])->name('project-setup.destroy');
    Route::get('/clusters', [RegisteredDataController::class, 'clusters'])->name('clusters.index');
    Route::get('/monitoring-stations', [RegisteredDataController::class, 'monitoringStations'])->name('monitoring-stations.index');
    Route::get('/warning-stations', [RegisteredDataController::class, 'warningStations'])->name('warning-stations.index');
    Route::get('/sensors', [RegisteredDataController::class, 'sensors'])->name('sensors.index');
    Route::get('/mst-prefixes', [RegisteredDataController::class, 'mstPrefixes'])->name('mst-prefixes.index');
    Route::post('/mst-prefixes', [DeviceSetupController::class, 'storeMstPrefix'])->name('mst-prefixes.store');
    Route::get('/modbus-configuration', [RegisteredDataController::class, 'modbusConfiguration'])->name('modbus-configuration.index');
    Route::post('/modbus-configuration/realtime-sensor-status', [DeviceSetupController::class, 'updateRealtimeSensorStatus'])->name('modbus-configuration.realtime-sensor-status');
    Route::get('/data-loggers', [RegisteredDataController::class, 'dataLoggers'])->name('data-loggers.index');
    Route::post('/data-loggers', [DeviceSetupController::class, 'storeDataLogger'])->name('data-loggers.store');
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
