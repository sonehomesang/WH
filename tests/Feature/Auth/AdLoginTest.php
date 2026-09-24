<?php

use App\Models\User;
use App\Services\LdapDirectory;
use Livewire\Volt\Volt;

/**
 * A stand-in for the real directory so tests never touch a DC. It accepts one
 * identity→password pair and, like the real one, rejects an empty password.
 */
class FakeLdap extends LdapDirectory
{
    public bool $enabled = true;

    /** @var array<string,string> identity => valid password */
    public array $valid = [];

    public function loginEnabled(): bool
    {
        return $this->enabled;
    }

    public function attemptBind(string $identity, string $password): bool
    {
        if (trim($identity) === '' || trim($password) === '') {
            return false;
        }

        return ($this->valid[$identity] ?? null) === $password;
    }
}

/** Install the fake and return it so the test can arm credentials. */
function fakeLdap(bool $enabled = true): FakeLdap
{
    $fake = new FakeLdap;
    $fake->enabled = $enabled;
    app()->instance(LdapDirectory::class, $fake);

    return $fake;
}

test('a domain user signs in with the correct AD password', function () {
    $fake = fakeLdap();
    $user = User::factory()->create([
        'email' => 'souksavanh@example.com',
        'auth_provider' => 'domain',
        'status' => 'active',
    ]);
    $fake->valid = ['souksavanh@example.com' => 'Ad-P@ss-123'];

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', $user->email)
        ->set('form.password', 'Ad-P@ss-123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('a domain user is rejected with the wrong AD password', function () {
    $fake = fakeLdap();
    $user = User::factory()->create([
        'email' => 'souksavanh@example.com',
        'auth_provider' => 'domain',
        'status' => 'active',
    ]);
    $fake->valid = ['souksavanh@example.com' => 'Ad-P@ss-123'];

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', $user->email)
        ->set('form.password', 'not-the-password')
        ->call('login')
        ->assertHasErrors('form.password');

    $this->assertGuest();
});

test('first successful AD sign-in activates a pending imported account', function () {
    $fake = fakeLdap();
    $user = User::factory()->create([
        'email' => 'newstaff@example.com',
        'auth_provider' => 'domain',
        'status' => 'pending',
        'email_verified_at' => null,
    ]);
    $fake->valid = ['newstaff@example.com' => 'Welcome@2026'];

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', $user->email)
        ->set('form.password', 'Welcome@2026')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user->refresh();
    expect($user->status)->toBe('active')
        ->and($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('a locked domain user cannot enter even with the right AD password', function () {
    $fake = fakeLdap();
    $user = User::factory()->create([
        'email' => 'locked@example.com',
        'auth_provider' => 'domain',
        'status' => 'locked',
    ]);
    $fake->valid = ['locked@example.com' => 'Ad-P@ss-123'];

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', $user->email)
        ->set('form.password', 'Ad-P@ss-123')
        ->call('login')
        ->assertHasErrors('form.password');

    $this->assertGuest();
});

test('the break-glass password account still signs in locally while AD login is on', function () {
    fakeLdap();   // AD login enabled, but this account is a local one
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'auth_provider' => 'password',
        'status' => 'active',
        'password' => bcrypt('wh-local-pass'),
    ]);

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', $user->email)
        ->set('form.password', 'wh-local-pass')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('with AD login off, the AD password is never accepted for a domain user', function () {
    $fake = fakeLdap(enabled: false);
    $user = User::factory()->create([
        'email' => 'souksavanh@example.com',
        'auth_provider' => 'domain',
        'status' => 'active',
        'password' => bcrypt('unused-random'),
    ]);
    $fake->valid = ['souksavanh@example.com' => 'Ad-P@ss-123'];

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', $user->email)
        ->set('form.password', 'Ad-P@ss-123')   // valid in AD, but login-with-AD is off
        ->call('login')
        ->assertHasErrors('form.password');

    $this->assertGuest();
});

test('attemptBind rejects an empty password without contacting a server', function () {
    expect((new LdapDirectory)->attemptBind('someone@example.com', ''))->toBeFalse()
        ->and((new LdapDirectory)->attemptBind('', 'whatever'))->toBeFalse();
});
