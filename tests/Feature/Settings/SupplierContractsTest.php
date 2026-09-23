<?php

use App\Livewire\Settings\SupplierDetail;
use App\Livewire\Settings\Suppliers;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\SupplierVatChange;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aSupplier(): Supplier
{
    return Supplier::create(['slug' => 'abc', 'name' => 'ABC', 'default_currency' => 'LAK', 'is_active' => true]);
}

test('super admin can add a contract record', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $s = aSupplier();

    Livewire::test(SupplierDetail::class, ['supplier' => $s])
        ->call('newContract')->set('contract_number', 'WIS-2026-SUP01')->set('status', 'active')->call('saveContract')->assertHasNoErrors();

    expect($s->contracts()->where('contract_number', 'WIS-2026-SUP01')->exists())->toBeTrue();
});

test('contract expiry before effective is rejected', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $s = aSupplier();

    Livewire::test(SupplierDetail::class, ['supplier' => $s])
        ->call('newContract')->set('contract_number', 'C2')->set('effective_date', '2026-12-31')->set('expiry_date', '2026-01-01')
        ->call('saveContract')->assertHasErrors(['expiry_date']);
});

test('resolveVat uses supplier rate over global', function () {
    Setting::put('vat', ['rate' => 10, 'enabled' => true]);
    $s = aSupplier();
    $s->update(['vat_rate' => 7]);

    $vat = $s->fresh()->resolveVat();
    expect($vat['rate'])->toEqual(7.0);
    expect($vat['source'])->toBe('supplier');
});

test('resolveVat falls back to global when supplier rate is null', function () {
    Setting::put('vat', ['rate' => 10, 'enabled' => true]);
    $s = aSupplier();

    $vat = $s->resolveVat();
    expect($vat['rate'])->toEqual(10.0);
    expect($vat['source'])->toBe('global');
});

test('deleting a contract requires a reason and can be restored', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $s = aSupplier();
    $c = $s->contracts()->create(['contract_number' => 'CT-1', 'status' => 'active']);

    Livewire::test(SupplierDetail::class, ['supplier' => $s])
        ->call('openDelete', $c->id)
        ->call('deleteRecord')
        ->assertHasErrors('deleteReason');

    Livewire::test(SupplierDetail::class, ['supplier' => $s])
        ->call('openDelete', $c->id)
        ->set('deleteReason', 'ຍົກເລີກ ສັນຍາ')
        ->call('deleteRecord')
        ->assertHasNoErrors();

    $c->refresh();
    expect($c->trashed())->toBeTrue();
    expect($c->deleted_reason)->toBe('ຍົກເລີກ ສັນຍາ');
    expect($c->deleted_by)->toBe($admin->id);

    Livewire::test(SupplierDetail::class, ['supplier' => $s])
        ->call('toggleDeleted')
        ->assertSee('CT-1')
        ->call('restore', $c->id);

    expect(SupplierContract::whereKey($c->id)->first()->trashed())->toBeFalse();
});

test('a contract from another supplier cannot be deleted through the wrong page', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin);
    $a = aSupplier();
    $b = Supplier::create(['slug' => 'bee', 'name' => 'Bee', 'default_currency' => 'LAK', 'is_active' => true]);
    $foreign = $a->contracts()->create(['contract_number' => 'A-1', 'status' => 'active']);

    // opening supplier B's page and trying to delete A's contract is blocked by the scope guard
    Livewire::test(SupplierDetail::class, ['supplier' => $b])
        ->call('openDelete', $foreign->id)
        ->assertForbidden();

    expect(SupplierContract::whereKey($foreign->id)->first()->trashed())->toBeFalse();
});

test('changing supplier VAT requires a reason and logs the change', function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true]));
    $s = aSupplier();

    $c = Livewire::test(Suppliers::class)->call('editItem', $s->id)->set('vat_rate', 7);
    $c->call('save')->assertHasErrors(['vat_reason']);

    $c->set('vat_reason', 'ສັນຍາໃໝ່ 2026')->set('changeReason', 'ປັບ VAT')->call('save')->assertHasNoErrors();

    expect($s->fresh()->vat_rate)->toEqual(7.0);
    expect(SupplierVatChange::where('supplier_id', $s->id)->where('new_rate', 7)->exists())->toBeTrue();
});
