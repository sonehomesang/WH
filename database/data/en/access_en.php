<?php
// English for the Settings › Access & Auth Modes page (Phase A). source => en.
return [
    'ຕັດສິນ ວ່າ WH ກວດ login ແນວໃດ. Local password ເປັນ ຮາກຖານ ທີ່ ໃຊ້ ໄດ້ ສະເໝີ · AD ເປັນ overlay ທີ່ ເປີດ/ປິດ ໄດ້ ບ່ອນ ນີ້. ມີ ຜົນ ທັນທີ.'
        => 'Decide how WH checks a login. A local password is the foundation that always works · AD is an overlay you toggle here. Takes effect immediately.',
    'ທຸກ ຄົນ ໃຊ້ local password · ບໍ່ ຕິດຕໍ່ AD ເລີຍ' => 'Everyone uses a local password · no AD contact at all',
    'domain user ຕ້ອງ bind AD · ລະຫັດ ຜິດ ຫຼື AD ລົ້ມ = ເຂົ້າ ບໍ່ ໄດ້' => 'Domain users must bind AD · wrong password or AD down = no entry',
    'bind AD · DC ລົ້ມ → local (Phase B — ຍັງ ບໍ່ ເປີດ)' => 'bind AD · DC down → local (Phase B — not enabled yet)',
    'local super admin — login ໄດ້ ທຸກ mode, ບໍ່ ຂຶ້ນ ກັບ AD.' => 'Local super admins — can log in in every mode, independent of AD.',
    'ຄວນ ມີ ຢ່າງ ໜ້ອຍ 1 ບັນຊີ. ສ້າງ local super admin ຢູ່ ໜ້າ Users ກ່ອນ ເປີດ AD mode.'
        => 'Keep at least 1 account. Create a local super admin on the Users page before enabling an AD mode.',
    'ບໍ່ ມີ' => 'none',
    'ປຸ່ມ ນີ້ ເປັນ ບ່ອນ ດຽວ ທີ່ ຕິດຕໍ່ DC — ກົດ ເອງ ເທົ່ານັ້ນ, ບໍ່ ມີ probe ອັດຕະໂນມັດ.'
        => 'This button is the only thing that contacts the DC — manual click only, no automatic probe.',
    'ກຳລັງ ທົດສອບ…' => 'Testing…',
    'Sync ຫຼ້າສຸດ:' => 'Last sync:',
    'ຍັງ ບໍ່ ມີ local password' => 'have no local password yet',
    'ຕັ້ງ local password ໃຫ້ domain users ເພື່ອ ໃຫ້ login ໄດ້ ຕອນ ຢູ່ Local mode. ບັງຄັບ ປ່ຽນ ຕອນ login ຄັ້ງ ທຳອິດ. (ຕັ້ງ ເປັນ ຄົນ ໄດ້ ຢູ່ ໜ້າ Users ຜ່ານ set-password link.)'
        => 'Give domain users a local password so they can log in while in Local mode. Forced change on first login. (Set them one at a time on the Users page via a set-password link.)',
    'ອອກ ລະຫັດ ຄົນ ລະ ອັນ (ປອດໄພ ສຸດ) · ສະ ແດງ ຄັ້ງ ດຽວ ໃຫ້ ກ໋ອບ ໄປ ແຈກ.'
        => 'A unique password per person (most secure) · shown once to copy and hand out.',
    'ລະຫັດ ດຽວ ໃຫ້ ໝົດ (ແຈກ ງ່າຍ) · ບັງຄັບ ປ່ຽນ ຢູ່ ດີ.' => 'One password for everyone (easy to hand out) · still forced to change.',
    'ຕັ້ງ' => 'Set',
    'ຊື່' => 'Name',
    '⚠ ບໍ່ ມີ' => '⚠ none',
    'ປິດ / ແຈກ ແລ້ວ' => 'Close / handed out',
    // component flash messages
    '✓ ປ່ຽນ Auth Mode ແລ້ວ — ມີ ຜົນ ທັນທີ.' => '✓ Auth mode changed — effective immediately.',
    'ໂໝດ ນີ້ ຍັງ ບໍ່ ເປີດ ໃຫ້ ໃຊ້ (Phase B).' => 'This mode is not available yet (Phase B).',
    'ໃສ່ ລະຫັດ ຊົ່ວຄາວ ຮ່ວມ.' => 'Enter a shared temp password.',
    'ຢ່າງ ໜ້ອຍ 8 ຕົວ.' => 'At least 8 characters.',
];
