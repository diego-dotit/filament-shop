<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Customer\AddressController;
use App\Http\Controllers\Api\Customer\CustomerAuthController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Order\OrderController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Review\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ── Auth endpoints ────────────────────────────────────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// ── Category endpoints ────────────────────────────────────────────────────────
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

// ── Product endpoints ─────────────────────────────────────────────────────────
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// ── Customer auth endpoints ───────────────────────────────────────────────────
Route::post('/customer/register', [CustomerAuthController::class, 'register']);
Route::post('/customer/login', [CustomerAuthController::class, 'login']);

Route::middleware('auth:customers')->group(function () {
    Route::get('/customer/me', [CustomerAuthController::class, 'me']);
});

// ── Customer endpoints ────────────────────────────────────────────────────────
Route::middleware('auth:customers')->prefix('customers')->group(function () {
    Route::get('me', [CustomerController::class, 'show']);
    Route::put('me', [CustomerController::class, 'update']);

    Route::get('me/addresses', [AddressController::class, 'index']);
    Route::post('me/addresses', [AddressController::class, 'store']);
    Route::get('me/addresses/{address}', [AddressController::class, 'show']);
    Route::put('me/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('me/addresses/{address}', [AddressController::class, 'destroy']);
});

// ── Cart endpoints ────────────────────────────────────────────────────────────
Route::middleware('auth:customers')->group(function () {
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{cartItemId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{cartItemId}', [CartController::class, 'removeItem']);
});

// ── Order endpoints ───────────────────────────────────────────────────────────
Route::middleware('auth:customers')->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
});

// ── Review endpoints ──────────────────────────────────────────────────────────
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
Route::middleware('auth:customers')->group(function () {
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
});
