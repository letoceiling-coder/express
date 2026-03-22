<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsCode;
use App\Models\User;
use App\Services\Sms\IqSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Регистрация пользователя
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'last_ticket_accrual_at' => now(), // Устанавливаем время для автоматического начисления
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'user' => $user->load('roles'),
            'token' => $token,
        ], 201);
    }

    /**
     * Авторизация пользователя
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Неверные учетные данные',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Успешная авторизация',
            'user' => $user->load('roles'),
            'token' => $token,
        ]);
    }

    /**
     * Выход пользователя
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Успешный выход',
        ]);
    }

    /**
     * Получить текущего пользователя
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('roles'),
        ]);
    }

    /**
     * Запрос на восстановление пароля
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Ссылка для восстановления пароля отправлена на email',
            ]);
        }

        return response()->json([
            'message' => 'Не удалось отправить ссылку для восстановления пароля',
        ], 400);
    }

    /**
     * Сброс пароля
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Пароль успешно изменен',
            ]);
        }

        return response()->json([
            'message' => 'Не удалось изменить пароль',
        ], 400);
    }

    /**
     * WEB AUTH: Отправить SMS с кодом подтверждения
     */
    public function sendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[\d\s\+\-\(\)]{10,20}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $this->normalizePhoneForStorage($request->phone);
        if (!$phone || strlen($phone) < 10) {
            return response()->json([
                'message' => 'Некорректный номер телефона',
            ], 422);
        }

        // Abuse detection: block phone after too many failed attempts
        if ($this->isPhoneBlocked($phone)) {
            $seconds = RateLimiter::availableIn('sms-abuse-phone:' . $phone);
            return response()->json([
                'message' => 'Доступ временно заблокирован. Попробуйте через ' . $seconds . ' сек.',
            ], 429);
        }
        if ($this->isIpBlocked($request->ip())) {
            $seconds = RateLimiter::availableIn('sms-abuse-ip:' . $request->ip());
            return response()->json([
                'message' => 'Превышен лимит попыток с вашего IP. Попробуйте позже.',
            ], 429);
        }

        // Rate limit per phone (per IP): 3 requests per 5 minutes
        $phoneLimit = config('sms.rate_limit_per_phone', 3);
        $phoneDecay = config('sms.rate_limit_per_phone_decay', 300);
        $phoneKey = 'sms-send-code-phone:' . $phone;
        if (RateLimiter::tooManyAttempts($phoneKey, $phoneLimit)) {
            $seconds = RateLimiter::availableIn($phoneKey);
            return response()->json([
                'message' => 'Слишком много запросов для этого номера. Попробуйте через ' . $seconds . ' сек.',
            ], 429);
        }
        RateLimiter::hit($phoneKey, $phoneDecay);

        // Global phone limit (across all IPs): max 5 per 10 minutes
        $globalLimit = config('sms.rate_limit_global_per_phone', 5);
        $globalDecay = config('sms.rate_limit_global_per_phone_decay', 600);
        $globalKey = 'sms-send-code-global:' . $phone;
        if (RateLimiter::tooManyAttempts($globalKey, $globalLimit)) {
            $seconds = RateLimiter::availableIn($globalKey);
            return response()->json([
                'message' => 'Превышен лимит отправки SMS на этот номер. Попробуйте через ' . $seconds . ' сек.',
            ], 429);
        }
        RateLimiter::hit($globalKey, $globalDecay);

        $ttlMinutes = config('sms.code_ttl_minutes', 5);
        $codeLength = config('sms.code_length', 6);
        $maxAttempts = config('sms.max_attempts', 5);

        return DB::transaction(function () use ($request, $phone, $ttlMinutes, $codeLength, $maxAttempts) {
            // Lock to prevent race condition (concurrent send-code for same phone)
            $existing = SmsCode::where('phone', $phone)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->where('attempts', '<', $maxAttempts)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $sms = app(IqSmsService::class);

            if ($sms->isDevMode()) {
                if ($existing) {
                    return response()->json([
                        'message' => 'Dev mode',
                        'dev_code' => '123456',
                    ], 429);
                }
                SmsCode::create([
                    'phone' => $phone,
                    'code' => '123456',
                    'expires_at' => now()->addMinutes($ttlMinutes),
                    'attempts' => 0,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent() ? substr($request->userAgent(), 0, 500) : null,
                    'device_id' => $request->header('X-Device-ID') ? substr($request->header('X-Device-ID'), 0, 100) : null,
                ]);
                return response()->json([
                    'message' => 'Dev mode',
                    'dev_code' => '123456',
                ]);
            }

            if ($existing) {
                return response()->json([
                    'message' => 'Код уже отправлен. Повторите попытку через ' . $ttlMinutes . ' минут.',
                ], 429);
            }

            $min = (int) str_pad('1', $codeLength, '0');
            $max = (int) str_repeat('9', $codeLength);
            $code = (string) random_int($min, $max);

            if (!$sms->sendCode($phone, $code)) {
                $maskedPhone = substr($phone, 0, 2) . '***' . substr($phone, -2);
                Log::error('AuthController::sendCode: SMS send failed', [
                    'phone' => $maskedPhone,
                    'reason' => $sms->getLastError() ?? 'unknown',
                    'credentials_missing' => !$sms->hasCredentials(),
                ]);
                return response()->json([
                    'message' => 'Не удалось отправить SMS',
                ], 400);
            }

            SmsCode::create([
                'phone' => $phone,
                'code' => $code,
                'expires_at' => now()->addMinutes($ttlMinutes),
                'attempts' => 0,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent() ? substr($request->userAgent(), 0, 500) : null,
                'device_id' => $request->header('X-Device-ID') ? substr($request->header('X-Device-ID'), 0, 100) : null,
            ]);

            return response()->json([
                'message' => 'Код отправлен на указанный номер',
                'expires_in' => $ttlMinutes * 60,
            ]);
        });
    }

    /**
     * WEB AUTH: Проверить код и выдать Sanctum token
     */
    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[\d\s\+\-\(\)]{10,20}$/',
            'code' => 'required|string|size:' . (config('sms.code_length', 6)),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $this->normalizePhoneForStorage($request->phone);
        if (!$phone) {
            return response()->json([
                'message' => 'Некорректный номер телефона',
            ], 422);
        }

        // Abuse detection: block phone/IP after too many failed attempts
        if ($this->isPhoneBlocked($phone)) {
            $seconds = RateLimiter::availableIn('sms-abuse-phone:' . $phone);
            return response()->json([
                'message' => 'Доступ временно заблокирован. Попробуйте через ' . $seconds . ' сек.',
            ], 429);
        }
        if ($this->isIpBlocked($request->ip())) {
            $seconds = RateLimiter::availableIn('sms-abuse-ip:' . $request->ip());
            return response()->json([
                'message' => 'Превышен лимит попыток с вашего IP. Попробуйте позже.',
            ], 429);
        }

        // Brute-force delay
        $delaySeconds = config('sms.verify_delay_seconds', 1);
        if ($delaySeconds > 0) {
            usleep((int) ($delaySeconds * 1000000));
        }

        // Verify rate limit: max 5 attempts per minute per phone
        $verifyLimit = config('sms.verify_rate_limit_per_phone', 5);
        $verifyDecay = config('sms.verify_rate_limit_decay', 60);
        $verifyKey = 'sms-verify-phone:' . $phone;
        if (RateLimiter::tooManyAttempts($verifyKey, $verifyLimit)) {
            $seconds = RateLimiter::availableIn($verifyKey);
            return response()->json([
                'message' => 'Слишком много попыток. Попробуйте через ' . $seconds . ' сек.',
            ], 429);
        }
        RateLimiter::hit($verifyKey, $verifyDecay);

        $code = $request->code;
        $isMock = in_array(config('app.env'), ['local', 'development', 'dev'], true) && $code === '123456';

        if (!$isMock) {
            $maxAttempts = config('sms.max_attempts', 5);

            $result = DB::transaction(function () use ($phone, $code, $maxAttempts) {
                $smsCode = SmsCode::where('phone', $phone)
                    ->whereNull('used_at')
                    ->where('expires_at', '>', now())
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (!$smsCode) {
                    return ['ok' => false, 'message' => 'Код не найден. Запросите новый код.', 'status' => 400];
                }
                if ($smsCode->isExpired()) {
                    return ['ok' => false, 'message' => 'Код истёк. Запросите новый код.', 'status' => 400];
                }
                if ($smsCode->hasExceededAttempts($maxAttempts)) {
                    return ['ok' => false, 'message' => 'Превышено количество попыток. Запросите новый код.', 'status' => 429];
                }

                $smsCode->incrementAttempts();

                if ($smsCode->code !== $code) {
                    return ['ok' => false, 'wrong_code' => true];
                }

                $smsCode->markAsUsed();
                return ['ok' => true];
            });

            if (isset($result['status'])) {
                return response()->json(['message' => $result['message']], $result['status']);
            }
            if (!empty($result['wrong_code'])) {
                $this->recordAbuseAttempt($phone, $request->ip());
                return response()->json(['message' => 'Неверный код'], 401);
            }
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $user = User::create([
                'name' => 'User',
                'email' => 'sms_' . $phone . '@web.local',
                'password' => Hash::make(Str::random(32)),
                'phone' => $phone,
                'phone_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'phone_verified_at' => now(),
            ]);
        }

        // Limit active tokens: max 3 per user, delete oldest
        $maxTokens = config('sms.max_tokens_per_user', 3);
        $tokens = $user->tokens()->orderByDesc('created_at')->get();
        if ($tokens->count() >= $maxTokens) {
            foreach ($tokens->skip($maxTokens - 1) as $oldToken) {
                $oldToken->delete();
            }
        }

        $expiresAt = now()->addDays(config('sms.token_expiration_days', 7));
        $token = $user->createToken('web_sms_auth', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'message' => 'Успешная авторизация',
            'user' => $user->load('roles'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    protected function isPhoneBlocked(string $phone): bool
    {
        $limit = config('sms.abuse_failed_attempts_phone', 10);
        $decay = config('sms.abuse_block_phone_minutes', 60) * 60;
        return RateLimiter::tooManyAttempts('sms-abuse-phone:' . $phone, $limit);
    }

    protected function isIpBlocked(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }
        $limit = config('sms.abuse_failed_attempts_ip', 15);
        return RateLimiter::tooManyAttempts('sms-abuse-ip:' . $ip, $limit);
    }

    protected function recordAbuseAttempt(string $phone, ?string $ip): void
    {
        $phoneLimit = config('sms.abuse_failed_attempts_phone', 10);
        $phoneDecay = config('sms.abuse_block_phone_minutes', 60) * 60;
        RateLimiter::hit('sms-abuse-phone:' . $phone, $phoneDecay);

        if ($ip) {
            $ipLimit = config('sms.abuse_failed_attempts_ip', 15);
            $ipDecay = config('sms.abuse_block_ip_minutes', 60) * 60;
            RateLimiter::hit('sms-abuse-ip:' . $ip, $ipDecay);
        }
    }

    /**
     * Нормализация номера для хранения (10+ цифр, для РФ: 7XXXXXXXXXX)
     */
    protected function normalizePhoneForStorage(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (strlen($digits) < 10) {
            return null;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '7' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            return '7' . substr($digits, 1);
        }
        return $digits;
    }
}
