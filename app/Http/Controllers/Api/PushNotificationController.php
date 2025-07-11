<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PushNotificationController extends Controller
{
    protected PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'subscription.keys' => 'required|array',
            'subscription.keys.p256dh' => 'required|string',
            'subscription.keys.auth' => 'required|string',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $subscriptionData = $request->input('subscription');
            
            // Add metadata if provided
            if ($request->has('metadata')) {
                $subscriptionData['metadata'] = $request->input('metadata');
            }

            $subscription = $this->pushService->subscribeUser($user, $subscriptionData);

            return response()->json([
                'success' => true,
                'message' => 'Push notification subscription successful',
                'subscription_id' => $subscription->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe to push notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $endpoint = $request->input('endpoint');

            $this->pushService->unsubscribeUser($user, $endpoint);

            return response()->json([
                'success' => true,
                'message' => 'Push notification unsubscription successful'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unsubscribe from push notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $payload = [
                'title' => 'Test Notification',
                'body' => 'This is a test notification from the project management system.',
                'icon' => '/favicon.ico',
                'badge' => '/favicon.ico',
                'tag' => 'test-' . time(),
                'data' => [
                    'type' => 'test',
                    'url' => '/dashboard',
                    'timestamp' => now()->toISOString()
                ]
            ];

            // Use reflection to call the private method for testing
            $reflection = new \ReflectionClass($this->pushService);
            $method = $reflection->getMethod('sendNotificationToUser');
            $method->setAccessible(true);
            $method->invoke($this->pushService, $user, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getVapidPublicKey(): JsonResponse
    {
        return response()->json([
            'vapid_public_key' => PushNotificationService::getVapidPublicKey()
        ]);
    }

    public function getSubscriptions(): JsonResponse
    {
        try {
            $user = Auth::user();
            $stats = $this->pushService->getSubscriptionStats($user);

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get subscription stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendBulkNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'url' => 'sometimes|string',
            'tag' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userIds = $request->input('user_ids');
            $payload = [
                'title' => $request->input('title'),
                'body' => $request->input('body'),
                'icon' => '/favicon.ico',
                'badge' => '/favicon.ico',
                'tag' => $request->input('tag', 'bulk-' . time()),
                'data' => [
                    'type' => 'bulk',
                    'url' => $request->input('url', '/dashboard'),
                    'timestamp' => now()->toISOString()
                ]
            ];

            $this->pushService->sendBulkNotification($userIds, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Bulk notification sent successfully',
                'recipients' => count($userIds)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}