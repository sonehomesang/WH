<?php

namespace App\Livewire\Settings;

use App\Models\Department;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserHistory;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Users extends Component
{
    use WithPagination;

    public string $search = '';

    /** A-Z group filter (ໂຕອັກສອນ ຂຶ້ນ ໜ້າ display_name / username / email); ວ່າງ = ທັງໝົດ. */
    public string $letter = '';

    // Modal + form
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $display_name = '';

    public string $email = '';

    public string $username = '';

    // Admin-set initial password (optional). When given, the account is usable
    // at once and is forced to change on first login. Prefilled from the
    // configurable Default Password (Settings › Access).
    public string $password = '';

    public string $role = '';

    /** ລິ້ງ ຕັ້ງ ລະຫັດ ທີ່ ສ້າງ ໃໝ່ (ໃຫ້ admin ກ໋ອບປີ້ ສົ່ງ ມື ຖ້າ ອີເມລ ຍັງ ບໍ່ ພ້ອມ). */
    public string $setPasswordLink = '';

    public string $setLinkEmail = '';

    public ?int $unit_id = null;

    public ?int $department_id = null;

    /** ສຳລັບ role=supplier — ຜູກ user ກັບ supplier (ໃຊ້ scope catalog/request/oga ຂອງ portal). */
    public ?int $supplier_id = null;

    public string $status = 'active';

    /** ສິດ ເພີ່ມເຕີມ ລາຍ ບຸກຄົນ — menu keys ທີ່ ເປີດ ໃຫ້ ໂດຍກົງ (ນອກ ເໜືອ ບົດບາດ). */
    public array $extraPerms = [];

    /**
     * ເມນູ ປະຕິບັດການ ທີ່ admin ເປີດ ໃຫ້ ລາຍ ບຸກຄົນ ໄດ້ (ເບິ່ງ+ເພີ່ມ+ແກ້).
     * ບໍ່ ລວມ ເມນູ admin (users/roles/settings/audit/reports) — ກັນ ການ ຍົກ ສິດ ຕົນເອງ.
     *
     * @return array<string,string>
     */
    public static function grantableMenus(): array
    {
        return [
            'inventory' => 'WH Inventories',
            'borrow' => 'Borrowing Material/Tools',
            'deposit' => 'Deposit Material/Equipment',
            'request' => 'Request Material',
            'catalog' => 'Shops Material',
            'equipment' => 'Equipment & Tools',
            'da' => 'DA Claims',
            'oga' => 'OGA',
            'expo' => 'Expo Info',
            'disposal' => 'Disposal',
            'ansi' => 'New Stock Item (ANSI)',
            'area_inspection' => 'Area Inspection',
            'survey' => 'Satisfaction Survey',
        ];
    }

    /**
     * Action ທີ່ ເປີດ ໃຫ້ ຕິກ ຕໍ່ ເມນູ (ຕໍ່ ບຸກຄົນ). label = Lao.
     *
     * @return array<string,string>
     */
    public static function grantableActions(): array
    {
        return ['view' => 'ເບິ່ງ ຂໍ້ມູນ', 'create' => 'ນຳໃຊ້ ຂໍ້ມູນ', 'edit' => 'ແກ້ ຂໍ້ມູນ ທີ່ ນຳໃຊ້'];
    }

    /** ຕັ້ງ ໂຄງ extraPerms ໃຫ້ ທຸກ ເມນູ/action = false. */
    protected function initExtraPerms(): void
    {
        $this->extraPerms = [];
        foreach (array_keys(self::grantableMenus()) as $m) {
            foreach (array_keys(self::grantableActions()) as $a) {
                $this->extraPerms[$m][$a] = false;
            }
        }
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('users.view'), 403);
    }

    public function updatedUnitId(): void
    {
        $this->department_id = null;   // reset department when unit changes
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /** ເລືອກ/ຍົກເລີກ ໂຕອັກສອນ A-Z (ກົດຊ້ຳ = ລ້າງ). */
    public function setLetter(string $l): void
    {
        $this->letter = ($this->letter === $l) ? '' : $l;
        $this->resetPage();
    }

    public function newUser(): void
    {
        $this->resetForm();
        $this->password = self::defaultPassword();   // prefill from Settings › Access
        $this->showModal = true;
    }

    /** The configurable default password for new accounts (decrypted, admin-set). */
    public static function defaultPassword(): string
    {
        $enc = Setting::get('auth')['default_password_enc'] ?? null;
        if (! $enc) {
            return '';
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($enc);
        } catch (\Throwable) {
            return '';
        }
    }

    public function editUser(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        $this->editingId = $user->id;
        $this->display_name = $user->display_name;
        $this->email = $user->email ?? '';
        $this->username = $user->username ?? '';
        $this->password = '';   // blank = keep current; typing a value resets it
        $this->role = $user->roles->first()?->name ?? '';
        $this->unit_id = $user->unit_id;
        $this->department_id = $user->department_id;
        $this->supplier_id = $user->supplier_id;
        $this->status = $user->status;

        // ໂຫຼດ ສິດ ເພີ່ມເຕີມ ໂດຍກົງ (ບໍ່ ນັບ ສິດ ທີ່ ມາ ຈາກ ບົດບາດ)
        $direct = $user->getDirectPermissions()->pluck('name')->all();
        $this->initExtraPerms();
        foreach (array_keys(self::grantableMenus()) as $m) {
            foreach (array_keys(self::grantableActions()) as $a) {
                $this->extraPerms[$m][$a] = in_array("{$m}.{$a}", $direct, true);
            }
        }

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('users.'.($this->editingId ? 'edit' : 'create')), 403);
        // only a super_admin may grant the super_admin role (it carries every permission)
        abort_unless(auth()->user()->is_super_admin || $this->role !== 'super_admin', 403);

        // A new account needs a way in: either an email (→ set-password link) or an
        // admin-typed password. Existing accounts leave password blank to keep it.
        $needsPassword = ! $this->editingId && trim($this->email) === '';

        $data = $this->validate([
            'display_name' => ['required', 'string', 'max:256'],
            // At least one identifier — username OR email. Username signs in when
            // there is no email; both are unique.
            'username' => ['required_without:email', 'nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.\-]+$/', Rule::unique('users', 'username')->ignore($this->editingId)],
            'email' => ['required_without:username', 'nullable', 'email', 'max:256', Rule::unique('users', 'email')->ignore($this->editingId)],
            // Admin-set password (optional; forces change on first login).
            'password' => [$needsPassword ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', 'exists:roles,name'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'supplier_id' => [$this->role === 'supplier' ? 'required' : 'nullable', 'exists:suppliers,id'],
            'status' => ['required', 'in:active,pending,locked'],
        ], [
            'username.regex' => 'ຊື່ຜູ້ໃຊ້ ໃຊ້ ໄດ້ ພຽງ a-z, 0-9, _ . -',
            'username.required_without' => 'ໃສ່ username ຫຼື email ຢ່າງ ໜ້ອຍ 1.',
            'email.required_without' => 'ໃສ່ email ຫຼື username ຢ່າງ ໜ້ອຍ 1.',
            'password.required' => 'ໃສ່ password (ບໍ່ ມີ email → ຕ້ອງ ຕັ້ງ password ໃຫ້).',
            'password.min' => 'password ຢ່າງ ໜ້ອຍ 8 ຕົວ.',
        ], ['supplier_id' => 'Supplier']);

        $attrs = [
            'display_name' => $data['display_name'],
            'username' => Str::lower($data['username']),   // identifier — matched lower-cased at login
            'email' => $data['email'] ?: null,             // optional now
            'unit_id' => $data['unit_id'] ?: null,
            'department_id' => $data['department_id'] ?: null,
            // supplier_id ສະເພາະ role=supplier; role ອື່ນ → null (ກັນ scope ຄ້າງ)
            'supplier_id' => $this->role === 'supplier' ? ($data['supplier_id'] ?: null) : null,
            'status' => $data['status'],
        ];

        // An admin-typed password makes the account usable now + forces a change
        // on first login (must_change_password middleware).
        $adminSetPassword = trim($data['password'] ?? '') !== '';
        if ($adminSetPassword) {
            $attrs += [
                'password' => bcrypt($data['password']),
                'auth_provider' => 'password',
                'must_change_password' => true,
                'local_password_set_at' => now(),
            ];
        }

        $isNew = ! $this->editingId;
        $oldStatus = null;
        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $this->guardSuperAdminTarget($user);   // ຜູ້ ບໍ່ ແມ່ນ super ຫ້າມ ແກ້/demote super_admin
            $oldStatus = $user->status;
            $user->fill($attrs);
            $user->save();
        } else {
            // No admin password → random placeholder; the user sets their own via
            // the email set-password link (issued below when an email exists).
            $user = User::create($attrs + ($adminSetPassword ? [] : [
                'password' => bcrypt(Str::random(40)),
                'auth_provider' => 'password',
            ]));
        }

        $user->syncRoles([$this->role]);

        // ສິດ ເພີ່ມເຕີມ ລາຍ ບຸກຄົນ (ໂດຍກົງ): view+create+edit ຕໍ່ ເມນູ ທີ່ ໝາຍ.
        // intersect ກັບ grantable → ກັນ ການ ໃຫ້ ສິດ admin (escalation).
        $directPerms = [];
        foreach (array_keys(self::grantableMenus()) as $m) {
            $p = $this->extraPerms[$m] ?? [];
            $create = ! empty($p['create']);
            $edit = ! empty($p['edit']);
            $view = ! empty($p['view']) || $create || $edit;   // create/edit ⇒ view
            if ($view) {
                $directPerms[] = "{$m}.view";
            }
            if ($create) {
                $directPerms[] = "{$m}.create";
            }
            if ($edit) {
                $directPerms[] = "{$m}.edit";
            }
        }
        $user->syncPermissions($directPerms);

        // Audit + notify every action. An edit that flips status to active
        // counts as an activation exactly like the ✓ Approve button does.
        $becameActive = $user->status === 'active' && ($isNew || $oldStatus !== 'active');
        $action = match (true) {
            $isNew => 'create',
            $becameActive => 'activate',
            $oldStatus !== $user->status && $user->status === 'locked' => 'lock',
            default => 'update',
        };
        $this->recordUserAction($user, $action);

        $this->showModal = false;
        $this->dispatch('saved');

        // ບັນຊີ ໃໝ່ ທີ່ admin ບໍ່ ໄດ້ ຕັ້ງ password + ມີ email → ສ້າງ ລິ້ງ ຕັ້ງ ລະຫັດ.
        if ($isNew && ! $adminSetPassword && $user->email && config('features.local_auth')) {
            $this->issueSetPasswordLink($user);
        }
    }

    /** ສ້າງ ລິ້ງ ຕັ້ງ-ລະຫັດ (ໝົດ ອາຍຸ 60 ນາທີ) + ສົ່ງ ອີເມລ; ເກັບ ລິ້ງ ໄວ້ ໃຫ້ admin ກ໋ອບປີ້. */
    protected function issueSetPasswordLink(User $user): void
    {
        $token = Password::createToken($user);
        $this->setLinkEmail = $user->email;
        $this->setPasswordLink = route('password.reset', ['token' => $token]).'?email='.urlencode($user->email);
        // ສົ່ງ notification ດ້ວຍ token ດຽວ ກັນ (ສົ່ງ ຈິງ ຖ້າ MAIL ຕັ້ງ; ບໍ່ ດັ່ງນັ້ນ ລົງ log).
        $user->sendPasswordResetNotification($token);
    }

    /** ຫ້າມ ຜູ້ ບໍ່ ແມ່ນ super_admin ໄປ ຈັດການ ບັນຊີ super_admin (lock/demote/reset-link). */
    protected function guardSuperAdminTarget(User $target): void
    {
        abort_if(! auth()->user()->is_super_admin && $target->is_super_admin, 403);
    }

    /** ປຸ່ມ "ສົ່ງ/ກ໋ອບປີ້ ລິ້ງ ຕັ້ງ ລະຫັດ" ຕໍ່ ຜູ້ໃຊ້ (resend). */
    public function linkFor(int $id): void
    {
        abort_unless(auth()->user()->can('users.edit'), 403);
        abort_unless(config('features.local_auth'), 403);
        $user = User::findOrFail($id);
        $this->guardSuperAdminTarget($user);
        $this->issueSetPasswordLink($user);
    }

    public function closeLink(): void
    {
        $this->setPasswordLink = '';
        $this->setLinkEmail = '';
    }

    public function approve(int $id): void
    {
        abort_unless(auth()->user()->can('users.activate'), 403);
        $user = User::findOrFail($id);
        $this->guardSuperAdminTarget($user);
        $user->update(['status' => 'active']);
        $this->recordUserAction($user, 'activate');
    }

    /** Log the action to the audit trail AND notify the super admins (who · what · when). */
    protected function recordUserAction(User $target, string $action, ?string $comment = null): void
    {
        $this->logUserHistory($target, $action, $comment);
        $this->notifyAdminsUserAction($target, $action);
    }

    /**
     * Append an audit row for a user-account action — record_id = the target
     * user, user_name/role = the ACTOR (which admin), created_at = when. Shows
     * up in Settings › Audit log (module "user").
     */
    protected function logUserHistory(User $target, string $action, ?string $comment = null): void
    {
        $actor = auth()->user();
        UserHistory::create([
            'record_id' => $target->id,
            'action' => $action,
            'status' => $target->status,
            'user_id' => $actor?->id,
            'user_name' => $actor?->display_name ?: $actor?->email,
            'role' => $actor?->roles->first()?->name,
            'comment' => $comment ?? $target->email,
            'created_at' => now(),
        ]);
    }

    /** Lao labels for user-account actions (bell title + message verb). */
    protected const ACTION_LABELS = [
        'create' => 'ສ້າງ ບັນຊີ',
        'activate' => 'ເປີດ ໃຊ້ ບັນຊີ',
        'lock' => 'ລັອກ ບັນຊີ',
        'unlock' => 'ປົດ ລັອກ ບັນຊີ',
        'update' => 'ແກ້ໄຂ ບັນຊີ',
    ];

    /** ແຈ້ງ super admin ທຸກ ຄົນ (ລວມ ຄົນ ທີ່ ກົດ) ວ່າ ໃຜ ເຮັດ ຫຍັງ ກັບ ບັນຊີ · ເມື່ອ ໃດ. */
    protected function notifyAdminsUserAction(User $target, string $action): void
    {
        $actor = auth()->user();
        $ids = User::where('is_super_admin', true)
            ->where('status', 'active')
            ->pluck('id')->all();
        if (empty($ids)) {
            return;
        }
        $label = self::ACTION_LABELS[$action] ?? 'ຈັດການ ບັນຊີ';
        app(NotificationService::class)->notifyMany(
            $ids,
            $action === 'lock' ? 'warning' : 'info',
            $label,
            ($actor->display_name ?: $actor->email).' '.$label.' '
                .($target->display_name ?: $target->email).' ເມື່ອ '.now()->format('d/m/Y H:i'),
            route('settings.users'),
        );
    }

    public function toggleLock(int $id): void
    {
        $user = User::findOrFail($id);
        $this->guardSuperAdminTarget($user);
        $target = $user->status === 'locked' ? 'active' : 'locked';
        abort_unless(auth()->user()->can('users.'.($target === 'locked' ? 'deactivate' : 'activate')), 403);
        $user->update(['status' => $target]);
        $this->recordUserAction($user, $target === 'locked' ? 'lock' : 'unlock');
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->display_name = '';
        $this->email = '';
        $this->username = '';
        $this->password = '';
        $this->role = '';
        $this->unit_id = null;
        $this->department_id = null;
        $this->supplier_id = null;
        $this->status = 'active';
        $this->initExtraPerms();
        $this->setPasswordLink = '';
        $this->setLinkEmail = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::with(['roles', 'unit', 'department'])
            ->when($this->search, function ($q) {
                $q->where(fn ($w) => $w->where('display_name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"));
            })
            ->when($this->letter, fn ($q) => $q->where(fn ($w) => $w->where('display_name', 'like', "{$this->letter}%")
                ->orWhere('username', 'like', "{$this->letter}%")
                ->orWhere('email', 'like', "{$this->letter}%")))
            ->orderBy('display_name')
            ->paginate(9);

        return view('livewire.settings.users', [
            'users' => $users,
            'letters' => range('A', 'Z'),
            'roles' => Role::when(! auth()->user()->is_super_admin, fn ($q) => $q->where('name', '!=', 'super_admin'))
                ->orderBy('name')->pluck('name'),
            'grantableMenus' => self::grantableMenus(),
            'grantableActions' => self::grantableActions(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'formDepartments' => $this->unit_id
                ? Department::where('unit_id', $this->unit_id)->where('is_active', true)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
