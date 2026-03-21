<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCode extends Model
{
    protected $fillable = ['phone', 'code', 'expires_at', 'attempts'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasExceededAttempts(int $max): bool
    {
        return $this->attempts >= $max;
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
