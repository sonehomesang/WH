<?php

namespace App\Livewire\Ansi;

use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\AnsiApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use SoftDeletesWithReason, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public int $perPage = 8;               // rows per page (whitelisted in render) — no inner scroll

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ansi.view'), 403);
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

    // ── delete-with-reason (trait) ──
    protected function deleteModelClass(): string
    {
        return AnsiApplication::class;
    }

    protected function deletePermission(): string
    {
        return 'ansi.delete';
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->request_number;
    }

    protected function deleteNoun(): string
    {
        return 'ANSI';
    }

    /** Visibility: staff-wide roles see all; others see the ones they originate or must act on. */
    protected function scopedQuery(): Builder
    {
        $u = auth()->user();
        $q = AnsiApplication::query()->with(['unit', 'department', 'items']);

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }
        if ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff'])) {
            return $q;
        }

        return $q->where(fn ($w) => $w
            ->where('originator_user_id', $u->id)
            ->orWhere('hos_user_id', $u->id)
            ->orWhere('manager_user_id', $u->id));
    }

    public function render(): View
    {
        $records = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('request_number', 'like', "%{$this->search}%")
                ->orWhere('originator_name', 'like', "%{$this->search}%")
                ->orWhere('summary_items', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('id')
            ->paginate(in_array($this->perPage, [8, 10, 25, 50, 100], true) ? $this->perPage : 8);

        $counts = $this->scopedQuery()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('livewire.ansi.index', [
            'records' => $records,
            'statusLabels' => AnsiApplication::STATUS_LABELS,
            'canManageDeleted' => $this->canManageDeleted(),
            'kpi' => [
                ['label' => '🆕 ANSI ທັງໝົດ', 'value' => $counts->sum(), 'hint' => 'records'],
                ['label' => '⏳ ລໍ HoS', 'value' => $counts['pending_hos'] ?? 0, 'hint' => 'pending HoS', 'tone' => 'text-amber-600'],
                ['label' => '⏳ ລໍ Manager', 'value' => $counts['pending_manager'] ?? 0, 'hint' => 'pending mgr', 'tone' => 'text-amber-600'],
                ['label' => '📦 ລໍ Warehouse', 'value' => $counts['pending_warehouse'] ?? 0, 'hint' => 'pending WH', 'tone' => 'text-amber-600'],
                ['label' => '✅ completed', 'value' => $counts['completed'] ?? 0, 'hint' => 'completed'],
            ],
        ]);
    }
}
