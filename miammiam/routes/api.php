<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\GerantController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ReclamationController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ParrainageController;
use App\Http\Controllers\Api\PromotionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-employe', [EmployeeController::class, 'login']);

// Routes promotions (publiques)
Route::prefix('promotions')->group(function () {
    Route::get('/', [PromotionController::class, 'index']); // Lister les promotions
    Route::get('/plats', [PromotionController::class, 'plats']); // Lister les plats en promotion
    Route::post('/verifier-code', [PromotionController::class, 'verifierCode']); // Vérifier un code promo
    Route::get('/{id}', [PromotionController::class, 'show']); // Détails d'une promotion
});
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Routes publiques - Consultation du menu
Route::get('/categories', [MenuController::class, 'categories']);
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menu/plats-du-jour', [MenuController::class, 'platsJour']);
// IMPORTANT : La route spécifique doit être AVANT /menu/{id} pour éviter les conflits
Route::middleware(['auth:sanctum', 'role:employe,gerant,admin'])->get('/menu/available', [MenuController::class, 'available']);
Route::get('/menu/{id}', [MenuController::class, 'show']);

// Routes publiques - Réclamations / Contact (accessible même sans authentification)
Route::post('/reclamations', [ReclamationController::class, 'store']);

// Routes protégées (utilisateurs connectés)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']); // Pour FormData avec _method=PUT
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Routes panier (utilisateurs connectés)
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'add']);
    Route::put('/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/items/{id}', [CartController::class, 'removeItem']);
    Route::delete('/', [CartController::class, 'clear']);
});

// Routes favoris (utilisateurs connectés)
Route::middleware('auth:sanctum')->prefix('favorites')->group(function () {
    Route::get('/', [FavoriteController::class, 'index']);
    Route::post('/', [FavoriteController::class, 'store']);
    Route::delete('/{id}', [FavoriteController::class, 'destroy']);
});

// Routes parrainage (utilisateurs connectés)
Route::middleware('auth:sanctum')->prefix('parrainage')->group(function () {
    Route::get('/code', [ParrainageController::class, 'getCode']);
    Route::get('/historique', [ParrainageController::class, 'historique']);
});

// Routes notifications (utilisateurs connectés)
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/employee', [NotificationController::class, 'employeeNotifications'])->middleware('role:employe,gerant,admin');
    Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});

// Route pour envoyer des notifications aux employés (gérant/admin uniquement)
Route::middleware(['auth:sanctum', 'role:gerant,admin'])->post('/notifications/employees', [NotificationController::class, 'sendToEmployees']);

// Routes avis (utilisateurs connectés)
// Routes pour les avis clients (création)
Route::middleware('auth:sanctum')->prefix('reviews')->group(function () {
    Route::post('/', [ReviewController::class, 'store']);
});

// Routes pour la gestion des avis par le gérant/admin
Route::middleware(['auth:sanctum', 'role:gerant,admin'])->prefix('reviews')->group(function () {
    Route::get('/', [ReviewController::class, 'index']); // Lister avec filtrage/tri
    Route::get('/{id}', [ReviewController::class, 'show']); // Afficher un avis
    Route::put('/{id}', [ReviewController::class, 'update']); // Répondre à un avis
    Route::put('/{id}/moderate', [ReviewController::class, 'moderate']); // Modérer un avis
    Route::delete('/{id}', [ReviewController::class, 'destroy']); // Supprimer un avis
});

// Routes commandes (utilisateurs connectés)
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    // IMPORTANT : Les routes spécifiques doivent être AVANT /{id} pour éviter les conflits
    Route::get('/summary', [OrderController::class, 'summary']);
    Route::get('/active', [OrderController::class, 'active']);
    Route::get('/recent-updates', [OrderController::class, 'recentUpdates']);
    Route::get('/history', [OrderController::class, 'history']);
    Route::get('/pending', [OrderController::class, 'pending'])->middleware('role:employe,gerant,admin');
    Route::get('/{id}/details', [OrderController::class, 'showForEmployee'])->middleware('role:employe,gerant,admin');
    Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->middleware('role:employe,gerant,admin');
    Route::put('/{id}/assign', [OrderController::class, 'assign'])->middleware('role:employe,gerant,admin');
    // Routes génériques en dernier
    Route::post('/{id}/payment', [OrderController::class, 'processPayment']);
    Route::delete('/{id}', [OrderController::class, 'destroy']); // Supprimer une commande
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
});

// Routes paiements (utilisateurs connectés)
Route::middleware('auth:sanctum')->prefix('payments')->group(function () {
    Route::post('/initialize', [PaymentController::class, 'initialize']);
});

// Routes callbacks Easypay (publiques - pas besoin d'authentification car appelées par Easypay)
Route::prefix('payments')->group(function () {
    Route::get('/success', [PaymentController::class, 'success']);
    Route::get('/error', [PaymentController::class, 'error']);
    Route::get('/cancel', [PaymentController::class, 'cancel']);
    Route::post('/webhook', [PaymentController::class, 'webhook']);
    // Route de test pour simuler un paiement réussi (à supprimer en production)
    Route::post('/test/simulate-success', [PaymentController::class, 'simulateSuccess']);
});

// Routes admin (admin uniquement)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Gestion des utilisateurs
    Route::get('/users', [AdminController::class, 'listUsers']);
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::put('/users/{id}/role', [AdminController::class, 'updateRole']);
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    
    // Gestion des employés
    Route::get('/employees', [AdminController::class, 'listEmployees']);
    Route::post('/employees', [AdminController::class, 'createEmployee']);
    
    // Configuration des paiements
    Route::get('/payment/config', [AdminController::class, 'getPaymentConfig']);
    Route::put('/payment/config', [AdminController::class, 'updatePaymentConfig']);
    
    // Logs et sécurité
    Route::get('/logs/connexions', [AdminController::class, 'listConnexionLogs']);
    Route::get('/logs/connexions/user/{id}', [AdminController::class, 'getUserConnexionLogs']);
    Route::get('/logs/connexions/suspectes', [AdminController::class, 'getConnexionsSuspectes']);
});

// Routes statistiques (employés, gérants, admins)
Route::middleware(['auth:sanctum', 'role:employe,gerant,admin'])->prefix('stats')->group(function () {
    Route::get('/employee', [OrderController::class, 'employeeStats']);
});

// Routes stock (employés, gérants, admins)
Route::middleware(['auth:sanctum', 'role:employe,gerant,admin'])->prefix('stock')->group(function () {
    Route::post('/out', [StockController::class, 'reportStockOut']);
});

// Routes messagerie (employés, gérants, admins)
Route::middleware(['auth:sanctum', 'role:employe,gerant,admin'])->prefix('messages')->group(function () {
    Route::post('/', [MessageController::class, 'store']);
    Route::get('/', [MessageController::class, 'index']);
});

// Routes gérant (gérant uniquement)
Route::middleware(['auth:sanctum', 'role:gerant'])->prefix('gerant')->group(function () {
    // Gestion du menu
    Route::get('/menu', [GerantController::class, 'listMenuItems']);
    Route::get('/menu/{id}', [GerantController::class, 'showMenuItem']);
    Route::post('/menu', [GerantController::class, 'createMenuItem']);
    Route::put('/menu/{id}', [GerantController::class, 'updateMenuItem']);
    Route::delete('/menu/{id}', [GerantController::class, 'deleteMenuItem']);
    
    // Consultation des commandes en cours
    Route::get('/orders/in-progress', [GerantController::class, 'listOrdersInProgress']);
    Route::get('/orders/{id}', [GerantController::class, 'showOrderDetails']);
    
    // Gestion des livreurs et assignation
    Route::get('/delivery-employees', [GerantController::class, 'listDeliveryEmployees']);
    Route::put('/orders/{id}/assign', [GerantController::class, 'assignOrderToDeliveryEmployee']);
});
