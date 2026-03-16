<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpChallenge extends Model
{
    protected $fillable = [
        'token',
        'channel',
        'recipient',
        'code',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        return $this->verified_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < 5;
    }

    public function markVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
}
