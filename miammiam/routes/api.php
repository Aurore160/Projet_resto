<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\GerantController;
use App\Http\Controllers\Api\MenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Routes publiques - Consultation du menu
Route::get('/categories', [MenuController::class, 'categories']);
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menu/{id}', [MenuController::class, 'show']);
Route::get('/menu/plats-du-jour', [MenuController::class, 'platsJour']);

// Routes protégées (utilisateurs connectés)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
});

// Routes admin (admin uniquement)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Gestion des utilisateurs
    Route::get('/users', [AdminController::class, 'listUsers']);
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::put('/users/{id}/role', [AdminController::class, 'updateRole']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    
    // Logs et sécurité
    Route::get('/logs/connexions', [AdminController::class, 'listConnexionLogs']);
    Route::get('/logs/connexions/user/{id}', [AdminController::class, 'getUserConnexionLogs']);
    Route::get('/logs/connexions/suspectes', [AdminController::class, 'getConnexionsSuspectes']);
});

// Routes gérant (gérant uniquement)
Route::middleware(['auth:sanctum', 'role:gerant'])->prefix('gerant')->group(function () {
    Route::get('/menu', [GerantController::class, 'listMenuItems']);
    Route::get('/menu/{id}', [GerantController::class, 'showMenuItem']);
    Route::post('/menu', [GerantController::class, 'createMenuItem']);
    Route::put('/menu/{id}', [GerantController::class, 'updateMenuItem']);
    Route::delete('/menu/{id}', [GerantController::class, 'deleteMenuItem']);
});
