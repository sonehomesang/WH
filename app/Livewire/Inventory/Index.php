<?php

namespace App\Livewire\Inventory;

use App\Imports\InventoryCsvImporter;
use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\Building;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemPhoto;
use App\Models\Location;
use App\Models\Room;
use App\Models\Uom;
use App\Support\ConditionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use SoftDeletesWithReason, WithFileUploads, WithPagination;

    public const MAX_PHOTOS = 6;

    #[Url]                                  // deep-linkable (e.g. ANSI item → /inventory?search=<Material No.>)
    public string $search = '';

    public string $statusFilter = '';

    public string $stockFilter = '';       // '' | ok | low | out — computed from qty vs min_quantity

    public int $perPage = 8;               // rows per page (whitelisted in render) — no inner scroll

    public string $prefixFilter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $category = '';

    public string $brand = '';

    public string $model = '';

    public string $serial_number = '';

    public int $quantity = 0;

    public int $min_quantity = 0;

    public string $unit = '';

    public ?int $location_id = null;

    public ?int $building_id = null;

    public ?int $room_id = null;

    public string $shelf_label = '';

    public ?int $department_id = null;

    public string $status = 'available';

    public string $condition_status = 'in_service';

    public bool $is_active = true;

    /** @var array<int, TemporaryUploadedFile> */
    public array $newPhotos = [];

    /** @var array<int, array{id:int, url:string}> ຮູບທີ່ມີຢູ່ແລ້ວ (ຕອນ edit). */
    public array $existingPhotos = [];

    // ── CSV import ──
    public bool $showImport = false;

    public $csvFile = null;

    /** @var array{imported:int, skipped:int, errors:array<int,string>}|array{} */
    public array $importResult = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPrefixFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedLocationId(): void
    {
        $this->building_id = null;
        $this->room_id = null;
    }

    public function updatedBuildingId(): void
    {
        $this->room_id = null;
    }

    public function newItem(): void
    {
        abort_unless(auth()->user()->can('inventory.create'), 403);
        $this->resetForm();
        $this->showModal = true;
    }

    public function editItem(int $id): void
    {
        abort_unless(auth()->user()->can('inventory.edit'), 403);
        $m = InventoryItem::findOrFail($id);
        $this->editingId = $m->id;
        $this->name = $m->name;
        $this->description = $m->description ?? '';
        $this->category = $m->category ?? '';
        $this->brand = $m->brand ?? '';
        $this->model = $m->model ?? '';
        $this->serial_number = $m->serial_number ?? '';
        $this->quantity = $m->quantity;
        $this->min_quantity = $m->min_quantity;
        $this->unit = $m->unit ?? '';
        $this->location_id = $m->location_id;
        $this->building_id = $m->building_id;
        $this->room_id = $m->room_id;
        $this->shelf_label = $m->shelf_label ?? '';
        $this->department_id = $m->department_id;
        $this->status = $m->status;
        $this->condition_status = $m->condition_status ?? 'in_service';
        $this->is_active = (bool) $m->is_active;
        $this->newPhotos = [];
        $this->loadPhotos($m);
        $this->resetValidation();
        $this->showModal = true;
    }

    protected function loadPhotos(InventoryItem $m): void
    {
        $this->existingPhotos = $m->photos->map(fn ($p) => ['id' => $p->id, 'url' => $p->url])->all();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('inventory.'.($this->editingId ? 'edit' : 'create')), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:128'],
            'brand' => ['nullable', 'string', 'max:128'],
            'model' => ['nullable', 'string', 'max:128'],
            'serial_number' => ['nullable', 'string', 'max:128'],
            'quantity' => ['required', 'integer', 'min:0'],
            'min_quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['nullable', 'string', 'max:32'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'shelf_label' => ['nullable', 'string', 'max:64'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'status' => ['required', 'in:available,borrowed,maintenance,low-stock'],
            'condition_status' => ['required', ConditionStatus::rule()],
            'is_active' => ['boolean'],
            'newPhotos' => ['array', 'max:'.self::MAX_PHOTOS],
            'newPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if (count($this->existingPhotos) + count($this->newPhotos) > self::MAX_PHOTOS) {
            $this->addError('newPhotos', 'ຮູບໄດ້ສູງສຸດ '.self::MAX_PHOTOS.' ໃບຕໍ່ item.');

            return;
        }

        $uid = auth()->id();
        $payload = collect($data)->except('newPhotos')->map(fn ($v) => $v === '' ? null : $v)->toArray();
        $payload['is_active'] = $this->is_active;
        $payload['updated_by'] = $uid;

        $newCond = $payload['condition_status'] ?? 'in_service';

        if ($this->editingId) {
            $item = InventoryItem::findOrFail($this->editingId);
            if ($item->condition_status !== $newCond) {   // stamp who/when on change
                $payload['condition_set_at'] = now();
                $payload['condition_set_by'] = $uid;
            }
            $item->update($payload);
        } else {
            if ($newCond !== 'in_service') {
                $payload['condition_set_at'] = now();
                $payload['condition_set_by'] = $uid;
            }
            $payload['slug'] = $this->uniqueSlug($data['name']);
            $payload['created_by'] = $uid;
            $item = InventoryItem::create($payload);
        }

        $this->storePhotos($item);

        $this->showModal = false;
        $this->dispatch('saved');
    }

    /** ເກັບຮູບໃໝ່ໄປ storage/app/public/inventory/{id}/ + ສ້າງ record. */
    protected function storePhotos(InventoryItem $item): void
    {
        if (empty($this->newPhotos)) {
            return;
        }

        $start = (int) $item->photos()->max('sort_order');
        foreach (array_values($this->newPhotos) as $i => $photo) {
            $path = $photo->store('inventory/'.$item->id, 'public');
            $item->photos()->create(['path' => $path, 'sort_order' => $start + $i + 1]);
        }
        $this->newPhotos = [];
    }

    public function removePhoto(int $photoId): void
    {
        abort_unless(auth()->user()->can('inventory.edit'), 403);
        $photo = InventoryItemPhoto::find($photoId);
        if (! $photo) {
            return;
        }
        Storage::disk('public')->delete($photo->path);
        $photo->delete();
        $this->existingPhotos = array_values(array_filter($this->existingPhotos, fn ($p) => $p['id'] !== $photoId));
    }

    public function openImport(): void
    {
        abort_unless(auth()->user()->can('inventory.create'), 403);
        $this->reset(['csvFile', 'importResult']);
        $this->resetValidation();
        $this->showImport = true;
    }

    public function importCsv(): void
    {
        abort_unless(auth()->user()->can('inventory.create'), 403);

        $this->validate(
            ['csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:25600']],
            [],
            ['csvFile' => 'ໄຟລ໌ CSV']
        );

        $ext = strtolower($this->csvFile->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            $this->addError('csvFile', 'ຮັບສະເພາະ .csv ຫຼື .txt');

            return;
        }

        $this->importResult = (new InventoryCsvImporter)->import($this->csvFile->getRealPath(), auth()->id());
        $this->csvFile = null;
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $m = InventoryItem::findOrFail($id);
        abort_unless(auth()->user()->can('inventory.'.($m->is_active ? 'deactivate' : 'activate')), 403);
        $m->update(['is_active' => ! $m->is_active, 'updated_by' => auth()->id()]);
    }

    // ── ລຶບ-ດ້ວຍ-ເຫດຜົນ + Deleted Log (trait SoftDeletesWithReason) ──
    protected function deleteModelClass(): string
    {
        return InventoryItem::class;
    }

    protected function deletePermission(): string
    {
        return 'inventory.delete';
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->name;
    }

    protected function deleteNoun(): string
    {
        return 'item';
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->category = '';
        $this->brand = '';
        $this->model = '';
        $this->serial_number = '';
        $this->quantity = 0;
        $this->min_quantity = 0;
        $this->unit = '';
        $this->location_id = null;
        $this->building_id = null;
        $this->room_id = null;
        $this->shelf_label = '';
        $this->department_id = null;
        $this->status = 'available';
        $this->condition_status = 'in_service';
        $this->is_active = true;
        $this->newPhotos = [];
        $this->existingPhotos = [];
        $this->resetValidation();
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'item';
        $original = $slug;
        $i = 2;
        while (DB::table('inventory_items')->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    public function render(): View
    {
        $items = InventoryItem::with(['location', 'building', 'room', 'primaryPhoto'])
            ->when($this->showDeleted && $this->canManageDeleted(), fn ($q) => $q->onlyTrashed()->with('deletedBy'))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('slug', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
                ->orWhere('category', 'like', "%{$this->search}%")
                ->orWhere('brand', 'like', "%{$this->search}%")
                ->orWhere('serial_number', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            // stock state derived from qty vs min_quantity (whitelisted values, no raw input)
            ->when($this->stockFilter === 'out', fn ($q) => $q->where('quantity', '<=', 0))
            ->when($this->stockFilter === 'low', fn ($q) => $q->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_quantity'))
            ->when($this->stockFilter === 'ok', fn ($q) => $q->whereColumn('quantity', '>', 'min_quantity'))
            ->when($this->prefixFilter, fn ($q) => $q->where('slug', 'like', $this->prefixFilter.'%'))
            ->orderBy('name')
            ->paginate(in_array($this->perPage, [8, 10, 25, 50, 100], true) ? $this->perPage : 8);

        // Stock-state summary (whole live inventory) — out (qty≤0) · low (0<qty≤min) · ok (qty>min).
        // The three partition the set exactly, so ok = total − out − low (one fewer query).
        $out = InventoryItem::where('quantity', '<=', 0)->count();
        $low = InventoryItem::where('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_quantity')->count();
        $totalItems = InventoryItem::count();

        return view('livewire.inventory.index', [
            'items' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
            'kpi' => [
                'items' => $totalItems,
                'qty' => (int) InventoryItem::sum('quantity'),
                'out' => $out,
                'low' => $low,
                'ok' => max(0, $totalItems - $out - $low),
                'locations' => InventoryItem::whereNotNull('location_id')->distinct()->count('location_id'),
            ],
            'prefixCounts' => InventoryItem::prefixCounts(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->with('unit:id,name')->orderBy('name')->get(['id', 'name', 'unit_id']),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'formBuildings' => $this->location_id ? Building::where('location_id', $this->location_id)->where('is_active', true)->orderBy('name')->get() : collect(),
            'formRooms' => $this->building_id ? Room::where('building_id', $this->building_id)->where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }
}
