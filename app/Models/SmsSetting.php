<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Настройки SMS-сервиса (IQSMS)
 *
 * @property int $id
 * @property string $driver
 * @property string|null $login
 * @property string|null $password (encrypted)
 * @property string|null $sender
 * @property bool $is_enabled
 */
class SmsSetting extends Model
{
    protected $table = 'sms_settings';

    protected $fillable = [
        'driver',
        'login',
        'password',
        'sender',
        'is_enabled',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'is_enabled' => 'boolean',
    ];

    public static function forDriver(string $driver = 'iqsms'): ?self
    {
        return self::where('driver', $driver)->first();
    }
}
