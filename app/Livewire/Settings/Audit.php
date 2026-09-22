<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class Audit extends Component
{
    use WithPagination;

    public string $search = '';

    public string $moduleFilter = '';

    public string $from = '';

    public string $to = '';

    /** history table → [parent table, number column, label]. */
    public const SOURCES = [
        'borrow' => ['borrow_history', 'borrow_records', 'request_number'],
        'request' => ['material_request_history', 'material_requests', 'request_number'],
        'deposit' => ['deposit_history', 'deposit_records', 'request_number'],
        'da' => ['discrepancy_advice_history', 'discrepancy_advices', 'da_number'],
        'oga' => ['oga_history', 'outwards_goods_advices', 'oga_number'],
        'user' => ['user_history', 'users', 'username'],   // account actions: create/activate/lock…
        'inventory' => ['inventory_history', 'inventory_items', 'slug'],   // item actions: create/update/(de)activate/delete/restore
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('audit.view'), 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'moduleFilter', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    protected function unionQuery()
    {
        $parts = [];
        foreach (self::SOURCES as $label => [$hTable, $pTable, $numCol]) {
            if ($this->moduleFilter !== '' && $this->moduleFilter !== $label) {
                continue;
            }
            $q = DB::table("{$hTable} as h")
                ->join("{$pTable} as p", 'p.id', '=', 'h.record_id')
                ->selectRaw("'{$label}' as module, p.{$numCol} as number, h.action, h.status, h.user_name, h.role, h.comment, h.created_at")
                ->when($this->search !== '', fn ($w) => $w->where(fn ($x) => $x
                    ->where("p.{$numCol}", 'like', "%{$this->search}%")
                    ->orWhere('h.user_name', 'like', "%{$this->search}%")
                    ->orWhere('h.action', 'like', "%{$this->search}%")))
                ->when($this->from !== '', fn ($w) => $w->whereDate('h.created_at', '>=', $this->from))
                ->when($this->to !== '', fn ($w) => $w->whereDate('h.created_at', '<=', $this->to));
            $parts[] = $q;
        }

        $union = array_shift($parts);
        foreach ($parts as $p) {
            $union->unionAll($p);
        }

        return DB::query()->fromSub($union, 'a')->orderByDesc('created_at');
    }

    public function export(): StreamedResponse
    {
        abort_unless(auth()->user()->can('audit.view'), 403);
        $rows = $this->unionQuery()->limit(5000)->get();

        // ກັນ CSV formula-injection: cell ທີ່ ຂຶ້ນຕົ້ນ ດ້ວຍ = + - @ tab cr → ນຳ ໜ້າ ດ້ວຍ ' (ບໍ່ ໃຫ້ Excel ຕີ ເປັນ ສູດ).
        $safe = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$v : $v;

        return response()->streamDownload(function () use ($rows, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Module', 'Number', 'Action', 'Status', 'User', 'Role', 'Comment', 'When']);
            foreach ($rows as $r) {
                fputcsv($out, array_map($safe, [$r->module, $r->number, $r->action, $r->status, $r->user_name, $r->role, $r->comment, (string) $r->created_at]));
            }
            fclose($out);
        }, 'audit-'.now()->format('Ymd-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        return view('livewire.settings.audit', [
            'rows' => $this->unionQuery()->paginate(25),
        ]);
    }
}
