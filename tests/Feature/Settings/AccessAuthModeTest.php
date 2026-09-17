<?php

use App\Livewire\Settings\Access;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserHistory;
use App\Services\LdapDirectory;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;

function superAdmin(): User
{
    return User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'password', 'status' => 'active']);
}

test('auth mode defaults to local_only and drives loginEnabled', function () {
    $ldap = new LdapDirectory;
    expect($ldap->mode())->toBe(LdapDirectory::MODE_LOCAL)
        ->and($ldap->loginEnabled())->toBeFalse();

    Setting::put('auth', ['mode' => LdapDirectory::MODE_AD_STRICT]);
    expect((new LdapDirectory)->mode())->toBe('ad_strict')
        ->and((new LdapDirectory)->loginEnabled())->toBeTrue();
});

test('legacy ldap.login_with_ad still derives ad_strict when no auth mode is set', function () {
    Setting::put('ldap', ['enabled' => true, 'login_with_ad' => true]);
    expect((new LdapDirectory)->mode())->toBe('ad_strict');
});

test('a super admin can switch the auth mode and it syncs the legacy flag', function () {
    $this->actingAs(superAdmin());

    Livewire::test(Access::class)
        ->call('setMode', 'ad_strict')
        ->assertHasNoErrors();

    expect(Setting::get('auth')['mode'])->toBe('ad_strict')
        ->and(Setting::get('ldap')['login_with_ad'])->toBeTrue();

    Livewire::test(Access::class)->call('setMode', 'local_only');
    expect(Setting::get('auth')['mode'])->toBe('local_only')
        ->and(Setting::get('ldap')['login_with_ad'])->toBeFalse();
});

test('ad_fallback is rejected in Phase A', function () {
    $this->actingAs(superAdmin());

    Livewire::test(Access::class)
        ->call('setMode', 'ad_fallback')
        ->assertHasErrors('mode');

    expect(Setting::get('auth'))->toBe([]);   // nothing written
});

test('a non-super-admin cannot open the Access page', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => false, 'status' => 'active']));

    Livewire::test(Access::class)->assertForbidden();
});

test('switching the mode writes an audit row', function () {
    $admin = superAdmin();
    $this->actingAs($admin);

    Livewire::test(Access::class)->call('setMode', 'ad_strict');

    expect(UserHistory::where('action', 'auth_mode')->where('user_id', $admin->id)->exists())->toBeTrue();
});

test('provisionUnique gives only unprovisioned domain users a real local password', function () {
    $this->actingAs(superAdmin());

    $fresh = User::factory()->create(['auth_provider' => 'domain', 'is_super_admin' => false, 'local_password_set_at' => null, 'must_change_password' => false]);
    $already = User::factory()->create(['auth_provider' => 'domain', 'is_super_admin' => false, 'local_password_set_at' => now()->subDay()]);
    $local = User::factory()->create(['auth_provider' => 'password', 'is_super_admin' => false, 'local_password_set_at' => null]);

    $comp = Livewire::test(Access::class)->call('provisionUnique');

    $provisioned = $comp->get('provisioned');
    expect($provisioned)->toHaveCount(1)
        ->and($provisioned[0]['email'])->toBe($fresh->email);

    $fresh->refresh();
    // the cast must NOT double-hash — the plaintext must verify against the stored hash
    expect(Hash::check($provisioned[0]['password'], $fresh->password))->toBeTrue()
        ->and($fresh->must_change_password)->toBeTrue()
        ->and($fresh->local_password_set_at)->not->toBeNull();

    // the already-provisioned domain user and the local user are untouched
    expect($already->refresh()->must_change_password)->toBeFalse()
        ->and($local->refresh()->local_password_set_at)->toBeNull();

    expect(UserHistory::where('action', 'temp_password')->where('record_id', $fresh->id)->exists())->toBeTrue();
});

test('provisionShared validates length and applies to unprovisioned domain users', function () {
    $this->actingAs(superAdmin());
    $u = User::factory()->create(['auth_provider' => 'domain', 'is_super_admin' => false, 'local_password_set_at' => null]);

    Livewire::test(Access::class)
        ->set('sharedPassword', 'short')
        ->call('provisionShared')
        ->assertHasErrors('sharedPassword');

    Livewire::test(Access::class)
        ->set('sharedPassword', 'Shared-Temp-8')
        ->call('provisionShared')
        ->assertHasNoErrors();

    $u->refresh();
    expect(Hash::check('Shared-Temp-8', $u->password))->toBeTrue()
        ->and($u->must_change_password)->toBeTrue()
        ->and($u->local_password_set_at)->not->toBeNull();
});

test('in local_only mode a domain user signs in with their local password', function () {
    Setting::put('auth', ['mode' => LdapDirectory::MODE_LOCAL]);

    $user = User::factory()->create([
        'email' => 'domainstaff@example.com',
        'auth_provider' => 'domain',
        'status' => 'active',
        'must_change_password' => false,
        'local_password_set_at' => now(),
        'password' => bcrypt('Local-P@ss-9'),
    ]);

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'Local-P@ss-9')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('break-glass listing counts local super admins, not domain ones', function () {
    $this->actingAs(superAdmin());
    $localSuper = User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'password', 'status' => 'active', 'username' => 'itadmin']);
    $domainSuper = User::factory()->create(['is_super_admin' => true, 'auth_provider' => 'domain', 'status' => 'active', 'username' => 'dsuper']);

    Livewire::test(Access::class)
        ->assertSee('itadmin')
        ->assertDontSee('dsuper');
});
