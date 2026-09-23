@php
    $statusMeta = fn ($s) => match ($s) {
        'finalized' => ['ສຳເລັດແລ້ວ', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        default => ['ຮ່າງ', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
    };
@endphp

<div class="pb-6">
    {{-- box-shadow (not border-bottom) so the sticky column-header divider stays
         glued to the header under border-collapse — a real border is "owned" by
         the first body row and scrolls away. --}}
    <style>.expo-list thead th { box-shadow: inset 0 -2px 0 #cbd5e1; }</style>
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- frozen header group: toolbar + chips freeze; publish its bottom edge
             as --freeze-top so the table header sticks just under it --}}
        <div class="sticky top-16 z-30 bg-gray-100 pt-3 pb-2" x-data
             x-init="const root = document.documentElement;
                     const set = () => root.style.setProperty('--freeze-top', (64 + $el.offsetHeight) + 'px');
                     set(); new ResizeObserver(set).observe($el);">
        {{-- total Expo count → teleported up into the app header top bar --}}
        <template x-teleport="#page-header-slot">
            <span class="hidden lg:inline-flex items-baseline gap-1.5 ml-2 px-2.5 py-1 rounded-lg bg-white/70 border border-white/70 shadow-sm">
                <span class="text-xl font-bold tabular-nums leading-none text-gray-800">{{ number_format($kpi[0]['value']) }}</span>
                <span class="text-xs text-gray-500 whitespace-nowrap">Expo ທັງໝົດ</span>
            </span>
        </template>
        {{-- toolbar --}}
        <div class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ EXP/ຊື່ງານ/ປະເທດ…" class="w-56 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="statusFilter" class="w-36 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ ສະຖານະ</option><option value="draft">ຮ່າງ</option><option value="finalized">ສຳເລັດແລ້ວ</option>
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <select wire:model.live="perPage" class="shrink-0 rounded-md border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                    @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} {{ app()->getLocale() === 'en' ? 'rows' : 'ແຖວ' }}</option>@endforeach
                </select>
                @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-md px-2.5 py-2 min-h-[40px] border whitespace-nowrap {{ $showDeleted ? 'bg-red-600 text-white border-red-600' : 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                @can('expo.create')<a href="{{ route('expo.create') }}" wire:navigate class="text-sm text-white bg-sky-600 rounded-md px-2.5 py-2 min-h-[40px] inline-flex items-center hover:bg-sky-700 whitespace-nowrap">+ ສ້າງ Expo</a>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])

        {{-- date-based metrics the status chips don't cover (kept from the old KPI band) --}}
        <div class="-mt-0.5 mb-2 flex flex-wrap items-center gap-1.5">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 text-amber-700 px-2.5 py-1 text-sm">📅 ກຳລັງຈະໄປ <span class="font-semibold tabular-nums">{{ number_format($kpi[1]['value']) }}</span></span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white text-gray-600 px-2.5 py-1 text-sm">📆 ໄປແລ້ວ <span class="font-semibold tabular-nums">{{ number_format($kpi[2]['value']) }}</span></span>
        </div>
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        <div class="hidden md:block bg-white border border-gray-100 rounded-lg">
            <table class="wh-list expo-list w-full text-sm">
                <thead class="sticky z-20 bg-slate-100 text-slate-600" style="top: var(--freeze-top, 14rem)">
                    <tr class="text-xs font-semibold uppercase tracking-wide">
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ໄອດີ <span class="text-gray-400">(EXP)</span></th>
                        <th class="text-left font-semibold px-3 py-1.5 w-full">ຊື່ງານ</th>
                        <th class="text-left font-semibold px-3 py-1.5">ສະຖານທີ່</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ວັນທີ</th>
                        <th class="text-left font-semibold px-3 py-1.5">ບໍລິສັດ/ຜູ້ໄປ</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ສະຖານະ</th>
                        <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ລາຍລະອຽດ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $r)
                        @php [$lbl, $cls] = $statusMeta($r->status); @endphp
                        <tr wire:key="exp-{{ $r->id }}" class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 align-top whitespace-nowrap"><a href="{{ route('expo.show', $r) }}" wire:navigate class="font-mono text-sm text-indigo-600 hover:underline">{{ $r->expo_number }}</a></td>
                            <td class="px-3 py-1.5 align-top w-full"><div class="font-medium text-gray-800">{{ $r->title }}</div><div class="text-xs text-gray-400">{{ Str::limit($r->topic, 40) }}</div></td>
                            <td class="px-3 py-1.5 align-top text-xs text-gray-600">{{ collect([$r->city, $r->country])->filter()->implode(', ') ?: '—' }}</td>
                            <td class="px-3 py-1.5 align-top text-xs whitespace-nowrap">{{ $r->start_date?->format('d/m/Y') }}@if ($r->end_date)–{{ $r->end_date->format('d/m/Y') }}@endif</td>
                            <td class="px-3 py-1.5 align-top text-xs text-gray-600">{{ $r->companies_count }} ບໍລິສັດ · {{ $r->attendees_count }} ຄົນ</td>
                            <td class="px-3 py-1.5 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span></td>
                            <td class="px-3 py-1.5 align-top whitespace-nowrap">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <a href="{{ route('expo.show', $r) }}" wire:navigate class="text-xs text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50 inline-block">View</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">ຍັງບໍ່ມີ Expo</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-2">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('expo.show', $r) }}" wire:navigate @endif wire:key="mexp-{{ $r->id }}" class="block bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0"><div class="font-mono text-xs text-indigo-600">{{ $r->expo_number }}</div><div class="font-semibold text-gray-800">{{ $r->title }}</div><div class="text-xs text-gray-500">{{ collect([$r->city, $r->country])->filter()->implode(', ') }} · {{ $r->start_date?->format('d/m/Y') }}</div></div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    @if ($showDeleted)<div class="mt-2 text-right"><button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs text-emerald-700 border border-emerald-300 rounded-md px-3 py-1.5">↩ ກູ້ຄືນ</button></div>@endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີ Expo</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
