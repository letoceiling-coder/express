<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsCode;
use App\Models\User;
use App\Services\Sms\IqSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
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

        $ttlMinutes = config('sms.code_ttl_minutes', 5);
        $codeLength = config('sms.code_length', 6);
        $maxAttempts = config('sms.max_attempts', 5);

        // Проверяем активный код
        $existing = SmsCode::where('phone', $phone)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', $maxAttempts)
            ->latest()
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Код уже отправлен. Повторите попытку через ' . $ttlMinutes . ' минут.',
            ], 429);
        }

        $min = (int) str_pad('1', $codeLength, '0');
        $max = (int) str_repeat('9', $codeLength);
        $code = (string) random_int($min, $max);

        SmsCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes($ttlMinutes),
            'attempts' => 0,
        ]);

        $sms = app(IqSmsService::class);
        if (!$sms->sendCode($phone, $code)) {
            Log::error('AuthController::sendCode: не удалось отправить SMS', ['phone' => substr($phone, 0, 2) . '***']);
            return response()->json([
                'message' => 'Не удалось отправить SMS. Попробуйте позже.',
            ], 500);
        }

        return response()->json([
            'message' => 'Код отправлен на указанный номер',
            'expires_in' => $ttlMinutes * 60,
        ]);
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
        $code = $request->code;
        $maxAttempts = config('sms.max_attempts', 5);

        $smsCode = SmsCode::where('phone', $phone)
            ->latest()
            ->first();

        if (!$smsCode) {
            return response()->json([
                'message' => 'Код не найден. Запросите новый код.',
            ], 404);
        }

        if ($smsCode->isExpired()) {
            return response()->json([
                'message' => 'Код истёк. Запросите новый код.',
            ], 400);
        }

        if ($smsCode->hasExceededAttempts($maxAttempts)) {
            return response()->json([
                'message' => 'Превышено количество попыток. Запросите новый код.',
            ], 429);
        }

        $smsCode->incrementAttempts();

        if ($smsCode->code !== $code) {
            return response()->json([
                'message' => 'Неверный код',
            ], 401);
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

        $token = $user->createToken('web_sms_auth')->plainTextToken;

        return response()->json([
            'message' => 'Успешная авторизация',
            'user' => $user->load('roles'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
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
