<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'token',
        'username',
        'webhook_url',
        'webhook_registered',
        'welcome_message',
        'button_text',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'webhook_registered' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Связь с пользователями бота
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function telegramUsers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TelegramUser::class, 'bot_id', 'id');
    }
}
