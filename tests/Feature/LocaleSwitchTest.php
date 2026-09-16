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

test('English validation messages resolve in en, Lao in lo', function () {
    app()->setLocale('en');
    expect(trans('validation.required', ['attribute' => 'name']))->toBe('The name field is required.');

    app()->setLocale('lo');
    expect(trans('validation.required', ['attribute' => 'ຊື່']))->toContain('ຈຳເປັນ');
});
