@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'],
        'dispatched' => ['DISPATCHED', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'delivered' => ['DELIVERED', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'returned' => ['RETURNED', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
        'cancelled' => ['CANCELLED', 'bg-gray-50 text-gray-500 ring-1 ring-gray-200'],
        default => [strtoupper($s), 'bg-gray-50 text-gray-700 ring-1 ring-gray-200'],
    };
    $srcMeta = fn ($t) => match ($t) {
        'da' => ['OGA-DA', 'bg-sky-50 text-sky-700'],
        'other' => ['OGA-OTH', 'bg-gray-100 text-gray-500'],
        default => ['OGA', 'bg-indigo-50 text-indigo-700'],
    };
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- live KPIs (page identity is already in the app top bar) --}}
        @include('partials._kpi-band', ['tiles' => $kpi])

        {{-- frozen header group: toolbar + chips freeze together --}}
        <div class="sticky top-16 z-30 bg-gray-100">
        {{-- toolbar --}}
        <div class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ OGA/PO/ປາຍທາງ/DA…" class="w-56 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="statusFilter" class="w-36 rounded-md border-gray-300 text-sm">
                    <option value="">All Statuses</option>
                    @foreach (['draft', 'dispatched', 'delivered', 'returned', 'cancelled'] as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <select wire:model.live="perPage" class="shrink-0 rounded-md border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                    @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} ແຖວ</option>@endforeach
                </select>
                @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                @can('oga.create')<a href="{{ route('oga.create') }}" wire:navigate class="text-sm text-white bg-sky-600 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-sky-700 whitespace-nowrap">+ OGA</a>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-x-auto">
            <table class="wh-list w-full text-sm">
                <thead class="bg-slate-100 text-slate-600 border-b-2 border-slate-200">
                    <tr class="text-xs font-semibold uppercase tracking-wide">
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ໄອດີ <span class="text-gray-400">(OGA No.)</span></th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ແຫຼ່ງ</th>
                        <th class="text-left font-semibold px-3 py-1.5">ປາຍທາງ</th>
                        <th class="text-left font-semibold px-3 py-1.5 w-full">ສິນຄ້າ</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ວັນທີ</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ສະຖານະ</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ລາຍລະອຽດ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        @php [$lbl, $cls] = $statusMeta($r->status); [$slbl, $scls] = $srcMeta($r->source_type); @endphp
                        <tr wire:key="oga-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 align-top whitespace-nowrap"><a href="{{ route('oga.show', $r) }}" wire:navigate class="font-mono text-sm text-indigo-600 hover:underline">{{ $r->oga_number }}</a></td>
                            <td class="px-3 py-1.5 align-top whitespace-nowrap"><span class="text-xs rounded px-2 py-0.5 {{ $scls }}">{{ $slbl }}</span>@if ($r->source_da_number)<div class="text-xs text-gray-400 mt-0.5">{{ $r->source_da_number }}</div>@endif</td>
                            <td class="px-3 py-1.5 align-top text-xs text-gray-600">{{ $r->dispatch_to_name ?? $r->supplier?->name ?? '—' }}</td>
                            <td class="px-3 py-1.5 align-top text-xs text-gray-600 w-full">{{ Str::limit($r->goods_consigned, 40) ?: '—' }}</td>
                            <td class="px-3 py-1.5 align-top text-xs text-gray-500 whitespace-nowrap">{{ $r->date?->format('d/m/Y') }}<div class="text-gray-400">{{ strtoupper($r->ship_via ?? '') }}</div></td>
                            <td class="px-3 py-1.5 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span></td>
                            <td class="px-3 py-1.5 align-top whitespace-nowrap">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <a href="{{ route('oga.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">View Details</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">ຍັງບໍ່ມີ OGA</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('oga.show', $r) }}" wire:navigate @endif wire:key="moga-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-mono text-xs text-indigo-600">{{ $r->oga_number }}</div>
                            <div class="font-semibold text-gray-800">{{ $r->dispatch_to_name ?? '—' }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ Str::limit($r->goods_consigned, 40) }} · {{ $r->date?->format('d/m/Y') }}</div>
                        </div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    @if ($showDeleted)<div class="mt-2 text-right"><button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5">↩ ກູ້ຄືນ</button></div>@endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີ OGA</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
