<?php

namespace App\Livewire\Deposit;

use App\Livewire\Concerns\SoftDeletesWithReason;
use App\Models\DepositRecord;
use App\Models\Unit;
use App\Support\ConditionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use SoftDeletesWithReason, WithPagination;

    /** ໃບ ຝາກ ທີ່ ລຶບ ໄດ້ (ຄື ໜ້າ ລາຍລະອຽດ) — ຍັງ ບໍ່ ຮັບ ເຂົ້າ ສາງ / ຈົບ ວົງຈອນ ແລ້ວ. */
    public const DELETABLE = ['draft', 'cancelled', 'claimed'];

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public string $unitFilter = '';

    public string $conditionFilter = '';

    public int $perPage = 8;               // rows per page (whitelisted in render) — no inner scroll

    public function mount(): void
    {
        abort_unless(auth()->user()->can('deposit.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingUnitFilter(): void
    {
        $this->resetPage();
    }

    public function updatingConditionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    // ── delete-with-reason (trait) — ໃຊ້ pattern ດຽວ ກັບ Disposal ແລະ ໂມດູລ ອື່ນ ──
    protected function deleteModelClass(): string
    {
        return DepositRecord::class;
    }

    protected function deletePermission(): string
    {
        return 'deposit.delete';
    }

    protected function deleteNoun(): string
    {
        return 'ໃບ ຝາກ';
    }

    protected function deleteLabel(Model $record): string
    {
        return $record->request_number;
    }

    /** ລຶບ ໄດ້ ສະເພາະ ໃບ ທີ່ ຍັງ ບໍ່ ຮັບ ເຂົ້າ ສາງ / ຈົບ ວົງຈອນ ແລ້ວ (ຄື ໜ້າ ລາຍລະອຽດ). */
    protected function deleteGuard(Model $record): void
    {
        abort_unless(in_array($record->status, self::DELETABLE, true), 403);
    }

    /** override trait: ບັນທຶກ history timeline ນຳ (ຮັກສາ audit ຂອງ ໃບ ຝາກ). */
    public function deleteRecord(): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $this->validate(
            ['deleteReason' => ['required', 'string', 'max:500']],
            ['deleteReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ລຶບ.']
        );
        $r = DepositRecord::findOrFail($this->deletingId);
        $this->deleteGuard($r);
        $u = auth()->user();
        $r->forceFill(['deleted_reason' => $this->deleteReason, 'deleted_by' => $u->id])->save();
        $r->history()->create([
            'action' => 'delete', 'status' => $r->status, 'user_id' => $u->id,
            'user_name' => $u->display_name ?? $u->email, 'comment' => $this->deleteReason, 'created_at' => now(),
        ]);
        $r->delete();
        $this->deletingId = null;
        $this->deleteReason = '';
        session()->flash('ok', '✓ ລຶບ ໃບ ຝາກ '.$r->request_number.' (ຍ້າຍ ໄປ Deleted Log · ກູ້ຄືນ ໄດ້)');
    }

    /** override trait: ກູ້ຄືນ + ບັນທຶກ history. */
    public function restore(int $id): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $r = DepositRecord::onlyTrashed()->find($id);
        if ($r) {
            $u = auth()->user();
            $r->restore();
            $r->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $r->history()->create([
                'action' => 'restore', 'status' => $r->status, 'user_id' => $u->id,
                'user_name' => $u->display_name ?? $u->email, 'comment' => 'restore', 'created_at' => now(),
            ]);
            session()->flash('ok', '✓ ກູ້ຄືນລາຍການ '.$r->request_number);
        }
    }

    /** Visibility: admin/warehouse see all; ຄົນອື່ນ ເຫັນຂອງຕົນ. */
    protected function scopedQuery()
    {
        $u = auth()->user();
        $q = DepositRecord::query()->with(['items.photos', 'unit']);

        if ($this->showDeleted && $this->canManageDeleted()) {
            $q->onlyTrashed();
        }

        if ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff'])) {
            return $q;
        }
        if ($u->transactionScope() === 'department' && $u->department_id) {
            return $q->where('owner_dept_id', $u->department_id);
        }
        $q->where('owner_user_id', $u->id);

        return $q;
    }

    public function render(): View
    {
        $items = $this->scopedQuery()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('request_number', 'like', "%{$this->search}%")
                ->orWhere('owner_name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter === 'needs_info', fn ($q) => $q->needsOfficeInfo())
            ->when($this->statusFilter && $this->statusFilter !== 'needs_info', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('request_type', $this->typeFilter))
            ->when($this->unitFilter, fn ($q) => $q->where('owner_unit_id', $this->unitFilter))
            ->when($this->conditionFilter, fn ($q) => $q->whereHas('items', fn ($w) => $w->where('condition_status', $this->conditionFilter)))
            ->orderByDesc('id')
            ->paginate(in_array($this->perPage, [8, 10, 25, 50, 100], true) ? $this->perPage : 8);

        $counts = $this->scopedQuery()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $needsInfo = $this->scopedQuery()->needsOfficeInfo()->count();
        $chip = fn ($k, $l, $c, $a = false) => ['key' => $k, 'label' => $l, 'count' => $c, 'alert' => $a];

        return view('livewire.deposit.index', [
            'records' => $items,
            'canManageDeleted' => $this->canManageDeleted(),
            'chips' => [
                $chip('', 'ທັງໝົດ', $counts->sum()),
                $chip('needs_info', 'ຮ່າງ ລໍ ຕື່ມ ຂໍ້ມູນ', $needsInfo, true),
                $chip('submitted', app()->getLocale() === 'en' ? 'Awaiting receipt' : 'ລໍຮັບ', $counts['submitted'] ?? 0, true),
                $chip('accepted', 'ຮັບແລ້ວ', $counts['accepted'] ?? 0),
                $chip('stored', 'ເກັບໄວ້', $counts['stored'] ?? 0),
                $chip('needs_fix', 'ຕ້ອງແກ້', $counts['needs_fix'] ?? 0, true),
                $chip('claimed', 'ເອົາຄືນແລ້ວ', $counts['claimed'] ?? 0),
                $chip('disposal', 'ກຳລັງຈຳໜ່າຍ', $counts['disposal'] ?? 0),
                $chip('disposed', 'ຈຳໜ່າຍແລ້ວ', $counts['disposed'] ?? 0),
                $chip('draft', 'draft', $counts['draft'] ?? 0),
                $chip('cancelled', 'ຍົກເລີກ', $counts['cancelled'] ?? 0),
            ],
            'kpi' => [
                ['label' => '📥 ໃບ ຝາກ ທັງໝົດ', 'value' => $counts->sum(), 'hint' => 'records'],
                ['label' => '⚠️ ຮ່າງ ລໍ ຕື່ມ ຂໍ້ມູນ', 'value' => $needsInfo, 'hint' => 'needs info', 'tone' => 'text-amber-600'],
                ['label' => '⏳ ລໍ ຮັບ', 'value' => $counts['submitted'] ?? 0, 'hint' => 'submitted', 'tone' => 'text-amber-600'],
                ['label' => '📦 ເກັບ ໄວ້', 'value' => $counts['stored'] ?? 0, 'hint' => 'stored'],
                ['label' => '✅ ເອົາ ຄືນ ແລ້ວ', 'value' => $counts['claimed'] ?? 0, 'hint' => 'claimed'],
            ],
            'units' => Unit::whereIn('id', $this->scopedQuery()->distinct()->pluck('owner_unit_id')->filter())->orderBy('name')->get(['id', 'name']),
            'conditionOptions' => ConditionStatus::options(),
        ]);
    }
}
