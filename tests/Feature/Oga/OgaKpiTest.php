<?php

use App\Livewire\Oga\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

test('oga index shows the KPI band and clamps a non-whitelisted perPage', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('OGA ທັງໝົດ')
        ->set('perPage', 999)
        ->assertViewHas('records', fn ($p) => $p->perPage() === 8);
});
