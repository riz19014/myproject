<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const TYPE_ACCOUNTANT = 'accountant';

    public const TYPE_USER = 'user';

    public const TYPE_ADMIN = 'admin';

    public const TYPE_OWNER = 'owner';

    public const TYPE_SUPER_ADMIN = 'super-admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'cnic',
        'type',
        'password',
        'is_active',
    ];

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_ACCOUNTANT,
            self::TYPE_USER,
            self::TYPE_ADMIN,
            self::TYPE_OWNER,
            self::TYPE_SUPER_ADMIN,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_ACCOUNTANT => 'Accountant',
            self::TYPE_USER => 'User',
            self::TYPE_ADMIN => 'Admin',
            self::TYPE_OWNER => 'Owner',
            self::TYPE_SUPER_ADMIN => 'Super Admin',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? ucfirst((string) $this->type);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The table does not have remember_token column.
     */
    public function getRememberTokenName(): ?string
    {
        return null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
