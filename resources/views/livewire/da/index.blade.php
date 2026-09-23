@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'],
        'submitted' => ['SUBMITTED', 'bg-sky-50 text-sky-700 ring-1 ring-sky-200'],
        'purchasing_review' => ['PURCHASING', 'bg-violet-50 text-violet-700 ring-1 ring-violet-200'],
        'pending_approval' => ['PENDING APPROVAL', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'resolved' => ['RESOLVED', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'cancelled' => ['CANCELLED', 'bg-gray-50 text-gray-500 ring-1 ring-gray-200'],
        default => [strtoupper($s), 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'],
    };
    $typeLabel = ['incorrect_supplied' => 'ສົ່ງຜິດ', 'oversupplied' => 'ເກີນ', 'undersupplied' => 'ຂາດ', 'damaged' => 'ເສຍ', 'no_paperwork' => 'ບໍ່ມີເອກະສານ', 'other' => 'ອື່ນ'];
@endphp

<div class="pb-6">
    {{-- box-shadow (not border-bottom) so the sticky column-header divider stays
         glued to the header under border-collapse — a real border is "owned" by
         the first body row and scrolls away. --}}
    <style>.da-list thead th { box-shadow: inset 0 -2px 0 #cbd5e1; }</style>
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- frozen header group: toolbar + chips freeze; publish its bottom edge
             as --freeze-top so the table header sticks just under it --}}
        <div class="sticky top-16 z-30 bg-gray-100 pt-3 pb-2" x-data
             x-init="const root = document.documentElement;
                     const set = () => root.style.setProperty('--freeze-top', (64 + $el.offsetHeight) + 'px');
                     set(); new ResizeObserver(set).observe($el);">
        {{-- total DA count → teleported up into the app header top bar --}}
        <template x-teleport="#page-header-slot">
            <span class="hidden lg:inline-flex items-baseline gap-1.5 ml-2 px-2.5 py-1 rounded-lg bg-white/70 border border-white/70 shadow-sm">
                <span class="text-xl font-bold tabular-nums leading-none text-gray-800">{{ number_format($kpi[0]['value']) }}</span>
                <span class="text-xs text-gray-500 whitespace-nowrap">DA ທັງໝົດ</span>
            </span>
        </template>
        {{-- toolbar --}}
        <div class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ DA/PO/supplier…" class="w-52 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="statusFilter" class="w-44 rounded-md border-gray-300 text-sm">
                    <option value="">All Statuses</option>
                    @foreach (['draft', 'submitted', 'purchasing_review', 'pending_approval', 'resolved', 'cancelled'] as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <select wire:model.live="perPage" class="shrink-0 rounded-md border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                    @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} {{ app()->getLocale() === 'en' ? 'rows' : 'ແຖວ' }}</option>@endforeach
                </select>
                @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                @can('da.create')<a href="{{ route('da.create') }}" wire:navigate class="text-sm text-white bg-sky-600 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-sky-700 whitespace-nowrap">+ DA</a>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        <div class="hidden md:block bg-white border border-gray-100 rounded-lg">
            <table class="wh-list da-list w-full text-sm">
                <thead class="sticky z-20 bg-slate-100 text-slate-600" style="top: var(--freeze-top, 14rem)">
                    <tr class="text-xs font-semibold uppercase tracking-wide">
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ໄອດີ <span class="text-gray-400">(DA No.)</span></th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">PO</th>
                        <th class="text-left font-semibold px-3 py-1.5">Supplier</th>
                        <th class="text-left font-semibold px-3 py-1.5 w-full">ປະເພດຄວາມຜິດ</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ວັນທີ</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ສະຖານະ</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ລາຍລະອຽດ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        @php [$lbl, $cls] = $statusMeta($r->status); @endphp
                        <tr wire:key="da-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 align-top whitespace-nowrap"><a href="{{ route('da.show', $r) }}" wire:navigate class="font-mono text-sm text-indigo-600 hover:underline">{{ $r->da_number }}</a></td>
                            <td class="px-3 py-1.5 align-top text-gray-700 whitespace-nowrap">{{ $r->po_number ?? '—' }}</td>
                            <td class="px-3 py-1.5 align-top text-xs text-gray-600">{{ $r->supplier_name ?? $r->supplier?->name ?? '—' }}</td>
                            <td class="px-3 py-1.5 align-top text-xs text-gray-600 w-full">{{ collect($r->discrepancy_types ?? [])->map(fn ($t) => $typeLabel[$t] ?? $t)->implode(', ') ?: '—' }}</td>
                            <td class="px-3 py-1.5 align-top text-xs text-gray-500 whitespace-nowrap">{{ $r->date?->format('d/m/Y') }}</td>
                            <td class="px-3 py-1.5 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span>@if ($r->next_step === 'oga')<div class="text-xs text-sky-600 mt-1">→ OGA</div>@endif</td>
                            <td class="px-3 py-1.5 align-top whitespace-nowrap">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <a href="{{ route('da.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">View Details</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">ຍັງບໍ່ມີ DA</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('da.show', $r) }}" wire:navigate @endif wire:key="mda-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-mono text-xs text-indigo-600">{{ $r->da_number }}</div>
                            <div class="font-semibold text-gray-800">{{ $r->supplier_name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">PO {{ $r->po_number ?? '—' }} · {{ $r->date?->format('d/m/Y') }}</div>
                        </div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    @if ($showDeleted)<div class="mt-2 text-right"><button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5">↩ ກູ້ຄືນ</button></div>@endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີ DA</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
