<?php

use App\Livewire\Request\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

test('request index shows the total in the header and clamps a non-whitelisted perPage', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('ໃບເບີກ ທັງໝົດ')      // total surfaced in the app header (teleported)
        ->set('perPage', 999)
        ->assertViewHas('records', fn ($p) => $p->perPage() === 8);
});
