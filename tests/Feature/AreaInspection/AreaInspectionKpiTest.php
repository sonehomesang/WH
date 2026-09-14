<?php

use App\Livewire\AreaInspection\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

test('area-inspection index shows the KPI band and clamps a non-whitelisted perPage', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('ໃບ ກວດ ທັງໝົດ')
        ->set('perPage', 999)
        ->assertViewHas('rows', fn ($p) => $p->perPage() === 8);
});
