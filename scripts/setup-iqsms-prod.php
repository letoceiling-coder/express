<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$login = getenv('IQSMS_LOGIN') ?: ($argv[1] ?? '');
$password = getenv('IQSMS_PASSWORD') ?: ($argv[2] ?? '');
$sender = getenv('IQSMS_SENDER') ?: ($argv[3] ?? '');

if ($login === '' || $password === '') {
    fwrite(STDERR, "Usage: IQSMS_LOGIN=... IQSMS_PASSWORD=... php setup-iqsms-prod.php\n");
    exit(1);
}

App\Models\SmsSetting::updateOrCreate(
    ['driver' => 'iqsms'],
    [
        'login' => $login,
        'password' => $password,
        'sender' => $sender,
        'is_enabled' => true,
    ]
);

$svc = app(App\Services\Sms\IqSmsService::class);
echo 'credentials_ok=' . ($svc->hasCredentials() ? 'yes' : 'no') . ' dev_mode=' . ($svc->isDevMode() ? 'yes' : 'no') . "\n";

$r = Illuminate\Support\Facades\Http::timeout(15)->get('https://api.iqsms.ru/');
echo 'iqsms_api_http=' . $r->status() . "\n";
