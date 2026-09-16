@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'acknowledged' => ['PENDING ACK', 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'],
        'approved' => ['APPROVED', 'bg-sky-50 text-sky-700 ring-1 ring-sky-200'],
        'active' => ['IN USE', 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200'],
        'overdue' => ['OVERDUE', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
        'returned' => ['RETURNED', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- live KPIs (page identity is already in the app top bar — no duplicate title) --}}
        <div class="rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden mb-3">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-px bg-gray-100">
                @php
                    $kpiTiles = [
                        ['label' => '📄 ໃບ ຢືມ ທັງໝົດ', 'value' => $kpi['total'], 'hint' => 'records', 'tone' => 'text-gray-800'],
                        ['label' => '🔄 ກຳລັງ ຢືມ ຢູ່', 'value' => $kpi['active'], 'hint' => 'active', 'tone' => 'text-gray-800'],
                        ['label' => '⚠️ ເກີນ ກຳນົດ', 'value' => $kpi['overdue'], 'hint' => 'overdue', 'tone' => 'text-rose-600'],
                        ['label' => '⏰ ໃກ້ ຮອດ ກຳນົດ', 'value' => $kpi['due_soon'], 'hint' => 'ຄືນ ໃນ ≤3 ວັນ', 'tone' => 'text-amber-600'],
                        ['label' => '✅ ສົ່ງ ຄືນ ແລ້ວ', 'value' => $kpi['returned'], 'hint' => 'returned', 'tone' => 'text-gray-800'],
                    ];
                @endphp
                @foreach ($kpiTiles as $t)
                    <div class="bg-white px-4 py-4">
                        <p class="text-sm text-gray-500 truncate">{{ $t['label'] }}</p>
                        <p class="text-3xl font-bold tabular-nums leading-tight mt-1 {{ $t['tone'] }}">{{ number_format($t['value']) }}</p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ $t['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- frozen header group: toolbar + chips freeze together --}}
        <div class="sticky top-16 z-30 bg-gray-100/95 backdrop-blur">
            <div class="flex flex-col gap-2 py-3 sm:py-2 sm:min-h-[52px] sm:flex-row sm:items-center sm:gap-3">
                {{-- left: search + filters (search flexes; the rest hold their width) --}}
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:flex-1 sm:min-w-0">
                    <div class="relative w-full sm:flex-1 sm:min-w-[9rem] sm:max-w-xs">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ BR/ຊື່ຜູ້ຢືມ…" class="w-full pl-8 rounded-lg border-gray-300 text-sm" />
                    </div>
                    <select wire:model.live="statusFilter" class="shrink-0 w-32 rounded-lg border-gray-300 text-sm">
                        <option value="">All Statuses</option>
                        <option value="draft">draft</option><option value="acknowledged">acknowledged</option>
                        <option value="approved">approved</option><option value="active">active (in use)</option>
                        <option value="overdue">overdue</option><option value="returned">returned</option><option value="cancelled">cancelled</option>
                    </select>
                    <select wire:model.live="typeFilter" class="shrink-0 w-28 rounded-lg border-gray-300 text-sm">
                        <option value="">All Types</option>
                        <option value="new_inventory">Inventory</option><option value="tools_equipment">Tools/Equip</option>
                        <option value="deposited_tools">Deposited</option><option value="others">Others</option>
                    </select>
                    <input type="date" wire:model.live="fromDate" class="shrink-0 w-36 rounded-lg border-gray-300 text-sm" title="ຈາກວັນທີ" />
                    <input type="date" wire:model.live="toDate" class="shrink-0 w-36 rounded-lg border-gray-300 text-sm" title="ຫາວັນທີ" />
                </div>
                {{-- right: per-page + actions --}}
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <select wire:model.live="perPage" class="shrink-0 rounded-lg border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                        @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} ແຖວ</option>@endforeach
                    </select>
                    @if ($canDailyCheck)<button wire:click="runDailyCheck" wire:loading.attr="disabled" class="text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 min-h-[40px] hover:bg-amber-100 transition whitespace-nowrap" title="ສົ່ງເຕືອນ ລາຍການ ໃກ້/ເກີນ ກຳນົດ">⏰ Daily Check</button>@endif
                    @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-lg px-3 py-2 min-h-[40px] border transition whitespace-nowrap {{ $showDeleted ? 'bg-rose-600 text-white border-rose-600' : 'text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                    @can('borrow.create')<a href="{{ route('borrow.create') }}" wire:navigate class="text-sm font-medium text-white bg-sky-600 rounded-lg px-3.5 py-2 min-h-[40px] inline-flex items-center hover:bg-sky-700 transition shadow-sm whitespace-nowrap">+ Borrow Request</a>@endcan
                </div>
            </div>

            @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-1.5 mb-3">{{ session('ok') }}</div>@endif

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="wh-list w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600 border-b-2 border-slate-200">
                        <tr class="text-xs font-semibold uppercase tracking-wide">
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ໄອດີ (BR)</th>
                            <th class="text-left font-semibold px-3 py-1.5 w-full">ຜູ້ຢືມ</th>
                            <th class="text-left font-semibold px-3 py-1.5">ເຄື່ອງທີ່ຢືມ</th>
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ລະຫັດ</th>
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ວັນທີ</th>
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ສະຖານະ</th>
                            <th class="text-right font-semibold px-3 py-1.5 whitespace-nowrap">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $r)
                            @php [$lbl, $cls] = $statusMeta($r->display_status); $first = $r->items->first(); $ph = $first?->photos->first() ?? $first?->inventoryItem?->primaryPhoto; $d = $r->days_left; @endphp
                            <tr wire:key="br-{{ $r->id }}" class="hover:bg-indigo-50/40 transition">
                                <td class="px-3 py-1.5 align-top whitespace-nowrap"><a href="{{ route('borrow.show', $r) }}" wire:navigate class="font-mono text-sm font-medium text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                                <td class="px-3 py-1.5 align-top w-full"><div class="font-semibold text-gray-800 truncate max-w-[220px]">{{ $r->borrower_name }}</div><div class="text-xs text-gray-400 truncate max-w-[220px]">{{ $r->unit?->name ?? $r->borrower_email }}</div></td>
                                <td class="px-3 py-1.5 align-top">
                                    <div class="flex gap-2.5">
                                        @if ($ph)<img src="{{ $ph->url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />
                                        @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">🔄</div>@endif
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-800 truncate max-w-[150px]">{{ $first?->item_name ?? '—' }}@if ($r->items->count() > 1) <span class="text-gray-400 text-xs font-normal">+{{ $r->items->count() - 1 }}</span>@endif</div>
                                            <div class="text-xs text-gray-400">Qty: {{ $r->items->sum('qty') }}@if ($r->purpose) · {{ Str::limit($r->purpose, 30) }}@endif</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap">
                                    @foreach ($r->items->take(2) as $bi)
                                        @if ($bi->inventoryItem)<span class="inline-block text-xs font-mono bg-gray-50 text-gray-600 border border-gray-200 rounded px-1.5 py-0.5 mb-0.5">{{ $bi->inventoryItem->slug }}</span>@endif
                                    @endforeach
                                </td>
                                <td class="px-3 py-1.5 align-top text-xs whitespace-nowrap">
                                    <div class="text-gray-700 font-medium">{{ $r->borrow_date?->format('M d, Y') }}</div>
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400 mt-0.5">↩ {{ $r->planned_return_date?->format('M d, Y') }}</div>
                                </td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span>
                                    @if ($d !== null)<div class="text-xs mt-1 {{ $d < 0 ? 'text-rose-600 font-medium' : 'text-gray-400' }}">{{ $d < 0 ? 'ເກີນ '.abs($d).' ມື້' : 'ອີກ '.$d.' ມື້' }}</div>@endif
                                </td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap text-right">
                                    @if ($showDeleted)
                                        <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 transition inline-block">↩ ກູ້ຄືນ</button>
                                        @if ($r->deleted_reason)<div class="text-xs text-gray-400 mt-1 max-w-[12rem] truncate ml-auto" title="{{ $r->deleted_reason }}">{{ $r->deleted_reason }}</div>@endif
                                    @else
                                        <a href="{{ route('borrow.show', $r) }}" wire:navigate class="text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition inline-block">ເບິ່ງ</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-14 text-center text-gray-400"><div class="text-4xl mb-2">🔄</div>ຍັງບໍ່ມີຄຳຂໍຢືມ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2.5">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->display_status); $first = $r->items->first(); $ph = $first?->photos->first() ?? $first?->inventoryItem?->primaryPhoto; $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('borrow.show', $r) }}" wire:navigate @endif wire:key="mbr-{{ $r->id }}" class="block bg-white border border-gray-200 rounded-xl shadow-sm p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex gap-2.5 min-w-0">
                            @if ($ph)<img src="{{ $ph->url }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0" />
                            @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">🔄</div>@endif
                            <div class="min-w-0"><div class="font-mono text-xs text-indigo-600">{{ $r->request_number }}</div><div class="font-semibold text-gray-800">{{ $r->borrower_name }}</div><div class="text-xs text-gray-500 truncate">{{ $first?->item_name }} · Qty {{ $r->items->sum('qty') }}</div></div>
                        </div>
                        <span class="text-xs font-semibold rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-2">{{ $r->borrow_date?->format('M d, Y') }} → {{ $r->planned_return_date?->format('M d, Y') }}</div>
                    @if ($showDeleted)
                        <div class="flex items-center justify-between gap-2 mt-2">
                            @if ($r->deleted_reason)<span class="text-xs text-gray-400 truncate">{{ $r->deleted_reason }}</span>@else<span></span>@endif
                            <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 shrink-0">↩ ກູ້ຄືນ</button>
                        </div>
                    @endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-10"><div class="text-4xl mb-2">🔄</div>ຍັງບໍ່ມີຄຳຂໍຢືມ</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
