<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Reset a user's password from the CLI. The plaintext is typed at a HIDDEN
 * prompt, hashed one-way (bcrypt), and stored only as that hash — it is never
 * echoed, logged, written to a file, kept in shell history, or held in .env.
 * Use it to reset a super admin securely on any host.
 */
class AdminResetPassword extends Command
{
    protected $signature = 'admin:reset-password {identifier : email or username of the account}';

    protected $description = 'Securely reset a user password (hidden prompt, one-way hash, no plaintext kept anywhere)';

    public function handle(): int
    {
        $id = Str::lower(trim((string) $this->argument('identifier')));
        $user = User::where('email', $id)->orWhere('username', $id)->first();

        if (! $user) {
            $this->error("No account matches '{$id}' (tried email and username).");

            return self::FAILURE;
        }

        $this->line('Account: '.$user->display_name.' · '.($user->email ?: $user->username).($user->is_super_admin ? '  [SUPER ADMIN]' : ''));

        $password = (string) $this->secret('New password (hidden, min 10 chars)');
        if (mb_strlen($password) < 10) {
            $this->error('Password must be at least 10 characters.');

            return self::FAILURE;
        }
        if ($password !== (string) $this->secret('Confirm new password')) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $user->forceFill([
            'password' => Hash::make($password),   // one-way hash; plaintext discarded
            'must_change_password' => false,        // the person set it, they know it
            'local_password_set_at' => now(),
            'status' => 'active',
        ])->save();

        $this->info('✓ Password reset for '.$user->display_name.'. Stored as a one-way hash — no plaintext kept.');

        return self::SUCCESS;
    }
}
