<?php

use App\Models\User;

test('the app defaults to Lao with a Lao fallback', function () {
    $user = User::factory()->create(['status' => 'active', 'is_super_admin' => true]);
    $this->actingAs($user)->get('/dashboard')->assertOk();

    expect(app()->getLocale())->toBe('lo');
    expect(config('app.fallback_locale'))->toBe('lo');
});

test('the language toggle switches to English and persists in the session', function () {
    $user = User::factory()->create(['status' => 'active', 'is_super_admin' => true]);

    $this->actingAs($user)->get(route('locale.switch', 'en'))->assertRedirect();
    expect(session('app_locale'))->toBe('en');

    // the SetLocale middleware applies the stored choice on the next request
    $this->actingAs($user)->get('/dashboard')->assertOk();
    expect(app()->getLocale())->toBe('en');
});

test('an unsupported locale is ignored (no injection into the session)', function () {
    $user = User::factory()->create(['status' => 'active', 'is_super_admin' => true]);

    $this->actingAs($user)->withSession(['app_locale' => 'lo'])
        ->get(route('locale.switch', 'zz'))->assertRedirect();

    expect(session('app_locale'))->toBe('lo');   // unchanged — 'zz' rejected
});

test('SECURITY — the locale toggle never redirects to a foreign host (no open redirect)', function () {
    $user = User::factory()->create(['status' => 'active', 'is_super_admin' => true]);

    // Forged external Referer → host is stripped, redirect stays same-origin.
    $r1 = $this->actingAs($user)
        ->get(route('locale.switch', 'en'), ['referer' => 'https://evil.example.com/phish']);
    expect($r1->headers->get('Location'))->not->toContain('evil.example.com');

    // Backslash trick ("/\host") that some browsers fold to "//host" → rejected.
    $r2 = $this->actingAs($user)
        ->get(route('locale.switch', 'en'), ['referer' => 'https://localhost/\\evil.example.com']);
    expect($r2->headers->get('Location'))->not->toContain('evil.example.com');

    // A legitimate local Referer → the user is returned to that same page.
    $this->actingAs($user)
        ->get(route('locale.switch', 'lo'), ['referer' => 'http://localhost/inventory?search=abc'])
        ->assertRedirect('/inventory?search=abc');
});

test('English validation messages resolve in en, Lao in lo', function () {
    app()->setLocale('en');
    expect(trans('validation.required', ['attribute' => 'name']))->toBe('The name field is required.');

    app()->setLocale('lo');
    expect(trans('validation.required', ['attribute' => 'ຊື່']))->toContain('ຈຳເປັນ');
});
