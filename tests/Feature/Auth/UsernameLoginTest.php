<?php

use App\Livewire\Settings\Access;
use App\Livewire\Settings\Users;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
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
        ->set('form.email', 'noemail')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($u);
});

test('email login still works', function () {
    $u = localUser(['username' => 'x1', 'email' => 'e@example.com']);

    Volt::test('pages.auth.login')
        ->set('form.email', 'e@example.com')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($u);
});

test('the identifier is matched case-insensitively', function () {
    $u = localUser(['username' => 'mixed', 'email' => null]);

    Volt::test('pages.auth.login')
        ->set('form.email', 'MIXED')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($u);
});

test('a wrong password is rejected', function () {
    localUser(['username' => 'somchan', 'email' => null]);

    Volt::test('pages.auth.login')
        ->set('form.email', 'somchan')
        ->set('form.password', 'wrong')
        ->call('login')
        ->assertHasErrors('form.email');

    $this->assertGuest();
});

test('default password saves encrypted and reads back decrypted', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'password', 'status' => 'active']));

    Livewire::test(Access::class)
        ->set('defaultPassword', 'Default-8888')
        ->call('saveDefaultPassword')
        ->assertHasNoErrors();

    $enc = Setting::get('auth')['default_password_enc'];
    expect($enc)->not->toBe('Default-8888')                       // stored encrypted
        ->and(Crypt::decryptString($enc))->toBe('Default-8888')
        ->and(Users::defaultPassword())->toBe('Default-8888');
});

test('changing the auth mode keeps the default password', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'password', 'status' => 'active']));
    Setting::put('auth', ['default_password_enc' => Crypt::encryptString('keepme')]);

    Livewire::test(Access::class)->call('setMode', 'ad_strict');

    expect(Users::defaultPassword())->toBe('keepme')
        ->and(Setting::get('auth')['mode'])->toBe('ad_strict');
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
