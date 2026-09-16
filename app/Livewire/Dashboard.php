<?php

namespace App\Livewire;

use App\Models\BorrowRecord;
use App\Models\DepositRecord;
use App\Models\DiscrepancyAdvice;
use App\Models\EquipmentMaintenance;
use App\Models\MaterialRequest;
use App\Models\Notification;
use App\Models\OutwardsGoodsAdvice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    /** Per-user widget toggles (merged over User::DASHBOARD_DEFAULTS). */
    public array $prefs = [];

    public bool $showSettings = false;

    public function mount(): void
    {
        $this->prefs = auth()->user()->dashboardPrefs();
    }

    /** Flip one widget on/off and persist to the user. */
    public function toggle(string $key): void
    {
        if (! array_key_exists($key, User::DASHBOARD_DEFAULTS)) {
            return;
        }
        $this->prefs[$key] = ! ($this->prefs[$key] ?? true);
        $u = auth()->user();
        // ເກັບ ສະເພาะ key ທີ່ ຮູ້ຈັກ (ກັນ client ຍັດ key ອື່ນ ເຂົ້າ prefs).
        $u->dashboard_prefs = array_intersect_key($this->prefs, User::DASHBOARD_DEFAULTS);
        $u->save();
    }

    protected function isStaff(): bool
    {
        $u = auth()->user();

        return $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']);
    }

    /** @return array<int,array> the 4 hero KPI tiles (icon-square style), gated by permission. */
    protected function heroKpis(): array
    {
        $u = auth()->user();
        $staff = $this->isStaff();
        $k = [];

        if ($u->can('borrow.view')) {
            $q = BorrowRecord::query()->when(! $staff, fn ($w) => $w->where('borrower_user_id', $u->id));
            $active = (clone $q)->whereIn('status', ['active', 'overdue'])->count();
            $overdue = (clone $q)->where('status', 'active')->whereDate('planned_return_date', '<', Carbon::today())->count();
            $k[] = ['key' => 'borrow', 'label' => 'ການຢືມ active', 'value' => $active, 'route' => 'borrow',
                'tone' => $overdue > 0 ? 'red' : 'sky', 'hint' => $overdue > 0 ? (app()->getLocale() === 'en' ? "⏰ {$overdue} overdue" : "⏰ ເກີນກຳນົດ {$overdue}") : 'ບໍ່ມີເກີນກຳນົດ'];
        }

        if ($u->can('request.view')) {
            $q = MaterialRequest::query();
            if ($u->hasRole('supplier') && ! $staff) {
                $q->where('assigned_supplier_id', $u->supplier_id);
            } elseif (! $staff) {
                $q->where('requester_user_id', $u->id);
            }
            $open = (clone $q)->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count();
            $k[] = ['key' => 'request', 'label' => 'ໃບເບີກ ເປີດຢູ່', 'value' => $open, 'route' => 'request',
                'tone' => 'violet', 'hint' => 'submitted → received'];
        }

        if ($u->can('deposit.view')) {
            $q = DepositRecord::query()->when(! $staff, fn ($w) => $w->where('owner_user_id', $u->id));
            $stored = (clone $q)->where('status', 'stored')->count();
            $needsFix = (clone $q)->where('status', 'needs_fix')->count();
            $k[] = ['key' => 'deposit', 'label' => 'ການຝາກເຄື່ອງ', 'value' => $stored, 'route' => 'deposit',
                'tone' => $needsFix > 0 ? 'amber' : 'emerald', 'hint' => $needsFix > 0 ? "⚠ ຕ້ອງແກ້ {$needsFix}" : 'submitted → stored'];
        }

        $unread = Notification::where('user_id', $u->id)->whereNull('read_at')->count();
        $k[] = ['key' => 'bell', 'label' => 'ການແຈ້ງເຕືອນ', 'value' => $unread, 'route' => null,
            'tone' => $unread > 0 ? 'amber' : 'slate', 'hint' => $unread > 0 ? 'ຍັງບໍ່ໄດ້ອ່ານ' : 'ອ່ານໝົດແລ້ວ'];

        return $k;
    }

    /** Combined DA + OGA status counts for the bottom panel. null if no access. */
    protected function daOga(): ?array
    {
        $u = auth()->user();
        $canDa = $u->can('da.view');
        $canOga = $u->can('oga.view');
        if (! $canDa && ! $canOga) {
            return null;
        }
        $staff = $this->isStaff();

        $da = $canDa ? DiscrepancyAdvice::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->all() : [];

        $ogaQ = OutwardsGoodsAdvice::query();
        if ($u->hasRole('supplier') && ! $staff) {
            $ogaQ->where('supplier_id', $u->supplier_id);
        }
        $oga = $canOga ? $ogaQ->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status')->all() : [];

        return [
            'canDa' => $canDa, 'canOga' => $canOga,
            'da' => $da, 'oga' => $oga,
            'daTotal' => array_sum($da), 'ogaTotal' => array_sum($oga),
        ];
    }

    /**
     * Compact per-module status for the "at a glance" panel — total + a couple of
     * status chips per module the user can see. Scoped like the KPIs.
     *
     * @return array<int,array{key:string,label:string,route:string,total:?int,tags:array<int,array{t:string,tone:string}>}>
     */
    protected function moduleGlance(): array
    {
        $u = auth()->user();
        $staff = $this->isStaff();
        $g = [];
        $tag = fn (int $n, string $t, string $tone) => $n > 0 ? ['t' => $t.' '.$n, 'tone' => $tone] : null;

        if ($u->can('borrow.view')) {
            $q = BorrowRecord::query()->when(! $staff, fn ($w) => $w->where('borrower_user_id', $u->id));
            $active = (clone $q)->whereIn('status', ['active', 'overdue'])->count();
            $overdue = (clone $q)->where('status', 'active')->whereDate('planned_return_date', '<', Carbon::today())->count();
            $g[] = ['key' => 'borrow', 'label' => 'ການ ຢືມ', 'route' => 'borrow', 'total' => (clone $q)->count(),
                'tags' => array_values(array_filter([$tag($active, 'active', 'sky'), $tag($overdue, 'overdue', 'red')]))];
        }
        if ($u->can('request.view')) {
            $q = MaterialRequest::query()->when(! $staff && ! $u->hasRole('supplier'), fn ($w) => $w->where('requester_user_id', $u->id));
            $open = (clone $q)->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count();
            $done = (clone $q)->where('status', 'completed')->count();
            $g[] = ['key' => 'request', 'label' => 'ໃບເບີກ', 'route' => 'request', 'total' => (clone $q)->count(),
                'tags' => array_values(array_filter([$tag($open, 'open', 'violet'), $tag($done, 'done', 'emerald')]))];
        }
        if ($u->can('deposit.view')) {
            $q = DepositRecord::query()->when(! $staff, fn ($w) => $w->where('owner_user_id', $u->id));
            $stored = (clone $q)->where('status', 'stored')->count();
            $fix = (clone $q)->where('status', 'needs_fix')->count();
            $g[] = ['key' => 'deposit', 'label' => 'ຝາກ', 'route' => 'deposit', 'total' => (clone $q)->count(),
                'tags' => array_values(array_filter([$tag($stored, 'stored', 'emerald'), $tag($fix, 'fix', 'amber')]))];
        }
        if ($u->can('da.view')) {
            $review = DiscrepancyAdvice::whereIn('status', ['submitted', 'purchasing_review', 'pending_approval'])->count();
            $resolved = DiscrepancyAdvice::where('status', 'resolved')->count();
            $g[] = ['key' => 'da', 'label' => 'DA', 'route' => 'da', 'total' => DiscrepancyAdvice::count(),
                'tags' => array_values(array_filter([$tag($review, 'review', 'amber'), $tag($resolved, 'resolved', 'emerald')]))];
        }
        if ($u->can('oga.view')) {
            $q = OutwardsGoodsAdvice::query()->when($u->hasRole('supplier') && ! $staff, fn ($w) => $w->where('supplier_id', $u->supplier_id));
            $disp = (clone $q)->where('status', 'dispatched')->count();
            $deliv = (clone $q)->where('status', 'delivered')->count();
            $g[] = ['key' => 'oga', 'label' => 'OGA', 'route' => 'oga', 'total' => (clone $q)->count(),
                'tags' => array_values(array_filter([$tag($disp, 'dispatched', 'sky'), $tag($deliv, 'delivered', 'emerald')]))];
        }
        if ($staff && $u->can('equipment.view')) {
            $cm = EquipmentMaintenance::openRepairs()->count();
            $g[] = ['key' => 'equipment', 'label' => 'ເຄື່ອງມື', 'route' => 'equipment', 'total' => null,
                'tags' => array_values(array_filter([$tag($cm, 'CM ຄ້າງ', 'red')]))];
        }

        return $g;
    }

    /** @return array<int,array{label:string,count:int,route:string,alert:bool}> role-specific to-do rows (count>0 only). */
    protected function actionRows(): array
    {
        $u = auth()->user();
        $uid = $u->id;
        $staff = $this->isStaff();
        $supplier = $u->hasRole('supplier') && ! $staff;
        $rows = [];

        if ($staff) {
            $rows[] = ['label' => 'ໃບເບີກ ລໍ້ dispatch', 'route' => 'request',
                'count' => MaterialRequest::whereIn('status', ['approved', 'validated'])->count(), 'alert' => false];
            $rows[] = ['label' => 'ໃບເບີກ ລໍ້ receive', 'route' => 'request',
                'count' => MaterialRequest::where('status', 'dispatched')->count(), 'alert' => false];
            $rows[] = ['label' => 'ການຢືມ ເກີນກຳນົດ', 'route' => 'borrow',
                'count' => BorrowRecord::where('status', 'active')->whereDate('planned_return_date', '<', Carbon::today())->count(), 'alert' => true];
            $rows[] = ['label' => 'ການຝາກ ລໍ້ຮັບເຂົ້າ', 'route' => 'deposit',
                'count' => DepositRecord::whereIn('status', ['submitted', 'accepted'])->count(), 'alert' => false];
            $rows[] = ['label' => 'DA ລໍ້ approve', 'route' => 'da',
                'count' => DiscrepancyAdvice::whereIn('status', ['submitted', 'purchasing_review', 'pending_approval'])->count(), 'alert' => false];
            $rows[] = ['label' => 'OGA ລໍ້ dispatch', 'route' => 'oga',
                'count' => OutwardsGoodsAdvice::where('status', 'draft')->count(), 'alert' => false];
            // ໃບ ສ້ອມ (CM) ທີ່ ຍັງ ບໍ່ ແລ້ວ — ລວມ ອັນ ທີ່ ສ້າງ ຈາກ ຂໍ້ NG (C3).
            $rows[] = ['label' => 'ວຽກ ສ້ອມ ເຄື່ອງ (CM) ລໍ ເຮັດ', 'route' => 'equipment',
                'count' => EquipmentMaintenance::openRepairs()->count(), 'alert' => true];
            // completed requests that have not opened a SAP PR/FR yet (sap_status empty)
            $rows[] = ['label' => 'ໃບເບີກເຄື່ອງ ຍັງບໍ່ເປີດ PR/FR', 'route' => 'request',
                'count' => MaterialRequest::where('status', 'completed')->whereNull('sap_status')->count(), 'alert' => false];
        }

        if ($u->hasRole('approver') && ! $staff) {
            $rows[] = ['label' => 'ໃບເບີກ ລໍ້ approve', 'route' => 'request',
                'count' => MaterialRequest::where('status', 'submitted')->count(), 'alert' => true];
            $rows[] = ['label' => 'ການຢືມ ລໍ້ approve', 'route' => 'borrow',
                'count' => BorrowRecord::whereIn('status', ['draft', 'acknowledged'])->count(), 'alert' => false];
        }

        if ($supplier) {
            $sid = $u->supplier_id;
            $rows[] = ['label' => 'orders ລໍ້ validate', 'route' => 'request',
                'count' => MaterialRequest::where('assigned_supplier_id', $sid)->where('status', 'approved')->count(), 'alert' => true];
            $rows[] = ['label' => 'orders ລໍ້ dispatch', 'route' => 'request',
                'count' => MaterialRequest::where('assigned_supplier_id', $sid)->where('status', 'validated')->count(), 'alert' => false];
        }

        // My-side rows — everyone who can create sees their own pending work.
        if ($u->can('borrow.view') || $u->can('request.view') || $u->can('deposit.view')) {
            $myDrafts = BorrowRecord::where('borrower_user_id', $uid)->where('status', 'draft')->count()
                + MaterialRequest::where('requester_user_id', $uid)->where('status', 'draft')->count()
                + DepositRecord::where('owner_user_id', $uid)->where('status', 'draft')->count();
            $rows[] = ['label' => 'ຮ່າງຂອງຂ້ອຍ ລໍ້ submit', 'route' => 'borrow', 'count' => $myDrafts, 'alert' => false];

            $myOverdue = BorrowRecord::where('borrower_user_id', $uid)->where('status', 'active')
                ->whereDate('planned_return_date', '<', Carbon::today())->count();
            $rows[] = ['label' => 'ການຢືມຂອງຂ້ອຍ ເກີນກຳນົດ', 'route' => 'borrow', 'count' => $myOverdue, 'alert' => true];
        }

        return array_values(array_filter($rows, fn ($r) => $r['count'] > 0));
    }

    /** @return array<int,array> last 10 status transitions across workflows (role-scoped). */
    protected function recentActivity(): array
    {
        $u = auth()->user();
        $staff = $this->isStaff();
        $supplier = $u->hasRole('supplier') && ! $staff;
        $items = [];

        $pull = function (string $hTable, string $pTable, string $numberCol, string $kind, string $route, ?string $ownerCol, $ownerVal) {
            $q = DB::table("{$hTable} as h")
                ->join("{$pTable} as p", 'p.id', '=', 'h.record_id')
                ->whereNull('p.deleted_at')
                ->selectRaw("h.status, h.user_name, h.created_at, p.{$numberCol} as number")
                ->orderByDesc('h.id')->limit(15);
            if ($ownerCol !== null) {
                $q->where("p.{$ownerCol}", $ownerVal);
            }

            return $q->get()->map(fn ($r) => [
                'kind' => $kind, 'route' => $route, 'number' => $r->number,
                'status' => $r->status, 'actor' => $r->user_name ?? '—',
                'when' => $r->created_at ? Carbon::parse($r->created_at) : null,
            ])->all();
        };

        if ($u->can('borrow.view')) {
            $items = array_merge($items, $pull('borrow_history', 'borrow_records', 'request_number', 'borrow', 'borrow', $staff ? null : 'borrower_user_id', $u->id));
        }
        if ($u->can('request.view')) {
            [$col, $val] = $staff ? [null, null] : ($supplier ? ['assigned_supplier_id', $u->supplier_id] : ['requester_user_id', $u->id]);
            $items = array_merge($items, $pull('material_request_history', 'material_requests', 'request_number', 'request', 'request', $col, $val));
        }
        if ($u->can('deposit.view')) {
            $items = array_merge($items, $pull('deposit_history', 'deposit_records', 'request_number', 'deposit', 'deposit', $staff ? null : 'owner_user_id', $u->id));
        }
        if ($u->can('da.view') && $staff) {
            $items = array_merge($items, $pull('discrepancy_advice_history', 'discrepancy_advices', 'da_number', 'da', 'da', null, null));
        }
        if ($u->can('oga.view')) {
            $items = array_merge($items, $pull('oga_history', 'outwards_goods_advices', 'oga_number', 'oga', 'oga', $supplier ? 'supplier_id' : null, $u->supplier_id));
        }

        usort($items, fn ($a, $b) => ($b['when']?->timestamp ?? 0) <=> ($a['when']?->timestamp ?? 0));

        return array_slice($items, 0, 30);
    }

    /** Chart data for staff: request-status distribution + 6-month created counts. */
    protected function chartData(): array
    {
        $statusCounts = MaterialRequest::select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status')->all();

        $months = [];
        $borrow = $request = $deposit = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::today()->subMonths($i);
            $start = $m->copy()->startOfMonth();
            $end = $m->copy()->endOfMonth();
            $months[] = $m->format('M');
            $borrow[] = BorrowRecord::whereBetween('created_at', [$start, $end])->count();
            $request[] = MaterialRequest::whereBetween('created_at', [$start, $end])->count();
            $deposit[] = DepositRecord::whereBetween('created_at', [$start, $end])->count();
        }

        return [
            'pie' => ['labels' => array_keys($statusCounts), 'data' => array_values($statusCounts)],
            'bar' => ['labels' => $months, 'borrow' => $borrow, 'request' => $request, 'deposit' => $deposit],
        ];
    }

    public function render(): View
    {
        $prefs = $this->prefs;
        $staff = $this->isStaff();

        return view('livewire.dashboard', [
            'prefs' => $prefs,
            'kpis' => ($prefs['kpi'] ?? true) ? $this->heroKpis() : [],
            'actionRows' => ($prefs['queue'] ?? true) ? $this->actionRows() : [],
            'activity' => ($prefs['activity'] ?? true) ? $this->recentActivity() : [],
            'showCharts' => $staff && ($prefs['charts'] ?? true),
            'chart' => ($staff && ($prefs['charts'] ?? true)) ? $this->chartData() : null,
            'daOga' => $this->daOga(),
            'glance' => $this->moduleGlance(),
        ]);
    }
}
