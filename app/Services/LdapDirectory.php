<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LdapRecord\Connection;

/**
 * Active Directory (LDAP) directory sync — SYNC ONLY.
 *
 * Reads connection settings from Setting::get('ldap') and pulls user accounts
 * from AD to pre-create local accounts (auth_provider=domain, is_pre_created=true,
 * status=pending). It never stores the synced users' passwords; domain-password
 * login (SSO) is a separate later phase.
 *
 * fetchUsers()/testConnection() bind to the DC and only work where the server can
 * reach it (the domain-joined server). syncRows() is pure DB logic and is unit-tested
 * locally with fabricated rows (no LDAP needed).
 */
class LdapDirectory
{
    /** Raw stored settings (bind password still encrypted). */
    public function settings(): array
    {
        return Setting::get('ldap', []);
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
    }

    /**
     * Build the LdapRecord connection config.
     *
     * The bind (authorize) account is supplied per-operation and is NOT kept at
     * rest — pass it in for a sync/test. Any legacy stored value is used only as a
     * fallback (older installs); after ldap:forget-bind / a save there is none, and
     * login never needs it (attemptBind overrides username/password with the user's).
     */
    public function config(?string $bindUsername = null, ?string $bindPassword = null): array
    {
        $s = $this->settings();
        $enc = $s['encryption'] ?? 'ssl';          // ssl (636) · tls (389 StartTLS) · none (389)
        $username = $bindUsername ?? ($s['bind_username'] ?? '');
        $password = $bindPassword ?? (! empty($s['password']) ? Crypt::decryptString($s['password']) : '');

        return [
            'hosts' => array_filter([$s['host'] ?? '']),
            'port' => (int) ($s['port'] ?? ($enc === 'ssl' ? 636 : 389)),
            'base_dn' => $s['base_dn'] ?? '',
            'username' => $username,
            'password' => $password,
            // LdapRecord v4: use_tls = LDAPS (ldaps://), use_starttls = StartTLS. No use_ssl.
            'use_tls' => $enc === 'ssl',
            'use_starttls' => $enc === 'tls',
            'timeout' => 8,
            // AD referrals break subtree searches; keep them off (this is also the default).
            'follow_referrals' => false,
        ];
    }

    // ── Auth Mode ────────────────────────────────────────────────────────
    // The single super-admin-controlled switch that decides how WH checks a
    // login. Local password is always the foundation (break-glass + local
    // accounts work in every mode); AD is an overlay toggled through here.
    public const MODE_LOCAL = 'local_only';       // ignore AD — everyone local

    public const MODE_AD_STRICT = 'ad_strict';    // domain users must bind AD, no fallback

    public const MODE_AD_FALLBACK = 'ad_fallback'; // bind AD; DC unreachable → local (Phase B)

    public const MODES = [self::MODE_LOCAL, self::MODE_AD_STRICT, self::MODE_AD_FALLBACK];

    /**
     * Active auth mode. Reads Setting('auth').mode; if unset (legacy installs),
     * derives it from the old ldap.enabled + login_with_ad pair so behaviour is
     * unchanged until an admin picks a mode on Settings › Access.
     */
    public function mode(): string
    {
        $m = Setting::get('auth')['mode'] ?? null;
        if (in_array($m, self::MODES, true)) {
            return $m;
        }

        if ($this->isEnabled() && (bool) ($this->settings()['login_with_ad'] ?? false)) {
            return self::MODE_AD_STRICT;
        }

        return self::MODE_LOCAL;
    }

    /** Is signing in with the AD password switched on? (ad_strict or ad_fallback) */
    public function loginEnabled(): bool
    {
        return in_array($this->mode(), [self::MODE_AD_STRICT, self::MODE_AD_FALLBACK], true);
    }

    /**
     * Verify a person's own AD password by binding to the directory as them.
     *
     * An empty password MUST be rejected before it reaches the server: LDAP reads a
     * bind carrying no password as an *unauthenticated* bind, and directories
     * commonly answer success — which would let anyone through.
     */
    public function attemptBind(string $identity, string $password): bool
    {
        if (trim($identity) === '' || trim($password) === '') {
            return false;
        }

        $this->applyTlsPolicy();
        $config = $this->config();
        $config['username'] = $identity;
        $config['password'] = $password;

        try {
            (new Connection($config))->connect();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Identities to try when binding as a user, best first: the UPN/mail we hold,
     * then samAccountName@<domain derived from the base DN>, then the bare name.
     *
     * @return array<int,string>
     */
    public function bindIdentities(User $user): array
    {
        $ids = [];

        if ($user->email && str_contains($user->email, '@')) {
            $ids[] = $user->email;
        }

        if ($user->username) {
            preg_match_all('/DC=([^,]+)/i', (string) ($this->settings()['base_dn'] ?? ''), $m);
            if (! empty($m[1])) {
                $ids[] = $user->username.'@'.implode('.', $m[1]);
            }
            $ids[] = $user->username;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Email domain for a fabricated address (an AD row with no mail), derived from the
     * configured base DN (DC=a,DC=b → a.b) so no site-specific domain is hard-coded in
     * source. Falls back to a neutral placeholder when the base DN is unset.
     */
    private function emailDomain(): string
    {
        preg_match_all('/DC=([^,]+)/i', (string) ($this->settings()['base_dn'] ?? ''), $m);

        return ! empty($m[1]) ? Str::lower(implode('.', $m[1])) : 'ad.local';
    }

    /**
     * Relax TLS certificate checking for an internal CA.
     *
     * AD domain controllers usually present a certificate issued by an internal CA
     * with an old/weak key, which OpenSSL then rejects
     * ("EE certificate key too weak"). This must be set on the GLOBAL (null) handle
     * before connecting — per-connection options are ignored for ldaps://.
     * The channel stays encrypted; only chain/strength verification is skipped.
     */
    private function applyTlsPolicy(): void
    {
        if (($this->settings()['tls_skip_verify'] ?? false) && function_exists('ldap_set_option')) {
            @ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
        }
    }

    /**
     * Search base for user queries: the Users OU when one is actually set,
     * otherwise the base DN. An empty/blank OU must fall back — `??` would not,
     * since a blank string is not null, and searching a non-existent OU silently
     * returns zero users.
     */
    public function searchBase(): string
    {
        $s = $this->settings();
        $ou = trim((string) ($s['user_ou'] ?? ''));

        return $ou !== '' ? $ou : trim((string) ($s['base_dn'] ?? ''));
    }

    /**
     * Bind to the DC and count users under the search base.
     *
     * @return array{ok:bool,message:string,count:int}
     */
    public function testConnection(?string $bindUsername = null, ?string $bindPassword = null): array
    {
        try {
            $this->applyTlsPolicy();
            $conn = new Connection($this->config($bindUsername, $bindPassword));
            $conn->connect();                       // binds with the service account

            // LdapRecord v4 query builders return a plain array (the LDAP 'count'
            // key is already stripped by the builder) — not a Collection.
            $results = $conn->query()->in($this->searchBase())
                ->where('objectclass', '=', 'user')
                ->where('objectcategory', '=', 'person')
                ->limit(1000)
                ->get();

            $count = is_array($results) ? count($results) : 0;

            return ['ok' => true, 'message' => "bind OK · ພົບ {$count} users", 'count' => $count];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'count' => 0];
        }
    }

    /**
     * Pull normalized user rows from AD.
     *
     * @return array<int,array{guid:?string,username:?string,email:?string,display_name:?string,phone:?string,department:?string,enabled:bool}>
     */
    public function fetchUsers(bool $enabledOnly = true, ?string $bindUsername = null, ?string $bindPassword = null): array
    {
        $this->applyTlsPolicy();
        $conn = new Connection($this->config($bindUsername, $bindPassword));
        $conn->connect();

        $records = $conn->query()->in($this->searchBase())
            ->where('objectclass', '=', 'user')
            ->where('objectcategory', '=', 'person')
            ->select(['samaccountname', 'mail', 'userprincipalname', 'displayname', 'cn', 'telephonenumber', 'department', 'useraccountcontrol', 'objectguid'])
            ->paginate(1000);

        $rows = [];
        foreach ($records as $r) {
            $uac = (int) ($this->first($r, 'useraccountcontrol') ?? 0);
            $enabled = ($uac & 2) === 0;            // 0x2 = ACCOUNTDISABLE
            if ($enabledOnly && ! $enabled) {
                continue;
            }
            $rows[] = [
                'guid' => $this->guid($r),
                'username' => $this->first($r, 'samaccountname'),
                'email' => $this->first($r, 'mail') ?: $this->first($r, 'userprincipalname'),
                'display_name' => $this->first($r, 'displayname') ?: $this->first($r, 'cn'),
                'phone' => $this->first($r, 'telephonenumber'),
                'department' => $this->first($r, 'department'),
                'enabled' => $enabled,
            ];
        }

        return $rows;
    }

    /** Fetch + sync in one call (used by the artisan command). Bind account passed in — never stored. */
    public function sync(bool $enabledOnly = true, ?string $bindUsername = null, ?string $bindPassword = null): array
    {
        return $this->syncRows($this->fetchUsers($enabledOnly, $bindUsername, $bindPassword));
    }

    /**
     * Remove the bind (authorize) account + password from stored settings.
     *
     * WH must never keep the AD service-account secret at rest — it is entered per
     * sync. Non-secret connection config (host/port/base_dn/OU/tls/login flag) stays.
     *
     * @return array{bind_username:bool,password:bool} what was present before clearing
     */
    public function forgetBindCredentials(): array
    {
        $s = $this->settings();
        $had = [
            'bind_username' => ! empty($s['bind_username']),
            'password' => ! empty($s['password']),
        ];
        unset($s['bind_username'], $s['password']);
        Setting::put('ldap', $s, optional(auth()->user())->id);
        Cache::forget('settings.ldap');

        return $had;
    }

    /**
     * Upsert normalized rows into the users table. PURE — no LDAP.
     * Match order: ad_guid → username → email.
     *
     * @param  array<int,array>  $rows
     * @param  array<int,string>|null  $onlyGuids  restrict to these AD guids (selection); null = all
     * @param  bool  $linkExisting  false (default, "keep separate") = never touch an existing
     *                              account; a match is reported as 'matched' and skipped. true =
     *                              backfill link fields onto the existing account.
     * @return array{created:int,updated:int,unchanged:int,matched:int,skipped:int}
     */
    public function syncRows(array $rows, ?array $onlyGuids = null, bool $linkExisting = false): array
    {
        $sum = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'matched' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            $guid = $row['guid'] ?? null;
            if ($onlyGuids !== null && (! $guid || ! in_array($guid, $onlyGuids, true))) {
                continue;
            }

            $username = $this->clean($row['username'] ?? null);
            $email = Str::lower($this->clean($row['email'] ?? null) ?? '');
            $name = $this->clean($row['display_name'] ?? null) ?: ($username ?: $email);

            // No stable identifier at all → cannot import safely.
            if (! $username && ! $email && ! $guid) {
                $sum['skipped']++;

                continue;
            }
            // email is required + unique in schema — fabricate a domain one if AD lacks it.
            if (! $email) {
                $email = Str::lower(($username ?: 'user').'@'.$this->emailDomain());
            }

            $user = ($guid ? User::withTrashed()->where('ad_guid', $guid)->first() : null)
                ?? ($username ? User::withTrashed()->where('username', $username)->first() : null)
                ?? User::withTrashed()->where('email', $email)->first();

            if (! $user) {
                User::create([
                    'ad_guid' => $this->fit($guid, 64),
                    'username' => $this->fit($username, 64),
                    'email' => $this->fit($email, 256),
                    'display_name' => $this->fit($name, 256),
                    'phone_number' => $this->phone($row['phone'] ?? null),
                    'password' => Str::random(40),        // unusable — domain login is a later phase
                    'auth_provider' => 'domain',
                    'is_pre_created' => true,
                    'status' => 'pending',
                    // stamp so the kill switch can identify exactly what IT created
                    'ldap_imported_at' => now(),
                ]);
                $sum['created']++;

                continue;
            }

            // "Keep separate" (default): an AD row matching a pre-existing REAL account is
            // left completely untouched and reported for manual review. Accounts WE created
            // by a prior import (domain + pre_created) are always refreshed so re-sync stays
            // idempotent. The admin flips linkExisting on to also backfill real accounts.
            $isOwnImport = $user->auth_provider === 'domain' && $user->is_pre_created;
            if (! $linkExisting && ! $isOwnImport) {
                $sum['matched']++;

                continue;
            }

            // Existing account — backfill link fields without downgrading the person.
            $dirty = [];
            if ($guid && $user->ad_guid !== ($g = $this->fit($guid, 64))) {
                $dirty['ad_guid'] = $g;
            }
            if ($username && $user->username !== ($u = $this->fit($username, 64)) && ! $this->usernameTaken($username, $user->id)) {
                $dirty['username'] = $u;
            }
            if ($name && $user->display_name !== ($n = $this->fit($name, 256))) {
                $dirty['display_name'] = $n;
            }
            if (($p = $this->phone($row['phone'] ?? null)) && $user->phone_number !== $p) {
                $dirty['phone_number'] = $p;
            }

            if ($dirty) {
                $user->forceFill($dirty)->save();
                $sum['updated']++;
            } else {
                $sum['unchanged']++;
            }
        }

        Setting::put('ldap_last_sync', [
            'at' => now()->toDateTimeString(),
            'by' => optional(auth()->user())->only(['id', 'display_name']),
            'summary' => $sum,
        ], auth()->id());

        Log::info('LDAP sync', $sum);

        return $sum;
    }

    /**
     * Kill-switch scope: ONLY accounts this importer created, identified by the
     * ldap_imported_at stamp.
     *
     * It must NOT key off auth_provider/is_pre_created — this app already
     * pre-creates real staff as domain + pre_created, and scoping on those once
     * permanently deleted genuine staff accounts during a rollback.
     */
    public function importedQuery(): Builder
    {
        return User::query()->whereNotNull('ldap_imported_at');
    }

    public function importedCount(): int
    {
        return $this->importedQuery()->count();
    }

    /**
     * Undo an import. $delete=false → lock (disable) every imported account; true → soft-delete.
     * Only ever affects pre-created domain accounts — never real/linked users.
     *
     * @return int number affected
     */
    public function rollbackImported(bool $delete = false): int
    {
        $ids = $this->importedQuery()->pluck('id');
        if ($ids->isEmpty()) {
            return 0;
        }
        $delete
            ? User::whereIn('id', $ids)->delete()                       // soft-delete
            : User::whereIn('id', $ids)->update(['status' => 'locked']);

        Log::info('LDAP rollback', ['action' => $delete ? 'remove' : 'disable', 'count' => $ids->count()]);

        return $ids->count();
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function usernameTaken(string $username, int $exceptId): bool
    {
        return User::withTrashed()->where('username', $username)->where('id', '!=', $exceptId)->exists();
    }

    private function clean(?string $v): ?string
    {
        $v = is_string($v) ? trim($v) : null;

        return $v === '' ? null : $v;
    }

    /** First value of an LdapRecord attribute (arrays) or a plain string. */
    private function first(array $entry, string $key): ?string
    {
        $v = $entry[$key] ?? null;
        if (is_array($v)) {
            $v = $v[0] ?? null;
        }

        return is_string($v) ? $v : null;
    }

    /** Convert AD binary objectGUID to a GUID string. */
    private function guid(array $entry): ?string
    {
        $raw = $entry['objectguid'][0] ?? ($entry['objectguid'] ?? null);
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        // Already a canonical GUID string? Must match 8-4-4-4-12 hex — a loose
        // "contains a dash" test is wrong, because the 16 raw bytes AD returns can
        // themselves contain the 0x2D ('-') byte, which then stored binary garbage.
        if (preg_match('/^\{?[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\}?$/i', $raw)) {
            return Str::lower(trim($raw, '{}'));
        }
        if (strlen($raw) !== 16) {
            return null;
        }
        $h = bin2hex($raw);
        $g = substr($h, 6, 2).substr($h, 4, 2).substr($h, 2, 2).substr($h, 0, 2).'-'
            .substr($h, 10, 2).substr($h, 8, 2).'-'
            .substr($h, 14, 2).substr($h, 12, 2).'-'
            .substr($h, 16, 4).'-'.substr($h, 20, 12);

        return Str::lower($g);
    }

    /**
     * Trim a value to what the column can hold. AD fields are free-text and can be
     * far longer than our columns (e.g. telephoneNumber holding two numbers), which
     * would otherwise abort the whole import with SQLSTATE 22001.
     */
    private function fit(?string $v, int $max): ?string
    {
        $v = $this->clean($v);

        return $v === null ? null : mb_substr($v, 0, $max);
    }

    /** First phone number only — AD often stores several in one value. */
    private function phone(?string $v): ?string
    {
        $v = $this->clean($v);
        if ($v === null) {
            return null;
        }

        return $this->fit(trim(explode(',', $v)[0]), 32);
    }
}
