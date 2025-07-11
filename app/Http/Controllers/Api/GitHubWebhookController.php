<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GitHubRepository;
use App\Models\GitHubWebhook;
use App\Services\GitHubWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    public function __construct(
        private GitHubWebhookService $webhookService
    ) {}

    public function handle(Request $request, int $repositoryId, string $secret): Response
    {
        try {
            // Find the repository
            $repository = GitHubRepository::findOrFail($repositoryId);

            // Verify the webhook secret
            if (!$this->verifySignature($request, $repository->webhook_secret)) {
                Log::warning('GitHub webhook signature verification failed', [
                    'repository_id' => $repositoryId,
                    'ip' => $request->ip(),
                ]);
                return response('Unauthorized', 401);
            }

            // Verify the URL secret parameter
            if (!hash_equals($secret, $repository->webhook_secret)) {
                Log::warning('GitHub webhook URL secret mismatch', [
                    'repository_id' => $repositoryId,
                    'ip' => $request->ip(),
                ]);
                return response('Unauthorized', 401);
            }

            // Get webhook headers and payload
            $eventType = $request->header('X-GitHub-Event');
            $deliveryId = $request->header('X-GitHub-Delivery');
            $payload = $request->all();
            $action = $payload['action'] ?? null;

            // Log the webhook receipt
            Log::info('GitHub webhook received', [
                'repository_id' => $repositoryId,
                'event_type' => $eventType,
                'action' => $action,
                'delivery_id' => $deliveryId,
            ]);

            // Store the webhook for processing
            $webhook = GitHubWebhook::createFromWebhook(
                $repository,
                $eventType,
                $action,
                $deliveryId,
                $payload
            );

            // Process the webhook immediately
            $this->webhookService->processWebhook($webhook);

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('GitHub webhook processing failed', [
                'repository_id' => $repositoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Internal Server Error', 500);
        }
    }

    private function verifySignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expectedSignature, $signature);
    }
}