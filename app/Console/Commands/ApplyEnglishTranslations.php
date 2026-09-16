<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;

/**
 * Applies English translations for the wording catalogue from data files in
 * database/data/en/*.php (each returns [translation_id => 'English']) and
 * reports coverage. English mode matches whole text nodes exactly (see
 * Translation::applyReplacements), so a partial catalogue never corrupts a
 * longer phrase — no substring scan is needed.
 */
class ApplyEnglishTranslations extends Command
{
    protected $signature = 'translations:apply-en {--report-only : Only report coverage, do not write}';

    protected $description = 'Apply target_en from database/data/en/*.php and report English coverage';

    public function handle(): int
    {
        if (! $this->option('report-only')) {
            $applied = 0;
            foreach (glob(database_path('data/en/*.php')) as $file) {
                $map = require $file;
                if (! is_array($map)) {
                    continue;
                }
                // Keyed by SOURCE (the Lao string) — stable across environments,
                // unlike row ids which the extractor assigns per-database.
                foreach ($map as $source => $en) {
                    $en = trim((string) $en);
                    if ($en === '' || $source === '') {
                        continue;
                    }
                    $applied += Translation::where('type', 'replace')->where('source', $source)
                        ->update(['target_en' => $en]);
                }
            }
            Translation::flushCache();   // bulk update() skips model events → bust caches by hand
            $this->info("Applied English to {$applied} row(s) from ".count(glob(database_path('data/en/*.php'))).' file(s).');
        }

        // ── Coverage report ──
        // English uses WHOLE-NODE exact matching (see Translation::applyReplacements),
        // so a partial catalogue is corruption-free by construction — no substring
        // scan needed. This just reports how much of the catalogue has English.
        $total = Translation::where('type', 'replace')->count();
        $done = Translation::where('type', 'replace')->whereNotNull('target_en')->where('target_en', '!=', '')->count();
        $pct = $total ? round($done / $total * 100, 1) : 0;

        $this->line("English coverage: {$done} / {$total} rows ({$pct}%).");

        return self::SUCCESS;
    }
}
