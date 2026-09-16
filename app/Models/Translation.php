<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable wording overrides (Phase 6.11).
 *
 * - type=replace: ຄຳຜິດ → ຄຳຖືກ, ໃຊ້ໂດຍ ReplaceTerms middleware (ທົ່ວແອັບ)
 * - type=term:    key → value, ໃຊ້ໂດຍ @term('key', 'default')
 *
 * Both maps are cached forever and busted on any write.
 */
class Translation extends Model
{
    protected $fillable = ['type', 'group', 'source', 'target', 'target_en', 'note', 'is_active', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];

    public const CACHE_REPLACE = 'translations.replace';

    public const CACHE_TERM = 'translations.term';

    /** UI languages the catalogue holds a column for. */
    public const LOCALES = ['lo', 'en'];

    protected static function booted(): void
    {
        $bust = function () {
            foreach (self::LOCALES as $l) {
                Cache::forget(self::CACHE_REPLACE.'.'.$l);
                Cache::forget(self::CACHE_TERM.'.'.$l);
            }
            // legacy un-suffixed keys (pre-i18n) — clear too, just in case.
            Cache::forget(self::CACHE_REPLACE);
            Cache::forget(self::CACHE_TERM);
        };
        static::saved($bust);
        static::deleted($bust);
    }

    /** The catalogue column that backs a given UI locale. */
    private static function columnFor(?string $locale): string
    {
        return ($locale ?: app()->getLocale()) === 'en' ? 'target_en' : 'target';
    }

    /**
     * @return array<string,string> source→translation for the given (or current)
     * locale, active, longest source first. English (target_en) drives EN mode;
     * Lao (target) drives everything else — untranslated rows are simply omitted
     * so the original source text stays on the page (graceful fallback).
     */
    public static function replaceMap(?string $locale = null): array
    {
        $col = self::columnFor($locale);
        $cacheKey = self::CACHE_REPLACE.'.'.($col === 'target_en' ? 'en' : 'lo');

        return Cache::rememberForever($cacheKey, function () use ($col) {
            $pairs = static::query()->where('type', 'replace')->where('is_active', true)
                ->whereNotNull($col)->where($col, '!=', '')->where('source', '!=', '')
                ->whereColumn($col, '!=', 'source')   // identity rows = no-op, skip
                ->pluck($col, 'source')->all();

            $rows = [];
            foreach ($pairs as $src => $target) {
                // Strip any HTML from the admin-entered target before it is injected
                // into rendered pages — prevents stored XSS via the replace middleware.
                $target = strip_tags((string) $target);
                $rows[$src] = $target;
                // Blade escapes display text (& → &amp;, < → &lt; …), so a source
                // containing special characters (e.g. "Equipment & Tools") never
                // matches the rendered HTML unless we also map its encoded form.
                $encSrc = e((string) $src);
                if ($encSrc !== (string) $src) {
                    $rows[$encSrc] = e($target);
                }
            }
            // longest source first so phrases win over their sub-words
            uksort($rows, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

            return $rows;
        });
    }

    /** @return array<string,string> key→value for active term overrides (per locale). */
    public static function termMap(?string $locale = null): array
    {
        $col = self::columnFor($locale);
        $cacheKey = self::CACHE_TERM.'.'.($col === 'target_en' ? 'en' : 'lo');

        return Cache::rememberForever($cacheKey, fn () => static::query()
            ->where('type', 'term')->where('is_active', true)
            ->whereNotNull($col)->where($col, '!=', '')
            ->pluck($col, 'source')->all());
    }

    /**
     * Resolve a term key to its override for the current locale, falling back to
     * the given default (which is the hard-coded Lao passed by @term) when the
     * locale has no translation — so English mode degrades to Lao, never blank.
     */
    public static function term(string $key, string $default = ''): string
    {
        return static::termMap()[$key] ?? $default;
    }

    /**
     * Attributes whose values are display text and safe to translate.
     * wire:confirm is the Livewire confirmation-dialog prompt shown to the user,
     * so it belongs here alongside the standard HTML display attributes.
     */
    public const SAFE_ATTRS = ['title', 'placeholder', 'alt', 'aria-label', 'wire:confirm'];

    /**
     * Apply active replace pairs — but only inside visible text nodes and a
     * whitelist of display attributes. <script>/<style>/comments and all other
     * tag internals (class, value, wire:*, x-*) are left untouched, so English
     * sources can't corrupt code, option values, or CSS class names.
     */
    public static function applyReplacements(string $html): string
    {
        $map = static::replaceMap();
        if (! $map) {
            return $html;
        }

        // 1. Protect script/style/comment blocks.
        $stash = [];
        $html = preg_replace_callback(
            '#<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>|<!--.*?-->#is',
            function ($m) use (&$stash) {
                $key = "\x00".count($stash)."\x00";
                $stash[$key] = $m[0];

                return $key;
            },
            $html
        );

        // 2. Walk tag/text segments; translate text nodes + safe attributes.
        $attrs = implode('|', self::SAFE_ATTRS);
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] === '<' && str_ends_with($part, '>')) {
                $out .= preg_replace_callback(
                    '/\b('.$attrs.')="([^"]*)"/i',
                    // encode any " a replacement target might introduce → no attribute breakout
                    fn ($m) => $m[1].'="'.str_replace('"', '&quot;', strtr($m[2], $map)).'"',
                    $part
                );
            } else {
                $out .= strtr($part, $map);
            }
        }

        // 3. Restore protected blocks.
        return strtr($out, $stash);
    }
}
