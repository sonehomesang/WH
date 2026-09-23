<?php

use App\Livewire\Settings\Audit;
use App\Livewire\Settings\Facilities;
use App\Models\AuditHistory;
use App\Models\Building;
use App\Models\BuildingType;
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function mkLocation(string $slug): Location
{
    return Location::create(['slug' => $slug, 'name' => 'Loc '.$slug, 'is_active' => true]);
}

function mkBuildingType(): BuildingType
{
    return BuildingType::create(['slug' => 'bt-'.uniqid(), 'name' => 'Warehouse', 'is_active' => true]);
}

function mkBuilding(Location $loc, BuildingType $type, string $slug): Building
{
    return Building::create([
        'location_id' => $loc->id, 'building_type_id' => $type->id,
        'slug' => $slug, 'name' => 'Bld '.$slug, 'is_active' => true,
    ]);
}

test('creating a location logs a create action with actor and timestamp', function () {
    $admin = User::factory()->create(['is_super_admin' => true, 'display_name' => 'Boss']);
    $this->actingAs($admin);

    Livewire::test(Facilities::class)
        ->call('newLocation')->set('name', 'ຄັງ ກາງ')
        ->call('save')->assertHasNoErrors();

    $loc = Location::where('name', 'ຄັງ ກາງ')->first();
    $row = AuditHistory::where('module', 'location')->where('record_id', $loc->id)->first();
    expect($row)->not->toBeNull();
    expect($row->action)->toBe('create');
    expect($row->record_label)->toBe('ຄັງ ກາງ');
    expect($row->user_name)->toBe('Boss');
});

test('editing a location logs an update with the reason and notifies other admins (not the actor)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $other = User::factory()->create(['status' => 'active']);
    $other->assignRole('admin');
    $this->actingAs($admin);
    $loc = mkLocation('lo-1');

    Livewire::test(Facilities::class)
        ->call('editLocation', $loc->id)->set('name', 'Renamed')->set('changeReason', 'ປັບ ຊື່')
        ->call('save')->assertHasNoErrors();

    $row = AuditHistory::where('module', 'location')->where('record_id', $loc->id)->where('action', 'update')->first();
    expect($row)->not->toBeNull();
    expect($row->comment)->toBe('ປັບ ຊື່');

    expect(\App\Models\Notification::where('user_id', $other->id)->where('title', 'like', 'Location:%')->exists())->toBeTrue();
    expect(\App\Models\Notification::where('user_id', $admin->id)->where('title', 'like', 'Location:%')->exists())->toBeFalse();
});

test('editing a location without a change reason is rejected', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $loc = mkLocation('lo-1b');

    Livewire::test(Facilities::class)
        ->call('editLocation', $loc->id)->set('name', 'X')
        ->call('save')->assertHasErrors('changeReason');
});

test('toggling a location logs deactivate then activate', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $loc = mkLocation('lo-2');

    Livewire::test(Facilities::class)->call('toggleLocation', $loc->id);
    Livewire::test(Facilities::class)->call('toggleLocation', $loc->id);

    expect(AuditHistory::where('module', 'location')->where('record_id', $loc->id)->where('action', 'deactivate')->exists())->toBeTrue();
    expect(AuditHistory::where('module', 'location')->where('record_id', $loc->id)->where('action', 'activate')->exists())->toBeTrue();
});

test('deleting and restoring a location log delete (with reason) and restore', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $loc = mkLocation('lo-3');   // no buildings → deletable

    Livewire::test(Facilities::class)
        ->call('openDelete', 'location', $loc->id)->set('deleteReason', 'ຊ້ຳ')
        ->call('deleteRecord')->assertHasNoErrors();

    $del = AuditHistory::where('module', 'location')->where('record_id', $loc->id)->where('action', 'delete')->first();
    expect($del)->not->toBeNull();
    expect($del->comment)->toBe('ຊ້ຳ');

    Livewire::test(Facilities::class)->call('restoreRecord', 'location', $loc->id);
    expect(AuditHistory::where('module', 'location')->where('record_id', $loc->id)->where('action', 'restore')->exists())->toBeTrue();
});

test('creating a building logs a create action under the building module', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $loc = mkLocation('lo-4');       // selected on mount
    mkBuildingType();                // newBuilding picks the first active type

    Livewire::test(Facilities::class)
        ->call('newBuilding')->set('name', 'ອາຄານ A')
        ->call('save')->assertHasNoErrors();

    $bld = Building::where('name', 'ອາຄານ A')->first();
    expect($bld)->not->toBeNull();
    expect(AuditHistory::where('module', 'building')->where('record_id', $bld->id)->where('action', 'create')->exists())->toBeTrue();
});

test('creating a room logs a create action under the room module', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $loc = mkLocation('lo-5');
    $bld = mkBuilding($loc, mkBuildingType(), 'b-5');   // location + building selected on mount

    Livewire::test(Facilities::class)
        ->call('newRoom')->set('name', 'ຫ້ອງ 101')
        ->call('save')->assertHasNoErrors();

    $room = Room::where('name', 'ຫ້ອງ 101')->first();
    expect($room)->not->toBeNull();
    expect(AuditHistory::where('module', 'room')->where('record_id', $room->id)->where('action', 'create')->exists())->toBeTrue();
});

test('facility actions show up in the Settings audit log', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $loc = mkLocation('lo-6');
    Livewire::test(Facilities::class)->call('toggleLocation', $loc->id);

    Livewire::test(Audit::class)
        ->set('moduleFilter', 'location')
        ->assertViewHas('rows', fn ($rows) => collect($rows->items())
            ->contains(fn ($r) => $r->module === 'location' && $r->number === 'Loc lo-6' && $r->action === 'deactivate'));
});
