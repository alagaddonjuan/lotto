<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'tenant_id',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isOperatorRep(): bool
    {
        return $this->role === 'operator_rep';
    }

    /**
     * Generate a 6-digit 2FA OTP code valid for 10 minutes.
     */
    public function generateTwoFactorCode(): string
    {
        $code = (string) random_int(100000, 999999);
        $this->forceFill([
            'two_factor_code' => $code,
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        return $code;
    }

    /**
     * Verify a 6-digit 2FA OTP code.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (
            $this->two_factor_code &&
            $this->two_factor_code === $code &&
            $this->two_factor_expires_at &&
            $this->two_factor_expires_at->isFuture()
        ) {
            $this->resetTwoFactorCode();
            return true;
        }

        return false;
    }

    /**
     * Clear the 2FA code after successful verification.
     */
    public function resetTwoFactorCode(): void
    {
        $this->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }
}
