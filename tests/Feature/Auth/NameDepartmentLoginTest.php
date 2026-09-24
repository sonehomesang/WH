<?php

use App\Models\User;
use App\Services\LdapDirectory;
use Livewire\Volt\Volt;

/**
 * The primary staff sign-in: pick Department + Name, then type the password.
 * The identifier (username + department) is decoupled from the auth method, so
 * these run under the default local_only mode; the AD case installs a fake DC.
 */
function nameUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'username' => 'somchan',
        'department_id' => 5,
        'auth_provider' => 'password',
        'status' => 'active',
        'must_change_password' => false,
        'password' => bcrypt('Pass-9999'),
    ], $attrs));
}

test('staff sign in with department + name + password', function () {
    $u = nameUser();

    Volt::test('pages.auth.login')
        ->set('form.department_id', 5)
        ->set('form.username', 'somchan')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($u);
});

test('the wrong department for a name is rejected', function () {
    nameUser(['department_id' => 5]);

    Volt::test('pages.auth.login')
        ->set('form.department_id', 9)          // person is in dept 5, not 9
        ->set('form.username', 'somchan')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasErrors('form.password');

    $this->assertGuest();
});

test('a wrong password is rejected in the name flow', function () {
    nameUser();

    Volt::test('pages.auth.login')
        ->set('form.department_id', 5)
        ->set('form.username', 'somchan')
        ->set('form.password', 'nope')
        ->call('login')
        ->assertHasErrors('form.password');

    $this->assertGuest();
});

test('department and name are required', function () {
    Volt::test('pages.auth.login')
        ->set('form.password', 'Pass-9999')
        ->call('login')
        ->assertHasErrors(['form.department_id', 'form.username']);

    $this->assertGuest();
});

test('a domain user signs in by name + department against AD', function () {
    // fake DC that accepts one identity→password pair
    $fake = new class extends LdapDirectory
    {
        public array $valid = [];

        public function loginEnabled(): bool
        {
            return true;
        }

        public function attemptBind(string $identity, string $password): bool
        {
            return trim($password) !== '' && ($this->valid[$identity] ?? null) === $password;
        }
    };
    app()->instance(LdapDirectory::class, $fake);

    $u = nameUser([
        'username' => 'phon',
        'email' => 'phon@example.com',
        'department_id' => 7,
        'auth_provider' => 'domain',
    ]);
    $fake->valid = ['phon@example.com' => 'Ad-P@ss-123'];

    Volt::test('pages.auth.login')
        ->set('form.department_id', 7)
        ->set('form.username', 'phon')
        ->set('form.password', 'Ad-P@ss-123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($u);
});
