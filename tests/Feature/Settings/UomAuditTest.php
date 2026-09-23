<?php

use App\Livewire\Settings\Audit;
use App\Livewire\Settings\Uom;
use App\Models\AuditHistory;
use App\Models\Uom as UomModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function mkUom(string $slug): UomModel
{
    return UomModel::create([
        'slug' => $slug, 'name' => 'Uom '.$slug, 'is_active' => true,
    ]);
}

test('creating a uom logs a create action with actor and timestamp', function () {
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Boss']);
    $this->actingAs($admin);

    Livewire::test(Uom::class)
        ->call('newItem')->set('name', 'ກ່ອງ')
        ->call('save')->assertHasNoErrors();

    $uom = UomModel::where('name', 'ກ່ອງ')->first();
    $row = AuditHistory::where('module', 'uom')->where('record_id', $uom->id)->first();
    expect($row)->not->toBeNull();
    expect($row->action)->toBe('create');
    expect($row->record_label)->toBe('ກ່ອງ');
    expect($row->user_name)->toBe('Boss');
    expect($row->created_at)->not->toBeNull();
});

test('editing logs an update with the reason and notifies other admins (not the actor)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $other = User::factory()->create(['status' => 'active']);
    $other->assignRole('admin');
    $this->actingAs($admin);
    $uom = mkUom('u-1');

    Livewire::test(Uom::class)
        ->call('editItem', $uom->id)->set('name', 'Renamed')->set('changeReason', 'ປັບ ຊື່')
        ->call('save')->assertHasNoErrors();

    $row = AuditHistory::where('module', 'uom')->where('record_id', $uom->id)->where('action', 'update')->first();
    expect($row)->not->toBeNull();
    expect($row->comment)->toBe('ປັບ ຊື່');

    expect(\App\Models\Notification::where('user_id', $other->id)->where('title', 'like', 'UoM:%')->exists())->toBeTrue();
    expect(\App\Models\Notification::where('user_id', $admin->id)->where('title', 'like', 'UoM:%')->exists())->toBeFalse();
});

test('editing a uom without a change reason is rejected', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $uom = mkUom('u-1b');

    Livewire::test(Uom::class)
        ->call('editItem', $uom->id)->set('name', 'X')
        ->call('save')->assertHasErrors('changeReason');
});

test('toggling active state logs deactivate then activate', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $uom = mkUom('u-2');

    Livewire::test(Uom::class)->call('toggle', $uom->id);
    Livewire::test(Uom::class)->call('toggle', $uom->id);

    expect(AuditHistory::where('module', 'uom')->where('record_id', $uom->id)->where('action', 'deactivate')->exists())->toBeTrue();
    expect(AuditHistory::where('module', 'uom')->where('record_id', $uom->id)->where('action', 'activate')->exists())->toBeTrue();
});

test('deleting and restoring log delete (with reason) and restore', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $uom = mkUom('u-3');

    Livewire::test(Uom::class)
        ->call('openDelete', $uom->id)->set('deleteReason', 'ຊ້ຳ')
        ->call('deleteRecord')->assertHasNoErrors();

    $del = AuditHistory::where('module', 'uom')->where('record_id', $uom->id)->where('action', 'delete')->first();
    expect($del)->not->toBeNull();
    expect($del->comment)->toBe('ຊ້ຳ');

    Livewire::test(Uom::class)->call('restore', $uom->id);
    expect(AuditHistory::where('module', 'uom')->where('record_id', $uom->id)->where('action', 'restore')->exists())->toBeTrue();
});

test('uom actions show up in the Settings audit log', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $uom = mkUom('u-4');
    Livewire::test(Uom::class)->call('toggle', $uom->id);

    Livewire::test(Audit::class)
        ->set('moduleFilter', 'uom')
        ->assertViewHas('rows', fn ($rows) => collect($rows->items())
            ->contains(fn ($r) => $r->module === 'uom' && $r->number === 'Uom u-4' && $r->action === 'deactivate'));
});
