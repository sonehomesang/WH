<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\LogsAuditHistory;
use App\Livewire\Concerns\MultiSoftDeletesWithReason;
use App\Models\Department;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Organization extends Component
{
    use LogsAuditHistory, MultiSoftDeletesWithReason;

    public ?int $selectedUnitId = null;

    // Modal state
    public bool $showModal = false;

    public string $type = 'unit';          // 'unit' | 'department'

    public ?int $editingId = null;

    // Form fields
    public string $name = '';

    public string $name_en = '';

    public string $description = '';

    public bool $is_active = true;

    public ?int $unitId = null;            // parent Org Unit (department form)

    public string $changeReason = '';      // required reason when EDITING (→ audit comment + admin notify)

    public function mount(): void
    {
        abort_unless(auth()->user()->can('units.view'), 403);
        $this->selectedUnitId = Unit::orderBy('name')->value('id');
    }

    public function selectUnit(int $id): void
    {
        $this->selectedUnitId = $id;
    }

    public function newUnit(): void
    {
        $this->resetForm('unit');
        $this->showModal = true;
    }

    public function editUnit(int $id): void
    {
        $this->fillForm('unit', Unit::findOrFail($id));
    }

    public function newDepartment(): void
    {
        if (! $this->selectedUnitId) {
            return;
        }
        $this->resetForm('department');
        $this->unitId = $this->selectedUnitId;
        $this->showModal = true;
    }

    public function editDepartment(int $id): void
    {
        $this->fillForm('department', Department::findOrFail($id));
    }

    public function save(): void
    {
        $menu = $this->type === 'unit' ? 'units' : 'departments';
        abort_unless(auth()->user()->can("{$menu}.".($this->editingId ? 'edit' : 'create')), 403);

        $nameRule = $this->type === 'unit'
            ? Rule::unique('units', 'name')->whereNull('deleted_at')->ignore($this->editingId)
            : Rule::unique('departments', 'name')->where('unit_id', $this->unitId)->whereNull('deleted_at')->ignore($this->editingId);

        $rules = [
            'name' => ['required', 'string', 'max:256', $nameRule],
            'name_en' => ['nullable', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
        if ($this->type === 'department') {
            $rules['unitId'] = ['required', 'integer', 'exists:units,id'];
        }
        if ($this->editingId) {
            $rules['changeReason'] = ['required', 'string', 'min:3', 'max:500'];
        }
        $data = $this->validate(
            $rules,
            ['changeReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ປ່ຽນແປງ.', 'changeReason.min' => 'ເຫດຜົນ ຢ່າງ ໜ້ອຍ 3 ຕົວ.'],
            ['name' => 'ຊື່', 'unitId' => 'ໜ່ວຍງານ']
        );

        $uid = auth()->id();
        $payload = [
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?: null,
            'description' => $data['description'] ?: null,
            'is_active' => $this->is_active,
            'updated_by' => $uid,
        ];

        if ($this->type === 'unit') {
            if ($this->editingId) {
                $model = Unit::findOrFail($this->editingId);
                $model->update($payload);
            } else {
                $payload['slug'] = $this->uniqueSlug($data['name'], 'units');
                $payload['created_by'] = $uid;
                $model = Unit::create($payload);
                $this->selectedUnitId = $model->id;
            }
        } else {
            $payload['unit_id'] = $this->unitId;
            if ($this->editingId) {
                $model = Department::findOrFail($this->editingId);
                $model->update($payload);
            } else {
                $payload['slug'] = $this->uniqueSlug($data['name'], 'departments');
                $payload['created_by'] = $uid;
                $model = Department::create($payload);
            }
            $this->selectedUnitId = $this->unitId;
        }

        if ($this->editingId) {
            $this->logAudit($this->type, $model, 'update', $this->changeReason);
            $this->notifyAdmins(ucfirst($this->type), $model->name, 'update', $this->changeReason, route('settings.organization'));
        } else {
            $this->logAudit($this->type, $model, 'create');
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggleUnit(int $id): void
    {
        $unit = Unit::findOrFail($id);
        abort_unless(auth()->user()->can('units.'.($unit->is_active ? 'deactivate' : 'activate')), 403);
        $unit->update(['is_active' => ! $unit->is_active, 'updated_by' => auth()->id()]);
        $action = $unit->is_active ? 'activate' : 'deactivate';
        $this->logAudit('unit', $unit, $action);
        if ($action === 'deactivate') {
            $this->notifyAdmins('Unit', $unit->name, 'deactivate', null, route('settings.organization'));
        }
    }

    public function toggleDepartment(int $id): void
    {
        $dept = Department::findOrFail($id);
        abort_unless(auth()->user()->can('departments.'.($dept->is_active ? 'deactivate' : 'activate')), 403);
        $dept->update(['is_active' => ! $dept->is_active, 'updated_by' => auth()->id()]);
        $action = $dept->is_active ? 'activate' : 'deactivate';
        $this->logAudit('department', $dept, $action);
        if ($action === 'deactivate') {
            $this->notifyAdmins('Department', $dept->name, 'deactivate', null, route('settings.organization'));
        }
    }

    // ── delete-with-reason + Deleted Log (trait MultiSoftDeletesWithReason) ──

    protected function deletableTypes(): array
    {
        return [
            'unit' => ['model' => Unit::class, 'perm' => 'units', 'noun' => 'ໜ່ວຍງານ (Unit)'],
            'department' => ['model' => Department::class, 'perm' => 'departments', 'noun' => 'ພະແນກ (Department)'],
        ];
    }

    protected function deleteBlockReason(string $type, Model $record): ?string
    {
        if ($type === 'unit' && $record->departments()->exists()) {
            return 'ລຶບ Unit ບໍ່ໄດ້ — ຍັງມີ Department ຢູ່ພາຍໃນ.';
        }

        return null;
    }

    protected function afterDelete(string $type, Model $record): void
    {
        $this->logAudit($type, $record, 'delete', $record->deleted_reason);
        $this->notifyAdmins(ucfirst($type), $record->name, 'delete', $record->deleted_reason, route('settings.organization'));

        if ($type === 'unit' && $this->selectedUnitId === $record->id) {
            $this->selectedUnitId = Unit::orderBy('name')->value('id');
        }
    }

    protected function afterRestore(string $type, Model $record): void
    {
        $this->logAudit($type, $record, 'restore');
        $this->notifyAdmins(ucfirst($type), $record->name, 'restore', null, route('settings.organization'));
    }

    protected function resetForm(string $type): void
    {
        $this->type = $type;
        $this->editingId = null;
        $this->changeReason = '';
        $this->name = '';
        $this->name_en = '';
        $this->description = '';
        $this->is_active = true;
        $this->unitId = null;
        $this->resetValidation();
    }

    protected function fillForm(string $type, Unit|Department $model): void
    {
        $this->type = $type;
        $this->editingId = $model->id;
        $this->changeReason = '';
        $this->name = $model->name;
        $this->name_en = $model->name_en ?? '';
        $this->description = $model->description ?? '';
        $this->is_active = (bool) $model->is_active;
        $this->unitId = $type === 'department' ? $model->unit_id : null;
        $this->resetValidation();
        $this->showModal = true;
    }

    protected function uniqueSlug(string $base, string $table): string
    {
        $slug = Str::slug($base) ?: 'item';
        $original = $slug;
        $i = 2;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $showDelUnits = $this->showDeletedType === 'unit' && $this->canManageDeletedType('unit');
        $showDelDepts = $this->showDeletedType === 'department' && $this->canManageDeletedType('department');

        $units = $showDelUnits
            ? Unit::onlyTrashed()->with('deletedBy')->orderBy('name')->get()
            : Unit::withCount('departments')->orderBy('name')->get();

        // ເລືອກ unit ຫາ ຈາກ ຖານ ຂໍ້ມູນ ໂດຍກົງ (ບໍ່ ຂຶ້ນ ກັບ ລາຍການ ທີ່ ໂຊ — trashed/active).
        $selectedUnit = $this->selectedUnitId ? Unit::find($this->selectedUnitId) : null;

        $departments = $this->selectedUnitId
            ? ($showDelDepts
                ? Department::onlyTrashed()->with('deletedBy')->where('unit_id', $this->selectedUnitId)->orderBy('name')->get()
                : Department::where('unit_id', $this->selectedUnitId)->orderBy('name')->get())
            : collect();

        // dropdown ໃນ ຟອມ department ຕ້ອງ ໃຊ້ unit ປົກກະຕິ ສະເໝີ (ບໍ່ ໃຫ້ ຕິດ trashed).
        $unitOptions = Unit::orderBy('name')->get(['id', 'name']);

        return view('livewire.settings.organization', compact('units', 'departments', 'selectedUnit', 'showDelUnits', 'showDelDepts', 'unitOptions'));
    }
}
