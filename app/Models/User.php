<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'opd_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    /**
     * Get the role that owns the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the OPD that owns the user.
     */
    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Check if user has a specific role by slug
     */
    public function hasRole($roleSlug)
    {
        return $this->role && $this->role->slug === $roleSlug;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is admin bappeda
     */
    public function isAdminBappeda()
    {
        return $this->hasRole('admin-bappeda');
    }

    /**
     * Check if user is admin opd
     */
    public function isAdminOpd()
    {
        return $this->hasRole('admin-opd');
    }

    /**
     * Check if user is any kind of admin
     */
    public function isAdmin()
    {
        return $this->isSuperAdmin() || $this->isAdminBappeda() || $this->isAdminOpd();
    }

    /**
     * Get user's full name with email
     */
    public function getFullNameWithEmailAttribute()
    {
        return $this->name . ' (' . $this->email . ')';
    }

    /**
     * Get user's initials for avatar
     */
    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name);
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        return substr($initials, 0, 2); // Maximum 2 characters
    }

    /**
     * Get user's status text
     */
    public function getStatusTextAttribute()
    {
        if ($this->isSuperAdmin()) {
            return 'Super Admin';
        }
        
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    /**
     * Get user's role name safely
     */
    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->name : 'No Role';
    }

    /**
     * Get user's OPD name safely
     */
    public function getOpdNameAttribute()
    {
        return $this->opd ? $this->opd->name : null;
    }

    /**
     * Get user's OPD singkatan safely
     */
    public function getOpdSingkatanAttribute()
    {
        return $this->opd ? $this->opd->singkatan : null;
    }
}