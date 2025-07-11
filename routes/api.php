<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GitHubWebhookController;
use App\Http\Controllers\Api\PushNotificationController;

Route::prefix('api')->group(function () {
    // GitHub webhook endpoint
    Route::post('/webhooks/github/{repository}/{secret}', [GitHubWebhookController::class, 'handle'])
        ->name('api.webhooks.github');
        
    // Push notification routes (need auth)
    Route::middleware('auth')->group(function () {
        Route::post('/push-subscribe', [PushNotificationController::class, 'subscribe']);
        Route::post('/push-unsubscribe', [PushNotificationController::class, 'unsubscribe']);
        Route::post('/push-test', [PushNotificationController::class, 'sendTestNotification']);
        Route::get('/push-vapid-key', [PushNotificationController::class, 'getVapidPublicKey']);
        Route::get('/push-subscriptions', [PushNotificationController::class, 'getSubscriptions']);
        Route::post('/push-bulk', [PushNotificationController::class, 'sendBulkNotification']);
    });
});