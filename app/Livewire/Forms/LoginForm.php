<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Services\LdapDirectory;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class LoginForm extends Form
{
    // How the person is signing in:
    //   'name'  → pick Department + Name (the normal staff flow)
    //   'email' → type an email (break-glass super admin / legacy)
    public string $loginBy = 'name';

    // name flow: the chosen department + the selected person's username.
    public ?int $department_id = null;

    public string $username = '';

    // email flow (admin / break-glass): a full email OR a bare username.
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * The IDENTIFIER (how we find the account) is decoupled from the AUTH METHOD
     * (local password vs AD bind). We resolve the user by name+department (or by
     * email for admins), then run the exact same per-mode check as before, so the
     * Access & Auth mode switch (local_only / ad_strict / ad_fallback) is untouched.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();
        $this->validateInput();

        $ldap = app(LdapDirectory::class);
        $user = $this->resolveUser();

        if ($ldap->loginEnabled() && $user && $user->auth_provider === 'domain') {
            // Domain account → verify the typed password against AD by binding as
            // the person. WH never stores the AD password. If AD is down, or the
            // account was disabled/removed in AD, the bind simply fails — we never
            // fall back to a stale local hash.
            if (! $this->bindAgainstAd($ldap, $user)) {
                $this->registerFailure();

                throw ValidationException::withMessages([
                    'form.password' => trans('auth.failed'),
                ]);
            }

            // First successful AD sign-in activates a pre-created pending account.
            if ($user->status === 'pending') {
                $user->forceFill([
                    'status' => 'active',
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }

            Auth::login($user, $this->remember);
        } elseif (! $user || ! Auth::attempt(['id' => $user->id, 'password' => $this->password], $this->remember)) {
            // local accounts (break-glass admin, name+dept users, domain users in
            // local_only mode) authenticate by their resolved id + local password
            $this->registerFailure();

            throw ValidationException::withMessages([
                'form.password' => trans('auth.failed'),
            ]);
        }

        // locked / not-yet-approved accounts may not enter even with valid creds
        if (Auth::user()->status !== 'active') {
            $status = Auth::user()->status;
            Auth::logout();
            $this->registerFailure();

            throw ValidationException::withMessages([
                'form.password' => $status === 'pending'
                    ? 'ບັນຊີ ລໍ ການອະນຸມັດ — ຕິດຕໍ່ admin.'
                    : 'ບັນຊີ ຖືກລັອກ — ຕິດຕໍ່ admin.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->accountKey());
    }

    /** Validate only the fields the active flow uses. */
    protected function validateInput(): void
    {
        if ($this->loginBy === 'email') {
            $this->validate([
                'email' => 'required|string|max:256',
                'password' => 'required|string',
            ]);

            return;
        }

        $this->validate([
            'department_id' => 'required|integer',
            'username' => 'required|string|max:64',
            'password' => 'required|string',
        ], [
            'department_id.required' => 'ເລືອກ ພະແນກ ກ່ອນ.',
            'username.required' => 'ເລືອກ ຊື່ ຂອງ ທ່ານ.',
            'password.required' => 'ໃສ່ ລະຫັດຜ່ານ.',
        ]);
    }

    /** Find the account for the active flow. */
    protected function resolveUser(): ?User
    {
        if ($this->loginBy === 'email') {
            $id = Str::lower(trim($this->email));

            return User::where('email', $id)->orWhere('username', $id)->first();
        }

        // name flow — username is globally unique; the department must also match
        // so a person only ever signs in from their own department entry.
        return User::where('username', Str::lower(trim($this->username)))
            ->where('department_id', $this->department_id)
            ->first();
    }

    /** Bind to AD as the user, trying each known identity (UPN, sam@domain, sam). */
    protected function bindAgainstAd(LdapDirectory $ldap, User $user): bool
    {
        foreach ($ldap->bindIdentities($user) as $identity) {
            if ($ldap->attemptBind($identity, $this->password)) {
                return true;
            }
        }

        return false;
    }

    /** Count a failed attempt against both rate-limit buckets. */
    protected function registerFailure(): void
    {
        RateLimiter::hit($this->throttleKey());
        RateLimiter::hit($this->accountKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     * per-(identifier+IP) = 3 · per-account across ALL IPs = 10 (ກັນ distributed password-spray).
     */
    protected function ensureIsNotRateLimited(): void
    {
        foreach ([$this->throttleKey() => 3, $this->accountKey() => 10] as $key => $max) {
            if (! RateLimiter::tooManyAttempts($key, $max)) {
                continue;
            }

            event(new Lockout(request()));
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'form.password' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
    }

    /** The identifier the person signed in with, stable across both flows. */
    protected function identifier(): string
    {
        return $this->loginBy === 'email'
            ? Str::lower(trim($this->email))
            : Str::lower(trim($this->username)).'@d'.$this->department_id;
    }

    /** per-(identifier+IP) throttle key. */
    protected function throttleKey(): string
    {
        return Str::transliterate($this->identifier().'|'.request()->ip());
    }

    /** per-account throttle key (identifier only, across all IPs). */
    protected function accountKey(): string
    {
        return 'acct:'.Str::transliterate($this->identifier());
    }
}
