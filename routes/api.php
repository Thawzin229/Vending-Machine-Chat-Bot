<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\ChatBotAgentController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductsController::class, 'index']);

//chat bot routes
Route::post('/chat-bots', [ChatBotAgentController::class, 'chat'])->middleware('throttle:ai-chat');
Route::post('/check-product', [ChatBotAgentController::class, 'checkAvailability']);
Route::get('/products/{product}', [ProductsController::class, 'show']);

Route::middleware('api.token')->group(function (): void {
    Route::post('/products/{product}/purchase', [ProductsController::class, 'purchase']);
    Route::post('/products', [ProductsController::class, 'store']);
    Route::put('/products/{product}', [ProductsController::class, 'update']);
    Route::delete('/products/{product}', [ProductsController::class, 'destroy']);
});
