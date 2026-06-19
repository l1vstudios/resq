<?php

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

Route::get('/', [App\Http\Controllers\HomeController::class, 'root'])->name('root')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'modules.dashboard.index')->name('dashboard');
    Route::view('/project-configuration', 'modules.dashboard.index')->name('project-configuration');
    Route::view('/projects', 'modules.projects.index')->name('projects.index');
    Route::view('/clusters', 'modules.clusters.index')->name('clusters.index');
    Route::view('/monitoring-stations', 'modules.monitoring-stations.index')->name('monitoring-stations.index');
    Route::view('/warning-stations', 'modules.warning-stations.index')->name('warning-stations.index');
    Route::view('/sensors', 'modules.sensors.index')->name('sensors.index');
    Route::view('/data-loggers', 'modules.data-loggers.index')->name('data-loggers.index');
    Route::view('/connectivity', 'modules.connectivity.index')->name('connectivity.index');
    Route::view('/credentials', 'modules.credentials.index')->name('credentials.index');
    Route::view('/telemetry', 'modules.telemetry.index')->name('telemetry.index');
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
