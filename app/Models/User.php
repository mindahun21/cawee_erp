<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // ── HR Role Helpers ────────────────────────────────────────────
    // These methods provide readable, semantic checks across the codebase.
    // Use these instead of hardcoding role name strings everywhere.

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isHrDirector(): bool
    {
        return $this->hasAnyRole(['hr_director', 'super_admin']);
    }

    public function isHrOfficer(): bool
    {
        return $this->hasAnyRole(['hr_officer', 'hr_director', 'super_admin']);
    }

    public function isHrSupervisor(): bool
    {
        return $this->hasAnyRole(['hr_supervisor', 'hr_officer', 'hr_director', 'super_admin']);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }
}
