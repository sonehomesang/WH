@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-600',
        'in_review', 'committee_review', 'technical_review', 'manager_review', 'executive_review' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'approved' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
        'disposed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        'cancelled' => 'bg-gray-100 text-gray-400',
        default => 'bg-gray-100 text-gray-600',
    };
    // ໃບ ຍັງ ດຳເນີນ ຢູ່ (ຮ່າງ/ກຳລັງ ຮັບຮອງ/ອະນຸມັດ) = ແກ້ໄຂ ໄດ້ · ສຳເລັດ ແລ້ວ (ຈຳໜ່າຍ/ຍົກເລີກ/ຕີ ກັບ) = ລັອກ
    $canEdit = fn ($r) => ! in_array($r->status, ['disposed', 'cancelled', 'rejected'])
        && (auth()->user()->can('disposal.edit') || $r->prepared_by_user_id === auth()->id() || auth()->user()->is_super_admin);
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgTrash = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
@endphp

<div class="pb-6">
    {{-- box-shadow (not border-bottom) so the sticky column-header divider stays
         glued to the header under border-collapse — a real border is "owned" by
         the first body row and scrolls away. --}}
    <style>.disposal-list thead th { box-shadow: inset 0 -2px 0 #cbd5e1; }</style>
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- live KPIs — no status chips on this page, so the KPI band stays (not duplicated) --}}
        <div class="pt-3">@include('partials._kpi-band', ['tiles' => $kpi])</div>

        {{-- frozen header group: toolbar freezes; publish its bottom edge as
             --freeze-top so the table header sticks just under it --}}
        <div class="sticky top-16 z-30 bg-gray-100 pt-3 pb-2" x-data
             x-init="const root = document.documentElement;
                     const set = () => root.style.setProperty('--freeze-top', (64 + $el.offsetHeight) + 'px');
                     set(); new ResizeObserver(set).observe($el);">
            <div class="flex flex-col gap-2 py-3 sm:py-2 sm:min-h-[52px] sm:flex-row sm:items-center sm:gap-3">
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:flex-1 sm:min-w-0">
                    <div class="relative w-full sm:flex-1 sm:min-w-[9rem] sm:max-w-md">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ DS/ຫົວຂໍ້/ຜູ້ເຮັດ…" class="w-full pl-8 rounded-lg border-gray-300 text-sm" />
                    </div>
                    <select wire:model.live="statusFilter" class="shrink-0 w-36 rounded-lg border-gray-300 text-sm">
                        <option value="">ທຸກ ສະຖານະ</option>
                        @foreach ($statusLabels as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <select wire:model.live="perPage" class="shrink-0 rounded-lg border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                        @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} {{ app()->getLocale() === 'en' ? 'rows' : 'ແຖວ' }}</option>@endforeach
                    </select>
                    <a href="{{ route('disposal.summary') }}" wire:navigate class="text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-2 min-h-[40px] inline-flex items-center hover:bg-gray-50 transition whitespace-nowrap">📊 ລິສ ລວມ</a>
                    @if ($canManageDeleted)<button wire:click="toggleDeleted" title="ເບິ່ງ ໃບ ຈຳໜ່າຍ ທີ່ ລຶບ ແລ້ວ ເພື່ອ ກູ້ຄືນ" class="text-sm rounded-lg px-3 py-2 min-h-[40px] border transition whitespace-nowrap {{ $showDeleted ? 'bg-rose-600 text-white border-rose-600' : 'text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100' }}">{{ $showDeleted ? '← ກັບ ລິສ' : '↩ ລາຍການ ທີ່ ຖືກ ລຶບ' }}</button>@endif
                    @can('disposal.create')<a href="{{ route('disposal.create') }}" wire:navigate class="text-sm font-medium text-white bg-sky-600 rounded-lg px-3.5 py-2 min-h-[40px] inline-flex items-center gap-1 hover:bg-sky-700 transition shadow-sm whitespace-nowrap">+ ຈຳໜ່າຍ</a>@endcan
                </div>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-1.5 mb-3">{{ session('ok') }}</div>@endif

        <div class="hidden md:block bg-white border border-gray-200 rounded-xl shadow-sm">
            <table class="wh-list disposal-list w-full text-sm table-fixed">
                    <colgroup>
                        <col style="width:7%"><col style="width:18%"><col style="width:12%"><col style="width:9%"><col style="width:9%">
                        <col style="width:8%"><col style="width:9%"><col style="width:7%"><col style="width:8%"><col style="width:13%">
                    </colgroup>
                    <thead class="sticky z-20 bg-slate-100 text-slate-600" style="top: var(--freeze-top, 14rem)">
                        <tr class="text-xs font-semibold uppercase tracking-wide">
                            <th class="text-left font-semibold px-3 py-1.5">ໄອດີ (DS)</th>
                            <th class="text-left font-semibold px-3 py-1.5">ເຄື່ອງ (Items)</th>
                            <th class="text-left font-semibold px-3 py-1.5">ເຈົ້າຂອງ (Org/Dept)</th>
                            <th class="text-left font-semibold px-3 py-1.5">ທະບຽນຊັບສິນ</th>
                            <th class="text-left font-semibold px-3 py-1.5">ລະຫັດຂອງສາງ</th>
                            <th class="text-left font-semibold px-3 py-1.5">ຈຳນວນ</th>
                            <th class="text-left font-semibold px-3 py-1.5">ຜູ້ ເຮັດລິສ</th>
                            <th class="text-left font-semibold px-3 py-1.5">ວັນທີ</th>
                            <th class="text-left font-semibold px-3 py-1.5">ສະຖານະ</th>
                            <th class="text-right font-semibold px-3 py-1.5">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $r)
                            @php $dimmed = in_array($r->status, ['approved', 'disposed'], true); @endphp
                            <tr wire:key="ds-{{ $r->id }}" class="transition {{ $dimmed ? 'opacity-60 bg-gray-50/70' : 'hover:bg-sky-50/40' }}" @if ($dimmed) title="{{ $r->status === 'disposed' ? 'ຈຳໜ່າຍ ແລ້ວ' : 'ອະນຸມັດ ແລ້ວ' }}" @endif>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap"><a href="{{ route('disposal.show', $r) }}" wire:navigate class="font-mono font-medium text-indigo-600 hover:underline">{{ $r->request_number }}</a></td>
                                <td class="px-3 py-1.5 align-top">
                                    @php $fi = $r->items->first(); $ph = $fi->photos[0] ?? null; @endphp
                                    <div class="flex gap-2.5">
                                        @if ($ph)<img src="{{ \Illuminate\Support\Facades\Storage::url($ph) }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                                        @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">🗑️</div>@endif
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-800 whitespace-normal break-words line-clamp-2">{{ $fi?->item_name ?: ($r->title ?: '—') }}@if ($r->items_count > 1) <span class="text-gray-400 text-xs font-normal">+{{ $r->items_count - 1 }}</span>@endif</div>
                                            <div class="text-xs text-gray-400 flex items-center gap-1.5 flex-wrap">
                                                @if ($fi?->asset_code)<span class="font-mono bg-gray-50 border border-gray-200 rounded px-1 py-0.5 text-gray-500">{{ $fi->asset_code }}</span>@endif
                                                @if ($r->department)<span>{{ $r->department->name }}</span>@elseif ($r->title && $fi)<span>{{ Str::limit($r->title, 30) }}</span>@endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-1.5 align-top text-xs">
                                    @if ($r->department)<div class="text-gray-700 font-medium">{{ $r->department->unit?->name ?? '—' }}</div><div class="text-gray-400">{{ $r->department->name }}</div>
                                    @else<span class="text-gray-300">—</span>@endif
                                </td>
                                <td class="px-3 py-1.5 align-top text-xs font-mono break-words {{ $fi?->fixed_asset_no ? 'text-gray-600' : 'text-gray-300' }}">{{ $fi?->fixed_asset_no ?: '—' }}@if ($fi?->fixed_asset_no && $r->items_count > 1)<span class="text-gray-300"> …</span>@endif</td>
                                <td class="px-3 py-1.5 align-top text-xs">@if ($fi?->asset_code)<span class="font-mono bg-gray-50 border border-gray-200 rounded px-1.5 py-0.5 text-gray-600">{{ $fi->asset_code }}</span>@if ($r->items_count > 1)<span class="text-gray-300"> …</span>@endif @else<span class="text-gray-300">—</span>@endif</td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap text-gray-700"><span class="font-semibold tabular-nums">{{ $r->items->sum('qty') }}</span> <span class="text-xs text-gray-400">({{ $r->items_count }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }})</span></td>
                                <td class="px-3 py-1.5 align-top text-gray-600 text-xs break-words">{{ $r->prepared_by_name ?? '—' }}</td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap text-gray-500 text-xs tabular-nums">{{ $r->created_at?->format('d/m/Y') }}</td>
                                <td class="px-3 py-1.5 align-top whitespace-nowrap"><span class="inline-flex text-xs font-semibold rounded-full px-2.5 py-1 {{ $badge($r->status) }}">{{ $statusLabels[$r->status] ?? $r->status }}</span></td>
                                <td class="px-3 py-1.5 align-top text-right whitespace-nowrap text-gray-500">
                                    @if ($showDeleted)
                                        <button wire:click="restore({{ $r->id }})" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50 transition">↩ ກູ້ຄືນ</button>
                                        @if ($r->deleted_reason)<div class="text-xs text-gray-400 mt-1 max-w-[12rem] truncate ml-auto" title="{{ $r->deleted_reason }}">{{ $r->deleted_reason }}</div>@endif
                                    @else
                                        <div class="inline-flex items-center gap-0.5">
                                            @if ($canEdit($r))<a href="{{ route('disposal.show', [$r, 'edit' => 1]) }}" wire:navigate class="p-1 hover:text-amber-700" title="ແກ້ໄຂ" aria-label="ແກ້ໄຂ"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></a>@endif
                                            <a href="{{ route('disposal.show', $r) }}" wire:navigate class="p-1 hover:text-sky-700" title="ເບິ່ງ" aria-label="ເບິ່ງ"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></a>
                                            @if ($canManageDeleted)<button wire:click="openDelete({{ $r->id }})" class="p-1 hover:text-rose-600" title="ລຶບ" aria-label="ລຶບ"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-14 text-center text-gray-400">
                                <div class="text-4xl mb-2">🗑️</div>
                                {{ $showDeleted ? 'ບໍ່ ມີ ໃບ ຈຳໜ່າຍ ທີ່ ຖືກ ລຶບ' : 'ຍັງ ບໍ່ ມີ ໃບ ຈຳໜ່າຍ — ກົດ “+ ຈຳໜ່າຍ” ເພື່ອ ສ້າງ' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden wh-cards space-y-2">
            @forelse ($records as $r)
                @php $dimmed = in_array($r->status, ['approved', 'disposed'], true); $fi = $r->items->first(); $ph = $fi->photos[0] ?? null; @endphp
                <div wire:key="dsm-{{ $r->id }}" class="bg-white border border-gray-200 rounded-xl shadow-sm p-3.5 {{ $dimmed ? 'opacity-60' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex gap-2.5 min-w-0">
                            @if ($ph)<img src="{{ \Illuminate\Support\Facades\Storage::url($ph) }}" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0 cursor-zoom-in" />
                            @else<div class="w-10 h-10 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-lg">🗑️</div>@endif
                            <div class="min-w-0">
                                <a href="{{ route('disposal.show', $r) }}" wire:navigate class="font-mono text-xs text-indigo-600">{{ $r->request_number }}</a>
                                <div class="font-semibold text-gray-800 break-words">{{ $fi?->item_name ?: ($r->title ?: '—') }}@if ($r->items_count > 1) <span class="text-gray-400 text-xs">+{{ $r->items_count - 1 }}</span>@endif</div>
                                <div class="text-xs text-gray-400">{{ $r->department?->name }} · Qty {{ $r->items->sum('qty') }}</div>
                            </div>
                        </div>
                        <span class="text-xs font-semibold rounded-full px-2 py-0.5 shrink-0 {{ $badge($r->status) }}">{{ $statusLabels[$r->status] ?? $r->status }}</span>
                    </div>
                    <div class="flex flex-wrap justify-end gap-1.5 mt-2.5">
                        @if ($showDeleted)
                            <button wire:click="restore({{ $r->id }})" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                        @else
                            @if ($canEdit($r))<a href="{{ route('disposal.show', [$r, 'edit' => 1]) }}" wire:navigate class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5">✏️ ແກ້ໄຂ</a>@endif
                            <a href="{{ route('disposal.show', $r) }}" wire:navigate class="text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5">ເບິ່ງ</a>
                            @if ($canManageDeleted)<button wire:click="openDelete({{ $r->id }})" class="text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-1.5">🗑 ລຶບ</button>@endif
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-10"><div class="text-4xl mb-2">🗑️</div>{{ $showDeleted ? 'ບໍ່ ມີ ໃບ ຈຳໜ່າຍ ທີ່ ຖືກ ລຶບ' : 'ຍັງ ບໍ່ ມີ ໃບ ຈຳໜ່າຍ' }}</div>
            @endforelse
        </div>

        @include('partials._delete-modal', ['title' => 'ລຶບ ໃບ ຈຳໜ່າຍ ນີ້?', 'subtitle' => $this->deletingRecord?->request_number])

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</div>
