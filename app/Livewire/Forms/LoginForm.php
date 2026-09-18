<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Services\LdapDirectory;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    // Login identifier — a username OR an email (staff without email sign in by
    // username). Kept as `$email` so the blade binding and error keys are stable.
    #[Validate('required|string|max:256')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $ldap = app(LdapDirectory::class);
        // Resolve by email OR username (case-insensitive) — one identifier field.
        $id = Str::lower(trim($this->email));
        $user = User::where('email', $id)->orWhere('username', $id)->first();

        if ($ldap->loginEnabled() && $user && $user->auth_provider === 'domain') {
            // Domain account → verify the typed password against AD by binding as
            // the person. WH never stores the AD password. If AD is down, or the
            // account was disabled/removed in AD, the bind simply fails — we never
            // fall back to a stale local hash.
            if (! $this->bindAgainstAd($ldap, $user)) {
                $this->registerFailure();

                throw ValidationException::withMessages([
                    'form.email' => trans('auth.failed'),
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
            // local accounts (break-glass admin, username users, domain users in
            // local_only mode) authenticate by their resolved id + local password
            $this->registerFailure();

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        // locked / not-yet-approved accounts may not enter even with valid creds
        if (Auth::user()->status !== 'active') {
            $status = Auth::user()->status;
            Auth::logout();
            $this->registerFailure();

            throw ValidationException::withMessages([
                'form.email' => $status === 'pending'
                    ? 'ບັນຊີ ລໍ ການອະນຸມັດ — ຕິດຕໍ່ admin.'
                    : 'ບັນຊີ ຖືກລັອກ — ຕິດຕໍ່ admin.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->accountKey());
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
     * per-(email+IP) = 3 · per-account across ALL IPs = 10 (ກັນ distributed password-spray).
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
                'form.email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
    }

    /** per-(email+IP) throttle key. */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    /** per-account throttle key (email only, across all IPs). */
    protected function accountKey(): string
    {
        return 'acct:'.Str::transliterate(Str::lower($this->email));
    }
}
