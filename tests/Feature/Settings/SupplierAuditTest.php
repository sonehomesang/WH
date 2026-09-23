<?php

use App\Livewire\Settings\Audit;
use App\Livewire\Settings\Suppliers;
use App\Models\Supplier;
use App\Models\SupplierHistory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function mkSupplier(string $slug): Supplier
{
    return Supplier::create([
        'slug' => $slug, 'name' => 'Sup '.$slug, 'default_currency' => 'LAK', 'is_active' => true,
    ]);
}

test('creating a supplier logs a create action with actor and timestamp', function () {
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Boss']);
    $this->actingAs($admin);

    Livewire::test(Suppliers::class)
        ->call('newItem')->set('name', 'ACME')->set('default_currency', 'LAK')
        ->call('save')->assertHasNoErrors();

    $sup = Supplier::where('name', 'ACME')->first();
    $row = SupplierHistory::where('record_id', $sup->id)->first();
    expect($row)->not->toBeNull();
    expect($row->action)->toBe('create');
    expect($row->user_name)->toBe('Boss');
    expect($row->created_at)->not->toBeNull();
});

test('editing logs an update with the reason and notifies other admins (not the actor)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $other = User::factory()->create(['status' => 'active']);
    $other->assignRole('admin');
    $this->actingAs($admin);
    $sup = mkSupplier('s-1');

    Livewire::test(Suppliers::class)
        ->call('editItem', $sup->id)->set('name', 'Renamed')->set('changeReason', 'ປັບ ຊື່')
        ->call('save')->assertHasNoErrors();

    $row = SupplierHistory::where('record_id', $sup->id)->where('action', 'update')->first();
    expect($row)->not->toBeNull();
    expect($row->comment)->toBe('ປັບ ຊື່');

    expect(\App\Models\Notification::where('user_id', $other->id)->where('title', 'like', 'Supplier:%')->exists())->toBeTrue();
    expect(\App\Models\Notification::where('user_id', $admin->id)->where('title', 'like', 'Supplier:%')->exists())->toBeFalse();
});

test('editing a supplier without a change reason is rejected', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $sup = mkSupplier('s-1b');

    Livewire::test(Suppliers::class)
        ->call('editItem', $sup->id)->set('name', 'X')
        ->call('save')->assertHasErrors('changeReason');
});

test('toggling active state logs deactivate then activate', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $sup = mkSupplier('s-2');

    Livewire::test(Suppliers::class)->call('toggle', $sup->id);
    Livewire::test(Suppliers::class)->call('toggle', $sup->id);

    expect(SupplierHistory::where('record_id', $sup->id)->where('action', 'deactivate')->exists())->toBeTrue();
    expect(SupplierHistory::where('record_id', $sup->id)->where('action', 'activate')->exists())->toBeTrue();
});

test('deleting and restoring log delete (with reason) and restore', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $sup = mkSupplier('s-3');

    Livewire::test(Suppliers::class)
        ->call('openDelete', $sup->id)->set('deleteReason', 'ຊ້ຳ')
        ->call('deleteRecord')->assertHasNoErrors();

    $del = SupplierHistory::where('record_id', $sup->id)->where('action', 'delete')->first();
    expect($del)->not->toBeNull();
    expect($del->comment)->toBe('ຊ້ຳ');

    Livewire::test(Suppliers::class)->call('restore', $sup->id);
    expect(SupplierHistory::where('record_id', $sup->id)->where('action', 'restore')->exists())->toBeTrue();
});

test('supplier actions show up in the Settings audit log', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $sup = mkSupplier('s-4');
    Livewire::test(Suppliers::class)->call('toggle', $sup->id);

    Livewire::test(Audit::class)
        ->set('moduleFilter', 'supplier')
        ->assertViewHas('rows', fn ($rows) => collect($rows->items())
            ->contains(fn ($r) => $r->module === 'supplier' && $r->number === 'Sup s-4' && $r->action === 'deactivate'));
});
