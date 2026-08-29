<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return file_get_contents(public_path('index.html'));
});

use App\Http\Controllers\SuperAdminController;

Route::get('/super-admin', [SuperAdminController::class, 'index'])->name('superadmin.index');
Route::post('/super-admin/table', [SuperAdminController::class, 'createTable'])->name('superadmin.createTable');
Route::post('/super-admin/relation', [SuperAdminController::class, 'createRelation'])->name('superadmin.createRelation');
Route::post('/super-admin/relation/pivot', [SuperAdminController::class, 'createPivotRelation'])->name('superadmin.createPivotRelation');
Route::post('/super-admin/relation/drop', [SuperAdminController::class, 'dropRelation'])->name('superadmin.dropRelation');
Route::post('/super-admin/table/drop', [SuperAdminController::class, 'dropTable'])->name('superadmin.dropTable');
