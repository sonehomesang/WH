<?php
// Second English pass — dropdown placeholders, remaining form labels, and
// component toast/validation messages the first pass missed. source => en.
// (glob loads this before translations_en.php; none of these collide.)
return [
    // ── dropdown / select placeholders ──
    '— ເລືອກ —' => '— select —', '— ບໍ່ລະບຸ —' => '— unspecified —', '— ບໍ່ ມອບໝາຍ —' => '— unassigned —',
    '— ບໍ່ ໃຊ້ ແມ່ແບບ —' => '— no template —', '— ບໍ່ ກຳນົດ (ຄັ້ງ ດຽວ) —' => '— none (one-off) —',
    '— ເລືອກ ຮອບ —' => '— select cycle —', '— ເລືອກ ເຄື່ອງ ຈາກ ທະບຽນ —' => '— select equipment from register —',
    '— ເລືອກ ປະເພດ —' => '— select type —', '— ເລືອກ supplier —' => '— select supplier —',
    '(ປິດ)' => '(off)', '— ດຶງ ຈາກ ເຄື່ອງຝາກ ຖ້າ ມີ' => '— pull from deposit if available',
    // ── form labels ──
    'ຊື່ *' => 'Name *', 'ຊື່ງານ *' => 'Event name *', 'ວັນທີເລີ່ມ *' => 'Start date *',
    'ຊື່ບໍລິສັດ *' => 'Company name *', 'ລາຍລະອຽດ *' => 'Description *',
    // ── placeholders / helper text ──
    'ຕ້ອງ “ຮັບ ເຂົ້າ ສາງ ແລ້ວ” (accepted/stored) ກ່ອນ.' => 'Must be "accepted/stored" first.',
    'ເຊັ່ນ: ມໍເຕີ ໄໝ້ · ໃຊ້ ບໍ່ ໄດ້' => 'e.g. burnt-out motor · unusable',
    'ລາຍລະອຽດ ເພີ່ມ (optional)' => 'Additional details (optional)',
    'ເຊັ່ນ: ຮ່າງກາຍ ແຕກ, ບໍ່ ຕິດ ໄຟ…' => "e.g. cracked body, won't power on…",
    'ເຊັ່ນ: ເສຍ/ຊຳລຸດ ໃຊ້ ບໍ່ ໄດ້, ຂາຍ/ໂອນ ຕໍ່, ບັນທຶກ ຊ້ຳ…' => 'e.g. broken/unusable, sold/transferred, duplicate…',
    'ເຊັ່ນ: ບັນທຶກ ຊ້ຳ, ໃສ່ ຂໍ້ມູນ ຜິດ…' => 'e.g. duplicate record, wrong data…',
    'ໝາຍເຫດ/ອ້າງອີງ ການປະຕິບັດ (ຖ້າ ມີ)…' => 'Note/reference for the work (if any)…',
    // ── settings misc ──
    'ກູ້ ຄືນ ບໍ່ ໄດ້' => 'Not recoverable', '(ບໍ່ ມີ ແຖວ ໃຫ້ ລຶບ)' => '(no rows to clear)',
    '🗑 ລ້າງ ຂໍ້ມູນ ທີ່ ເລືອກ' => '🗑 Clear selected data', '🗑 ແມ່ນ' => '🗑 Yes',
    'ຮູບແບບ “ລາວ · English” — ຄໍລັ້ມ ຈະ ສະແດງ ສ່ວນ ລາວ.' => 'Format "Lao · English" — the column shows the Lao part.',
    'ແກ້ ບໍ່ ໄດ້ ພາຍຫຼັງ' => 'Cannot edit later',
    // ── component toasts / validation ──
    'ຕ້ອງ ເລືອກ ຫຼື ພິມ ສະຖານທີ່.' => 'Select or type a location.',
    'ຕ້ອງ ມີ ຢ່າງໜ້ອຍ 1 ຂໍ້ (ໃສ່ ຊື່ ຂໍ້).' => 'At least 1 item is required (enter an item name).',
    'ຕ້ອງ ໃສ່ ຈຳນວນ ຮັບຄືນ ຢ່າງ ໜ້ອຍ 1 ລາຍການ.' => 'Enter a return quantity for at least 1 item.',
    'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ລຶບ.' => 'Please enter a reason for deletion.',
    '(ຍ້າຍ ໄປ Deleted Log)' => '(moved to the Deleted Log)',
    '(ຍ້າຍ ໄປ Deleted Log · ກູ້ຄືນ ໄດ້)' => '(moved to the Deleted Log · recoverable)',
    'ລໍ approve' => 'Awaiting approval', '✓ ຕັ້ງ ສະຖານະ ໃໝ່ (admin):' => '✓ Set new status (admin):',
    'ເລືອກ ຢ່າງ ໜ້ອຍ 1 ສະຖານະ.' => 'Select at least 1 status.',
    'ແຫຼ່ງ ທີ່ ດຶງ ບໍ່ ພົບ ໃນ ທະບຽນ.' => 'The pulled source was not found in the register.',
    'ຈາກ:' => 'From:',
    'ລຶບ Location ບໍ່ໄດ້ — ຍັງມີ Building ຢູ່ພາຍໃນ.' => 'Cannot delete Location — it still contains Buildings.',
    'ລຶບ Building ບໍ່ໄດ້ — ຍັງມີ Room ຢູ່ພາຍໃນ.' => 'Cannot delete Building — it still contains Rooms.',
    'ລຶບ type ບໍ່ໄດ້ — ມີ building ໃຊ້ຢູ່.' => 'Cannot delete type — buildings still use it.',
    'ລຶບ Unit ບໍ່ໄດ້ — ຍັງມີ Department ຢູ່ພາຍໃນ.' => 'Cannot delete Unit — it still contains Departments.',
    '(ບໍ່ມີ)' => '(none)', 'ສັນຍາ' => 'Contract',
    'ກະລຸນາ ເລືອກ ຄວາມຖີ່ ໃນການຕິດຕໍ່.' => 'Please select a contact frequency.',
    'ກະລຸນາ ໃຫ້ຄະແນນ ຢ່າງໜ້ອຍ 1 ຂໍ້.' => 'Please rate at least 1 item.',
];
