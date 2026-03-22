<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCode extends Model
{
    protected $fillable = ['phone', 'code', 'expires_at', 'attempts', 'used_at', 'ip', 'user_agent', 'device_id'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function hasExceededAttempts(int $max): bool
    {
        return $this->attempts >= $max;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
