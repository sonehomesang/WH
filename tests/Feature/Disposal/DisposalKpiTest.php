<?php

use App\Livewire\Disposal\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

test('disposal index shows the KPI band and clamps a non-whitelisted perPage', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('ໃບ ຈຳໜ່າຍ ທັງໝົດ')
        ->set('perPage', 999)
        ->assertViewHas('records', fn ($p) => $p->perPage() === 8);
});
