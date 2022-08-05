<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PerformanceController;


Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout');
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('folder')->group(function () {
        Route::post('create', [FolderController::class, 'create']);
        Route::delete('{folder_id}/delete', [FolderController::class, 'delete']);
        Route::put('update/{folder_id}', [FolderController::class, 'update']);
        Route::post('{folder_id}/restore', [FolderController::class, 'restore']);
        Route::get('display/{folder_id}', [FolderController::class, 'display']);
        Route::get('{slug}', [FolderController::class, 'show'])->where('slug','.+');
    });

    Route::prefix('workspace')->group(function () {
        Route::get('/', [PerformanceController::class, 'index']);
        Route::get('recycle_bin', [PerformanceController::class, 'recycleBin']);
        Route::get('shared', [PerformanceController::class, 'shared']);
        Route::get('search/{search_parameter}', [PerformanceController::class, 'search']);
    });

    Route::prefix('document')->group(function () {
        Route::post('create', [DocumentController::class, 'create']);
        Route::get('download/{document_id}', [DocumentController::class, 'download']);
        Route::put('update/{document_id}', [DocumentController::class, 'update']);
        Route::delete('{document_id}/delete', [DocumentController::class, 'delete']);
        Route::post('{document_id}/restore', [DocumentController::class, 'restore']);
        Route::get('display/{document_id}', [DocumentController::class, 'display']);
    });
});
