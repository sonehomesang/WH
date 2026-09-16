<?php

namespace App\Providers;

use App\Livewire\Settings\Translations;
use App\Models\Material;
use App\Models\Setting;
use App\Models\Translation;
use App\Observers\MaterialObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lao-first app: default UI language = lo (validation / diffForHumans in
        // Lao). A user may switch to 'en' via the top-right toggle — the SetLocale
        // middleware applies the per-request choice. Missing 'en' strings fall back
        // to 'lo' so nothing ever renders a raw translation key.
        config(['app.fallback_locale' => 'lo']);
        $this->app->setLocale('lo');

        // super_admin ມີສິດເໜືອທຸກຢ່າງ (ເໜືອ admin ທົ່ວໄປ) — bypass ທຸກ permission check.
        Gate::before(fn ($user, $ability) => $user->is_super_admin ? true : null);

        // ນະໂຍບາຍ ລະຫັດຜ່ານ ຂັ້ນຕ່ຳ (ISO-aligned): ຍາວ ≥ 10. ໃຊ້ ກັບ ໜ້າ ຕັ້ງ/reset ລະຫັດ.
        // ບໍ່ ບັງຄັບ symbol/case — ຄວາມ ຍາວ ໃຫ້ ປະໂຫຍກ ສັ້ນໆ ຜ່ານ ໄດ້ (ບໍ່ ລຳຄານ).
        Password::defaults(fn () => Password::min(10));

        Material::observe(MaterialObserver::class);

        // @term('key', 'default fallback') — admin-overridable wording.
        Blade::directive('term', function (string $expr) {
            return "<?php echo e(\\App\\Models\\Translation::term({$expr})); ?>";
        });

        // Livewire AJAX updates (pagination, filters, live search, saves) return
        // JSON, so they bypass the ReplaceTerms HTTP middleware and would show the
        // untranslated wording once the user paginates/filters. Re-apply the
        // overrides to re-rendered component HTML — but ONLY on update requests,
        // since the initial full-page load is already covered by the middleware
        // (avoids double-processing the same HTML twice).
        \Livewire\on('render', function ($component, $view) {
            // The Translations editor IS the replacement catalogue — its Livewire
            // state carries the raw source strings (which are replacement keys), so
            // re-applying the replacer to its own update HTML corrupts the editor
            // inputs (they blank out on search/filter). Never translate the editor's
            // own reactive updates; the full-page middleware still translates the
            // surrounding settings chrome on load.
            $isEditor = $component instanceof Translations;

            return function ($html) use ($isEditor) {
                return request()->routeIs('*livewire.update') && ! $isEditor
                    ? Translation::applyReplacements($html)
                    : $html;
            };
        });

        // ຄ່າ SMTP ທີ່ admin ຕັ້ງ ໃນ Settings › Email → override config ຕອນ runtime.
        self::applyMailSettings();
    }

    /**
     * ນຳ ຄ່າ mail ຈາກ ຕາຕະລາງ settings (admin ຕັ້ງ) ມາ ໃສ່ config.
     * ຖ້າ ຍັງ ບໍ່ ຕັ້ງ (mailer != smtp) → ໃຊ້ ຄ່າ .env ຕາມ ເດີມ (log).
     */
    public static function applyMailSettings(): void
    {
        try {
            $m = Cache::rememberForever(
                'settings.mail',
                fn () => Schema::hasTable('settings') ? Setting::get('mail', []) : []
            );
        } catch (\Throwable $e) {
            return;   // DB ຍັງ ບໍ່ ພ້ອມ (migrate / fresh install)
        }

        if (($m['mailer'] ?? '') !== 'smtp' || empty($m['host'])) {
            return;
        }

        $password = null;
        if (! empty($m['password'])) {
            try {
                $password = Crypt::decryptString($m['password']);
            } catch (\Throwable $e) {
                $password = null;
            }
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $m['host'],
            'mail.mailers.smtp.port' => (int) ($m['port'] ?? 587),
            'mail.mailers.smtp.username' => ($m['username'] ?? '') ?: null,
            'mail.mailers.smtp.password' => $password,
            // ssl → smtps (port 465) · tls/none → ປ່ອຍ null (STARTTLS ຢູ່ 587).
            'mail.mailers.smtp.scheme' => ($m['scheme'] ?? '') === 'ssl' ? 'smtps' : null,
        ]);

        if (! empty($m['from_address'])) {
            config([
                'mail.from.address' => $m['from_address'],
                'mail.from.name' => ($m['from_name'] ?? '') ?: config('app.name'),
            ]);
        }
    }
}
