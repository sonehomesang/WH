<?php

namespace Database\Seeders;

use App\Models\Translation;
use Illuminate\Database\Seeder;

/**
 * A small, SAFE English starter set for the wording catalogue (target_en).
 *
 * We only seed sources that are DISTINCTIVE — icon-prefixed button labels and
 * whole unambiguous phrases — never bare words. The ReplaceTerms engine does a
 * substring swap (longest-first), so translating a bare word like "ລຶບ" would
 * corrupt any longer phrase that contains it; the emoji/prefix makes these
 * sources match only their own button. Everything else is filled by a human via
 * Settings › Translations (the English column). Idempotent: only existing
 * catalogue rows are touched, and re-running just re-sets the same values.
 */
class EnglishStarterSeeder extends Seeder
{
    public function run(): void
    {
        // source (exact, as extracted) => English
        $pairs = [
            '💾 ບັນທຶກ' => '💾 Save',
            '✏️ ແກ້ໄຂ' => '✏️ Edit',
            '✓ ຮັບຮອງ' => '✓ Endorse',
            '✗ ຕີ ກັບ' => '✗ Reject',
            '↩ ກູ້ຄືນ' => '↩ Restore',
            '👁 ພຣີວິວ' => '👁 Preview',
            '+ ຈຳໜ່າຍ' => '+ Dispose',
            '🔄 ດຶງ ຄຳ ໃໝ່' => '🔄 Pull new words',
            '🗑 ລຶບ DA' => '🗑 Delete DA',
        ];

        $updated = 0;
        foreach ($pairs as $source => $en) {
            $updated += Translation::where('type', 'replace')
                ->where('source', $source)
                ->update(['target_en' => $en]);
        }

        $this->command?->info("EnglishStarterSeeder: set target_en on {$updated} catalogue row(s).");
    }
}
