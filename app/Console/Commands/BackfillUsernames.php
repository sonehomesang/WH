<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Give every account a `username` = the local-part of its email, so people can
 * sign in with Department + Name. Safe to re-run: it only fills accounts that
 * have no username yet, and it SKIPS any local-part already taken by another
 * live account (those are duplicates to resolve first — see the report).
 */
class BackfillUsernames extends Command
{
    protected $signature = 'users:backfill-usernames {--dry-run : ສະ ແດງ ຜົນ ໂດຍ ບໍ່ ບັນທຶກ}';

    protected $description = 'Set username = email local-part for accounts with none (skips collisions, idempotent)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $done = 0;
        $noEmail = 0;
        $conflicts = [];

        $targets = User::query()->where(fn ($q) => $q->whereNull('username')->orWhere('username', ''))->get();

        foreach ($targets as $u) {
            if (! $u->email || ! str_contains($u->email, '@')) {
                $noEmail++;

                continue;
            }
            $lp = Str::lower(Str::before($u->email, '@'));

            if (User::where('username', $lp)->where('id', '!=', $u->id)->exists()) {
                $conflicts[] = "#{$u->id} {$u->email} → '{$lp}'";

                continue;
            }

            if (! $dry) {
                $u->forceFill(['username' => $lp])->save();
            }
            $done++;
        }

        $this->info(($dry ? '[dry-run] would set' : 'set')." username on {$done} account(s).");
        if ($noEmail) {
            $this->warn("{$noEmail} account(s) skipped — no email to derive a name from.");
        }
        if ($conflicts) {
            $this->warn(count($conflicts).' account(s) skipped — the name is already taken (resolve the duplicate first):');
            foreach ($conflicts as $c) {
                $this->line("  {$c}");
            }
        }

        return self::SUCCESS;
    }
}
