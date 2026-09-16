@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'],
        'approved' => ['APPROVED', 'bg-sky-50 text-sky-700 ring-1 ring-sky-200'],
        'validated' => ['VALIDATED', 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200'],
        'dispatched' => ['DISPATCHED', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'received' => ['RECEIVED', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'completed' => ['COMPLETED', 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200'],
        'rejected' => ['REJECTED', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- live KPIs (page identity is already in the app top bar — no duplicate title) --}}
        @include('partials._kpi-band', ['tiles' => $kpi])

        {{-- frozen header group: toolbar + chips freeze together --}}
        <div class="sticky top-16 z-30 bg-gray-100/95 backdrop-blur">
            <div class="flex flex-col gap-2 py-3 sm:py-2 sm:min-h-[52px] sm:flex-row sm:items-center sm:gap-3">
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:flex-1 sm:min-w-0">
                    <div class="relative w-full sm:flex-1 sm:min-w-[9rem] sm:max-w-md">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ MR/ຜູ້ເບີກ/ຈຸດປະສົງ…" class="w-full pl-8 rounded-lg border-gray-300 text-sm" />
                    </div>
                    <select wire:model.live="statusFilter" class="shrink-0 w-36 rounded-lg border-gray-300 text-sm">
                        <option value="">All Statuses</option>
                        @foreach (['draft', 'submitted', 'approved', 'validated', 'dispatched', 'received', 'completed', 'rejected', 'cancelled'] as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <select wire:model.live="perPage" class="shrink-0 rounded-lg border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                        @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} {{ app()->getLocale() === 'en' ? 'rows' : 'ແຖວ' }}</option>@endforeach
                    </select>
                    @if ($canManageDeleted)<button wire:click="toggleDeleted" class="text-sm rounded-lg px-3 py-2 min-h-[40px] border transition whitespace-nowrap {{ $showDeleted ? 'bg-rose-600 text-white border-rose-600' : 'text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100' }}">🗑 {{ $showDeleted ? 'ກັບຄືນ' : 'Deleted' }}</button>@endif
                    @can('request.create')<a href="{{ route('request.create') }}" wire:navigate class="text-sm font-medium text-white bg-sky-600 rounded-lg px-3.5 py-2 min-h-[40px] inline-flex items-center hover:bg-sky-700 transition shadow-sm whitespace-nowrap">+ Request</a>@endcan
                </div>
            </div>

            @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-1.5 mb-3">{{ session('ok') }}</div>@endif

        {{-- Desktop --}}
        <div class="hidden md:block bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="wh-list w-full text-sm">
                    <thead class="bg-slate-100 text-slate-600 border-b-2 border-slate-200">
                        <tr class="text-xs font-semibold uppercase tracking-wide">
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ໄອດີ (MR)</th>
                            <th class="text-left font-semibold px-3 py-1.5">ຜູ້ເບີກ</th>
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ລາຍການ</th>
                            <th class="text-left font-semibold px-3 py-1.5 w-full">ຈຸດປະສົງ</th>
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">WO</th>
                            <th class="text-right font-semibold px-3 py-1.5 whitespace-nowrap">ມູນຄ່າ</th>
                            <th class="text-left font-semibold px-3 py-1.5">Supplier</th>
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">ສະຖານະ</th>
                            <th class="text-left font-semibold px-3 py-1.5 whitespace-nowrap">SAP</th>
                            <th class="text-right font-semibold px-3 py-1.5 whitespace-nowrap">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $r)
                            @php [$lbl, $cls] = $statusMeta($r->status); @endphp
                            <tr wire:key="mr-{{ $r->id }}" class="hover:bg-amber-50/40 transition">
                                <td class="px-3 py-1.5 align-top whitespace-nowrap"><a href="{{ route('request.show', $r) }}" wire:navigate class="font-mono text-sm font-medium text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                                <td class="px-3 py-1.5 align-top"><div class="font-semibold text-gray-800">{{ $r->requester_name }}</div><div class="text-xs text-gray-400">{{ $r->unit?->name ?? $r->requester_email }}</div></td>
                                <td class="px-3 py-1.5 align-top text-gray-600 whitespace-nowrap">{{ $r->items->count() }} ລາຍການ<div class="text-xs text-gray-400">Qty {{ $r->items->sum('quantity') }}</div></td>
                                <td class="px-3 py-1.5 align-top text-gray-600 w-full">{{ $r->purpose ?: '—' }}</td>
                                <td class="px-3 py-1.5 align-top text-gray-600 whitespace-nowrap">@if ($r->wo_e_form)<span class="font-mono text-xs bg-gray-50 border border-gray-200 rounded px-1.5 py-0.5 text-gray-600">{{ $r->wo_e_form }}</span>@else—@endif@if ($r->request_type)<div class="text-xs text-gray-400 mt-0.5">{{ $r->request_type }}</div>@endif</td>
                                <td class="px-3 py-1.5 align-top text-gray-700 whitespace-nowrap text-right tabular-nums"><span class="font-medium">{{ number_format($r->grand_total, 2) }}</span> <span class="text-xs text-gray-400">{{ $r->currency }}</span>@if ($r->vat_enabled)<div class="text-xs text-gray-400">+VAT {{ rtrim(rtrim(number_format($r->vat_rate, 2), '0'), '.') }}%</div>@endif</td>
                                <td class="px-3 py-1.5 align-top text-xs text-gray-600">{{ $r->supplier?->name ?? '—' }}</td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span></td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap">@if ($r->sapStatusLabel())<span class="inline-flex items-center text-xs font-medium rounded-full px-2 py-0.5 bg-violet-50 text-violet-700 ring-1 ring-violet-200">{{ $r->sapStatusLabel() }}</span>@else<span class="text-gray-300">—</span>@endif</td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap text-right">
                                    @if ($showDeleted)
                                        <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 transition">↩ ກູ້ຄືນ</button>
                                    @else
                                        <a href="{{ route('request.show', $r) }}" wire:navigate class="text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition inline-block">ເບິ່ງ</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-14 text-center text-gray-400"><div class="text-4xl mb-2">📝</div>ຍັງບໍ່ມີໃບເບີກ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile --}}
        <div class="md:hidden space-y-2.5">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('request.show', $r) }}" wire:navigate @endif wire:key="mmr-{{ $r->id }}" class="block bg-white border border-gray-200 rounded-xl shadow-sm p-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-mono text-xs text-indigo-600">{{ $r->request_number }}</div>
                            <div class="font-semibold text-gray-800">{{ $r->requester_name }}</div>
                            <div class="text-xs text-gray-500">{{ $r->items->count() }} ລາຍການ · {{ number_format($r->grand_total, 2) }} {{ $r->currency }}</div>
                            @if ($r->purpose)<div class="text-xs text-gray-500 truncate">{{ $r->purpose }}</div>@endif
                            @if ($r->wo_e_form || $r->sapStatusLabel())<div class="text-xs text-gray-400 truncate">@if ($r->wo_e_form)WO {{ $r->wo_e_form }}@endif @if ($r->sapStatusLabel())<span class="text-violet-600">SAP: {{ $r->sapStatusLabel() }}</span>@endif</div>@endif
                        </div>
                        <span class="text-xs font-semibold rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    @if ($showDeleted)<div class="mt-2 text-right"><button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນ?" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5">↩ ກູ້ຄືນ</button></div>@endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-10"><div class="text-4xl mb-2">📝</div>ຍັງບໍ່ມີໃບເບີກ</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
