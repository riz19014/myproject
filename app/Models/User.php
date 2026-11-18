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
        'society_role',
        'password',
        'is_active',
    ];

    public const SOCIETY_ROLE_VIEWER = 'viewer';

    public const SOCIETY_ROLE_OPERATOR = 'operator';

    public const SOCIETY_ROLE_MANAGER = 'manager';

    public const SOCIETY_ROLE_APPROVER = 'approver';

    /**
     * @return array<string, string>
     */
    public static function societyRoleLabels(): array
    {
        return [
            self::SOCIETY_ROLE_VIEWER => 'Viewer',
            self::SOCIETY_ROLE_OPERATOR => 'Operator',
            self::SOCIETY_ROLE_MANAGER => 'Manager',
            self::SOCIETY_ROLE_APPROVER => 'Approver',
        ];
    }

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

    public function isElevatedAdmin(): bool
    {
        return in_array($this->type, [
            self::TYPE_ADMIN,
            self::TYPE_OWNER,
            self::TYPE_SUPER_ADMIN,
        ], true);
    }

    public function effectiveSocietyRole(): string
    {
        if ($this->isElevatedAdmin()) {
            return self::SOCIETY_ROLE_APPROVER;
        }

        if ($this->society_role) {
            return (string) $this->society_role;
        }

        return match ($this->type) {
            self::TYPE_ACCOUNTANT => self::SOCIETY_ROLE_MANAGER,
            default => self::SOCIETY_ROLE_OPERATOR,
        };
    }

    public function canManageSociety(): bool
    {
        return in_array($this->effectiveSocietyRole(), [
            self::SOCIETY_ROLE_OPERATOR,
            self::SOCIETY_ROLE_MANAGER,
            self::SOCIETY_ROLE_APPROVER,
        ], true);
    }

    public function canApproveSociety(): bool
    {
        return in_array($this->effectiveSocietyRole(), [
            self::SOCIETY_ROLE_MANAGER,
            self::SOCIETY_ROLE_APPROVER,
        ], true) || $this->isElevatedAdmin();
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
