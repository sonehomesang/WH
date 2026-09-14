<?php

namespace App\Livewire\AreaInspection;

use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\AreaInspection;
use App\Models\AreaInspectionTemplate;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/** ກວດ ສະຖານທີ່ (Area/Workplace Inspection) — ບັນທຶກ + ລິສ + ຮັບຊາບ. */
#[Layout('layouts.app')]
class Index extends Component
{
    use SoftDeletesWithReason;
    use WithFileUploads;
    use WithPagination;

    public int $perPage = 8;               // rows per page (whitelisted in render) — no inner scroll

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    // ── record modal ──
    public bool $showForm = false;

    public ?int $fTemplateId = null;

    public ?int $fLocationId = null;

    public string $fLocationLabel = '';

    public string $fDate = '';

    public string $fTime = '';

    public string $fFrequency = 'monthly';

    /** @var array<int,string> ຜູ້ ກວດ (ສูงສุด 3) */
    public array $fInspectors = ['', '', ''];

    public string $fNotes = '';

    /** @var array<int,array{label:string,requirement:string,status:string,observation:string}> */
    public array $checklist = [];

    /** @var array<int,TemporaryUploadedFile> ຮູບ ຫຼັກຖານ ຕໍ່ ຂໍ້ (ສະເພາະ ຂໍ້ NC) */
    public array $photos = [];

    /** @var array<int,TemporaryUploadedFile> ຮູບ ພາບ ລວມ ຂອງ ສະຖານທີ່ (ສูงສุด 3) */
    public array $overviewPhotos = [];

    // ── tab + template management (ແມ່ແບບ) ──
    public string $tab = 'records';   // records | templates

    public bool $showTemplateForm = false;

    public ?int $tEditingId = null;

    public string $tName = '';

    public string $tFrequency = 'monthly';

    public bool $tActive = true;

    /** @var array<int,array{label:string,requirement:string}> */
    public array $tItems = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('area_inspection.view'), 403);
        $this->fDate = now()->toDateString();
        if (request('tab') === 'templates') {
            $this->tab = 'templates';
        }
    }

    protected function deleteModelClass(): string
    {
        return AreaInspection::class;
    }

    protected function deletePermission(): string
    {
        return 'area_inspection.delete';
    }

    protected function deleteNoun(): string
    {
        return 'ໃບ ກວດ';
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->inspection_number ?? $record->locationName();
    }

    protected function canAcknowledge(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('area_inspection.activate');
    }

    // ── record ──
    public function newInspection(): void
    {
        abort_unless(auth()->user()->can('area_inspection.create'), 403);
        $this->reset(['fTemplateId', 'fLocationId', 'fLocationLabel', 'fTime', 'fNotes', 'checklist', 'photos', 'overviewPhotos']);
        $this->fInspectors = ['', '', ''];
        $this->fDate = now()->toDateString();
        $this->fFrequency = 'monthly';
        $this->resetValidation();
        $this->showForm = true;
    }

    /** ເລືອກ ແມ່ແບບ → ໂຫຼດ ຂໍ້ ເຂົ້າ checklist. */
    public function updatedFTemplateId($value): void
    {
        $t = AreaInspectionTemplate::find($value);
        if (! $t) {
            $this->checklist = [];

            return;
        }
        $this->fFrequency = $t->frequency;
        $this->checklist = collect($t->normalizedItems())
            ->map(fn ($x) => ['label' => $x['label'], 'requirement' => $x['requirement'], 'status' => 'C', 'observation' => ''])
            ->all();
        $this->photos = [];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('area_inspection.create'), 403);

        $this->validate([
            'fLocationId' => ['nullable', 'exists:locations,id'],
            'fLocationLabel' => ['nullable', 'string', 'max:256'],
            'fDate' => ['required', 'date'],
            'fTime' => ['nullable', 'string', 'max:16'],
            'fFrequency' => ['required', 'in:'.implode(',', AreaInspectionTemplate::FREQUENCIES)],
            'checklist' => ['required', 'array', 'min:1'],
            'checklist.*.status' => ['required', 'in:C,NC,NA'],
            'checklist.*.observation' => ['nullable', 'string', 'max:500'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'overviewPhotos' => ['nullable', 'array', 'max:3'],
            'overviewPhotos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], ['checklist' => 'ເຊັກລິສ', 'overviewPhotos' => 'ຮູບ ພາບ ລວມ']);

        // ຕ້ອງ ມີ ສະຖານທີ່ (ເລືອກ ຫຼື ພິມ).
        if (! $this->fLocationId && trim($this->fLocationLabel) === '') {
            $this->addError('fLocationLabel', 'ຕ້ອງ ເລືອກ ຫຼື ພິມ ສະຖານທີ່.');

            return;
        }

        $label = trim($this->fLocationLabel) !== ''
            ? trim($this->fLocationLabel)
            : (string) optional(Location::find($this->fLocationId))->name;

        // ຮູບ ຫຼັກຖານ ຕໍ່ ຂໍ້ (ຝັງ ວັນ+ເວລາ) — ໂດຍ ປົກກະຕິ ມີ ສະເພາະ ຂໍ້ NC.
        $items = [];
        foreach ($this->checklist as $i => $row) {
            $status = in_array($row['status'] ?? 'C', ['C', 'NC', 'NA'], true) ? $row['status'] : 'C';
            $path = null;
            if ($status === 'NC' && isset($this->photos[$i]) && $this->photos[$i]) {
                $path = $this->stampAndStore($this->photos[$i], 'area-inspection');
            }
            $items[] = [
                'label' => $row['label'] ?? '',
                'requirement' => $row['requirement'] ?? '',
                'status' => $status,
                'observation' => $status === 'NC' ? ($row['observation'] ?? '') : '',
                'photo' => $path,
            ];
        }

        // ຮູບ ພາບ ລວມ ຂອງ ສະຖານທີ່ (ຝັງ ວັນ+ເວລາ).
        $overview = [];
        foreach (array_slice(array_values($this->overviewPhotos), 0, 3) as $file) {
            if ($file) {
                $overview[] = $this->stampAndStore($file, 'area-inspection/overview');
            }
        }

        $hasNc = collect($items)->contains(fn ($x) => $x['status'] === 'NC');

        DB::transaction(function () use ($label, $items, $overview, $hasNc) {
            AreaInspection::create([
                'inspection_number' => $this->nextNumber(),
                'template_id' => $this->fTemplateId,
                'location_id' => $this->fLocationId,
                'location_label' => $label,
                'inspected_on' => $this->fDate,
                'inspected_time' => $this->fTime ?: null,
                'frequency' => $this->fFrequency,
                'inspectors' => array_values(array_filter(array_map('trim', $this->fInspectors))),
                'checklist' => $items,
                'overview_photos' => $overview,
                'result' => $hasNc ? 'has_nc' : 'compliant',
                'next_due_date' => $this->computeNextDue($this->fDate, $this->fFrequency),
                'notes' => $this->fNotes ?: null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

        $this->showForm = false;
        session()->flash('ok', '✓ ບັນທຶກ ໃບ ກວດ ສະຖານທີ່ ແລ້ວ');
        $this->reset(['checklist', 'photos', 'overviewPhotos']);
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
                $path = rtrim($dir, '/').'/ai_'.Str::random(32).'.jpg';
                Storage::disk('public')->put($path, $bytes);

                return $path;
            }
        } catch (\Throwable $ex) {
            // fall through to plain store
        }

        return $photo->store($dir, 'public');
    }

    public function acknowledge(int $id): void
    {
        abort_unless($this->canAcknowledge(), 403);
        $r = AreaInspection::findOrFail($id);
        $u = auth()->user();
        $r->update([
            'acknowledged_by' => $u->id,
            'acknowledged_by_name' => $u->display_name ?? $u->email,
            'acknowledged_at' => now(),
        ]);
        session()->flash('ok', '✓ ຮັບຊາບ ໃບ ກວດ ແລ້ວ');
    }

    public function unacknowledge(int $id): void
    {
        abort_unless($this->canAcknowledge(), 403);
        AreaInspection::whereKey($id)->update(['acknowledged_by' => null, 'acknowledged_by_name' => null, 'acknowledged_at' => null]);
    }

    // ── template management (tab ແມ່ແບບ — ຄື Equipment › ແມ່ແບບ) ──
    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'templates' ? 'templates' : 'records';
    }

    public function newTemplate(): void
    {
        abort_unless(auth()->user()->can('area_inspection.create'), 403);
        $this->reset(['tEditingId', 'tName']);
        $this->tFrequency = 'monthly';
        $this->tActive = true;
        $this->tItems = [['label' => '', 'requirement' => '']];
        $this->resetValidation();
        $this->showTemplateForm = true;
    }

    public function editTemplate(int $id): void
    {
        abort_unless(auth()->user()->can('area_inspection.edit'), 403);
        $t = AreaInspectionTemplate::findOrFail($id);
        $this->tEditingId = $t->id;
        $this->tName = $t->name;
        $this->tFrequency = $t->frequency;
        $this->tActive = (bool) $t->is_active;
        $this->tItems = $t->normalizedItems() ?: [['label' => '', 'requirement' => '']];
        $this->resetValidation();
        $this->showTemplateForm = true;
    }

    public function addTemplateItem(): void
    {
        $this->tItems[] = ['label' => '', 'requirement' => ''];
    }

    public function removeTemplateItem(int $i): void
    {
        unset($this->tItems[$i]);
        $this->tItems = array_values($this->tItems);
        if (empty($this->tItems)) {
            $this->tItems = [['label' => '', 'requirement' => '']];
        }
    }

    public function saveTemplate(): void
    {
        abort_unless(auth()->user()->can('area_inspection.'.($this->tEditingId ? 'edit' : 'create')), 403);

        $this->validate([
            'tName' => ['required', 'string', 'max:256'],
            'tFrequency' => ['required', 'in:'.implode(',', AreaInspectionTemplate::FREQUENCIES)],
            'tItems' => ['required', 'array', 'min:1'],
            'tItems.*.label' => ['nullable', 'string', 'max:256'],
            'tItems.*.requirement' => ['nullable', 'string', 'max:500'],
        ], [], ['tName' => 'ຊື່ ແມ່ແບບ']);

        $items = collect($this->tItems)
            ->map(fn ($x) => ['label' => trim((string) ($x['label'] ?? '')), 'requirement' => trim((string) ($x['requirement'] ?? ''))])
            ->filter(fn ($x) => $x['label'] !== '')
            ->values()->all();

        if (! $items) {
            $this->addError('tItems', 'ຕ້ອງ ມີ ຢ່າງໜ້ອຍ 1 ຂໍ້ (ໃສ່ ຊື່ ຂໍ້).');

            return;
        }

        $data = ['name' => $this->tName, 'frequency' => $this->tFrequency, 'items' => $items, 'is_active' => $this->tActive, 'updated_by' => auth()->id()];
        if ($this->tEditingId) {
            AreaInspectionTemplate::findOrFail($this->tEditingId)->update($data);
        } else {
            AreaInspectionTemplate::create($data + ['created_by' => auth()->id()]);
        }

        $this->showTemplateForm = false;
        session()->flash('ok', '✓ ບັນທຶກ ແມ່ແບບ ແລ້ວ');
    }

    public function toggleTemplateActive(int $id): void
    {
        $t = AreaInspectionTemplate::findOrFail($id);
        abort_unless(auth()->user()->can('area_inspection.'.($t->is_active ? 'deactivate' : 'activate')), 403);
        $t->update(['is_active' => ! $t->is_active, 'updated_by' => auth()->id()]);
    }

    protected function nextNumber(): string
    {
        $year = now()->year;
        $prefix = "AI{$year}-";
        $max = AreaInspection::withTrashed()->where('inspection_number', 'like', $prefix.'%')->lockForUpdate()->max('inspection_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    protected function computeNextDue(string $date, string $freq): ?string
    {
        $d = Carbon::parse($date);

        return match ($freq) {
            'daily' => $d->addDay()->toDateString(),
            'weekly' => $d->addWeek()->toDateString(),
            'monthly' => $d->addMonth()->toDateString(),
            'quarterly' => $d->addMonths(3)->toDateString(),
            'annual' => $d->addYear()->toDateString(),
            default => null,
        };
    }

    public function render(): View
    {
        $canDel = $this->showDeleted && $this->canManageDeleted();
        $q = AreaInspection::query()->with(['template', 'location', 'acknowledgedBy']);
        if ($canDel) {
            $q->onlyTrashed()->with('deletedBy');
        }

        $total = AreaInspection::count();
        $hasNc = AreaInspection::where('result', 'has_nc')->count();

        return view('livewire.area-inspection.index', [
            'rows' => $q->orderByDesc('id')->paginate(in_array($this->perPage, [8, 10, 25, 50, 100], true) ? $this->perPage : 8),
            'templates' => AreaInspectionTemplate::where('is_active', true)->orderBy('name')->get(['id', 'name', 'frequency']),
            'allTemplates' => AreaInspectionTemplate::orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'canAck' => $this->canAcknowledge(),
            'freqLabels' => AreaInspectionTemplate::FREQ_LABELS,
            'kpi' => [
                ['label' => '📋 ໃບ ກວດ ທັງໝົດ', 'value' => $total, 'hint' => 'records'],
                ['label' => '⚠️ ພົບ NC', 'value' => $hasNc, 'hint' => 'has NC', 'tone' => 'text-rose-600'],
                ['label' => '✅ ຜ່ານ', 'value' => $total - $hasNc, 'hint' => 'compliant'],
                ['label' => '📅 ຮອດ ກຳນົດ', 'value' => AreaInspection::whereDate('next_due_date', '<=', today())->count(), 'hint' => 'due', 'tone' => 'text-amber-600'],
                ['label' => '👍 ຍັງ ບໍ່ ຮັບຊາບ', 'value' => AreaInspection::whereNull('acknowledged_at')->count(), 'hint' => 'unack', 'tone' => 'text-amber-600'],
            ],
        ]);
    }
}
