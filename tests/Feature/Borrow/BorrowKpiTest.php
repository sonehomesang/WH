<?php

use App\Livewire\Borrow\Index;
use App\Models\BorrowRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($this->admin);

    $mk = function (string $status, ?string $return, int $n) {
        BorrowRecord::create([
            'request_number' => 'BR'.now()->year.'-'.$n,
            'borrower_user_id' => $this->admin->id, 'borrower_email' => $this->admin->email,
            'borrower_name' => 'B'.$n, 'borrow_type' => 'new_inventory',
            'borrow_date' => now()->subDays(5)->toDateString(), 'period_days' => 5,
            'planned_return_date' => $return, 'status' => $status,
        ]);
    };
    $mk('active', now()->addDays(10)->toDateString(), 1);   // active — plenty of time
    $mk('active', now()->subDays(2)->toDateString(), 2);    // active + OVERDUE
    $mk('active', now()->addDays(2)->toDateString(), 3);    // active + DUE SOON (≤3d)
    $mk('returned', now()->subDays(1)->toDateString(), 4);  // returned
    $mk('draft', now()->addDays(5)->toDateString(), 5);     // draft (not active → not in active/overdue/due-soon)
});

test('borrow KPI band counts total / active / overdue / due-soon / returned', function () {
    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('ໃບຢືມ ທັງໝົດ')      // total surfaced in the app header (teleported)
        ->assertViewHas('kpi', function ($k) {
            return $k['total'] === 5
                && $k['active'] === 3
                && $k['overdue'] === 1
                && $k['due_soon'] === 1
                && $k['returned'] === 1;
        });
});

test('perPage limits the rows shown and rejects a non-whitelisted value', function () {
    foreach (range(6, 11) as $n) {   // beforeEach made 5 → 11 total
        BorrowRecord::create([
            'request_number' => 'BR'.now()->year.'-'.$n, 'borrower_user_id' => $this->admin->id,
            'borrower_email' => $this->admin->email, 'borrower_name' => 'X'.$n,
            'borrow_type' => 'new_inventory', 'borrow_date' => now()->toDateString(),
            'period_days' => 5, 'planned_return_date' => now()->addDays(5)->toDateString(), 'status' => 'active',
        ]);
    }

    Livewire::test(Index::class)
        ->assertViewHas('records', fn ($p) => $p->total() === 11 && $p->perPage() === 8 && count($p->items()) === 8)
        ->set('perPage', 999)   // non-whitelisted → clamped to 8
        ->assertViewHas('records', fn ($p) => $p->perPage() === 8);
});
