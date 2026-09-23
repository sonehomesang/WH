<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\LogsAuditHistory;
use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\Uom as UomModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Uom extends Component
{
    use LogsAuditHistory, SoftDeletesWithReason;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $name_en = '';

    public bool $is_active = true;

    public string $changeReason = '';   // required reason when EDITING (→ audit comment + admin notify)

    public function mount(): void
    {
        abort_unless(auth()->user()->can('units.view'), 403);
    }

    public function newItem(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $m = UomModel::findOrFail($id);
        $this->editingId = $m->id;
        $this->changeReason = '';
        $this->name = $m->name;
        $this->name_en = $m->name_en ?? '';
        $this->is_active = (bool) $m->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('units.'.($this->editingId ? 'edit' : 'create')), 403);

        $rules = [
            'name' => ['required', 'string', 'max:64', Rule::unique('uoms', 'name')->whereNull('deleted_at')->ignore($this->editingId)],
            'name_en' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ];
        if ($this->editingId) {
            $rules['changeReason'] = ['required', 'string', 'min:3', 'max:500'];
        }
        $data = $this->validate(
            $rules,
            ['changeReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ປ່ຽນແປງ.', 'changeReason.min' => 'ເຫດຜົນ ຢ່າງ ໜ້ອຍ 3 ຕົວ.'],
            ['name' => 'ຊື່']
        );

        $uid = auth()->id();
        $payload = [
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?: null,
            'is_active' => $this->is_active,
            'updated_by' => $uid,
        ];

        if ($this->editingId) {
            $uom = UomModel::findOrFail($this->editingId);
            $uom->update($payload);
            $this->logAudit('uom', $uom, 'update', $this->changeReason);
            $this->notifyAdmins('UoM', $uom->name, 'update', $this->changeReason, route('settings.uom'));
        } else {
            $payload['slug'] = $this->uniqueSlug($data['name']);
            $payload['created_by'] = $uid;
            $uom = UomModel::create($payload);
            $this->logAudit('uom', $uom, 'create');
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggle(int $id): void
    {
        $m = UomModel::findOrFail($id);
        abort_unless(auth()->user()->can('units.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
        $action = $m->is_active ? 'activate' : 'deactivate';
        $this->logAudit('uom', $m, $action);
        if ($action === 'deactivate') {
            $this->notifyAdmins('UoM', $m->name, 'deactivate', null, route('settings.uom'));
        }
    }

    // ── ລຶບ-ດ້ວຍ-ເຫດຜົນ + Deleted Log (trait SoftDeletesWithReason) ──
    protected function deleteModelClass(): string
    {
        return UomModel::class;
    }

    protected function afterDeleted(Model $record): void
    {
        $this->logAudit('uom', $record, 'delete', $record->deleted_reason);
        $this->notifyAdmins('UoM', $record->name, 'delete', $record->deleted_reason, route('settings.uom'));
    }

    protected function afterRestored(Model $record): void
    {
        $this->logAudit('uom', $record, 'restore');
        $this->notifyAdmins('UoM', $record->name, 'restore', null, route('settings.uom'));
    }

    protected function deletePermission(): string
    {
        return 'units.delete';
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->name;
    }

    protected function deleteNoun(): string
    {
        return 'ໜ່ວຍວັດ';
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->changeReason = '';
        $this->name = '';
        $this->name_en = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'uom';
        $original = $slug;
        $i = 2;
        while (DB::table('uoms')->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $items = UomModel::query()
            ->when($this->showDeleted && $this->canManageDeleted(), fn ($q) => $q->onlyTrashed()->with('deletedBy'))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('name_en', 'like', "%{$this->search}%")))
            ->orderBy('name')->get();

        return view('livewire.settings.uom', [
            'items' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
        ]);
    }
}
