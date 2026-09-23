<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\LogsAuditHistory;
use App\Livewire\Concerns\MultiSoftDeletesWithReason;
use App\Models\Building;
use App\Models\BuildingType;
use App\Models\Location;
use App\Models\Room;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Facilities extends Component
{
    use LogsAuditHistory, MultiSoftDeletesWithReason;

    public ?int $selectedLocationId = null;

    public ?int $selectedBuildingId = null;

    // Modal + form
    public bool $showModal = false;

    public string $type = 'location';   // location | building | room

    public ?int $editingId = null;

    public ?int $parentId = null;       // location_id (building) | building_id (room)

    public string $name = '';

    public string $name_en = '';

    public string $address = '';

    public string $code = '';

    public ?int $buildingTypeId = null;

    public string $function = '';

    // Building-types manager
    public bool $showTypesModal = false;

    public string $typeName = '';

    public ?int $typeEditingId = null;

    public string $description = '';

    public bool $is_active = true;

    public string $changeReason = '';   // required reason when EDITING (→ audit comment + admin notify)

    public function mount(): void
    {
        abort_unless(auth()->user()->can('locations.view'), 403);
        $this->selectedLocationId = Location::orderBy('name')->value('id');
        $this->selectedBuildingId = $this->selectedLocationId
            ? Building::where('location_id', $this->selectedLocationId)->orderBy('name')->value('id')
            : null;
    }

    public function selectLocation(int $id): void
    {
        $this->selectedLocationId = $id;
        $this->selectedBuildingId = Building::where('location_id', $id)->orderBy('name')->value('id');
    }

    public function selectBuilding(int $id): void
    {
        $this->selectedBuildingId = $id;
    }

    public function newLocation(): void
    {
        $this->resetForm('location');
        $this->showModal = true;
    }

    public function editLocation(int $id): void
    {
        $m = Location::findOrFail($id);
        $this->resetForm('location');
        $this->editingId = $m->id;
        $this->name = $m->name;
        $this->name_en = $m->name_en ?? '';
        $this->address = $m->address ?? '';
        $this->description = $m->description ?? '';
        $this->is_active = (bool) $m->is_active;
        $this->showModal = true;
    }

    public function newBuilding(): void
    {
        if (! $this->selectedLocationId) {
            return;
        }
        $this->resetForm('building');
        $this->parentId = $this->selectedLocationId;
        $this->buildingTypeId = BuildingType::where('is_active', true)->orderBy('name')->value('id');
        $this->showModal = true;
    }

    public function editBuilding(int $id): void
    {
        $m = Building::findOrFail($id);
        $this->resetForm('building');
        $this->editingId = $m->id;
        $this->parentId = $m->location_id;
        $this->name = $m->name;
        $this->name_en = $m->name_en ?? '';
        $this->code = $m->code ?? '';
        $this->buildingTypeId = $m->building_type_id;
        $this->description = $m->description ?? '';
        $this->is_active = (bool) $m->is_active;
        $this->showModal = true;
    }

    public function newRoom(): void
    {
        if (! $this->selectedBuildingId) {
            return;
        }
        $this->resetForm('room');
        $this->parentId = $this->selectedBuildingId;
        $this->showModal = true;
    }

    public function editRoom(int $id): void
    {
        $m = Room::findOrFail($id);
        $this->resetForm('room');
        $this->editingId = $m->id;
        $this->parentId = $m->building_id;
        $this->name = $m->name;
        $this->function = $m->function ?? '';
        $this->description = $m->description ?? '';
        $this->is_active = (bool) $m->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $menus = ['location' => 'locations', 'building' => 'buildings', 'room' => 'rooms'];
        $menu = $menus[$this->type];
        abort_unless(auth()->user()->can("{$menu}.".($this->editingId ? 'edit' : 'create')), 403);

        // name unique within parent scope (locations unique globally)
        if ($this->type === 'location') {
            $nameRule = Rule::unique('locations', 'name')->whereNull('deleted_at')->ignore($this->editingId);
        } elseif ($this->type === 'building') {
            $nameRule = Rule::unique('buildings', 'name')->where('location_id', $this->parentId)->whereNull('deleted_at')->ignore($this->editingId);
        } else {
            $nameRule = Rule::unique('rooms', 'name')->where('building_id', $this->parentId)->whereNull('deleted_at')->ignore($this->editingId);
        }

        $rules = [
            'name' => ['required', 'string', 'max:256', $nameRule],
            'name_en' => ['nullable', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
        if ($this->type === 'location') {
            $rules['address'] = ['nullable', 'string', 'max:1000'];
        } elseif ($this->type === 'building') {
            $rules['parentId'] = ['required', 'exists:locations,id'];
            $rules['code'] = ['nullable', 'string', 'max:64'];
            $rules['buildingTypeId'] = ['required', 'exists:building_types,id'];
        } else {
            $rules['parentId'] = ['required', 'exists:buildings,id'];
            $rules['function'] = ['nullable', 'string', 'max:256'];
        }
        if ($this->editingId) {
            $rules['changeReason'] = ['required', 'string', 'min:3', 'max:500'];
        }
        $this->validate(
            $rules,
            ['changeReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ປ່ຽນແປງ.', 'changeReason.min' => 'ເຫດຜົນ ຢ່າງ ໜ້ອຍ 3 ຕົວ.'],
            ['name' => 'ຊື່', 'parentId' => 'parent']
        );

        $uid = auth()->id();
        $base = [
            'name' => $this->name,
            'name_en' => $this->name_en ?: null,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
            'updated_by' => $uid,
        ];

        if ($this->type === 'location') {
            $base['address'] = $this->address ?: null;
            if ($this->editingId) {
                $model = Location::findOrFail($this->editingId);
                $model->update($base);
            } else {
                $base['slug'] = $this->uniqueSlug($this->name, 'locations');
                $base['created_by'] = $uid;
                $model = Location::create($base);
                $this->selectedLocationId = $model->id;
                $this->selectedBuildingId = null;
            }
        } elseif ($this->type === 'building') {
            $base['location_id'] = $this->parentId;
            $base['code'] = $this->code ?: null;
            $base['building_type_id'] = $this->buildingTypeId;
            if ($this->editingId) {
                $model = Building::findOrFail($this->editingId);
                $model->update($base);
            } else {
                $base['slug'] = $this->uniqueSlug($this->name, 'buildings');
                $base['created_by'] = $uid;
                $model = Building::create($base);
                $this->selectedLocationId = $this->parentId;
                $this->selectedBuildingId = $model->id;
            }
        } else {
            $base['building_id'] = $this->parentId;
            $base['function'] = $this->function ?: null;
            if ($this->editingId) {
                $model = Room::findOrFail($this->editingId);
                $model->update($base);
            } else {
                $base['slug'] = $this->uniqueSlug($this->name, 'rooms');
                $base['created_by'] = $uid;
                $model = Room::create($base);
                $this->selectedBuildingId = $this->parentId;
            }
        }

        if ($this->editingId) {
            $this->logAudit($this->type, $model, 'update', $this->changeReason);
            $this->notifyAdmins(ucfirst($this->type), $model->name, 'update', $this->changeReason, route('settings.facilities'));
        } else {
            $this->logAudit($this->type, $model, 'create');
        }

        $this->showModal = false;
        $this->dispatch('saved');
    }

    public function toggleLocation(int $id): void
    {
        $m = Location::findOrFail($id);
        abort_unless(auth()->user()->can('locations.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
        $action = $m->is_active ? 'activate' : 'deactivate';
        $this->logAudit('location', $m, $action);
        if ($action === 'deactivate') {
            $this->notifyAdmins('Location', $m->name, 'deactivate', null, route('settings.facilities'));
        }
    }

    public function toggleBuilding(int $id): void
    {
        $m = Building::findOrFail($id);
        abort_unless(auth()->user()->can('buildings.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
        $action = $m->is_active ? 'activate' : 'deactivate';
        $this->logAudit('building', $m, $action);
        if ($action === 'deactivate') {
            $this->notifyAdmins('Building', $m->name, 'deactivate', null, route('settings.facilities'));
        }
    }

    public function toggleRoom(int $id): void
    {
        $m = Room::findOrFail($id);
        abort_unless(auth()->user()->can('rooms.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
        $action = $m->is_active ? 'activate' : 'deactivate';
        $this->logAudit('room', $m, $action);
        if ($action === 'deactivate') {
            $this->notifyAdmins('Room', $m->name, 'deactivate', null, route('settings.facilities'));
        }
    }

    // ── delete-with-reason + Deleted Log (trait MultiSoftDeletesWithReason) ──

    protected function deletableTypes(): array
    {
        return [
            'location' => ['model' => Location::class, 'perm' => 'locations', 'noun' => 'ບ່ອນຕັ້ງ (Location)'],
            'building' => ['model' => Building::class, 'perm' => 'buildings', 'noun' => 'ອາຄານ (Building)'],
            'room' => ['model' => Room::class, 'perm' => 'rooms', 'noun' => 'ຫ້ອງ (Room)'],
        ];
    }

    protected function deleteBlockReason(string $type, Model $record): ?string
    {
        return match ($type) {
            'location' => $record->buildings()->exists() ? 'ລຶບ Location ບໍ່ໄດ້ — ຍັງມີ Building ຢູ່ພາຍໃນ.' : null,
            'building' => $record->rooms()->exists() ? 'ລຶບ Building ບໍ່ໄດ້ — ຍັງມີ Room ຢູ່ພາຍໃນ.' : null,
            default => null,
        };
    }

    protected function afterDelete(string $type, Model $record): void
    {
        $this->logAudit($type, $record, 'delete', $record->deleted_reason);
        $this->notifyAdmins(ucfirst($type), $record->name, 'delete', $record->deleted_reason, route('settings.facilities'));

        if ($type === 'location' && $this->selectedLocationId === $record->id) {
            $this->selectedLocationId = Location::orderBy('name')->value('id');
            $this->selectedBuildingId = $this->selectedLocationId
                ? Building::where('location_id', $this->selectedLocationId)->orderBy('name')->value('id')
                : null;
        }
        if ($type === 'building' && $this->selectedBuildingId === $record->id) {
            $this->selectedBuildingId = Building::where('location_id', $this->selectedLocationId)->orderBy('name')->value('id');
        }
    }

    protected function afterRestore(string $type, Model $record): void
    {
        $this->logAudit($type, $record, 'restore');
        $this->notifyAdmins(ucfirst($type), $record->name, 'restore', null, route('settings.facilities'));
    }

    // ── Building-types manager ────────────────────────────────
    public function openTypesManager(): void
    {
        abort_unless(auth()->user()->canAny(['buildings.create', 'buildings.edit']), 403);
        $this->typeEditingId = null;
        $this->typeName = '';
        $this->resetValidation();
        $this->showTypesModal = true;
    }

    public function editType(int $id): void
    {
        $t = BuildingType::findOrFail($id);
        $this->typeEditingId = $t->id;
        $this->typeName = $t->name;
        $this->resetValidation();
    }

    public function saveType(): void
    {
        abort_unless(auth()->user()->can('buildings.'.($this->typeEditingId ? 'edit' : 'create')), 403);
        $this->validate([
            'typeName' => ['required', 'string', 'max:128', Rule::unique('building_types', 'name')->whereNull('deleted_at')->ignore($this->typeEditingId)],
        ], [], ['typeName' => 'ຊື່ type']);

        if ($this->typeEditingId) {
            BuildingType::findOrFail($this->typeEditingId)->update(['name' => $this->typeName, 'updated_by' => auth()->id()]);
        } else {
            BuildingType::create([
                'slug' => $this->uniqueSlug($this->typeName, 'building_types'),
                'name' => $this->typeName,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
        }
        $this->typeEditingId = null;
        $this->typeName = '';
    }

    public function toggleType(int $id): void
    {
        abort_unless(auth()->user()->canAny(['buildings.activate', 'buildings.deactivate']), 403);
        $t = BuildingType::findOrFail($id);
        $t->update(['is_active' => ! $t->is_active]);
    }

    public function deleteType(int $id): void
    {
        abort_unless(auth()->user()->can('buildings.delete'), 403);
        $t = BuildingType::findOrFail($id);
        if ($t->buildings()->exists()) {
            $this->addError('typeRow', 'ລຶບ type ບໍ່ໄດ້ — ມີ building ໃຊ້ຢູ່.');

            return;
        }
        $t->delete();
    }

    protected function resetForm(string $type): void
    {
        $this->type = $type;
        $this->editingId = null;
        $this->changeReason = '';
        $this->parentId = null;
        $this->name = '';
        $this->name_en = '';
        $this->address = '';
        $this->code = '';
        $this->buildingTypeId = null;
        $this->function = '';
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
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
        $showDelLoc = $this->showDeletedType === 'location' && $this->canManageDeletedType('location');
        $showDelBld = $this->showDeletedType === 'building' && $this->canManageDeletedType('building');
        $showDelRoom = $this->showDeletedType === 'room' && $this->canManageDeletedType('room');

        $locations = $showDelLoc
            ? Location::onlyTrashed()->with('deletedBy')->orderBy('name')->get()
            : Location::withCount('buildings')->orderBy('name')->get();

        $buildings = $this->selectedLocationId
            ? ($showDelBld
                ? Building::onlyTrashed()->with('deletedBy')->where('location_id', $this->selectedLocationId)->orderBy('name')->get()
                : Building::where('location_id', $this->selectedLocationId)->with('buildingType')->withCount('rooms')->orderBy('name')->get())
            : collect();

        $rooms = $this->selectedBuildingId
            ? ($showDelRoom
                ? Room::onlyTrashed()->with('deletedBy')->where('building_id', $this->selectedBuildingId)->orderBy('name')->get()
                : Room::where('building_id', $this->selectedBuildingId)->orderBy('name')->get())
            : collect();

        return view('livewire.settings.facilities', [
            'locations' => $locations,
            'buildings' => $buildings,
            'rooms' => $rooms,
            // ເລືອກ ຫາ ຈາກ DB ໂດຍກົງ (ບໍ່ ຂຶ້ນ ກັບ ລາຍການ ທີ່ ໂຊ — trashed/active).
            'selectedLocation' => $this->selectedLocationId ? Location::find($this->selectedLocationId) : null,
            'selectedBuilding' => $this->selectedBuildingId ? Building::find($this->selectedBuildingId) : null,
            'buildingTypes' => BuildingType::where('is_active', true)->orderBy('name')->get(),
            'allTypes' => BuildingType::orderBy('name')->get(),
            'showDelLoc' => $showDelLoc,
            'showDelBld' => $showDelBld,
            'showDelRoom' => $showDelRoom,
            // dropdown ໃນ ຟອມ ຕ້ອງ ໃຊ້ ລາຍການ ປົກກະຕິ ສະເໝີ (ບໍ່ ໃຫ້ ຕິດ trashed).
            'locationOptions' => Location::orderBy('name')->get(['id', 'name']),
            'buildingOptions' => $this->selectedLocationId
                ? Building::where('location_id', $this->selectedLocationId)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }
}
