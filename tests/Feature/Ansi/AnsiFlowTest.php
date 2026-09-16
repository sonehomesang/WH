<?php

use App\Livewire\Ansi\Create;
use App\Models\AnsiApplication;
use App\Models\User;
use App\Services\AnsiService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->originator = User::factory()->create(['status' => 'active']);
    $this->hos = User::factory()->create(['status' => 'active']);
    $this->manager = User::factory()->create(['status' => 'active']);
    $this->warehouse = User::factory()->create(['status' => 'active', 'is_super_admin' => true]);
    $this->originator->givePermissionTo('ansi.create', 'ansi.view');
});

function draftFor($originator, $hos, $manager): AnsiApplication
{
    return app(AnsiService::class)->createDraft([
        'hos_user_id' => $hos->id, 'manager_user_id' => $manager->id,
        'app_date' => now()->toDateString(), 'purpose' => 'test',
        'items' => [['description' => 'CONTACT SOCKET 10A', 'qty_order' => 600, 'unit' => 'ea', 'stock' => true, 'special_storage' => 'Air Cond room']],
    ], $originator);
}

test('createDraft builds an application with items and an auto summary', function () {
    $app = draftFor($this->originator, $this->hos, $this->manager);
    expect($app->status)->toBe('draft');
    expect($app->items)->toHaveCount(1);
    expect($app->summary_items)->toContain('CONTACT SOCKET 10A');
    expect($app->request_number)->toStartWith('ANSI-');
    expect($app->originator_user_id)->toBe($this->originator->id);
});

test('the full approval chain runs Originator -> HoS -> Manager -> Warehouse -> completed', function () {
    $svc = app(AnsiService::class);
    $app = draftFor($this->originator, $this->hos, $this->manager);

    $svc->submit($app, $this->originator);
    expect($app->refresh()->status)->toBe('pending_hos');

    $svc->endorse($app, $this->hos);
    expect($app->refresh()->status)->toBe('pending_manager');

    $svc->approve($app, $this->manager);
    expect($app->refresh()->status)->toBe('pending_warehouse');

    $svc->warehouseDone($app, $this->warehouse, [
        'item_numbers' => [$app->items->first()->id => 'INV-0001'], 'pr_number' => 'PR-77',
    ]);
    $app->refresh();
    expect($app->status)->toBe('completed');
    expect($app->pr_number)->toBe('PR-77');
    expect($app->items->first()->item_number)->toBe('INV-0001');
});

test('warehouse completion mirrors each item into the Inventory master (v2 hook)', function () {
    $svc = app(AnsiService::class);
    $app = draftFor($this->originator, $this->hos, $this->manager);
    $svc->submit($app, $this->originator);
    $svc->endorse($app, $this->hos);
    $svc->approve($app, $this->manager);

    $itemId = $app->items->first()->id;
    $svc->warehouseDone($app, $this->warehouse, [
        'item_numbers' => [$itemId => 'MAT-9001'], 'pr_number' => 'PR-1',
    ]);

    $inv = App\Models\InventoryItem::where('slug', 'MAT-9001')->first();
    expect($inv)->not->toBeNull();
    expect($inv->name)->toBe('CONTACT SOCKET 10A');   // derived from the ANSI description
    expect($inv->quantity)->toBe(0);                  // master created empty
    expect($inv->unit)->toBe('ea');
    expect($inv->department_id)->toBe($app->owner_dept_id);
    expect($app->items->first()->fresh()->created_inventory_id)->toBe($inv->id);
});

test('a duplicate Material No. links to the existing item instead of erroring', function () {
    $svc = app(AnsiService::class);

    // First application creates MAT-DUP in Inventory.
    $a = draftFor($this->originator, $this->hos, $this->manager);
    $svc->submit($a, $this->originator);
    $svc->endorse($a, $this->hos);
    $svc->approve($a, $this->manager);
    $svc->warehouseDone($a, $this->warehouse, ['item_numbers' => [$a->items->first()->id => 'MAT-DUP']]);

    // Second application reuses the same Material No.
    $b = draftFor($this->originator, $this->hos, $this->manager);
    $svc->submit($b, $this->originator);
    $svc->endorse($b, $this->hos);
    $svc->approve($b, $this->manager);
    $svc->warehouseDone($b, $this->warehouse, ['item_numbers' => [$b->items->first()->id => 'MAT-DUP']]);

    // Only ONE inventory row exists, and both ANSI items point at it.
    expect(App\Models\InventoryItem::where('slug', 'MAT-DUP')->count())->toBe(1);
    $invId = App\Models\InventoryItem::where('slug', 'MAT-DUP')->value('id');
    expect($a->items->first()->fresh()->created_inventory_id)->toBe($invId);
    expect($b->items->first()->fresh()->created_inventory_id)->toBe($invId);
});

test('only the assigned HoS can endorse', function () {
    $svc = app(AnsiService::class);
    $app = draftFor($this->originator, $this->hos, $this->manager);
    $svc->submit($app, $this->originator);

    expect(fn () => $svc->endorse($app->refresh(), $this->manager))->toThrow(ValidationException::class);
    expect($app->refresh()->status)->toBe('pending_hos'); // unchanged
});

test('reject returns to the originator with a reason', function () {
    $svc = app(AnsiService::class);
    $app = draftFor($this->originator, $this->hos, $this->manager);
    $svc->submit($app, $this->originator);

    $svc->reject($app->refresh(), $this->hos, 'hos', 'Duplicate of an existing item');
    $app->refresh();
    expect($app->status)->toBe('rejected');
    expect($app->reject_stage)->toBe('hos');
    expect($app->reject_reason)->toContain('Duplicate');
});

test('submit is blocked without HoS/Manager or items', function () {
    $svc = app(AnsiService::class);
    $app = app(AnsiService::class)->createDraft(['app_date' => now()->toDateString(), 'items' => []], $this->originator);
    expect(fn () => $svc->submit($app, $this->originator))->toThrow(ValidationException::class);
});

test('the Create component saves a draft and submits it', function () {
    $this->actingAs($this->originator);
    Livewire::test(Create::class)
        ->set('hos_user_id', $this->hos->id)
        ->set('manager_user_id', $this->manager->id)
        ->set('items.0.description', 'BEARING 6203')
        ->set('items.0.qty_order', 10)
        ->call('save', true);

    $app = AnsiApplication::where('originator_user_id', $this->originator->id)->first();
    expect($app)->not->toBeNull();
    expect($app->status)->toBe('pending_hos');
});
