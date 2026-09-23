<?php

use App\Livewire\Settings\Audit;
use App\Livewire\Settings\Organization;
use App\Models\AuditHistory;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function mkUnit(string $slug): Unit
{
    return Unit::create(['slug' => $slug, 'name' => 'Unit '.$slug, 'is_active' => true]);
}

test('creating a unit logs a create action with actor and timestamp', function () {
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Boss']);
    $this->actingAs($admin);

    Livewire::test(Organization::class)
        ->call('newUnit')->set('name', 'ກອງ ຄັງ')
        ->call('save')->assertHasNoErrors();

    $unit = Unit::where('name', 'ກອງ ຄັງ')->first();
    $row = AuditHistory::where('module', 'unit')->where('record_id', $unit->id)->first();
    expect($row)->not->toBeNull();
    expect($row->action)->toBe('create');
    expect($row->record_label)->toBe('ກອງ ຄັງ');
    expect($row->user_name)->toBe('Boss');
});

test('editing a unit logs an update with the reason and notifies other admins (not the actor)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $other = User::factory()->create(['status' => 'active']);
    $other->assignRole('admin');
    $this->actingAs($admin);
    $unit = mkUnit('un-1');

    Livewire::test(Organization::class)
        ->call('editUnit', $unit->id)->set('name', 'Renamed')->set('changeReason', 'ປັບ ຊື່')
        ->call('save')->assertHasNoErrors();

    $row = AuditHistory::where('module', 'unit')->where('record_id', $unit->id)->where('action', 'update')->first();
    expect($row)->not->toBeNull();
    expect($row->comment)->toBe('ປັບ ຊື່');

    expect(\App\Models\Notification::where('user_id', $other->id)->where('title', 'like', 'Unit:%')->exists())->toBeTrue();
    expect(\App\Models\Notification::where('user_id', $admin->id)->where('title', 'like', 'Unit:%')->exists())->toBeFalse();
});

test('editing a unit without a change reason is rejected', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $unit = mkUnit('un-1b');

    Livewire::test(Organization::class)
        ->call('editUnit', $unit->id)->set('name', 'X')
        ->call('save')->assertHasErrors('changeReason');
});

test('toggling a unit logs deactivate then activate', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $unit = mkUnit('un-2');

    Livewire::test(Organization::class)->call('toggleUnit', $unit->id);
    Livewire::test(Organization::class)->call('toggleUnit', $unit->id);

    expect(AuditHistory::where('module', 'unit')->where('record_id', $unit->id)->where('action', 'deactivate')->exists())->toBeTrue();
    expect(AuditHistory::where('module', 'unit')->where('record_id', $unit->id)->where('action', 'activate')->exists())->toBeTrue();
});

test('deleting and restoring a unit log delete (with reason) and restore', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $unit = mkUnit('un-3');   // no departments → deletable

    Livewire::test(Organization::class)
        ->call('openDelete', 'unit', $unit->id)->set('deleteReason', 'ຊ້ຳ')
        ->call('deleteRecord')->assertHasNoErrors();

    $del = AuditHistory::where('module', 'unit')->where('record_id', $unit->id)->where('action', 'delete')->first();
    expect($del)->not->toBeNull();
    expect($del->comment)->toBe('ຊ້ຳ');

    Livewire::test(Organization::class)->call('restoreRecord', 'unit', $unit->id);
    expect(AuditHistory::where('module', 'unit')->where('record_id', $unit->id)->where('action', 'restore')->exists())->toBeTrue();
});

test('creating a department logs a create action under the department module', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $unit = mkUnit('un-4');   // becomes the selected unit on mount

    Livewire::test(Organization::class)
        ->call('newDepartment')->set('name', 'ພະແນກ ຈັດຊື້')
        ->call('save')->assertHasNoErrors();

    $dept = Department::where('name', 'ພະແນກ ຈັດຊື້')->first();
    expect($dept)->not->toBeNull();
    $row = AuditHistory::where('module', 'department')->where('record_id', $dept->id)->where('action', 'create')->first();
    expect($row)->not->toBeNull();
    expect($row->record_label)->toBe('ພະແນກ ຈັດຊື້');
});

test('organization actions show up in the Settings audit log', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $unit = mkUnit('un-5');
    Livewire::test(Organization::class)->call('toggleUnit', $unit->id);

    Livewire::test(Audit::class)
        ->set('moduleFilter', 'unit')
        ->assertViewHas('rows', fn ($rows) => collect($rows->items())
            ->contains(fn ($r) => $r->module === 'unit' && $r->number === 'Unit un-5' && $r->action === 'deactivate'));
});
