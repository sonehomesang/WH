<?php

namespace App\Livewire\Equipment;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentInspection;
use App\Models\EquipmentPhoto;
use App\Models\InspectionTemplate;
use App\Models\Uom;
use App\Models\User;
use App\Support\ConditionStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    /** ຮູບ ສູງສຸດ ຕໍ່ ເຄື່ອງ. */
    public const MAX_PHOTOS = 3;

    /** ຮູບ ຫຼັກຖານ ສູງສຸດ ຕໍ່ ການ ກວດ. */
    public const MAX_INS_PHOTOS = 4;

    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    /** filter ຕາມ owner/ພະແນກ (department_id). */
    public string $departmentFilter = '';

    public int $perPage = 8;               // rows per page (whitelisted in render) — no inner scroll

    // Modal + form (register)
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $asset_code = '';

    public string $fixed_asset_no = '';

    public string $name = '';

    public string $category = '';

    /** Owner = ພະແນກ ເຈົ້າຂອງ. */
    public ?int $department_id = null;

    /** Loc-Bin = ບ່ອນ ວາງ/ຊັ້ນ ໃນ ສາງ. */
    public string $loc_bin = '';

    public string $brand_model = '';

    public string $serial_no = '';

    public string $location = '';

    public string $responsible_name = '';

    /** ຜູ້ຮັບຜິດຊອບ = ລິ້ງ ຫາ ຜູ້ໃຊ້. */
    public ?int $responsible_user_id = null;

    // ຈຳນວນ ລວມ + ຫົວໜ່ວຍ + ຈຳນວນ ທີ່ ຊ່ອມ/ຢຸດ (ໃຊ້ງານ = quantity − repair − retired)
    public int $quantity = 1;

    public ?int $unit_id = null;

    public int $qtyRepair = 0;

    public int $qtyRetired = 0;

    public string $condition_status = 'in_service';

    public ?string $purchase_date = null;

    public string $notes = '';

    /** @var array ຮູບ ໃໝ່ ທີ່ ອັບໂຫຼດ (TemporaryUploadedFile) — ຈາກ ກ້ອງ ຫຼື ແກເລີຣີ */
    public array $newPhotos = [];

    /** @var array<int,array{id:int,url:string}> ຮູບ ທີ່ ບັນທຶກ ແລ້ວ (ຕອນ ແກ້ໄຂ) */
    public array $existingPhotos = [];

    // ── ແທັບ 2: ການ ກວດກາ (Inspection) ──
    public bool $showInspectionModal = false;

    public string $insSearch = '';

    public ?int $insEquipmentId = null;

    public string $insEquipmentLabel = '';

    public int $insEquipmentQty = 1;

    public ?string $insDate = null;

    public string $insInspector = '';

    public string $insResult = 'pass';

    public string $insNotes = '';

    public ?string $insNextDue = null;

    /** ຮູບ ຫຼັກຖານ ໃໝ່ ທີ່ ອັບໂຫຼດ (ກ້ອງ/ແກເລີຣີ) — ສູງສຸດ MAX_INS_PHOTOS. */
    public array $insPhotos = [];

    /** ຮູບ ຫຼັກຖານ ທີ່ ບັນທຶກ ແລ້ວ (ຕອນ ແກ້ໄຂ) — array ຂອງ path. */
    public array $insExistingPhotos = [];

    public bool $insUpdateStatus = false;

    public int $insRepair = 0;

    public int $insRetired = 0;

    public ?int $insTemplateId = null;

    /** ປະເພດ ນ້ຳມັນ ຕອນ ກວດ: '' | 'ev' | 'engine'. */
    public string $insFuelType = '';

    /** ຮອບ ການ ກວດ: '' | pre_use | monthly | quarterly | semi_annual | annual. */
    public string $insFrequency = '';

    /** @var array<int,array{label:string,status:string,note:string}> */
    public array $insChecklist = [];

    public ?int $editingInspectionId = null;

    public ?int $viewingInspectionId = null;

    /** ເຄື່ອງ ທີ່ ເປີດ ໜ້າຕ່າງ ປະຫວັດ ການ ກວດເຊັກ. */
    public ?int $historyEquipmentId = null;

    /** ເຄື່ອງ ທີ່ ເປີດ ໜ້າຕ່າງ ລາຍລະອຽດ (read-only). */
    public ?int $viewingItemId = null;

    /** ສະແດງ Deleted Log (onlyTrashed) ແທນ ລາຍການ ປົກກະຕິ. */
    public bool $showDeleted = false;

    /** ເຄື່ອງ ທີ່ ກຳລັງ ຈະ ລຶບ (ເປີດ modal ຖາມ ເຫດຜົນ). */
    public ?int $deletingId = null;

    /** ເຫດຜົນ ການ ລຶບ (ບັງຄັບ). */
    public string $deleteReason = '';

    // ── ຄົ້ນຫາ + filter ໃບ ກວດ (tab 2, ຄື ແທັບ ທະບຽນເຄື່ອງ) ──
    public string $inspSearch = '';

    public string $inspResultFilter = '';

    public string $inspCategoryFilter = '';

    /** Deleted Log ສຳລັບ ໃບ ກວດ (tab 2). */
    public bool $showDeletedInspections = false;

    /** ໃບ ກວດ ທີ່ ກຳລັງ ຈະ ລຶບ (ເປີດ modal ຖາມ ເຫດຜົນ). */
    public ?int $deletingInsId = null;

    /** ເຫດຜົນ ການ ລຶບ ໃບ ກວດ (ບັງຄັບ). */
    public string $insDeleteReason = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /** ຖືກ ຈຳກັດ ໃຫ້ ເບິ່ງ/ຈັດການ ສະເພາະ ພະແນກ ຕົນ ບໍ (department_admin). */
    protected function deptScoped(): bool
    {
        return auth()->user()->equipmentDepartmentScoped();
    }

    /** ID ພະແນກ ຂອງ ຜູ້ ໃຊ້ ປັດຈຸບັນ. */
    protected function userDeptId(): ?int
    {
        return auth()->user()->department_id;
    }

    /** ກັນ ບໍ່ ໃຫ້ department_admin ແຕະ ເຄື່ອງ ພະແນກ ອື່ນ. */
    protected function guardDept(Equipment $e): void
    {
        if ($this->deptScoped() && $e->department_id !== $this->userDeptId()) {
            abort(403);
        }
    }

    public function newItem(): void
    {
        $this->resetForm();
        // department_admin: ບັງຄັບ Owner = ພະແນກ ຕົນ ໄວ້ ລ່ວງໜ້າ.
        if ($this->deptScoped()) {
            $this->department_id = $this->userDeptId();
        }
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        $e = Equipment::findOrFail($id);
        $this->guardDept($e);
        $this->viewingItemId = null;   // ປິດ ໜ້າຕ່າງ ລາຍລະອຽດ ຖ້າ ເປີດ ຢູ່
        $this->editingId = $e->id;
        $this->asset_code = $e->asset_code;
        $this->fixed_asset_no = $e->fixed_asset_no ?? '';
        $this->name = $e->name;
        $this->category = $e->category ?? '';
        $this->department_id = $e->department_id;
        $this->loc_bin = $e->loc_bin ?? '';
        $this->brand_model = $e->brand_model ?? '';
        $this->serial_no = $e->serial_no ?? '';
        $this->location = $e->location ?? '';
        $this->responsible_name = $e->responsible_name ?? '';
        $this->responsible_user_id = $e->responsible_user_id;
        $this->quantity = $e->quantity ?: 1;
        $this->unit_id = $e->unit_id;
        $b = $e->statusBreakdown();
        $this->qtyRepair = $b['repair'];
        $this->qtyRetired = $b['retired'];
        $this->condition_status = $e->condition_status ?? 'in_service';
        $this->purchase_date = $e->purchase_date?->toDateString();
        $this->notes = $e->notes ?? '';
        $this->existingPhotos = $e->photos->map(fn ($p) => ['id' => $p->id, 'url' => Storage::url($p->path)])->all();
        $this->newPhotos = [];
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.'.($this->editingId ? 'edit' : 'create')), 403);

        // department_admin ແກ້ ເຄື່ອງ ພະແນກ ອື່ນ ບໍ່ ໄດ້.
        if ($this->editingId) {
            $this->guardDept(Equipment::findOrFail($this->editingId));
        }

        $data = $this->validate([
            'asset_code' => ['required', 'string', 'max:10', Rule::unique('equipment', 'asset_code')->ignore($this->editingId)],
            'fixed_asset_no' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:256'],
            'category' => ['nullable', 'string', 'max:128'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'loc_bin' => ['nullable', 'string', 'max:64'],
            'brand_model' => ['nullable', 'string', 'max:256'],
            'serial_no' => ['nullable', 'string', 'max:128'],
            'location' => ['nullable', 'string', 'max:128'],
            'responsible_name' => ['nullable', 'string', 'max:128'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_id' => ['nullable', 'exists:uoms,id'],
            'qtyRepair' => ['integer', 'min:0'],
            'qtyRetired' => ['integer', 'min:0'],
            'condition_status' => ['required', ConditionStatus::rule()],
            'purchase_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'newPhotos' => ['array', 'max:'.self::MAX_PHOTOS],
            'newPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'asset_code.unique' => 'ລະຫັດເຄື່ອງ ນີ້ ມີ ຢູ່ ແລ້ວ — ຫ້າມ ຊ້ຳ.',
            'asset_code.max' => 'ລະຫັດເຄື່ອງ ບໍ່ ເກີນ 10 ຕົວ.',
            'asset_code.required' => 'ກະລຸນາ ໃສ່ ລະຫັດເຄື່ອງ.',
        ], ['newPhotos.*' => 'ຮູບ']);

        if (count($this->existingPhotos) + count($this->newPhotos) > self::MAX_PHOTOS) {
            $this->addError('newPhotos', 'ຮູບ ໄດ້ ສູງສຸດ '.self::MAX_PHOTOS.' ໃບ ຕໍ່ ເຄື່ອງ.');

            return;
        }

        // ໃຊ້ງານ = ຈຳນວນ ລວມ − ຊ່ອມ − ຢຸດ (ຕ້ອງ ບໍ່ ຕິດລົບ)
        $active = $this->quantity - $this->qtyRepair - $this->qtyRetired;
        if ($active < 0) {
            $this->addError('qtyRepair', 'ຊ່ອມແປງ + ຢຸດໃຊ້ ຕ້ອງ ບໍ່ ເກີນ ຈຳນວນ ລວມ.');

            return;
        }

        // department_admin: ບັງຄັບ Owner = ພະແນກ ຕົນ ສະເໝີ (ຫ້າມ ຍ້າຍ ໄປ ພະແນກ ອື່ນ).
        $departmentId = $this->deptScoped() ? $this->userDeptId() : ($data['department_id'] ?: null);

        // ຜູ້ຮັບຜິດຊອບ: ຖ້າ ລິ້ງ ຜູ້ໃຊ້ → ໃຊ້ ຊື່ ຜູ້ໃຊ້ ນັ້ນ; ບໍ່ ດັ່ງນັ້ນ ຮັກສາ ຄ່າ ຂໍ້ຄວາມ (legacy).
        $responsibleUserId = $data['responsible_user_id'] ?: null;
        $responsibleName = $responsibleUserId
            ? (User::find($responsibleUserId)?->display_name ?? ($data['responsible_name'] ?: null))
            : ($data['responsible_name'] ?: null);

        $attrs = [
            'asset_code' => $data['asset_code'],
            'fixed_asset_no' => $data['fixed_asset_no'] ?: null,
            'name' => $data['name'],
            'category' => $data['category'] ?: null,
            'department_id' => $departmentId,
            'loc_bin' => $data['loc_bin'] ?: null,
            'brand_model' => $data['brand_model'] ?: null,
            'serial_no' => $data['serial_no'] ?: null,
            'location' => $data['location'] ?: null,
            'responsible_name' => $responsibleName,
            'responsible_user_id' => $responsibleUserId,
            'quantity' => $this->quantity,
            'unit_id' => $data['unit_id'] ?: null,
            'status_counts' => ['active' => $active, 'repair' => $this->qtyRepair, 'retired' => $this->qtyRetired],
            'condition_status' => $data['condition_status'],
            'purchase_date' => $data['purchase_date'] ?: null,
            'notes' => $data['notes'] ?: null,
            'updated_by' => auth()->id(),
        ];

        $newCond = $data['condition_status'];
        if ($this->editingId) {
            $e = Equipment::findOrFail($this->editingId);
            if ($e->condition_status !== $newCond) {   // stamp who/when on change
                $attrs['condition_set_at'] = now();
                $attrs['condition_set_by'] = auth()->id();
            }
            $e->update($attrs);
        } else {
            if ($newCond !== 'in_service') {
                $attrs['condition_set_at'] = now();
                $attrs['condition_set_by'] = auth()->id();
            }
            $e = Equipment::create($attrs + ['created_by' => auth()->id()]);
        }

        $this->storePhotos($e);

        $this->showModal = false;
        $this->dispatch('saved');
    }

    /** ບັນທຶກ ຮູບ ໃໝ່ (ຈາກ ກ້ອງ/ແກເລີຣີ) ໃສ່ public disk. */
    protected function storePhotos(Equipment $e): void
    {
        if (empty($this->newPhotos)) {
            return;
        }
        $start = (int) $e->photos()->max('sort_order');
        foreach (array_values($this->newPhotos) as $i => $photo) {
            $path = $photo->store('equipment/'.$e->id, 'public');
            $e->photos()->create(['path' => $path, 'sort_order' => $start + $i + 1]);
        }
        $this->newPhotos = [];
    }

    public function removePhoto(int $photoId): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        if ($p = EquipmentPhoto::with('equipment')->find($photoId)) {
            if ($p->equipment) {
                $this->guardDept($p->equipment);   // ກັນ ລຶບ ຮູບ ເຄື່ອງ ຂ້າມ ພະແນກ
            }
            Storage::disk('public')->delete($p->path);
            $p->delete();
        }
        $this->existingPhotos = array_values(array_filter($this->existingPhotos, fn ($x) => $x['id'] !== $photoId));
    }

    protected function canManageDeleted(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('equipment.delete');
    }

    /** ສະຫຼັບ ລະຫວ່າງ ລາຍການ ປົກກະຕິ ↔ Deleted Log. */
    public function toggleDeleted(): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $this->showDeleted = ! $this->showDeleted;
        $this->resetPage();
    }

    /** ເປີດ modal ຖາມ ເຫດຜົນ ກ່ອນ ລຶບ. */
    public function openDelete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.delete'), 403);
        $this->guardDept(Equipment::findOrFail($id));
        $this->deletingId = $id;
        $this->deleteReason = '';
        $this->resetValidation();
    }

    /** ຢືນຢັນ ລຶບ — ບັນທຶກ ເຫດຜົນ + ຜູ້ລຶບ ແລ້ວ soft-delete (ຍ້າຍ ໄປ Deleted Log). */
    public function deleteRecord(): void
    {
        abort_unless(auth()->user()->can('equipment.delete'), 403);
        $this->validate(
            ['deleteReason' => ['required', 'string', 'max:500']],
            ['deleteReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ລຶບ.']
        );
        $e = Equipment::findOrFail($this->deletingId);
        $this->guardDept($e);
        $e->forceFill(['deleted_reason' => $this->deleteReason, 'deleted_by' => auth()->id()])->save();
        $e->delete();
        $this->deletingId = null;
        $this->deleteReason = '';
        session()->flash('ok', '✓ ລຶບ ເຄື່ອງ '.$e->asset_code.' (ຍ້າຍ ໄປ Deleted Log)');
    }

    /** ກູ້ຄືນ ເຄື່ອງ ຈາກ Deleted Log. */
    public function restore(int $id): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $e = Equipment::onlyTrashed()->find($id);
        if ($e) {
            $this->guardDept($e);
            $e->restore();
            $e->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            session()->flash('ok', '✓ ກູ້ຄືນ ເຄື່ອງ '.$e->asset_code);
        }
    }

    // ─── ໃບ ກວດ (tab 2): ລຶບ-ດ້ວຍ-ເຫດຜົນ + Deleted Log ───

    /** ສະຫຼັບ ໃບ ກວດ ປົກກະຕິ ↔ Deleted Log. */
    public function toggleDeletedInspections(): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $this->showDeletedInspections = ! $this->showDeletedInspections;
    }

    /** ເປີດ modal ຖາມ ເຫດຜົນ ກ່ອນ ລຶບ ໃບ ກວດ. */
    public function openDeleteInspection(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.delete'), 403);
        $ins = EquipmentInspection::with('equipment')->findOrFail($id);
        if ($ins->equipment) {
            $this->guardDept($ins->equipment);
        }
        $this->deletingInsId = $id;
        $this->insDeleteReason = '';
        $this->resetValidation();
    }

    /** ຢືນຢັນ ລຶບ ໃບ ກວດ — ບັນທຶກ ເຫດຜົນ + ຜູ້ລຶບ ແລ້ວ soft-delete. */
    public function deleteInspectionRecord(): void
    {
        abort_unless(auth()->user()->can('equipment.delete'), 403);
        $this->validate(
            ['insDeleteReason' => ['required', 'string', 'max:500']],
            ['insDeleteReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ລຶບ.']
        );
        $ins = EquipmentInspection::with('equipment')->findOrFail($this->deletingInsId);
        if ($ins->equipment) {
            $this->guardDept($ins->equipment);
        }
        $ins->forceFill(['deleted_reason' => $this->insDeleteReason, 'deleted_by' => auth()->id()])->save();
        $ins->delete();
        $this->deletingInsId = null;
        $this->insDeleteReason = '';
        session()->flash('ok', '✓ ລຶບ ໃບ ກວດ (ຍ້າຍ ໄປ Deleted Log)');
    }

    /** ກູ້ຄືນ ໃບ ກວດ ຈາກ Deleted Log. */
    public function restoreInspection(int $id): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $ins = EquipmentInspection::onlyTrashed()->with('equipment')->find($id);
        if ($ins) {
            if ($ins->equipment) {
                $this->guardDept($ins->equipment);
            }
            $ins->restore();
            $ins->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            session()->flash('ok', '✓ ກູ້ຄືນ ໃບ ກວດ');
        }
    }

    // ─────────── ແທັບ 2: ການ ກວດກາ ───────────

    public function newInspection(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $this->reset([
            'insSearch', 'insEquipmentId', 'insEquipmentLabel', 'insInspector',
            'insNotes', 'insNextDue', 'insPhotos', 'insExistingPhotos', 'insUpdateStatus', 'insRepair', 'insRetired',
            'insTemplateId', 'insFuelType', 'insFrequency', 'insChecklist', 'editingInspectionId',
        ]);
        $this->insDate = now()->toDateString();
        $this->insResult = 'pass';
        $this->insEquipmentQty = 1;
        $this->resetValidation();
        $this->showInspectionModal = true;
    }

    /** ແກ້ໄຂ ບັນທຶກ ການ ກວດ ທີ່ ມີ ຢູ່ ແລ້ວ. */
    public function editInspection(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $ins = EquipmentInspection::with('equipment')->findOrFail($id);
        $e = $ins->equipment;
        if ($e) {
            $this->guardDept($e);
        }

        $this->reset([
            'insSearch', 'insPhotos', 'insUpdateStatus',
        ]);
        $this->insExistingPhotos = $ins->allPhotos();
        $this->viewingInspectionId = null;
        $this->editingInspectionId = $ins->id;
        $this->insEquipmentId = $e?->id;
        $this->insEquipmentLabel = $e ? $e->asset_code.' · '.$e->name : '';
        $this->insEquipmentQty = $e?->quantity ?: 1;
        $b = $e ? $e->statusBreakdown() : ['repair' => 0, 'retired' => 0];
        $this->insRepair = $b['repair'];
        $this->insRetired = $b['retired'];
        $this->insTemplateId = $ins->template_id;
        $this->insFuelType = $ins->fuel_type ?? '';
        $this->insFrequency = $ins->frequency ?? '';
        $this->insChecklist = $ins->checklist ?? [];
        $this->insResult = $ins->result;
        $this->insNotes = $ins->notes ?? '';
        $this->insNextDue = $ins->next_due_date?->toDateString();
        $this->insDate = $ins->inspected_at?->toDateString();
        $this->resetValidation();
        $this->showInspectionModal = true;
    }

    /** ເປີດ ໜ້າຕ່າງ ເບິ່ງ ລາຍລະອຽດ ການ ກວດ (read-only). */
    public function viewInspection(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
        $ins = EquipmentInspection::with('equipment')->findOrFail($id);
        if ($ins->equipment) {
            $this->guardDept($ins->equipment);
        }
        $this->viewingInspectionId = $id;
    }

    /** ຜູ້ ມີ ສິດ ຮັບຊາບ (Manager/Leader) ບໍ — ໃຊ້ ສິດ equipment.activate. */
    protected function canAcknowledge(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('equipment.activate');
    }

    /** Manager/Leader ຮັບຊາບ ໃບ ກວດ → ບັນທຶກ ຜູ້ + ວັນທີ (ຂຶ້ນ ໃນ ຫ້ອງ ລາຍເຊັນ PDF). */
    public function acknowledgeInspection(int $id): void
    {
        abort_unless($this->canAcknowledge(), 403);
        $ins = EquipmentInspection::with('equipment')->findOrFail($id);
        if ($ins->equipment) {
            $this->guardDept($ins->equipment);
        }
        $ins->update([
            'acknowledged_by' => auth()->id(),
            'acknowledged_by_name' => auth()->user()->display_name,
            'acknowledged_at' => now(),
        ]);
        session()->flash('ok', '✓ ຮັບຊາບ ໃບ ກວດ ແລ້ວ ໂດຍ '.auth()->user()->display_name);
        $this->dispatch('saved');
    }

    /** ຖອນ ການ ຮັບຊາບ ໃບ ກວດ (ກໍລະນີ ກົດ ຜິດ). */
    public function unacknowledgeInspection(int $id): void
    {
        abort_unless($this->canAcknowledge(), 403);
        $ins = EquipmentInspection::with('equipment')->findOrFail($id);
        if ($ins->equipment) {
            $this->guardDept($ins->equipment);
        }
        $ins->update(['acknowledged_by' => null, 'acknowledged_by_name' => null, 'acknowledged_at' => null]);
        session()->flash('ok', 'ຖອນ ການ ຮັບຊາບ ໃບ ກວດ ແລ້ວ');
        $this->dispatch('saved');
    }

    /** ເປີດ ໜ້າຕ່າງ ປະຫວັດ/ສະຖານະ ການ ກວດເຊັກ ຂອງ ເຄື່ອງ ໜຶ່ງ. */
    public function viewInspectionHistory(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
        $e = Equipment::findOrFail($id);
        $this->guardDept($e);
        $this->historyEquipmentId = $id;
    }

    /** ເປີດ ໜ້າຕ່າງ ເບິ່ງ ລາຍລະອຽດ ເຄື່ອງ (read-only). */
    public function viewItem(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.view'), 403);
        $e = Equipment::findOrFail($id);
        $this->guardDept($e);
        $this->viewingItemId = $id;
    }

    /** ຈາກ ໜ້າ ປະຫວັດ → ເລີ່ມ ກວດ ໃໝ່ ໃຫ້ ເຄື່ອງ ນີ້ ເລີຍ (ໃສ່ ເຄື່ອງ ໃຫ້ ອັດຕະໂນມັດ). */
    public function inspectEquipment(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        $this->historyEquipmentId = null;
        $this->newInspection();
        $this->pickInspectionEquipment($id);
    }

    /** ລຶບ ຮູບ ໃໝ່ (ຍັງ ບໍ່ ບັນທຶກ) ອອກ ຈາກ ຄິວ. */
    public function removeInsPhoto(int $i): void
    {
        unset($this->insPhotos[$i]);
        $this->insPhotos = array_values($this->insPhotos);
    }

    /** ລຶບ ຮູບ ຫຼັກຖານ ທີ່ ບັນທຶກ ແລ້ວ (ຕອນ ແກ້ໄຂ). */
    public function removeExistingInsPhoto(int $i): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);
        if (! isset($this->insExistingPhotos[$i])) {
            return;
        }
        // ລຶບ ໄຟລ໌ ຈິງ ໄດ້ ສະເພาະ path ທີ່ ຢູ່ ໃນ ໃບ ກວດ ນັ້ນ ຈິງ + ຕາມ ພະແນກ (ບໍ່ ເຊື່ອ array ຈາກ client).
        $path = $this->insExistingPhotos[$i];
        if ($this->editingInspectionId && ($ins = EquipmentInspection::with('equipment')->find($this->editingInspectionId))) {
            if ($ins->equipment) {
                $this->guardDept($ins->equipment);
            }
            if (in_array($path, $ins->allPhotos(), true)) {
                Storage::disk('public')->delete($path);
            }
        }
        unset($this->insExistingPhotos[$i]);
        $this->insExistingPhotos = array_values($this->insExistingPhotos);
    }

    public function pickInspectionEquipment(int $id): void
    {
        $e = Equipment::find($id);
        if (! $e) {
            return;
        }
        $this->guardDept($e);
        $this->insEquipmentId = $e->id;
        $this->insEquipmentLabel = $e->asset_code.' · '.$e->name;
        $this->insEquipmentQty = $e->quantity ?: 1;
        $b = $e->statusBreakdown();
        $this->insRepair = $b['repair'];
        $this->insRetired = $b['retired'];
        $this->insTemplateId = null;
        $this->insFuelType = '';
        $this->insFrequency = '';
        $this->insChecklist = [];
        $this->insSearch = '';
    }

    /** ເລືອກ ແມ່ແບບ → ລ້າງ ຕົວ ເລືອກ ແລ້ວ ສ້າງ ເຊັກລິສ (ຖ້າ ຕ້ອງ ເລືອກ ປະເພດ/ຮອບ ກ່ອນ → ລໍ). */
    public function updatedInsTemplateId($value): void
    {
        $this->insFuelType = '';
        $this->insFrequency = '';
        $this->rebuildInsChecklist();
    }

    /** ເລືອກ ປະເພດ ນ້ຳມັນ → ສ້າງ ເຊັກລິສ ໃໝ່. */
    public function updatedInsFuelType($value): void
    {
        $this->rebuildInsChecklist();
    }

    /** ເລືອກ ຮອບ ກວດ → ສ້າງ ເຊັກລິສ ໃໝ່ + ຄິດ ວັນ ກວດ ຄັ້ງ ໜ້າ. */
    public function updatedInsFrequency($value): void
    {
        $this->rebuildInsChecklist();
        $due = $this->nextDueFor((string) $value);
        if ($due) {
            $this->insNextDue = $due;
        }
    }

    /** ສ້າງ ເຊັກລິສ ຕາມ ຕົວ ເລືອກ ປັດຈຸບັນ (ນ້ຳມັນ + ຮອບ) — ວ່າງ ຖ້າ ຍັງ ບໍ່ ເລືອກ ຄົບ. */
    protected function rebuildInsChecklist(): void
    {
        $t = $this->insTemplateId ? InspectionTemplate::find($this->insTemplateId) : null;
        if (! $t) {
            $this->insChecklist = [];

            return;
        }
        // ຕ້ອງ ເລືອກ ໃຫ້ ຄົບ ກ່ອນ ຈຶ່ງ ຄັດ ລິສ.
        if (($t->hasFuelTypes() && $this->insFuelType === '') || ($t->hasFrequencies() && $this->insFrequency === '')) {
            $this->insChecklist = [];

            return;
        }
        $this->insChecklist = $this->buildInsChecklist($t, $this->insFuelType, $this->insFrequency);
    }

    /** ວັນ ກວດ ຄັ້ງ ໜ້າ ຕາມ ຮອບ (null = ບໍ່ ກຳນົດ, ເຊັ່ນ ກ່ອນ ໃຊ້). */
    protected function nextDueFor(string $frequency): ?string
    {
        return match ($frequency) {
            'monthly' => now()->addMonth()->toDateString(),
            'quarterly' => now()->addMonths(3)->toDateString(),
            'semi_annual' => now()->addMonths(6)->toDateString(),
            'annual' => now()->addYear()->toDateString(),
            default => null,
        };
    }

    /** ຕິກ ສະຖານະ OK/NG ຕໍ່ ຂໍ້ (ກົດ ຊ້ຳ = ກັບ ໄປ N/A). */
    public function toggleChecklist(int $i, string $status): void
    {
        if (! isset($this->insChecklist[$i])) {
            return;
        }
        $this->insChecklist[$i]['status'] = ($this->insChecklist[$i]['status'] ?? '') === $status ? 'na' : $status;
    }

    /** ສ້າງ ເຊັກລິສ ຈາກ ແມ່ແບບ: ຄັດ ຕາມ ປະເພດ (ນ້ຳມັນ) + ຮອບ ທີ່ ເລືອກ. */
    protected function buildInsChecklist(InspectionTemplate $t, string $fuelType, string $frequency): array
    {
        return collect($t->normalizedItems())
            ->filter(fn ($x) => // ປະເພດ: "ທັງ 2" ຫຼື ກົງ ປະເພດ ທີ່ ເລືອກ
                ($x['applies'] === 'both' || ($fuelType !== '' && $x['applies'] === $fuelType))
                // ຮອບ: ບໍ່ ຕິດ ຮອບ (ຂຶ້ນ ທຸກ ຮອບ) ຫຼື ຮອບ ທີ່ ເລືອກ ຢູ່ ໃນ ຂໍ້ ນັ້ນ
                && (empty($x['freqs']) || ($frequency !== '' && in_array($frequency, $x['freqs'], true)))
            )
            ->map(fn ($x) => ['label' => $x['label'], 'status' => 'pass', 'note' => ''])
            ->values()
            ->all();
    }

    public function saveInspection(): void
    {
        abort_unless(auth()->user()->can('equipment.edit'), 403);

        $this->validate([
            'insEquipmentId' => ['required', 'exists:equipment,id'],
            'insResult' => ['required', 'in:pass,fail,follow_up'],
            'insNotes' => ['nullable', 'string', 'max:2000'],
            'insNextDue' => ['nullable', 'date'],
            'insPhotos' => ['array', 'max:'.self::MAX_INS_PHOTOS],
            'insPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'insRepair' => ['integer', 'min:0'],
            'insRetired' => ['integer', 'min:0'],
            'insTemplateId' => ['nullable', 'exists:inspection_templates,id'],
            'insFuelType' => ['nullable', 'in:ev,engine'],
            'insFrequency' => ['nullable', 'in:'.implode(',', InspectionTemplate::FREQUENCIES)],
        ]);

        // ແມ່ແບບ ທີ່ ລາຍການ ຂຶ້ນ ຕາມ ປະເພດ/ຮອບ → ຕ້ອງ ເລືອກ ກ່ອນ.
        $tpl = $this->insTemplateId ? InspectionTemplate::find($this->insTemplateId) : null;
        if ($tpl && $tpl->hasFuelTypes() && $this->insFuelType === '') {
            $this->addError('insFuelType', 'ກະລຸນາ ເລືອກ ປະເພດ (ໄຟຟ້າ/ນ້ຳມັນ) ກ່ອນ.');

            return;
        }
        if ($tpl && $tpl->hasFrequencies() && $this->insFrequency === '') {
            $this->addError('insFrequency', 'ກະລຸນາ ເລືອກ ຮອບ ການ ກວດ ກ່ອນ.');

            return;
        }

        $e = Equipment::findOrFail($this->insEquipmentId);
        $this->guardDept($e);

        // ອັບເດດ ສະຖານະ ຈາກ ຜົນ ກວດກາ (ຖ້າ ເລືອກ)
        if ($this->insUpdateStatus) {
            $active = $e->quantity - $this->insRepair - $this->insRetired;
            if ($active < 0) {
                $this->addError('insRepair', 'ຊ່ອມແປງ + ຢຸດໃຊ້ ຕ້ອງ ບໍ່ ເກີນ ຈຳນວນ.');

                return;
            }
            $e->update(['status_counts' => ['active' => $active, 'repair' => $this->insRepair, 'retired' => $this->insRetired]]);
        }

        // ຜົນ + ຄະແນນ: ຖ້າ ໃຊ້ ເຊັກລິສ → ຄິດໄລ່ ອັດຕະໂນມັດ; ບໍ່ ດັ່ງນັ້ນ ໃຊ້ ຄ່າ ທີ່ ເລືອກ.
        $score = null;
        $result = $this->insResult;
        if (count($this->insChecklist)) {
            $s = $this->scoreChecklist($this->insChecklist);
            $score = $s['pct'];
            $result = $s['result'];
        }

        // ຮູບ ຫຼັກຖານ: ຮູບ ເກົ່າ (ຄົງ ໄວ້) + ຮູບ ໃໝ່ (ຝັງ ວັນທີ+ເວລາ). ຮວມ ບໍ່ ເກີນ MAX.
        if (count($this->insExistingPhotos) + count($this->insPhotos) > self::MAX_INS_PHOTOS) {
            $this->addError('insPhotos', 'ຮູບ ໄດ້ ສູງສຸດ '.self::MAX_INS_PHOTOS.' ໃບ ຕໍ່ ການ ກວດ.');

            return;
        }
        $photoPaths = $this->insExistingPhotos;
        foreach ($this->insPhotos as $photo) {
            $photoPaths[] = $this->stampAndStore($photo, 'equipment/inspections/'.$e->id);
        }

        // ວັນ ກວດ ຄັ້ງ ໜ້າ: ໃຊ້ ຄ່າ ທີ່ ພິມ; ຖ້າ ວ່າງ ຄິດ ຈາກ ຮອບ ໃຫ້ ເອງ.
        $nextDue = $this->insNextDue ?: $this->nextDueFor($this->insFrequency);

        $payload = [
            'template_id' => $this->insTemplateId ?: null,
            'fuel_type' => $this->insFuelType ?: null,
            'frequency' => $this->insFrequency ?: null,
            'inspector_name' => auth()->user()->display_name,   // ບັງຄັບ ຈາກ ຜູ້ ລ໋ອກອິນ
            'result' => $result,
            'score' => $score,
            'checklist' => $this->insChecklist ?: null,
            'notes' => $this->insNotes ?: null,
            'next_due_date' => $nextDue ?: null,
            'photos' => $photoPaths ?: null,
            'photo_path' => $photoPaths[0] ?? null,   // ຮູບ ທຳອິດ = ໂຊ ໃນ list
        ];

        if ($this->editingInspectionId) {
            // ກວດ dept-scope ຂອງ record ທີ່ ກຳລັງ ແກ້ (ບໍ່ ແມ່ນ ພຽງ equipment ທີ່ client ສົ່ງ ມາ) — ກັນ IDOR ຂ້າມ ພະແນກ.
            $ins = EquipmentInspection::with('equipment')->findOrFail($this->editingInspectionId);
            if ($ins->equipment) {
                $this->guardDept($ins->equipment);
            }
            $ins->update($payload);
        } else {
            $payload['inspected_at'] = now();   // ສະແຕັມ ເວລາ submit
            $payload['created_by'] = auth()->id();
            $e->inspections()->create($payload);
        }

        $this->showInspectionModal = false;
        $this->dispatch('saved');
    }

    /**
     * ຄິດໄລ່ ຄະແນນ ຈາກ ເຊັກລິສ: ຜ່ານ / (ຜ່ານ+ບໍ່ຜ່ານ) — ຂໍ້ "ບໍ່ກ່ຽວ" ບໍ່ ນັບ.
     *
     * @return array{pass:int,fail:int,na:int,considered:int,pct:int,result:string}
     */
    protected function scoreChecklist(array $checklist): array
    {
        $pass = $fail = $na = 0;
        foreach ($checklist as $c) {
            match ($c['status'] ?? 'pass') {
                'fail' => $fail++,
                'na' => $na++,
                default => $pass++,
            };
        }
        $considered = $pass + $fail;
        $pct = $considered > 0 ? (int) round($pass / $considered * 100) : 100;
        // 100% → ຜ່ານ · 70–99% → ຕ້ອງຕິດຕາມ · <70% → ບໍ່ຜ່ານ
        $result = $pct === 100 ? 'pass' : ($pct >= 70 ? 'follow_up' : 'fail');

        return compact('pass', 'fail', 'na', 'considered', 'pct', 'result');
    }

    /** ຄະແນນ ສົດ ສຳລັບ ສະແດງ ໃນ ຟອມ (null ຖ້າ ບໍ່ ໃຊ້ ເຊັກລິສ). */
    public function getInsScoreProperty(): ?array
    {
        return count($this->insChecklist) ? $this->scoreChecklist($this->insChecklist) : null;
    }

    /** ຝັງ ວັນທີ+ເວລາ (ຕອນ upload) ໃສ່ ຮູບ ດ້ວຍ GD ແລ້ວ ບັນທຶກ; fallback ຖ້າ GD ລົ້ມ. */
    protected function stampAndStore($photo, string $dir): string
    {
        $stamp = now()->timezone('Asia/Vientiane')->format('Y-m-d H:i');
        try {
            $img = @imagecreatefromstring(file_get_contents($photo->getRealPath()));
            if ($img !== false) {
                $w = imagesx($img);
                $h = imagesy($img);
                $tw = imagefontwidth(5) * strlen($stamp);
                $th = imagefontheight(5);
                $tmp = imagecreatetruecolor($tw, $th);
                imagefill($tmp, 0, 0, imagecolorallocate($tmp, 0, 0, 0));
                imagestring($tmp, 5, 0, 0, $stamp, imagecolorallocate($tmp, 255, 255, 255));
                $scale = max(2, (int) floor($w / 500));
                $dw = $tw * $scale;
                $dh = $th * $scale;
                $pad = max(4, (int) round($dh * 0.35));
                $bar = imagecolorallocatealpha($img, 0, 0, 0, 55);
                imagefilledrectangle($img, 0, $h - $dh - 2 * $pad, $dw + 2 * $pad, $h, $bar);
                imagecopyresized($img, $tmp, $pad, $h - $dh - $pad, 0, 0, $dw, $dh, $tw, $th);
                imagedestroy($tmp);
                ob_start();
                imagejpeg($img, null, 85);
                $bytes = ob_get_clean();
                imagedestroy($img);
                $path = rtrim($dir, '/').'/ins_'.Str::random(32).'.jpg';
                Storage::disk('public')->put($path, $bytes);

                return $path;
            }
        } catch (\Throwable $ex) {
            // fall through to plain store
        }

        return $photo->store($dir, 'public');
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->asset_code = '';
        $this->fixed_asset_no = '';
        $this->name = '';
        $this->category = '';
        $this->department_id = null;
        $this->loc_bin = '';
        $this->brand_model = '';
        $this->serial_no = '';
        $this->location = '';
        $this->responsible_name = '';
        $this->responsible_user_id = null;
        $this->quantity = 1;
        $this->unit_id = null;
        $this->qtyRepair = 0;
        $this->qtyRetired = 0;
        $this->condition_status = 'in_service';
        $this->purchase_date = null;
        $this->notes = '';
        $this->newPhotos = [];
        $this->existingPhotos = [];
        $this->resetValidation();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        // department_admin ເຫັນ ສະເພາະ ເຄື່ອງ ຂອງ ພະແນກ ຕົນ.
        $deptScoped = $this->deptScoped();
        $deptId = $this->userDeptId();

        $showingDeleted = $this->showDeleted && $this->canManageDeleted();
        $items = Equipment::query()
            ->with(['photos', 'unit', 'department', 'responsibleUser', 'activeBorrowItems.record'])
            ->when($showingDeleted, fn ($q) => $q->onlyTrashed()->with('deletedBy'))
            ->when($deptScoped, fn ($q) => $q->where('department_id', $deptId))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('asset_code', 'like', "%{$this->search}%")
                ->orWhere('serial_no', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->departmentFilter, fn ($q) => $q->where('department_id', $this->departmentFilter))
            // ກັ່ນຕອງ ສະຖານະ: ມີ ≥1 ໜ່ວຍ ໃນ ສະຖານະ ນັ້ນ
            ->when($this->statusFilter, fn ($q) => $q->where('status_counts->'.$this->statusFilter, '>', 0))
            ->when($showingDeleted, fn ($q) => $q->orderByDesc('deleted_at'), fn ($q) => $q->orderBy('asset_code'))
            ->paginate(in_array($this->perPage, [8, 10, 25, 50, 100], true) ? $this->perPage : 8);

        // KPI band (equipment register, dept-scoped like the list)
        $kpiBase = Equipment::query()->when($deptScoped, fn ($q) => $q->where('department_id', $deptId));
        $kpi = [
            ['label' => '🛠️ ເຄື່ອງ ທັງໝົດ', 'value' => (clone $kpiBase)->count(), 'hint' => 'equipment'],
            ['label' => '🔧 ຕ້ອງ ສ້ອມ', 'value' => (clone $kpiBase)->where('status_counts->repair', '>', 0)->count(), 'hint' => 'in repair', 'tone' => 'text-amber-600'],
            ['label' => '⛔ ปลด ระวาง', 'value' => (clone $kpiBase)->where('status_counts->retired', '>', 0)->count(), 'hint' => 'retired'],
            ['label' => '🔄 ກຳລັງ ຢືມ', 'value' => (clone $kpiBase)->whereHas('activeBorrowItems')->count(), 'hint' => 'on loan', 'tone' => 'text-amber-600'],
            ['label' => '📁 ປະເພດ', 'value' => EquipmentCategory::where('is_active', true)->count(), 'hint' => 'categories'],
        ];

        $insResults = $this->showInspectionModal && strlen($this->insSearch) >= 2
            ? Equipment::where(fn ($q) => $q->where('name', 'like', "%{$this->insSearch}%")
                ->orWhere('asset_code', 'like', "%{$this->insSearch}%"))
                ->when($deptScoped, fn ($q) => $q->where('department_id', $deptId))
                ->orderBy('asset_code')->limit(8)->get()
            : collect();

        $insTemplateOptions = collect();
        if ($this->insEquipmentId) {
            $cat = Equipment::whereKey($this->insEquipmentId)->value('category');
            // ໂຊ ແມ່ແບບ ທັງ ໝົດ ທີ່ ເປີດ ໃຊ້ — ຮຽງ ອັນ ກົງ ປະເພດ ຂຶ້ນ ກ່ອນ (ກັນ ບໍ່ ໃຫ້ ຫາຍ ຍ້ອນ ຊື່ ປະເພດ ບໍ່ ກົງ).
            $insTemplateOptions = InspectionTemplate::where('is_active', true)
                ->orderByRaw('CASE WHEN category = ? THEN 0 WHEN category IS NULL OR category = ? THEN 1 ELSE 2 END', [$cat, ''])
                ->orderBy('name')
                ->get(['id', 'name', 'category'])
                ->map(fn ($t) => tap($t, fn ($x) => $x->matches = $cat && $x->category === $cat));
        }

        // ແມ່ແບບ ທີ່ ເລືອກ ຕ້ອງ ເລືອກ ປະເພດ ນ້ຳມັນ / ຮອບ ກ່ອນ ບໍ.
        $insTpl = $this->insTemplateId ? InspectionTemplate::find($this->insTemplateId) : null;
        $insTemplateNeedsFuel = (bool) $insTpl?->hasFuelTypes();
        $insTemplateNeedsFreq = (bool) $insTpl?->hasFrequencies();

        $viewingInspection = $this->viewingInspectionId
            ? EquipmentInspection::with(['equipment', 'template', 'acknowledgedBy'])->find($this->viewingInspectionId)
            : null;

        // ປະຫວັດ ການ ກວດເຊັກ ຂອງ ເຄື່ອງ (ໜ້າຕ່າງ ຈາກ ໄອຄ່ອນ ດວງຕາ ໃນ ທະບຽນ).
        $historyEquipment = $this->historyEquipmentId ? Equipment::find($this->historyEquipmentId) : null;
        $historyInspections = $this->historyEquipmentId
            ? EquipmentInspection::where('equipment_id', $this->historyEquipmentId)
                ->orderByDesc('inspected_at')->orderByDesc('id')->limit(50)->get()
            : collect();

        // ລາຍລະອຽດ ເຄື່ອງ (ໜ້າຕ່າງ ຈາກ ໄອຄ່ອນ ດວງຕາ ໃນ ຄໍລັມ "ຈັດການ").
        $viewingItem = $this->viewingItemId
            ? Equipment::with(['photos', 'unit', 'department', 'responsibleUser', 'activeBorrowItems.record'])->find($this->viewingItemId)
            : null;

        // ເຄື່ອງ ທີ່ ກຳລັງ ຈະ ລຶບ (ສະແດງ ໃນ modal ຖາມ ເຫດຜົນ).
        $deletingItem = $this->deletingId ? Equipment::find($this->deletingId) : null;

        // ໃບ ກວດ ທີ່ ກຳລັງ ຈະ ລຶບ (modal ຖາມ ເຫດຜົນ).
        $deletingInspection = $this->deletingInsId
            ? EquipmentInspection::with('equipment')->find($this->deletingInsId)
            : null;

        // ປະເພດ ສຳລັບ dropdown ຟອມ (ເປີດ ໃຊ້) — ຮວມ ຄ່າ ປັດຈຸບັນ ຕອນ ແກ້ (ກັນ ຫາຍ ຖ້າ ຖືກ ປິດ).
        $categoryOptions = EquipmentCategory::where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')->pluck('name');
        if ($this->category !== '' && ! $categoryOptions->contains($this->category)) {
            $categoryOptions = $categoryOptions->prepend($this->category);
        }
        $authUser = auth()->user();
        $canManageCategories = $authUser->is_super_admin || $authUser->hasRole('admin');

        return view('livewire.equipment.index', [
            'items' => $items,
            'kpi' => $kpi,
            'insTemplateOptions' => $insTemplateOptions,
            'units' => Uom::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'responsibleUsers' => User::orderBy('display_name')->get(['id', 'display_name']),
            'categoryOptions' => $categoryOptions,
            'canManageCategories' => $canManageCategories,
            // filter dropdown ← ອ່ານ master ດຽວກັນກັບ ຟອມ (EquipmentCategory ທີ່ເປີດ), ບໍ່ແມ່ນ distinct ຈາກ record.
            'categories' => EquipmentCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name'),
            'inspections' => EquipmentInspection::with('equipment')
                ->when($this->showDeletedInspections && $this->canManageDeleted(), fn ($q) => $q->onlyTrashed()->with('deletedBy'))
                ->when($deptScoped, fn ($q) => $q->whereHas('equipment', fn ($w) => $w->where('department_id', $deptId)))
                ->when($this->inspSearch, fn ($q) => $q->where(fn ($w) => $w
                    ->where('inspector_name', 'like', "%{$this->inspSearch}%")
                    ->orWhereHas('equipment', fn ($e) => $e
                        ->where('name', 'like', "%{$this->inspSearch}%")
                        ->orWhere('asset_code', 'like', "%{$this->inspSearch}%"))))
                ->when($this->inspResultFilter, fn ($q) => $q->where('result', $this->inspResultFilter))
                ->when($this->inspCategoryFilter, fn ($q) => $q->whereHas('equipment', fn ($e) => $e->where('category', $this->inspCategoryFilter)))
                ->orderByDesc('inspected_at')->orderByDesc('id')->limit(50)->get(),
            'deletingInspection' => $deletingInspection,
            'insResults' => $insResults,
            'insTemplateNeedsFuel' => $insTemplateNeedsFuel,
            'insTemplateNeedsFreq' => $insTemplateNeedsFreq,
            'viewingInspection' => $viewingInspection,
            'canAcknowledge' => $this->canAcknowledge(),
            'historyEquipment' => $historyEquipment,
            'historyInspections' => $historyInspections,
            'viewingItem' => $viewingItem,
            'deletingItem' => $deletingItem,
            'canManageDeleted' => $this->canManageDeleted(),
            'deptScoped' => $deptScoped,
        ]);
    }
}
