<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\DynamicApiController;
use App\Http\Controllers\SyncController;

// Endpoint CRUD Pegawai
Route::get('/pegawai', [PegawaiController::class, 'index']);
Route::post('/pegawai', [PegawaiController::class, 'store']);
Route::get('/pegawai/{id}', [PegawaiController::class, 'show']);
Route::put('/pegawai/{id}', [PegawaiController::class, 'update']);
Route::delete('/pegawai/{id}', [PegawaiController::class, 'destroy']);

// Endpoint Sinkronisasi (Node to Node)
Route::get('/system/ip', [SyncController::class, 'getLocalIp']);
Route::get('/system/ngrok', [SyncController::class, 'getNgrokUrl']);
Route::post('/system/start-ngrok', [SyncController::class, 'startNgrok']);
Route::post('/sync', [SyncController::class, 'pullSync']);

// Route API Dinamis untuk semua tabel
Route::get('/dynamic/{table}', [DynamicApiController::class, 'index']);
Route::post('/dynamic/{table}', [DynamicApiController::class, 'store']);
Route::get('/dynamic/{table}/{id}', [DynamicApiController::class, 'show']);
Route::put('/dynamic/{table}/{id}', [DynamicApiController::class, 'update']);
Route::delete('/dynamic/{table}/{id}', [DynamicApiController::class, 'destroy']);
