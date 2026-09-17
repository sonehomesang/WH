<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\SetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'username',
        'ad_guid',
        'ldap_imported_at',
        'password',
        'display_name',
        'phone_number',
        'photo_path',
        'unit_id',
        'department_id',
        'supplier_id',
        'status',
        'auth_provider',
        'is_pre_created',
        // 'is_super_admin' is intentionally NOT mass-assignable — it drives the
        // Gate::before bypass. Set it explicitly via forceFill() (see SuperAdminSeeder).
        'must_change_password',
        'dashboard_prefs',
    ];

    /** Default dashboard widget visibility (all on). */
    public const DASHBOARD_DEFAULTS = ['kpi' => true, 'queue' => true, 'activity' => true, 'charts' => true];

    /** Resolved dashboard prefs merged over defaults. */
    public function dashboardPrefs(): array
    {
        return array_merge(self::DASHBOARD_DEFAULTS, $this->dashboard_prefs ?? []);
    }

    /** ໃຊ້ notification ຂອງ ເຮົາ (ລາວ/ອັງກິດ, admin ແກ້ ໄດ້) ແທນ default ຂອງ Laravel. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new SetPasswordNotification($token));
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'ldap_imported_at' => 'datetime',
            'local_password_set_at' => 'datetime',
            'is_pre_created' => 'boolean',
            'is_super_admin' => 'boolean',
            'must_change_password' => 'boolean',
            'dashboard_prefs' => 'array',
        ];
    }

    /**
     * Model hooks: auto-generate uuid + lowercase email on create.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Always store email lowercased (ປ້ອງກັນ case mismatch — WIS lineage).
     */
    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value !== null ? strtolower(trim($value)) : null;
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * ຂອບເຂດ ສິດ Equipment ຈາກ role scope_rules — 'all' > 'department' > 'none'.
     * super_admin ໄດ້ 'all' ສະເໝີ.
     */
    public function equipmentScope(): string
    {
        if ($this->is_super_admin) {
            return 'all';
        }
        $scopes = $this->roles->pluck('scope_rules')
            ->map(fn ($r) => $r['equipmentScope'] ?? null)
            ->filter()
            ->all();
        foreach (['all', 'department', 'none'] as $level) {
            if (in_array($level, $scopes, true)) {
                return $level;
            }
        }

        return 'all';
    }

    /** ຖືກ ຈຳກັດ ໃຫ້ ເຫັນ/ຈັດການ ສະເພາະ ເຄື່ອງ ຂອງ ພະແນກ ຕົນ ບໍ. */
    public function equipmentDepartmentScoped(): bool
    {
        return $this->equipmentScope() === 'department';
    }

    /**
     * ຂອບເຂດ ການ ເຫັນ transaction (borrow/deposit/request) ຈາກ role scope_rules —
     * 'all' > 'department' > 'assigned' > 'own'. super_admin ໄດ້ 'all' ສະເໝີ.
     */
    public function transactionScope(): string
    {
        if ($this->is_super_admin) {
            return 'all';
        }
        $scopes = $this->roles->pluck('scope_rules')
            ->map(fn ($r) => $r['transactionScope'] ?? null)
            ->filter()
            ->all();
        foreach (['all', 'department', 'assigned', 'own'] as $level) {
            if (in_array($level, $scopes, true)) {
                return $level;
            }
        }

        return 'own';
    }
}
