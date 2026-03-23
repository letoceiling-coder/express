<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Если передан верный X-Integration-Token, обрабатывает preflight OPTIONS
 * и добавляет CORS-заголовки к ответу — удобно для SPA на другом домене.
 *
 * Не заменяет Sanctum: пользовательские сессии по-прежнему через Bearer Sanctum.
 */
class IntegrationPartnerCors
{
    public const HEADER_TOKEN = 'X-Integration-Token';

    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('integration.token');

        if (! is_string($expected) || $expected === '') {
            return $next($request);
        }

        $token = $request->header(self::HEADER_TOKEN);
        if (! is_string($token) || $token === '' || ! hash_equals($expected, $token)) {
            return $next($request);
        }

        $allowedOrigins = config('integration.allowed_origins', []);
        $originHeader = $request->header('Origin');
        if (is_array($allowedOrigins) && $allowedOrigins !== [] && $originHeader
            && ! in_array($originHeader, $allowedOrigins, true)) {
            return response()->json([
                'message' => 'Origin is not allowed for X-Integration-Token',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($request->isMethod('OPTIONS')) {
            return $this->preflightResponse($request);
        }

        $response = $next($request);

        return $this->applyCorsHeaders($request, $response);
    }

    protected function preflightResponse(Request $request): Response
    {
        $response = response('', Response::HTTP_NO_CONTENT);
        $this->applyCorsHeaders($request, $response);

        return $response;
    }

    protected function applyCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $request->header('Origin');
        $allowOrigin = $this->resolveAllowOrigin($origin);

        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, Accept, X-Requested-With, X-Integration-Token, X-Device-ID, X-Telegram-Init-Data, X-Bot-Token'
        );
        $response->headers->set('Access-Control-Max-Age', '86400');

        $credentials = filter_var(config('cors.supports_credentials', false), FILTER_VALIDATE_BOOLEAN);
        if ($credentials && $allowOrigin !== '*') {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    protected function resolveAllowOrigin(?string $requestOrigin): string
    {
        $allowed = config('integration.allowed_origins', []);

        if (is_array($allowed) && $allowed !== []) {
            if ($requestOrigin && in_array($requestOrigin, $allowed, true)) {
                return $requestOrigin;
            }

            return $allowed[0];
        }

        if ($requestOrigin) {
            return $requestOrigin;
        }

        return '*';
    }
}
