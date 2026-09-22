<?php

use App\Livewire\Inventory\Index;
use App\Livewire\Settings\Audit;
use App\Models\InventoryHistory;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function mkItem(string $slug): InventoryItem
{
    return InventoryItem::create([
        'slug' => $slug, 'name' => 'Item '.$slug, 'quantity' => 1, 'min_quantity' => 0,
        'status' => 'available', 'condition_status' => 'in_service', 'is_active' => true,
    ]);
}

test('creating an item logs a create action with actor and timestamp', function () {
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Boss']);
    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('newItem')->set('name', 'Wrench')->set('quantity', 2)->set('status', 'available')
        ->call('save')->assertHasNoErrors();

    $item = InventoryItem::where('name', 'Wrench')->first();
    $row = InventoryHistory::where('record_id', $item->id)->first();
    expect($row)->not->toBeNull();
    expect($row->action)->toBe('create');
    expect($row->user_id)->toBe($admin->id);
    expect($row->user_name)->toBe('Boss');
    expect($row->created_at)->not->toBeNull();
});

test('editing logs an update with the reason and notifies other admins (not the actor)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);   // actor
    $other = User::factory()->create(['status' => 'active']);
    $other->assignRole('admin');
    $this->actingAs($admin);
    $item = mkItem('w-1');

    Livewire::test(Index::class)
        ->call('editItem', $item->id)->set('name', 'W edited')->set('changeReason', 'ປັບ ຊື່')
        ->call('save')->assertHasNoErrors();

    $row = InventoryHistory::where('record_id', $item->id)->where('action', 'update')->first();
    expect($row)->not->toBeNull();
    expect($row->comment)->toBe('ປັບ ຊື່');

    expect(\App\Models\Notification::where('user_id', $other->id)->where('title', 'like', 'Inventory:%')->exists())->toBeTrue();
    expect(\App\Models\Notification::where('user_id', $admin->id)->where('title', 'like', 'Inventory:%')->exists())->toBeFalse();
});

test('editing without a change reason is rejected', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $item = mkItem('w-1b');

    Livewire::test(Index::class)
        ->call('editItem', $item->id)->set('name', 'X')
        ->call('save')->assertHasErrors('changeReason');
});

test('toggling active state logs deactivate then activate', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $item = mkItem('w-2');

    Livewire::test(Index::class)->call('toggle', $item->id);   // active → deactivate
    Livewire::test(Index::class)->call('toggle', $item->id);   // inactive → activate

    expect(InventoryHistory::where('record_id', $item->id)->where('action', 'deactivate')->exists())->toBeTrue();
    expect(InventoryHistory::where('record_id', $item->id)->where('action', 'activate')->exists())->toBeTrue();
});

test('deleting and restoring log delete (with reason) and restore', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $item = mkItem('w-3');

    Livewire::test(Index::class)
        ->call('openDelete', $item->id)->set('deleteReason', 'ນັບ ຊ້ຳ')
        ->call('deleteRecord')->assertHasNoErrors();

    $del = InventoryHistory::where('record_id', $item->id)->where('action', 'delete')->first();
    expect($del)->not->toBeNull();
    expect($del->comment)->toBe('ນັບ ຊ້ຳ');

    Livewire::test(Index::class)->call('restore', $item->id);
    expect(InventoryHistory::where('record_id', $item->id)->where('action', 'restore')->exists())->toBeTrue();
});

test('inventory actions show up in the Settings audit log', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $item = mkItem('w-4');
    Livewire::test(Index::class)->call('toggle', $item->id);   // logs a deactivate row

    Livewire::test(Audit::class)
        ->set('moduleFilter', 'inventory')
        ->assertViewHas('rows', fn ($rows) => collect($rows->items())
            ->contains(fn ($r) => $r->module === 'inventory' && $r->number === 'w-4' && $r->action === 'deactivate'));
});
