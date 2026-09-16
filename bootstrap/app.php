<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\MustChangePassword;
use App\Http\Middleware\ReplaceTerms;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Per-request UI language (Lao default, user may switch to English).
        $middleware->appendToGroup('web', SetLocale::class);

        // Admin wording overrides applied to rendered HTML (Phase 6.11).
        $middleware->appendToGroup('web', ReplaceTerms::class);

        // HTTP security headers on every web response (clickjacking/MIME/HSTS/CSP).
        $middleware->appendToGroup('web', SecurityHeaders::class);

        // ບັງຄັບ ຕັ້ງ ລະຫັດ ໃໝ່ ຕອນ login ຄັ້ງ ທຳອິດ (ຜູ້ໃຊ້ ທີ່ ໃຫ້ ລະຫັດ ຊົ່ວຄາວ).
        $middleware->appendToGroup('web', MustChangePassword::class);

        // ຕັດ session ທັນທີ ຖ້າ ບັນຊີ ຖືກ lock/deactivate ຫຼັງ login (M1).
        $middleware->appendToGroup('web', EnsureActiveUser::class);

        // 404 ໂມດູລ ທີ່ admin ປິດ ໄວ້ ໃນ Settings (feature flag).
        $middleware->appendToGroup('web', EnsureModuleEnabled::class);

        // Trust reverse-proxy headers (X-Forwarded-Proto/Host) so HTTPS is detected
        // behind cloudflared tunnel / nginx — otherwise asset URLs come out as http://
        // on an https page and get blocked as mixed content (broken CSS on mobile).
        //
        // SECURITY: pin to the real edge IP(s), NOT '*'. Trusting all proxies lets a
        // client spoof X-Forwarded-For (defeating the per-IP login throttle → brute
        // force) and X-Forwarded-Proto. Default = loopback (tunnel/nginx on the same
        // host); override on the server via TRUSTED_PROXIES=ip1,cidr2 (or '*' at own risk).
        $proxies = trim((string) env('TRUSTED_PROXIES', '127.0.0.1,::1'));
        $middleware->trustProxies(at: $proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $proxies)))));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
