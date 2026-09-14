@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'],
        'accepted' => ['ACCEPTED', 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200'],
        'stored' => ['STORED', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'needs_fix' => ['NEEDS FIX', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'claimed' => ['CLAIMED', 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200'],
        'disposal' => ['DISPOSAL', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
        'disposed' => ['DISPOSED', 'bg-gray-800 text-gray-100'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    // ຖືກ ດຶງ ໄປ ຈຳໜ່າຍ = ລິສ ຕາຍ, ລັອກ ແກ້ໄຂ
    $locked = fn ($s) => in_array($s, ['disposal', 'disposed'], true);
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- live KPIs (page identity is already in the app top bar — no duplicate title) --}}
        @include('partials._kpi-band', ['tiles' => $kpi])

        {{-- frozen header group: toolbar + chips freeze together --}}
        <div class="sticky top-16 z-30 bg-gray-100/95 backdrop-blur">
            <div class="flex flex-col gap-2 py-3 sm:py-2 sm:min-h-[52px] sm:flex-row sm:items-center sm:gap-3">
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:flex-1 sm:min-w-0">
                    <div class="relative w-full sm:flex-1 sm:min-w-[9rem] sm:max-w-xs">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ DP/ຊື່ເຈົ້າຂອງ…" class="w-full pl-8 rounded-lg border-gray-300 text-sm" />
                    </div>
                    <select wire:model.live="statusFilter" class="shrink-0 w-40 rounded-lg border-gray-300 text-sm" title="ສະຖານະ ການ ຝາກ">
                        <option value="">ທຸກ ສະຖານະການ ຝາກ</option>
                        <option value="draft">draft</option><option value="submitted">submitted</option>
                        <option value="accepted">accepted</option><option value="stored">stored</option>
                        <option value="needs_fix">needs_fix</option><option value="claimed">claimed</option><option value="cancelled">cancelled</option>
                        <option value="disposal">disposal</option><option value="disposed">disposed</option>
                    </select>
                    <select wire:model.live="typeFilter" class="shrink-0 w-36 rounded-lg border-gray-300 text-sm" title="ປະເພດ ການ ຝາກ">
                        <option value="">ທຸກ ປະເພດ</option>
                        <option value="walk_in">Walk-in · ນຳມາແລ້ວ</option>
                        <option value="pre_request">Pre-request · ສົ່ງລ່ວງໜ້າ</option>
                        <option value="legacy">ເຄື່ອງຝາກເກົ່າ · ຄ້າງ ດົນ</option>
                    </select>
                    <select wire:model.live="unitFilter" class="shrink-0 w-36 rounded-lg border-gray-300 text-sm" title="ໜ່ວຍງານ ເຈົ້າຂອງ">
                        <option value="">ທຸກ ໜ່ວຍງານ</option>
                        @foreach ($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                    <select wire:model.live="conditionFilter" class="shrink-0 w-44 rounded-lg border-gray-300 text-sm" title="ສະພາບ ເຄື່ອງ">
                        <option value="">ທຸກ ສະພາບເຄື່ອງ</option>
                        @foreach ($conditionOptions as $cv => $cl)<option value="{{ $cv }}">{{ $cl }}</option>@endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <select wire:model.live="perPage" class="shrink-0 rounded-lg border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                        @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} ແຖວ</option>@endforeach
                    </select>
                    @if ($canManageDeleted)<button wire:click="toggleDeleted" title="ເບິ່ງ ລາຍການ ທີ່ ລຶບ ແລ້ວ ເພື່ອ ກູ້ຄືນ" class="text-sm rounded-lg px-3 py-2 min-h-[40px] border transition whitespace-nowrap {{ $showDeleted ? 'bg-rose-600 text-white border-rose-600' : 'text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100' }}">{{ $showDeleted ? '← ກັບ ລິສ' : '↩ ລາຍການ ທີ່ ຖືກ ລຶບ' }}</button>@endif
                    <a href="{{ route('deposit.report', ['search' => $search, 'status' => $statusFilter, 'type' => $typeFilter, 'unit' => $unitFilter, 'condition' => $conditionFilter]) }}" target="_blank" rel="noopener" title="ພິມ ບັນຊີ ລາຍການ (ຕາມ filter ນີ້) ພ້ອມ letterhead" class="text-sm rounded-lg px-3 py-2 min-h-[40px] border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 inline-flex items-center transition whitespace-nowrap">🖨 ພິມ ບັນຊີ</a>
                    @can('deposit.create')<a href="{{ route('deposit.create') }}" wire:navigate class="text-sm font-medium text-white bg-indigo-600 rounded-lg px-3.5 py-2 min-h-[40px] inline-flex items-center hover:bg-indigo-700 transition shadow-sm whitespace-nowrap">+ Deposit</a>@endcan
                </div>
            </div>

            @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($records->total()).' records'])
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-1.5 mb-3">{{ session('ok') }}</div>@endif

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="wh-list w-full text-sm table-fixed">
                    <colgroup>
                        <col style="width:8%"><col style="width:13%"><col style="width:21%"><col style="width:6%">
                        <col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:8%"><col style="width:14%">
                    </colgroup>
                    <thead class="bg-slate-100 text-slate-600 border-b-2 border-slate-200">
                        <tr class="text-xs font-semibold uppercase tracking-wide">
                            <th class="text-left font-semibold px-3 py-2.5">ໄອດີ (DP)</th>
                            <th class="text-left font-semibold px-3 py-2.5">ໜ່ວຍງານ</th>
                            <th class="text-left font-semibold px-3 py-2.5">ເຄື່ອງຝາກ</th>
                            <th class="text-center font-semibold px-3 py-2.5">ຈຳນວນ</th>
                            <th class="text-left font-semibold px-3 py-2.5">ລະຫັດເຄື່ອງ</th>
                            <th class="text-left font-semibold px-3 py-2.5">ຊັບສິນ</th>
                            <th class="text-left font-semibold px-3 py-2.5">ສະພາບເຄື່ອງ</th>
                            <th class="text-left font-semibold px-3 py-2.5">ສະຖານະການຝາກ</th>
                            <th class="text-right font-semibold px-3 py-2.5">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $r)
                            @php [$lbl, $cls] = $statusMeta($r->status); $first = $r->items->first(); $ph = $first?->photos->first(); @endphp
                            <tr wire:key="dp-{{ $r->id }}" class="transition {{ $locked($r->status) ? 'opacity-60 bg-gray-50/70' : 'hover:bg-sky-50/40' }}" @if ($locked($r->status)) title="ດຶງ ໄປ ຈຳໜ່າຍ ແລ້ວ — ລັອກ ການ ແກ້ໄຂ" @endif>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap"><a href="{{ route('deposit.show', $r) }}" wire:navigate class="font-mono text-sm font-medium text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                                <td class="px-3 py-1.5 align-top"><div class="font-semibold text-gray-800">{{ $r->unit?->name ?? '—' }}</div></td>
                                <td class="px-3 py-1.5 align-top min-w-[13rem]">
                                    <div class="flex gap-2.5">
                                        @if ($ph)<img src="{{ $ph->url }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                                        @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">📦</div>@endif
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-800 whitespace-normal break-words line-clamp-2">{{ $first?->item_name ?? '—' }}@if ($r->items->count() > 1) <span class="text-gray-400 text-xs font-normal">+{{ $r->items->count() - 1 }}</span>@endif</div>
                                            @if ($r->item_category)<div class="text-xs text-gray-400">{{ Str::limit($r->item_category, 30) }}</div>@endif
                                            @if ($r->needsOfficeInfo())<div class="mt-1 inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> ຂັ້ນ 2: ລໍ ຫົວໜ້າ ຕື່ມ ຂໍ້ມູນ</div>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-1.5 align-top text-center whitespace-nowrap">
                                    <span class="font-semibold text-gray-800 tabular-nums">{{ $r->items->sum('qty') }}</span>
                                    @if ($first?->unit)<span class="text-xs text-gray-400"> {{ $first->unit }}</span>@endif
                                    @if ($r->items->count() > 1)<div class="text-[11px] text-gray-400">{{ $r->items->count() }} ລາຍການ</div>@endif
                                </td>
                                <td class="px-3 py-1.5 align-top text-xs whitespace-nowrap">@if ($first?->asset_code)<span class="font-mono bg-gray-50 text-gray-600 border border-gray-200 rounded px-1.5 py-0.5">{{ $first->asset_code }}</span>@if ($r->items->count() > 1)<span class="text-gray-300"> …</span>@endif @else<span class="text-gray-300">—</span>@endif</td>
                                <td class="px-3 py-1.5 align-top text-xs whitespace-nowrap font-mono {{ $first?->fixed_asset_no ? 'text-gray-600' : 'text-gray-300' }}">{{ $first?->fixed_asset_no ?: '—' }}</td>
                                <td class="px-3 py-1.5 align-top">
                                    @php $conds = $r->items->pluck('condition_status')->map(fn ($c) => $c ?: 'in_service')->unique(); @endphp
                                    @foreach ($conds as $cs)
                                        <span class="inline-block text-xs rounded-full px-2 py-0.5 {{ \App\Support\ConditionStatus::badge($cs) }}">{{ \App\Support\ConditionStatus::shortLabel($cs) }}</span>
                                    @endforeach
                                </td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap"><span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 {{ $cls }}">{{ $lbl }}</span></td>
                                <td class="px-3 py-2.5 align-top text-right">
                                    <div class="flex flex-wrap gap-1 justify-end">
                                    @if ($showDeleted)
                                        <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 transition inline-block">↩ ກູ້ຄືນ</button>
                                        @if ($r->deleted_reason)<div class="text-xs text-gray-400 mt-1 max-w-[12rem] truncate ml-auto" title="{{ $r->deleted_reason }}">{{ $r->deleted_reason }}</div>@endif
                                    @else
                                        @can('deposit.edit')@unless ($locked($r->status))
                                            @if ($r->needsOfficeInfo())
                                                <a href="{{ route('deposit.show', [$r, 'edit' => 1]) }}" wire:navigate class="text-xs font-semibold text-white bg-amber-600 border border-amber-600 rounded-lg px-3 py-1.5 hover:bg-amber-700 transition inline-block mr-1">✏️ ຕື່ມ ຂໍ້ມູນ →</a>
                                            @else
                                                <a href="{{ route('deposit.show', [$r, 'edit' => 1]) }}" wire:navigate class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 hover:bg-amber-100 transition inline-block mr-1">✏️ ແກ້ໄຂ</a>
                                            @endif
                                        @endunless @endcan
                                        <a href="{{ route('deposit.show', $r) }}" wire:navigate class="text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition inline-block">ເບິ່ງ</a>
                                        @if ($canManageDeleted && in_array($r->status, \App\Livewire\Deposit\Index::DELETABLE, true))<button wire:click="openDelete({{ $r->id }})" class="text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-1.5 hover:bg-rose-100 transition inline-block ml-1">🗑 ລຶບ</button>@endif
                                    @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-14 text-center text-gray-400"><div class="text-4xl mb-2">📦</div>ຍັງບໍ່ມີລາຍການຝາກ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2.5">
            @forelse ($records as $r)
                @php [$lbl, $cls] = $statusMeta($r->status); $first = $r->items->first(); $ph = $first?->photos->first(); $tag = $showDeleted ? 'div' : 'a'; @endphp
                <{{ $tag }} @if (! $showDeleted) href="{{ route('deposit.show', $r) }}" wire:navigate @endif wire:key="mdp-{{ $r->id }}" class="block bg-white border border-gray-200 rounded-xl shadow-sm p-3.5 {{ $locked($r->status) ? 'opacity-60 bg-gray-50/70' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex gap-2.5 min-w-0">
                            @if ($ph)<img src="{{ $ph->url }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                            @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">📦</div>@endif
                            <div class="min-w-0"><div class="font-mono text-xs text-indigo-600">{{ $r->request_number }}</div><div class="font-semibold text-gray-800">{{ $r->owner_name }}</div><div class="text-xs text-gray-500 truncate">{{ $first?->item_name }} · Qty {{ $r->items->sum('qty') }}</div>@if ($first?->asset_code || $first?->fixed_asset_no)<div class="text-[11px] text-gray-400 truncate">@if ($first->asset_code)ທ.ເຄື່ອງ: {{ $first->asset_code }}@endif@if ($first->fixed_asset_no) · ຊັບສິນ: {{ $first->fixed_asset_no }}@endif</div>@endif</div>
                        </div>
                        <span class="text-xs font-semibold rounded-full px-2 py-0.5 {{ $cls }} shrink-0">{{ $lbl }}</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-2">{{ $r->deposit_date?->format('M d, Y') }} · {{ collect([$r->storage_location, $r->storage_shelf_label])->filter()->implode(' / ') ?: 'ຍັງບໍ່ກຳນົດບ່ອນເກັບ' }}</div>
                    @if (! $showDeleted && $canManageDeleted && in_array($r->status, \App\Livewire\Deposit\Index::DELETABLE, true))
                        <div class="flex justify-end mt-2">
                            <button type="button" wire:click="openDelete({{ $r->id }})" @click.stop.prevent class="text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-1.5 hover:bg-rose-100 shrink-0">🗑 ລຶບ</button>
                        </div>
                    @endif
                    @if ($showDeleted)
                        <div class="flex items-center justify-between gap-2 mt-2">
                            @if ($r->deleted_reason)<span class="text-xs text-gray-400 truncate">{{ $r->deleted_reason }}</span>@else<span></span>@endif
                            <button wire:click="restore({{ $r->id }})" wire:confirm="ກູ້ຄືນລາຍການນີ້?" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 shrink-0">↩ ກູ້ຄືນ</button>
                        </div>
                    @endif
                </{{ $tag }}>
            @empty
                <div class="text-center text-gray-400 py-10"><div class="text-4xl mb-2">📦</div>ຍັງບໍ່ມີລາຍການຝາກ</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>

    @include('partials._delete-modal', ['title' => 'ລຶບ ໃບ ຝາກ ນີ້?', 'subtitle' => $this->deletingRecord?->request_number])
</div>
