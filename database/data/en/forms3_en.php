<?php
// Third English pass — admin/help-text fragments that render as their own
// text node between <b>/<code> tags (LDAP, notifications, settings, deposit/
// disposal/equipment help). Keyed by the EXACT rendered node bytes so the
// whole-node exact matcher hits them with NO blade structure change. English
// follows the Lao clause order around the inline tags (fine for config hints).
// Skipped (not catalogue-fixable without restructuring): directive-glued
// labels welded to a {{ value }} (deposit show "ຝາກ:" / "ເອົາຄືນ:") and the
// maintenance preview hint welded to {{ count }}.
return [
    // ── settings/ldap.blade.php ──
    'ດຶງລາຍຊື່ຜູ້ໃຊ້ ຈາກ AD ຂອງ ອົງກອນ → ສ້າງບັນຊີໄວ້ລ່ວງໜ້າ (Sync only · login ຄືເກົ່າ).' => 'Pull the user list from the organisation AD → pre-create accounts (Sync only · login unchanged).',
    '(ທາງເລືອກ)' => '(optional)',
    '— ໃສ່ ສະເພາະ ຕອນ ຈະ' => '— enter it only when about to',
    'ເທົ່ານັ້ນ. WH' => 'only. WH',
    '— ພິມ ໃໝ່ ທຸກ ຄັ້ງ ທີ່ ເປີດ ໜ້ານີ້ (login ຂອງ ຜູ້ໃຊ້ ບໍ່ ຕ້ອງ ໃຊ້ ບັນຊີ ນີ້).' => '— re-enter it every time you open this page (user login does not use this account).',
    'ເປີດເມື່ອ DC ໃຊ້' => 'Turn on when the DC uses',
    ') ຫຼື ຕໍ່ດ້ວຍ' => ') or connecting via',
    '. ການເຊື່ອມຕໍ່' => '. The connection',
    '— ພຽງບໍ່ກວດ CA/ຄວາມແຮງກະແຈ.' => '— it just skips the CA / key-strength check.',
    'ເມື່ອເປີດ: ບັນຊີ' => 'When on: accounts',
    '(import ຈາກ AD) ຈະ login ດ້ວຍ' => '(imported from AD) log in with',
    'ລະຫັດ AD. ບັນຊີ' => 'AD password. Accounts',
    '(ເຊັ່ນ admin) ຍັງໃຊ້ລະຫັດ WH ເປັນ' => '(e.g. admin) still use the WH password as',
    'ຍັງບໍ່ໄດ້ດຶງ — ກົດ "Preview from AD" (ຕ້ອງ run ຢູ່ server ທີ່ເຫັນ DC).' => 'Not pulled yet — click "Preview from AD" (must run on a server that can see the DC).',
    'ໂຕ. ໃຊ້ຕອນຢາກຍົກເລີກ —' => 'accounts. Use it to cancel —',
    '— ບໍ່ສະແດງຄືນ.' => '— never shown again.',
    'ບັນຊີ sync =' => 'Sync accounts =',
    '— ບໍ່ເກັບ password ຂອງ user.' => '— user passwords are not stored.',
    '(default): user ຊ້ຳ =' => '(default): a duplicate user =',
    '(matched → review). ເປີດ "Link existing" ເອງ ຕອນໝັ້ນໃຈ.' => '(matched → review). Turn on "Link existing" yourself when confident.',
    // ── settings/notifications.blade.php ──
    '— ປິດ = ບໍ່ ສ້າງ notification ໃໝ່ ທັງໝົດ' => '— off = create no new notifications at all',
    '. (WhatsApp ຈະ ຕາມ ມາ ພາຍຫຼັງ)' => '. (WhatsApp will follow later)',
    'ຕໍ່ ໂມດູລ (ຈັດ ກຸ່ມ ຕາມ ເມນູ) — ຕິກ ເປີດ + ໃສ່ webhook ຂອງ channel ນັ້ນ (ວ່າງ = ໃຊ້ default)' => 'Per module (grouped by menu) — tick on + enter that channel\'s webhook (blank = use default)',
    // ── settings/request-types.blade.php ──
    'ທີ່ ໃຊ້ ໃນ ຟອມ ຂໍ ເຄື່ອງ (New Material Request). ເພີ່ມ / ແກ້ໄຂ / ເປີດ-ປິດ ໄດ້ ຕາມ ຫຼັງ ໂດຍ ບໍ່ ຕ້ອງ ແຕະ ໂຄ໋ດ.' => 'used in the material request form (New Material Request). Add / edit / enable-disable later without touching code.',
    'ຕົວອັກສອນ/ຕົວເລກ/_ ·' => 'letters/numbers/_ ·',
    // ── settings/suppliers.blade.php ──
    '· ວ່າງ = global' => '· blank = global',
    // ── settings/translations.blade.php ──
    '= ຄ່າ ສຳລັບ' => '= the value for',
    // ── settings/condition-statuses.blade.php ──
    'ທີ່ ໃຊ້ ຮ່ວມ ໃນ Inventory · Equipment · Deposit ແລະ ການ ດຶງ ໄປ Disposal. ຕິກ' => 'shared across Inventory · Equipment · Deposit and pulling to Disposal. Tick',
    'a-z, 0-9, _ ເທົ່ານັ້ນ ·' => 'a-z, 0-9, _ only ·',
    // ── settings/email.blade.php ──
    '· ຕັ້ງ ໄວ້ ແລ້ວ' => '· already set',
    // ── settings/backup.blade.php ──
    '— super_admin ເທົ່ານັ້ນ.' => '— super admin only.',
    // ── settings/clear-test-data.blade.php ──
    '— ໃຊ້ ລ້າງ ຂໍ້ມູນ ທົດສອບ ກ່ອນ Go-Live ເພື່ອ ບໍ່ ໃຫ້ ປະປົນ ກັບ ຂໍ້ມູນ ຈິງ. ຂໍ້ມູນ ທີ່ ລຶບ' => '— use it to clear test data before Go-Live so it does not mix with real data. Deleted data',
    '. ຜູ້ໃຊ້ · ບົດບາດ · ການຕັ້ງຄ່າ · ໜ່ວຍງານ · templates · ສະຖານະພາບ —' => '. Users · roles · settings · units · templates · conditions —',
    'ຂໍ້ມູນ ຫຼັກ (Master) — ລ້າງ ສະເພາະ ຖ້າ ເປັນ ຂໍ້ມູນ ທົດສອບ ເທົ່ານັ້ນ' => 'Master data — clear only if it is test data',
    // ── settings/system.blade.php ──
    'ຫ້ອງ ເສີມ ໃນ ຟອມ ຝາກ (ຂັ້ນ 1 · ໜ້າງານ). ປິດ = ເຊື່ອງ. ຊື່ · ລະຫັດ · ຮູບ · ຈຳນວນ · ບ່ອນຈັດເກັບ · ສະຖານະ = ສະແດງ ຕະຫຼອດ.' => 'Extra fields on the deposit form (stage 1 · on-site). Off = hidden. Name · code · photo · quantity · storage · status = always shown.',
    // ── disposal/create.blade.php ──
    'ດຶງ ໄດ້ ເລີຍ ຖ້າ ສະຖານະພາບ ກົງ ·' => 'can be pulled directly if the condition matches ·',
    // ── deposit/create.blade.php ──
    '/ Depositor · ບໍ່ ບັງຄັບ' => '/ Depositor · optional',
    'ໜ້າງານ · ໄວ: ຊື່ · ລະຫັດ · ຖ່າຍ ຮູບ · ຈຳນວນ · ສະຖານະ → ບັນທຶກ ຮ່າງ' => 'On-site · quick: name · code · photo · quantity · status → save draft',
    'ບັນທຶກ ແລ້ວ → ຟອມ ໃໝ່ ຂຶ້ນ ໃຫ້ ເພີ່ມ ລາຍການ ຕໍ່ · ຫົວໜ້າ ຕື່ມ ຂໍ້ມູນ + ສົ່ງ ໃນ ໜ້າ ແກ້ໄຂ' => 'Saved → a new form opens to add the next item · the lead fills in details + submits on the edit page',
    // ── deposit/show.blade.php ──
    'ໜ້າງານ ບັນທຶກ ລາຍການ + ຮູບ ແລ້ວ. ກະລຸນາ ຕື່ມ: ປະເພດ · ແຫຼ່ງທີ່ມາ · ເຈົ້າຂອງ · ພະແນກ · ໄລຍະ · ເຫດຜົນ → ແລ້ວ ກົດ ສົ່ງ.' => 'On-site has recorded the items + photos. Please add: type · source · owner · department · period · reason → then press submit.',
    'ສຳລັບ ແກ້ ຄວາມ ຜິດ / ທົດສອບ ເທົ່ານັ້ນ. ຕັ້ງ ສະຖານະ ໃບ ໃໝ່ ໂດຍ ກົງ (ບໍ່ ຜ່ານ ໂຟລ).' => 'For fixing mistakes / testing only. Set the record\'s new status directly (bypassing the flow).',
    'ແຍກ 3 ມູມ · ຮູບ ເກົ່າ ລຶບ ໄດ້ (×) · ເພີ່ມ ໃໝ່ ຈາກ ກ້ອງ/ຄັງ (ຫຍໍ້ ອັດຕະໂນມັດ)' => '3 angles · delete old photos (×) · add new from camera/gallery (auto-resized)',
    '🟢 ຂອບ ຂຽວ = ບັນທຶກ ແລ້ວ · 🔵 ຂອບ ຟ້າ = ຮູບ ໃໝ່ ຈະ ບັນທຶກ ຕອນ ກົດ “ບັນທຶກ”' => '🟢 green border = saved · 🔵 blue border = new photos save when you press "Save"',
    '· ໃຊ້ ໃນ ບັນຊີ letterhead' => '· used on the letterhead report',
    // ── equipment/maintenance-templates.blade.php ──
    '=ກວດ ຫຼື' => '=inspect or',
    '=ກວດ ·' => '=inspect ·',
    '=ປ່ຽນ · —=ບໍ່ ເຮັດ' => '=replace · —=skip',
    // ── equipment/index.blade.php (both @unless render branches) ──
    'ຍັງ ບໍ່ ມີ ແມ່ແບບ ໃດ' => 'No templates yet',
    'ຍັງ ບໍ່ ມີ ແມ່ແບບ ໃດ — ສ້າງ ແມ່ແບບ' => 'No templates yet — create a template',
];
