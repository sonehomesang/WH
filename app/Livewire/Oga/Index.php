<?php

namespace App\Livewire\Oga;

use App\Models\OutwardsGoodsAdvice;
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
        abort_unless(auth()->user()->can('oga.view'), 403);
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

        return $u->is_super_admin || $u->can('oga.edit');
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
        $r = OutwardsGoodsAdvice::onlyTrashed()->find($id);
        $u = auth()->user();
        // ກູ້ຄືນ ໄດ້ ສະເພາະ ໃບ ທີ່ ຢູ່ ໃນ scope ການ ເບິ່ງ ຂອງ ຕົນ (ກັນ IDOR ຂ້າມ ຜູ້ສະໜອງ).
        $privileged = $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']);
        if ($r && ($privileged || $r->supplier_id === $u->supplier_id)) {
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $r->history()->create([
                'action' => 'restore', 'status' => $r->status, 'user_id' => $u->id,
                'user_name' => $u->display_name ?? $u->email, 'comment' => 'restore', 'created_at' => now(),
            ]);
            session()->flash('ok', '✓ ກູ້ຄືນ '.$r->oga_number);
        }
    }

    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = OutwardsGoodsAdvice::query()->with('supplier');

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if ($u->hasRole('supplier') && ! $u->is_super_admin && ! $u->hasAnyRole(['admin', 'warehouse_staff'])) {
            $q->where('supplier_id', $u->supplier_id);
        }

        return $q;
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('oga_number', 'like', "%{$this->search}%")
                ->orWhere('po_number', 'like', "%{$this->search}%")
                ->orWhere('dispatch_to_name', 'like', "%{$this->search}%")
                ->orWhere('source_da_number', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('id')
            ->paginate(in_array($this->perPage, [8, 10, 25, 50, 100], true) ? $this->perPage : 8);

        $counts = $this->scopedQuery()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $chip = fn ($k, $l, $c, $a = false) => ['key' => $k, 'label' => $l, 'count' => $c, 'alert' => $a];

        return view('livewire.oga.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
            'chips' => [
                $chip('', 'ທັງໝົດ', $counts->sum()),
                $chip('dispatched', 'ກຳລັງສົ່ງ', $counts['dispatched'] ?? 0, true),
                $chip('delivered', 'ສົ່ງເຖິງ', $counts['delivered'] ?? 0),
                $chip('returned', 'ສົ່ງກັບ', $counts['returned'] ?? 0),
                $chip('draft', 'draft', $counts['draft'] ?? 0),
                $chip('cancelled', 'ຍົກເລີກ', $counts['cancelled'] ?? 0),
            ],
            'kpi' => [
                ['label' => '📤 OGA ທັງໝົດ', 'value' => $counts->sum(), 'hint' => 'records'],
                ['label' => '🚚 ກຳລັງ ສົ່ງ', 'value' => $counts['dispatched'] ?? 0, 'hint' => 'dispatched', 'tone' => 'text-amber-600'],
                ['label' => '📬 ສົ່ງ ເຖິງ', 'value' => $counts['delivered'] ?? 0, 'hint' => 'delivered'],
                ['label' => '↩️ ສົ່ງ ກັບ', 'value' => $counts['returned'] ?? 0, 'hint' => 'returned'],
                ['label' => '📝 draft', 'value' => $counts['draft'] ?? 0, 'hint' => 'draft'],
            ],
        ]);
    }
}
