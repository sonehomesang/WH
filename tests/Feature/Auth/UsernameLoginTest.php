<?php

use App\Livewire\Settings\Access;
use App\Livewire\Settings\Users;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;

function localUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'auth_provider' => 'password',
        'status' => 'active',
        'must_change_password' => false,
        'password' => bcrypt('Pass-9999'),
    ], $attrs));
}

test('a user signs in with their username', function () {
    $u = localUser(['username' => 'somchan', 'email' => 'somchan@example.com']);

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', 'somchan')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($u);
});

test('a user with no email signs in by username', function () {
    $u = localUser(['username' => 'noemail', 'email' => null]);

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', 'noemail')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($u);
});

test('email login still works', function () {
    $u = localUser(['username' => 'x1', 'email' => 'e@example.com']);

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', 'e@example.com')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($u);
});

test('the identifier is matched case-insensitively', function () {
    $u = localUser(['username' => 'mixed', 'email' => null]);

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', 'MIXED')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($u);
});

test('a wrong password is rejected', function () {
    localUser(['username' => 'somchan', 'email' => null]);

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', 'somchan')
        ->set('form.password', 'wrong')
        ->call('login')
        ->assertHasErrors('form.password');

    $this->assertGuest();
});

test('no default password is stored anywhere', function () {
    // The auth setting must never carry a recoverable default password.
    $this->actingAs(User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'password', 'status' => 'active']));
    Livewire::test(Access::class)->call('setMode', 'ad_strict');

    expect(Setting::get('auth'))->not->toHaveKey('default_password_enc')
        ->and(Setting::get('auth'))->not->toHaveKey('default_password');
});

test('generate fills a strong random password (never persisted)', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'password', 'status' => 'active']));

    $comp = Livewire::test(Users::class)->call('newUser')->call('generatePassword');
    expect(strlen((string) $comp->get('password')))->toBeGreaterThanOrEqual(12);
});

test('admin:reset-password sets a one-way hash via hidden prompts', function () {
    $u = User::factory()->create([
        'username' => 'boss', 'email' => null, 'is_super_admin' => true,
        'status' => 'active', 'password' => bcrypt('old-password'),
    ]);

    $this->artisan('admin:reset-password', ['identifier' => 'boss'])
        ->expectsQuestion('New password (hidden, min 10 chars)', 'Brand-New-Pass-1')
        ->expectsQuestion('Confirm new password', 'Brand-New-Pass-1')
        ->assertExitCode(0);

    expect(Hash::check('Brand-New-Pass-1', $u->refresh()->password))->toBeTrue()
        ->and($u->must_change_password)->toBeFalse();
});

test('admin:reset-password rejects a short password without changing anything', function () {
    $u = User::factory()->create(['username' => 'boss', 'email' => null, 'password' => bcrypt('old-password')]);

    $this->artisan('admin:reset-password', ['identifier' => 'boss'])
        ->expectsQuestion('New password (hidden, min 10 chars)', 'short')
        ->assertExitCode(1);

    expect(Hash::check('old-password', $u->refresh()->password))->toBeTrue();
});

test('admin creates a username account with a password and no email', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'password', 'status' => 'active']));
    Role::findOrCreate('warehouse_staff', 'web');

    Livewire::test(Users::class)
        ->call('newUser')
        ->set('display_name', 'Som Chan')
        ->set('username', 'somchan')
        ->set('email', '')
        ->set('password', 'Default-8888')
        ->set('role', 'warehouse_staff')
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    $new = User::where('username', 'somchan')->first();
    expect($new)->not->toBeNull()
        ->and($new->email)->toBeNull()
        ->and($new->auth_provider)->toBe('password')
        ->and($new->must_change_password)->toBeTrue()
        ->and($new->local_password_set_at)->not->toBeNull()
        ->and(Hash::check('Default-8888', $new->password))->toBeTrue();
});

test('username is required and validated', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'password', 'status' => 'active']));
    Role::findOrCreate('warehouse_staff', 'web');

    Livewire::test(Users::class)
        ->call('newUser')
        ->set('display_name', 'Bad Name')
        ->set('username', 'has space!')       // invalid chars
        ->set('password', 'Default-8888')
        ->set('role', 'warehouse_staff')
        ->set('status', 'active')
        ->call('save')
        ->assertHasErrors('username');
});
