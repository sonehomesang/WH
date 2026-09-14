<?php

namespace App\Livewire\Da;

use App\Models\DiscrepancyAdvice;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public int $perPage = 8;               // rows per page (whitelisted in render) — no inner scroll

    public bool $showDeleted = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('da.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    protected function canManageDeleted(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->can('da.edit');
    }

    public function toggleDeleted(): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $this->showDeleted = ! $this->showDeleted;
        $this->resetPage();
    }

    public function restore(int $id): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $r = DiscrepancyAdvice::onlyTrashed()->find($id);
        $u = auth()->user();
        // ກູ້ຄືນ ໄດ້ ສະເພາະ ໃບ ທີ່ ຢູ່ ໃນ scope ການ ເບິ່ງ ຂອງ ຕົນ (ກັນ IDOR ຂ້າມ ເຈົ້າ ຂອງ).
        $privileged = $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff', 'approver', 'line_manager']);
        if ($r && ($privileged || $r->raised_by === $u->id)) {
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $r->history()->create([
                'action' => 'restore', 'status' => $r->status, 'user_id' => $u->id,
                'user_name' => $u->display_name ?? $u->email, 'comment' => 'restore', 'created_at' => now(),
            ]);
            session()->flash('ok', '✓ ກູ້ຄືນ '.$r->da_number);
        }
    }

    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = DiscrepancyAdvice::query()->with('supplier');

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if (! ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff', 'approver', 'line_manager']))) {
            $q->where('raised_by', $u->id);
        }

        return $q;
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('da_number', 'like', "%{$this->search}%")
                ->orWhere('po_number', 'like', "%{$this->search}%")
                ->orWhere('supplier_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('id')
            ->paginate(in_array($this->perPage, [8, 10, 25, 50, 100], true) ? $this->perPage : 8);

        $counts = $this->scopedQuery()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $chip = fn ($k, $l, $c, $a = false) => ['key' => $k, 'label' => $l, 'count' => $c, 'alert' => $a];

        return view('livewire.da.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
            'chips' => [
                $chip('', 'ທັງໝົດ', $counts->sum()),
                $chip('submitted', 'submitted', $counts['submitted'] ?? 0, true),
                $chip('purchasing_review', 'purchasing', $counts['purchasing_review'] ?? 0, true),
                $chip('pending_approval', 'ລໍ approve', $counts['pending_approval'] ?? 0, true),
                $chip('resolved', 'resolved', $counts['resolved'] ?? 0),
                $chip('draft', 'draft', $counts['draft'] ?? 0),
                $chip('cancelled', 'ຍົກເລີກ', $counts['cancelled'] ?? 0),
            ],
            'kpi' => [
                ['label' => '🧾 DA ທັງໝົດ', 'value' => $counts->sum(), 'hint' => 'records'],
                ['label' => '⏳ submitted', 'value' => $counts['submitted'] ?? 0, 'hint' => 'submitted', 'tone' => 'text-amber-600'],
                ['label' => '🔍 purchasing', 'value' => $counts['purchasing_review'] ?? 0, 'hint' => 'review', 'tone' => 'text-amber-600'],
                ['label' => '✍️ ລໍ approve', 'value' => $counts['pending_approval'] ?? 0, 'hint' => 'pending', 'tone' => 'text-amber-600'],
                ['label' => '✅ resolved', 'value' => $counts['resolved'] ?? 0, 'hint' => 'resolved'],
            ],
        ]);
    }
}
