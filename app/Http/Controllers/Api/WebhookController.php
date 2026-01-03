<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class WebhookController extends Controller
{
    /**
     * Обработка webhook от GitHub
     * Проверяет подпись и запускает деплой
     */
    public function github(Request $request)
    {
        // Проверяем, что это push событие
        $event = $request->header('X-GitHub-Event');
        if ($event !== 'push') {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        // Проверка безопасности: либо подпись GitHub, либо deploy.token
        $secret = config('app.github_webhook_secret') ?: env('GITHUB_WEBHOOK_SECRET');
        $signature = $request->header('X-Hub-Signature-256');
        
        if ($secret && $signature) {
            // Проверяем подпись GitHub
            $payload = $request->getContent();
            
            if (!$this->verifySignature($signature, $payload)) {
                Log::warning('GitHub webhook: Invalid signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        } else {
            // Fallback: проверяем deploy.token если подпись не настроена
            $token = $request->header('Authorization');
            $token = $token ? preg_replace('/Bearer\s+/i', '', $token) : null;
            $token = $token ?: $request->input('token');
            
            $expectedToken = config('app.deploy_token') ?: env('DEPLOY_TOKEN');
            
            if (!$expectedToken || !$token || $token !== $expectedToken) {
                Log::warning('GitHub webhook: Invalid token');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            Log::info('GitHub webhook: Authenticated via deploy.token');
        }

        $data = $request->json()->all();
        $ref = $data['ref'] ?? '';
        $branch = str_replace('refs/heads/', '', $ref);

        // Деплоим только из main ветки
        if ($branch !== 'main') {
            Log::info("Webhook: пропущено, ветка {$branch} не main");
            return response()->json(['message' => 'Branch ignored'], 200);
        }

        Log::info('GitHub webhook received, starting deploy', [
            'branch' => $branch,
            'commit' => $data['after'] ?? 'unknown',
        ]);

        // Запускаем деплой напрямую (webhook должен отвечать быстро)
        try {
            $deployController = new \App\Http\Controllers\Api\DeployController();
            $deployRequest = new \Illuminate\Http\Request(['branch' => 'main']);
            // Вызываем напрямую, так как middleware уже проверен
            $response = $deployController->deploy($deployRequest);
            
            return response()->json([
                'message' => 'Deploy triggered',
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Webhook deploy failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Deploy failed: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Проверить подпись webhook
     */
    protected function verifySignature(?string $signature, string $payload): bool
    {
        $secret = config('app.github_webhook_secret') ?: env('GITHUB_WEBHOOK_SECRET');
        
        if (!$secret || !$signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

}

