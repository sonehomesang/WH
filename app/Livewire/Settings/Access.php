<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserHistory;
use App\Services\LdapDirectory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings › Access & Auth Modes — the super-admin control that decides how WH
 * checks a login (local_only / ad_strict / ad_fallback), guarantees break-glass
 * local admins, and provisions local passwords for domain users so the system
 * stays usable when AD is down. Local auth is the permanent foundation; AD is an
 * overlay toggled here. See LdapDirectory::mode().
 */
#[Layout('layouts.app')]
class Access extends Component
{
    /** Selected auth mode (bound to the radio cards). */
    public string $mode = LdapDirectory::MODE_LOCAL;

    /** Optional shared temp password for the "one password for everyone" path. */
    public string $sharedPassword = '';

    /**
     * Re-issue mode. Off (default): only touch domain users who still have NO local
     * password — safe for onboarding. On: (re)set EVERY domain user, overwriting an
     * existing temp password too, so an admin can rotate/rescue passwords from the UI
     * without ever editing code. auth_provider stays 'domain' either way (AD-safe).
     */
    public bool $resetAll = false;

    /** Freshly generated unique temp passwords, shown ONCE for secure hand-out. */
    public array $provisioned = [];

    /** Manual AD reachability test result. */
    public string $testResult = '';

    public string $testType = '';

    public function mount(): void
    {
        // super admin only — Auth Mode is a system-wide security control.
        abort_unless(auth()->user()->is_super_admin, 403);
        $this->mode = app(LdapDirectory::class)->mode();
    }

    /** Switch the auth mode. Phase A ships local_only + ad_strict; ad_fallback is Phase B. */
    public function setMode(string $mode): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        if (! in_array($mode, [LdapDirectory::MODE_LOCAL, LdapDirectory::MODE_AD_STRICT], true)) {
            $this->addError('mode', 'ໂໝດ ນີ້ ຍັງ ບໍ່ ເປີດ ໃຫ້ ໃຊ້ (Phase B).');

            return;
        }

        $this->mode = $mode;
        $auth = Setting::get('auth', []);
        $auth['mode'] = $mode;
        Setting::put('auth', $auth, auth()->id());   // merge — keep other auth keys intact
        // Keep the legacy ldap.login_with_ad flag in step so the Active Directory
        // page reflects reality (mode() is authoritative, but this avoids a toggle
        // that looks like it does nothing).
        $ldap = Setting::get('ldap', []);
        $ldap['login_with_ad'] = $mode !== LdapDirectory::MODE_LOCAL;
        Setting::put('ldap', $ldap, auth()->id());

        $this->auditModeChange($mode);
        session()->flash('access_ok', '✓ ປ່ຽນ Auth Mode ແລ້ວ — ມີ ຜົນ ທັນທີ.');
    }

    /**
     * Domain accounts eligible for local-password provisioning. A super admin is
     * NEVER touched — excluded by BOTH the is_super_admin flag AND the super_admin
     * role, because some role-super-admins carry the role without the flag and must
     * not have their password reset out from under them.
     */
    protected function eligibleQuery()
    {
        return User::query()
            ->where('auth_provider', 'domain')
            ->where('is_super_admin', false)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'));
    }

    /**
     * The accounts a provisioning action will touch. Default: only those still
     * without a local password. In reset mode: every eligible domain user.
     */
    protected function targetUsers()
    {
        return $this->eligibleQuery()
            ->when(! $this->resetAll, fn ($q) => $q->whereNull('local_password_set_at'));
    }

    /** Give every targeted domain user a UNIQUE temp password. */
    public function provisionUnique(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        $this->provisioned = [];
        $targets = $this->targetUsers()->get();

        foreach ($targets as $u) {
            $temp = Str::password(10, symbols: false);   // readable for hand-out
            $u->forceFill([
                'password' => bcrypt($temp),
                'must_change_password' => true,
                'local_password_set_at' => now(),
                'status' => $u->status === 'locked' ? 'locked' : 'active',
            ])->save();

            $this->logAudit($u, 'temp_password', $this->resetAll ? 'reset' : null);
            $this->provisioned[] = [
                'name' => $u->display_name ?: $u->email,
                'email' => $u->email,
                'password' => $temp,
            ];
        }

        session()->flash('access_ok', '✓ ອອກ ລະຫັດ ຊົ່ວຄາວ ໃຫ້ '.count($this->provisioned).' ຄົນ — ສະ ແດງ ຄັ້ງ ດຽວ, ກ໋ອບ ໄປ ແຈກ ໃຫ້ ປອດໄພ.');
    }

    /** Apply ONE shared temp password to every domain user still without a local one. */
    public function provisionShared(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);
        $this->validate(
            ['sharedPassword' => ['required', 'string', 'min:8']],
            ['sharedPassword.required' => 'ໃສ່ ລະຫັດ ຊົ່ວຄາວ ຮ່ວມ.', 'sharedPassword.min' => 'ຢ່າງ ໜ້ອຍ 8 ຕົວ.'],
        );

        $n = $this->targetUsers()
            ->get()
            ->each(fn (User $u) => $u->forceFill([
                'password' => bcrypt($this->sharedPassword),
                'must_change_password' => true,
                'local_password_set_at' => now(),
                'status' => $u->status === 'locked' ? 'locked' : 'active',
            ])->save() && $this->logAudit($u, 'temp_password', $this->resetAll ? 'reset' : null))
            ->count();

        $this->sharedPassword = '';
        session()->flash('access_ok', "✓ ຕັ້ງ ລະຫັດ ຊົ່ວຄາວ ຮ່ວມ ໃຫ້ {$n} ຄົນ · ບັງຄັບ ປ່ຽນ ຕອນ login ຄັ້ງ ທຳອິດ.");
    }

    public function clearProvisioned(): void
    {
        $this->provisioned = [];
    }

    /** Manual, explicit AD reachability test (the only thing here that touches the DC). */
    public function testConnection(): void
    {
        abort_unless(auth()->user()->is_super_admin, 403);
        $res = app(LdapDirectory::class)->testConnection();
        $this->testType = ($res['ok'] ?? false) ? 'ok' : 'err';
        $this->testResult = (string) ($res['message'] ?? '');
    }

    protected function auditModeChange(string $mode): void
    {
        $this->logAudit(auth()->user(), 'auth_mode', "mode → {$mode}");
        Log::info('auth.mode changed', ['mode' => $mode, 'by' => auth()->id()]);
    }

    protected function logAudit(User $target, string $action, ?string $comment = null): void
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

    public function render(): View
    {
        $breakGlass = User::query()
            ->where('is_super_admin', true)
            ->where('auth_provider', '!=', 'domain')
            ->where('status', 'active')
            ->get(['id', 'username', 'email', 'display_name']);

        $domainTotal = $this->eligibleQuery()->count();
        $needingLocal = $this->eligibleQuery()->whereNull('local_password_set_at')->count();

        return view('livewire.settings.access', [
            'breakGlass' => $breakGlass,
            'domainTotal' => $domainTotal,
            'needingLocal' => $needingLocal,
            // how many a provisioning click will touch, given the reset toggle
            'targetCount' => $this->resetAll ? $domainTotal : $needingLocal,
            'lastSync' => Setting::get('ldap_last_sync', []),
        ]);
    }
}
