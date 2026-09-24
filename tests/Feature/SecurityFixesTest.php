<?php

use App\Livewire\Settings\Users;
use App\Models\Translation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a locked account cannot log in even with valid credentials', function () {
    $u = User::factory()->create(['status' => 'locked', 'password' => bcrypt('password')]);

    Volt::test('pages.auth.login')
        ->set('adminMode', true)
        ->set('form.email', $u->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasErrors('form.password');

    $this->assertGuest();
});

test('a pending account cannot log in', function () {
    $u = User::factory()->create(['status' => 'pending', 'password' => bcrypt('password')]);

    Volt::test('pages.auth.login')->set('adminMode', true)->set('form.email', $u->email)->set('form.password', 'password')->call('login');
    $this->assertGuest();
});

test('a non-super admin cannot grant the super_admin role', function () {
    $admin = User::factory()->create(['is_super_admin' => false]);
    $admin->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(Users::class)
        ->set('display_name', 'X')->set('email', 'x@nt2.test')
        ->set('role', 'super_admin')->set('status', 'active')
        ->call('save')
        ->assertForbidden();

    expect(User::where('email', 'x@nt2.test')->exists())->toBeFalse();
});

test('a super_admin may grant the super_admin role', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));

    Livewire::test(Users::class)
        ->set('display_name', 'New Super')->set('email', 'sup@nt2.test')
        ->set('role', 'super_admin')->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('email', 'sup@nt2.test')->first()->hasRole('super_admin'))->toBeTrue();
});

test('html in a replace target is stripped (no stored XSS via the middleware)', function () {
    Translation::create([
        'type' => 'replace', 'source' => 'ສິນຄ້າ',
        'target' => '<img src=x onerror=alert(document.cookie)>ວັດສະດຸ', 'is_active' => true,
    ]);

    $out = Translation::applyReplacements('<p>ສິນຄ້າ</p>');

    expect($out)->not->toContain('onerror');
    expect($out)->not->toContain('<img');
    expect($out)->toContain('ວັດສະດຸ');   // visible text still replaced
});
