<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'company_id',
        'role',
        'is_active',
        'name',
        'email',
        'password',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role === 'platform_admin';
    }

    public function isCompanyOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isCompanyManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isCompanyStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    public function isReceiver(): bool
    {
        return $this->role === 'receiver';
    }

    public function isAccountant(): bool
    {
        return in_array($this->role, ['accountant', 'finance_approver']);
    }

    public function isFinanceApprover(): bool
    {
        return in_array($this->role, ['accountant', 'finance_approver', 'owner', 'manager']) || $this->isPlatformAdmin();
    }

    public function canManageFinances(): bool
    {
        return $this->isFinanceApprover();
    }

    public function canManageOrders(): bool
    {
        return $this->isPlatformAdmin() || in_array($this->role, ['owner', 'manager', 'buyer', 'receiver', 'staff']);
    }

    public function canManageCatalog(): bool
    {
        return $this->isPlatformAdmin() || in_array($this->role, ['owner', 'manager']);
    }

    public function canAccessCompany(int $targetCompanyId): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }
        return (int)$this->company_id === (int)$targetCompanyId;
    }

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
            'is_active' => 'boolean',
        ];
    }
}
