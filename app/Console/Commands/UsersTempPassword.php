<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * ຕັ້ງ ລະຫັດ ຊົ່ວຄາວ ຮ່ວມ + ບັງຄັບ ປ່ຽນ ຄັ້ງ ທຳອິດ ໃຫ້ ຜູ້ໃຊ້ AD (pre-created, auth_provider=domain).
 * super admin ບໍ່ ຖືກ ແຕະ. ໃຊ້ ຕອນ ເປີດ ໃຫ້ ພະນັກງານ ທົດລອງ ກ່ອນ AD/SSO ພ້ອມ.
 */
class UsersTempPassword extends Command
{
    protected $signature = 'users:temp-password {password : ລະຫັດ ຊົ່ວຄາວ (ບັງຄັບ ໃສ່ — ບໍ່ ມີ ຄ່າ ເລີ່ມຕົ້ນ, ≥8 ຕົວ)}
        {--all : ທຸກ ຄົນ ທີ່ ບໍ່ ແມ່ນ super admin (ບໍ່ ສະເພາະ provider=domain)}';

    protected $description = 'ຕັ້ງ ລະຫັດ ຊົ່ວຄາວ ຮ່ວມ + ບັງຄັບ ປ່ຽນ ຄັ້ງ ທຳອິດ ໃຫ້ ຜູ້ໃຊ້ ທີ່ ຍັງ ບໍ່ ມີ ລະຫັດ ໃຊ້ ໄດ້';

    public function handle(): int
    {
        $password = (string) $this->argument('password');
        // ບໍ່ ໃຊ້ default ຄ້າງ ໃນ repo · ບັງຄັບ ຄວາມ ຍາວ ຂັ້ນ ຕ່ຳ.
        if (mb_strlen($password) < 8) {
            $this->error('ລະຫັດ ຕ້ອງ ≥ 8 ຕົວ.');

            return self::FAILURE;
        }

        // mass update → bypass cast 'hashed', ເກັບ bcrypt hash ໂດຍ ກົງ.
        $count = User::query()
            ->where('is_super_admin', false)
            // never a super admin — exclude the role too, not only the flag
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->when(! $this->option('all'), fn ($q) => $q->where('auth_provider', 'domain'))
            ->update([
                'password' => bcrypt($password),
                'must_change_password' => true,
                'status' => 'active',
            ]);

        $this->info("ຕັ້ງ ລະຫັດ ຊົ່ວຄາວ ໃຫ້ {$count} ຜູ້ໃຊ້ · ລະຫັດ: {$password} · ບັງຄັບ ປ່ຽນ ຄັ້ງ ທຳອິດ.");

        return self::SUCCESS;
    }
}
