<?php

use App\Livewire\Ansi\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
});

test('ansi index shows the KPI band and clamps a non-whitelisted perPage', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('ANSI ທັງໝົດ')
        ->set('perPage', 999)
        ->assertViewHas('records', fn ($p) => $p->perPage() === 8);
});
