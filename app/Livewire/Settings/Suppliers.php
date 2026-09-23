<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\Supplier;
use App\Models\SupplierHistory;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Suppliers extends Component
{
    use SoftDeletesWithReason;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $changeReason = '';   // required reason when EDITING (→ audit comment + admin notify)

    public string $name = '';

    public string $name_en = '';

    public string $contact_person = '';

    public string $contact_phone = '';

    public string $contact_email = '';

    public string $address = '';

    public string $tax_id = '';

    public string $payment_terms = '';

    public string $default_currency = 'LAK';

    public string $notes = '';

    public bool $is_active = true;

    public $vat_rate = null;               // null = use global

    public ?float $originalVatRate = null;

    public string $vat_reason = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('supplier.view'), 403);
    }

    public function newItem(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $m = Supplier::findOrFail($id);
        $this->editingId = $m->id;
        $this->changeReason = '';
        $this->name = $m->name;
        $this->name_en = $m->name_en ?? '';
        $this->contact_person = $m->contact_person ?? '';
        $this->contact_phone = $m->contact_phone ?? '';
        $this->contact_email = $m->contact_email ?? '';
        $this->address = $m->address ?? '';
        $this->tax_id = $m->tax_id ?? '';
        $this->payment_terms = $m->payment_terms ?? '';
        $this->default_currency = $m->default_currency;
        $this->notes = $m->notes ?? '';
        $this->is_active = (bool) $m->is_active;
        $this->vat_rate = $m->vat_rate !== null ? (float) $m->vat_rate : null;
        $this->originalVatRate = $this->vat_rate;
        $this->vat_reason = '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('supplier.'.($this->editingId ? 'edit' : 'create')), 403);

        $newRate = ($this->vat_rate === '' || $this->vat_rate === null) ? null : (float) $this->vat_rate;
        $vatChanged = $this->editingId && $newRate !== ($this->originalVatRate === null ? null : (float) $this->originalVatRate);

        $rules = [
            'name' => ['required', 'string', 'max:256', Rule::unique('suppliers', 'name')->whereNull('deleted_at')->ignore($this->editingId)],
            'name_en' => ['nullable', 'string', 'max:256'],
            'contact_person' => ['nullable', 'string', 'max:256'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'contact_email' => ['nullable', 'email', 'max:256'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'payment_terms' => ['nullable', 'string', 'max:128'],
            'default_currency' => ['required', 'in:LAK,THB,USD'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
        if ($vatChanged) {
            $rules['vat_reason'] = ['required', 'string', 'max:500'];
        }
        if ($this->editingId) {
            $rules['changeReason'] = ['required', 'string', 'min:3', 'max:500'];
        }
        $data = $this->validate(
            $rules,
            ['changeReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ປ່ຽນແປງ.', 'changeReason.min' => 'ເຫດຜົນ ຢ່າງ ໜ້ອຍ 3 ຕົວ.'],
            ['name' => 'ຊື່', 'vat_reason' => 'ເຫດຜົນ', 'vat_rate' => 'VAT']
        );

        $uid = auth()->id();
        $payload = [
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?: null,
            'contact_person' => $data['contact_person'] ?: null,
            'contact_phone' => $data['contact_phone'] ?: null,
            'contact_email' => $data['contact_email'] ?: null,
            'address' => $data['address'] ?: null,
            'tax_id' => $data['tax_id'] ?: null,
            'payment_terms' => $data['payment_terms'] ?: null,
            'default_currency' => $data['default_currency'],
            'vat_rate' => $newRate,
            'notes' => $data['notes'] ?: null,
            'is_active' => $this->is_active,
            'updated_by' => $uid,
        ];

        if ($this->editingId) {
            $supplier = Supplier::findOrFail($this->editingId);
            $supplier->update($payload);
            if ($vatChanged) {
                $supplier->vatChanges()->create([
                    'old_rate' => $this->originalVatRate,
                    'new_rate' => $newRate,
                    'reason' => $this->vat_reason ?: null,
                    'changed_by' => $uid,
                    'changed_at' => now(),
                ]);
                $this->originalVatRate = $newRate;
                $this->vat_reason = '';
            }
        } else {
            $payload['slug'] = $this->uniqueSlug($data['name']);
            $payload['created_by'] = $uid;
            $supplier = Supplier::create($payload);
        }

        if ($this->editingId) {
            $this->logAudit($supplier, 'update', $this->changeReason);
            $this->notifyAdmins($supplier, 'update', $this->changeReason);
        } else {
            $this->logAudit($supplier, 'create');
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggle(int $id): void
    {
        $m = Supplier::findOrFail($id);
        abort_unless(auth()->user()->can('supplier.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
        $action = $m->is_active ? 'activate' : 'deactivate';
        $this->logAudit($m, $action);
        if ($action === 'deactivate') {
            $this->notifyAdmins($m, 'deactivate');
        }
    }

    // ── audit history (append-only, → Settings › Audit) + admin notifications ──
    protected function logAudit(Supplier $item, string $action, ?string $comment = null): void
    {
        $actor = auth()->user();
        SupplierHistory::create([
            'record_id' => $item->id,
            'action' => $action,
            'status' => $item->is_active ? 'active' : 'inactive',
            'user_id' => $actor?->id,
            'user_name' => $actor?->display_name ?: $actor?->email,
            'role' => $actor?->roles->first()?->name,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    protected function afterDeleted(Model $record): void
    {
        $this->logAudit($record, 'delete', $record->deleted_reason);
        $this->notifyAdmins($record, 'delete', $record->deleted_reason);
    }

    protected function afterRestored(Model $record): void
    {
        $this->logAudit($record, 'restore');
        $this->notifyAdmins($record, 'restore');
    }

    /** Alert other admins (never the actor) so multi-level management can see who changed what. */
    protected function notifyAdmins(Supplier $item, string $action, ?string $reason = null): void
    {
        $actor = auth()->user();
        $adminIds = User::query()->where('status', 'active')->where('id', '!=', $actor?->id)
            ->where(fn ($q) => $q
                ->whereHas('roles', fn ($r) => $r->whereIn('name', ['admin', 'super_admin']))
                ->orWhere('is_super_admin', true))
            ->pluck('id')->all();
        if (empty($adminIds)) {
            return;
        }
        $verb = ['update' => 'ແກ້ໄຂ', 'deactivate' => 'ປິດໃຊ້', 'delete' => 'ລຶບ', 'restore' => 'ກູ້ຄືນ'][$action] ?? $action;
        $who = $actor?->display_name ?: $actor?->email;
        $msg = $who.' · '.$item->name.($reason ? ' · ເຫດຜົນ: '.$reason : '');
        app(NotificationService::class)->notifyMany($adminIds, 'info', "Supplier: {$verb} {$item->name}", $msg, route('settings.suppliers'));
    }

    // ── ລຶບ-ດ້ວຍ-ເຫດຜົນ + Deleted Log (trait SoftDeletesWithReason) ──
    protected function deleteModelClass(): string
    {
        return Supplier::class;
    }

    protected function deletePermission(): string
    {
        return 'supplier.delete';
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->name;
    }

    protected function deleteNoun(): string
    {
        return 'ຜູ້ສະໜອງ';
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->changeReason = '';
        $this->name = '';
        $this->name_en = '';
        $this->contact_person = '';
        $this->contact_phone = '';
        $this->contact_email = '';
        $this->address = '';
        $this->tax_id = '';
        $this->payment_terms = '';
        $this->default_currency = 'LAK';
        $this->notes = '';
        $this->is_active = true;
        $this->vat_rate = null;
        $this->originalVatRate = null;
        $this->vat_reason = '';
        $this->resetValidation();
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'supplier';
        $original = $slug;
        $i = 2;
        while (DB::table('suppliers')->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $items = Supplier::query()
            ->when($this->showDeleted && $this->canManageDeleted(), fn ($q) => $q->onlyTrashed()->with('deletedBy'))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")
                ->orWhere('name_en', 'like', "%{$this->search}%")
                ->orWhere('contact_person', 'like', "%{$this->search}%")))
            ->orderBy('name')->get();

        return view('livewire.settings.suppliers', [
            'items' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
        ]);
    }
}
