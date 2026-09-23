@php
    $badge = fn ($s) => match ($s) {
        'active' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'repair' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'retired' => 'bg-gray-100 text-gray-500 ring-1 ring-gray-200',
        default => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200',
    };
    $statusLabel = ['active' => 'ໃຊ້ງານ', 'repair' => 'ຊ່ອມແປງ', 'retired' => 'ຢຸດໃຊ້'];
@endphp

<div class="pb-6" x-data="{ tab: '{{ in_array(request('tab'), ['register', 'inspection', 'maintenance'], true) ? request('tab') : 'register' }}', bigImg: null }" @wh-lightbox.window="bigImg = $event.detail">
    {{-- box-shadow (not border-bottom) so the sticky column-header divider stays
         glued to the register table header under border-collapse — a real border
         is "owned" by the first body row and scrolls away. --}}
    <style>.eq-list thead th { box-shadow: inset 0 -2px 0 #cbd5e1; }</style>
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- Tabs (frozen ໃຫ້ ຄ້າງ ຢູ່ ໃຕ້ app-header ຕອນ scroll/ສະຫຼັບ ແທັບ) --}}
        <div class="sticky top-16 z-30 bg-gray-100 flex gap-1 border-b border-gray-200">
            <button @click="tab='register'" :class="tab==='register' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ທະບຽນ ເຄື່ອງ</button>
            <button @click="tab='inspection'" :class="tab==='inspection' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ການ ກວດກາ</button>
            <button @click="tab='maintenance'" :class="tab==='maintenance' ? 'border-sky-600 text-sky-700 font-medium' : 'border-transparent text-gray-500'" class="px-4 py-2 text-sm border-b-2 -mb-px">ບຳລຸງຮັກສາ</button>
        </div>

        {{-- ═══ TAB 1: Register ═══ --}}
        <div x-show="tab==='register'">
            {{-- live KPIs (page identity is already in the app top bar) --}}
            <div class="pt-3">@include('partials._kpi-band', ['tiles' => $kpi])</div>
            {{-- toolbar (frozen ໃຕ້ ແທັບ) — publishes --freeze-top (104px = 64 app-header + 40 tabs) so the register thead sticks just under it --}}
            <div class="sticky top-[6.5rem] z-20 bg-gray-100 flex flex-wrap items-center gap-2 py-2" x-data
                 x-init="const root = document.documentElement;
                         const set = () => root.style.setProperty('--freeze-top', (104 + $el.offsetHeight) + 'px');
                         set(); new ResizeObserver(set).observe($el);">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຊື່/ລະຫັດ/serial…"
                       class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm w-56" />
                <select wire:model.live="categoryFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">ທຸກ ປະເພດ</option>
                    @foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                </select>
                <select wire:model.live="statusFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">ທຸກ ສະຖານະ</option>
                    <option value="active">ໃຊ້ງານ</option>
                    <option value="repair">ຊ່ອມແປງ</option>
                    <option value="retired">ຢຸດໃຊ້</option>
                </select>
                <select wire:model.live="departmentFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">ທຸກ Owner (ພະແນກ)</option>
                    @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
                <div class="flex-1"></div>
                <span class="text-xs text-gray-400">ທັງໝົດ {{ $items->total() }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }}</span>
                <select wire:model.live="perPage" class="shrink-0 rounded-md border-gray-300 shadow-sm text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                    @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} {{ app()->getLocale() === 'en' ? 'rows' : 'ແຖວ' }}</option>@endforeach
                </select>
                @if ($canManageDeleted)
                    <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('equipment.create')
                    <button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>+ ເພີ່ມ ເຄື່ອງ</button>
                @endcan
            </div>

            {{-- Desktop table --}}
            <div class="hidden md:block bg-white border border-gray-100 rounded-lg">
                <table class="wh-list eq-list w-full text-sm table-fixed">
                    <thead class="sticky z-10 bg-slate-100 text-slate-600" style="top: var(--freeze-top, 14rem)">
                        <tr class="text-xs font-semibold uppercase tracking-wide">
                            <th class="text-left font-semibold px-3 py-2 w-28">ລະຫັດເຄື່ອງ</th>
                            <th class="text-left font-semibold px-3 py-2 w-28">ທະບຽນຊັບສິນ</th>
                            <th class="text-left font-semibold px-3 py-2">ຊື່ ເຄື່ອງ / ລາຍລະອຽດ</th>
                            <th class="text-left font-semibold px-3 py-2 w-20">Owner (ພະແນກ)</th>
                            <th class="text-left font-semibold px-3 py-2 w-24">Loc-Bin</th>
                            <th class="text-left font-semibold px-3 py-2 w-24">ຮູບ</th>
                            <th class="text-left font-semibold px-3 py-2 w-16">ຈຳນວນ</th>
                            <th class="text-left font-semibold px-3 py-2 w-28">ສະຖານະ</th>
                            <th class="text-left font-semibold px-3 py-2 w-24">ຜູ້ຢືມ</th>
                            <th class="text-center font-semibold px-2 py-2 w-20">ການ ກວດເຊັກ</th>
                            <th class="text-center font-semibold px-3 py-2 w-24">ຈັດການ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($items as $e)
                            <tr wire:key="eq-{{ $e->id }}">
                                <td class="px-3 py-2 text-gray-500 truncate">{{ $e->asset_code }}</td>
                                <td class="px-3 py-2 text-gray-600 truncate">{{ $e->fixed_asset_no ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-800 truncate">{{ $e->name }}</div>
                                    @php $meta = array_filter([$e->category, $e->brand_model, $e->serial_no, $e->location, $e->responsibleLabel()]); @endphp
                                    <div class="text-xs text-gray-400 truncate">{{ $meta ? implode(' · ', $meta) : '—' }}</div>
                                    @if ($showDeleted)
                                        <div class="text-[11px] text-red-600 mt-0.5">🗑 ລຶບ: {{ $e->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $e->deletedBy?->display_name ?? '—' }}@if ($e->deleted_reason) · ເຫດຜົນ: {{ $e->deleted_reason }}@endif</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-600 truncate">{{ $e->department?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-600 break-words">{{ $e->loc_bin ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex gap-0.5">
                                        @forelse ($e->photos->take(3) as $p)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($p->path) }}" @click="bigImg='{{ \Illuminate\Support\Facades\Storage::url($p->path) }}'"
                                                 class="w-8 h-8 rounded object-cover border border-gray-200 cursor-pointer hover:ring-2 hover:ring-sky-400" alt="ຮູບ" />
                                        @empty
                                            <span class="text-gray-300 text-xs">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $e->quantity }}<span class="text-xs text-gray-400"> {{ $e->unit?->name }}</span></td>
                                <td class="px-3 py-2">
                                    @php $bd = $e->statusBreakdown(); @endphp
                                    <div class="flex flex-wrap gap-0.5">
                                        @foreach (['active', 'repair', 'retired'] as $s)
                                            @if ($bd[$s] > 0)<span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $badge($s) }}">{{ $bd[$s] }} {{ $statusLabel[$s] }}</span>@endif
                                        @endforeach
                                    </div>
                                    @if ($e->condition_status && $e->condition_status !== 'in_service')<span class="inline-block mt-1 text-xs rounded-full px-2 py-0.5 {{ \App\Support\ConditionStatus::badge($e->condition_status) }}">{{ \App\Support\ConditionStatus::shortLabel($e->condition_status) }}</span>@endif
                                </td>
                                <td class="px-3 py-2 text-gray-600 truncate">
                                    @php $bws = $e->currentBorrowers(); @endphp
                                    @if (count($bws))
                                        <span class="text-amber-700" title="ກຳລັງ ຖືກ ຢືມ">{{ $bws[0] }}</span>@if (count($bws) > 1)<span class="text-xs text-gray-400"> +{{ count($bws) - 1 }}</span>@endif
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <button wire:click="viewInspectionHistory({{ $e->id }})" class="text-gray-400 hover:text-sky-700 p-1" title="ສະຖານະ ການ ກວດເຊັກ">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </button>
                                </td>
                                <td class="px-3 py-2 text-center whitespace-nowrap text-gray-500">
                                    @if ($showDeleted)
                                        <button wire:click="restore({{ $e->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                    @else
                                        <button wire:click="viewItem({{ $e->id }})" class="hover:text-sky-700 p-1" title="ເບິ່ງ ລາຍລະອຽດ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                        </button>
                                        @can('equipment.edit')
                                            <button wire:click="editItem({{ $e->id }})" class="hover:text-gray-800 p-1" title="ແກ້ໄຂ">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                            </button>
                                        @endcan
                                        @can('equipment.delete')
                                            <button wire:click="openDelete({{ $e->id }})" class="hover:text-red-600 p-1" title="ລຶບ">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-3 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ລາຍການ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ ເຄື່ອງ — ກົດ "+ ເພີ່ມ ເຄື່ອງ"' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden wh-cards space-y-2">
                @forelse ($items as $e)
                    <div wire:key="m-{{ $e->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-medium text-gray-800">{{ $e->name }}</div>
                            <div class="text-right shrink-0">
                                @php $bd = $e->statusBreakdown(); @endphp
                                @foreach (['active', 'repair', 'retired'] as $s)
                                    @if ($bd[$s] > 0)<span class="text-[11px] rounded-full font-medium px-1.5 py-0.5 {{ $badge($s) }} ml-0.5 whitespace-nowrap">{{ $bd[$s] }} {{ $statusLabel[$s] }}</span>@endif
                                @endforeach
                            </div>
                        </div>
                        <div class="text-xs text-gray-400">{{ $e->asset_code }}@if ($e->fixed_asset_no) · FA: {{ $e->fixed_asset_no }}@endif · {{ $e->category ?? '—' }} · {{ $e->quantity }} {{ $e->unit?->name ?? '' }}</div>
                        <div class="text-xs text-gray-600 mt-1">Owner: {{ $e->department?->name ?? '—' }} · Loc-Bin: {{ $e->loc_bin ?? '—' }}</div>
                        <div class="text-xs text-gray-600">{{ $e->location ?? '—' }} · {{ $e->responsibleLabel() ?? '—' }}</div>
                        @php $bwsM = $e->currentBorrowers(); @endphp
                        @if (count($bwsM))<div class="text-xs text-amber-700 mt-0.5">ຜູ້ຢືມ: {{ $bwsM[0] }}@if (count($bwsM) > 1) +{{ count($bwsM) - 1 }}@endif</div>@endif
                        @if ($showDeleted)
                            <div class="text-[11px] text-red-600 mt-1">🗑 ລຶບ: {{ $e->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $e->deletedBy?->display_name ?? '—' }}@if ($e->deleted_reason) · ເຫດຜົນ: {{ $e->deleted_reason }}@endif</div>
                        @endif
                        <div class="flex gap-2 mt-2">
                            @if ($showDeleted)
                                <button wire:click="restore({{ $e->id }})" class="text-xs border border-emerald-200 rounded px-2 py-1 min-h-[36px] text-emerald-700">↩ ກູ້ຄືນ</button>
                            @else
                                <button wire:click="viewItem({{ $e->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px] text-sky-700">ເບິ່ງ</button>
                                @can('equipment.edit')<button wire:click="editItem({{ $e->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">ແກ້ໄຂ</button>@endcan
                                @can('equipment.delete')<button wire:click="openDelete({{ $e->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px] text-red-600">ລຶບ</button>@endcan
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-6">ຍັງບໍ່ມີ ເຄື່ອງ</div>
                @endforelse
            </div>

            <div class="mt-4">{{ $items->links() }}</div>
        </div>

        {{-- ═══ TAB 2: ການ ກວດກາ ═══ --}}
        <div x-show="tab==='inspection'" x-cloak>
            <div class="sticky top-[6.5rem] z-20 bg-gray-100 flex flex-wrap items-center gap-2 py-2">
                <input type="text" wire:model.live.debounce.300ms="inspSearch" placeholder="ຄົ້ນຫາ ເຄື່ອງ/ຜູ້ກວດ…"
                       class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm w-56" />
                <select wire:model.live="inspResultFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">ທຸກ ຜົນ</option>
                    <option value="pass">ຜ່ານ</option>
                    <option value="fail">ບໍ່ຜ່ານ</option>
                    <option value="follow_up">ຕ້ອງຕິດຕາມ</option>
                </select>
                <select wire:model.live="inspCategoryFilter" class="rounded-md border-gray-300 shadow-sm text-sm max-w-[12rem]">
                    <option value="">ທຸກ ປະເພດ</option>
                    @foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                </select>
                <div class="flex-1"></div>
                <span class="text-xs text-gray-400">{{ $inspections->count() }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }}</span>
                @if ($canManageDeleted)
                    <button wire:click="toggleDeletedInspections" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeletedInspections ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        {{ $showDeletedInspections ? '← ໃບ ກວດ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('equipment.edit')
                    @unless ($deptScoped)
                        <a href="{{ route('equipment.templates') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-50 whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" /></svg>
                            ເບິ່ງ/ສ້າງ ເທມເພລດ ກວດ ເຄື່ອງ
                        </a>
                    @endunless
                    <button wire:click="newInspection" class="inline-flex items-center gap-1.5 text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        ລົງມື ກວດເຄື່ອງ
                    </button>
                @endcan
            </div>
            <div class="bg-white border border-gray-100 rounded-lg overflow-x-auto overflow-y-auto max-h-[calc(100vh-16rem)]">
                <table class="w-full min-w-[680px] text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 text-gray-600 text-xs border-b border-gray-200 shadow-sm">
                        <tr>
                            {{-- ຄໍລັມ ເຄື່ອງ = ໜ້າ ຄ້າງ ຊ້າຍ (ມືຖື swipe ເບິ່ງ ຄໍລັມ ອື່ນ) --}}
                            <th class="text-left px-3 py-2 font-semibold w-full sticky left-0 z-20 bg-gray-50 min-w-[170px]">ເຄື່ອງ</th>
                            <th class="text-left px-2 py-2 font-semibold w-14">ວັນທີ</th>
                            <th class="text-left px-3 py-2 font-semibold">ຜູ້ກວດ</th>
                            <th class="text-left px-3 py-2 font-semibold">ຜົນ</th>
                            <th class="text-left px-3 py-2 font-semibold">ກວດ ຄັ້ງ ໜ້າ</th>
                            <th class="text-left px-3 py-2 font-semibold">ຮູບ</th>
                            <th class="px-3 py-2 font-semibold w-20 text-right pr-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php $rb = ['pass' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'fail' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'follow_up' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200']; $rl = ['pass' => 'ຜ່ານ', 'fail' => 'ບໍ່ຜ່ານ', 'follow_up' => 'ຕ້ອງຕິດຕາມ']; @endphp
                        @forelse ($inspections as $ins)
                            <tr wire:key="ins-{{ $ins->id }}">
                                <td class="px-3 py-2 w-full sticky left-0 z-10 bg-white min-w-[170px]">
                                    {{ $ins->equipment?->asset_code }} · {{ $ins->equipment?->name }}
                                    @if ($showDeletedInspections)
                                        <div class="text-[11px] text-red-600 mt-0.5">🗑 ລຶບ: {{ $ins->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $ins->deletedBy?->display_name ?? '—' }}@if ($ins->deleted_reason) · ເຫດຜົນ: {{ $ins->deleted_reason }}@endif</div>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-xs text-gray-600 w-14">{{ $ins->inspected_at?->format('d/m/y') }}<div class="text-gray-400">{{ $ins->inspected_at?->format('H:i') }}</div></td>
                                <td class="px-3 py-2">{{ $ins->inspector_name ?? '—' }}</td>
                                <td class="px-2 py-2 whitespace-nowrap">
                                    <span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $rb[$ins->result] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $rl[$ins->result] ?? $ins->result }}</span>
                                    @if (! is_null($ins->score))
                                        <div class="text-[10px] text-gray-400 mt-0.5">{{ $ins->score }}%</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    @if ($ins->next_due_date)
                                        <span class="{{ $ins->next_due_date->isPast() ? 'text-red-600 font-medium' : ($ins->next_due_date->lte(now()->addDays(14)) ? 'text-amber-600' : 'text-gray-600') }}">{{ $ins->next_due_date->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if ($ins->photo_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($ins->photo_path) }}" @click="bigImg='{{ \Illuminate\Support\Facades\Storage::url($ins->photo_path) }}'" class="w-8 h-8 rounded object-cover border border-gray-200 cursor-pointer hover:ring-2 hover:ring-sky-400" alt="ຮູບ ກວດກາ" />
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 pr-4 text-right whitespace-nowrap text-gray-500">
                                    @if ($showDeletedInspections)
                                        <button wire:click="restoreInspection({{ $ins->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                    @else
                                        <button wire:click="viewInspection({{ $ins->id }})" class="hover:text-sky-700 p-1" title="ເບິ່ງ ລາຍລະອຽດ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                        </button>
                                        @can('equipment.edit')
                                            <button wire:click="editInspection({{ $ins->id }})" class="hover:text-gray-800 p-1" title="ແກ້ໄຂ">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                            </button>
                                        @endcan
                                        @can('equipment.delete')
                                            <button wire:click="openDeleteInspection({{ $ins->id }})" class="hover:text-red-600 p-1" title="ລຶບ">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">{{ $showDeletedInspections ? 'ບໍ່ ມີ ໃບ ກວດ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ ການ ກວດກາ' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div x-show="tab==='maintenance'" x-cloak>
            <livewire:equipment.maintenance />
        </div>
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="eq-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ ເຄື່ອງ' : 'ເພີ່ມ ເຄື່ອງ ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-4 overflow-y-auto">
                @include('partials._form-errors')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ລະຫັດເຄື່ອງ <span class="text-red-500">*</span> <span class="text-xs text-gray-400">(ບໍ່ ຊ້ຳ · ບໍ່ ເກີນ 10 ຕົວ)</span></label>
                        <input type="text" wire:model="asset_code" maxlength="10" placeholder="ເຊັ່ນ EQ-0001" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('asset_code')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ ເຄື່ອງ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ທະບຽນຊັບສິນ (Fixed Asset)</label>
                        <input type="text" wire:model="fixed_asset_no" placeholder="ເລກ ຈາກ ບັນຊີ ຊັບສິນ" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm text-gray-600">ປະເພດ</label>
                            @if ($canManageCategories)
                                <a href="{{ route('equipment.categories') }}" wire:navigate class="text-xs text-sky-600 hover:underline">＋ ຈັດການ ປະເພດ</a>
                            @endif
                        </div>
                        <select wire:model="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">—</option>
                            @foreach ($categoryOptions as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Owner (ພະແນກ ເຈົ້າຂອງ)</label>
                        <select wire:model="department_id" @disabled($deptScoped) class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm @if ($deptScoped) bg-gray-100 @endif">
                            <option value="">—</option>
                            @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                        @if ($deptScoped)<p class="text-xs text-gray-400 mt-1">ລ໋ອກ ໄວ້ ຕາມ ພະແນກ ຂອງ ທ່ານ</p>@endif
                        @error('department_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຍີ່ຫໍ້ / ຮຸ່ນ</label>
                        <input type="text" wire:model="brand_model" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Serial</label>
                        <input type="text" wire:model="serial_no" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ສະຖານທີ່</label>
                        <input type="text" wire:model="location" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Loc-Bin <span class="text-xs text-gray-400">(ບ່ອນ ວາງ/ຊັ້ນ)</span></label>
                        <input type="text" wire:model="loc_bin" placeholder="A-01-03 · WH-Rack-B2…" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('loc_bin')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຜູ້ຮັບຜິດຊອບ <span class="text-xs text-gray-400">(ຈາກ ຜູ້ໃຊ້)</span></label>
                        <select wire:model="responsible_user_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">—</option>
                            @foreach ($responsibleUsers as $ru)<option value="{{ $ru->id }}">{{ $ru->display_name }}</option>@endforeach
                        </select>
                        @if ($responsible_name && ! $responsible_user_id)
                            <p class="text-xs text-gray-400 mt-1">ຄ່າ ເກົ່າ: {{ $responsible_name }} — ເລືອກ ຜູ້ໃຊ້ ເພື່ອ ລິ້ງ</p>
                        @endif
                        @error('responsible_user_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຈຳນວນ ລວມ <span class="text-red-500">*</span></label>
                        <input type="number" min="1" wire:model="quantity" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('quantity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຫົວໜ່ວຍ</label>
                        <select wire:model="unit_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">—</option>
                            @foreach ($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຈຳນວນ ຊ່ອມແປງ</label>
                        <input type="number" min="0" wire:model="qtyRepair" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຈຳນວນ ຢຸດໃຊ້</label>
                        <input type="number" min="0" wire:model="qtyRetired" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('qtyRepair')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2 text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-md px-3 py-2">
                        ໃຊ້ງານ = ຈຳນວນ ລວມ − ຊ່ອມແປງ − ຢຸດໃຊ້. ເຄື່ອງ ໃໝ່ ປ່ອຍ ຊ່ອມ/ຢຸດ = 0 → <b>ໃຊ້ງານ ໝົດ</b>. ປັບ ຕໍ່ ຕອນ ກວດກາ.
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ສະຖານະພາບ (Condition) — ໃຊ້ ຄັດ ເຄື່ອງ ໄປ ຈຳໜ່າຍ</label>
                        <select wire:model="condition_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">@foreach (\App\Support\ConditionStatus::options() as $cv => $cl)<option value="{{ $cv }}">{{ $cl }}</option>@endforeach</select>
                        @error('condition_status')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ວັນທີ ຊື້/ຮັບ</label>
                        <input type="date" wire:model="purchase_date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ</label>
                        <textarea wire:model="notes" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"></textarea>
                    </div>

                    {{-- ຮູບ (ສູງສຸດ 3 · ກ້ອງ ຫຼື ແກເລີຣີ) --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຮູບ <span class="text-xs text-gray-400">(ສູງສຸດ 3 · 📷 ຖ່າຍ ຫຼື ເລືອກ ຈາກ ແກເລີຣີ · ກົດ ຮູບ ເພື່ອ ເບິ່ງ ໃຫຍ່)</span></label>
                        @if (count($existingPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($existingPhotos as $p)
                                    <div class="relative">
                                        <img src="{{ $p['url'] }}" @click="bigImg='{{ $p['url'] }}'" class="w-16 h-16 rounded object-cover border border-gray-200 cursor-pointer" alt="ຮູບ" />
                                        <button type="button" wire:click="removePhoto({{ $p['id'] }})" wire:confirm="ລຶບ ຮູບ ນີ້?" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center" aria-label="ລຶບ ຮູບ">×</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if (count($newPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($newPhotos as $ph)
                                    <img src="{{ $ph->temporaryUrl() }}" class="w-16 h-16 rounded object-cover border border-sky-200" alt="ຮູບ ໃໝ່" />
                                @endforeach
                            </div>
                        @endif
                        <input type="file" wire:model="newPhotos" multiple accept="image/*"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-sky-50 file:text-sky-700 file:min-h-[40px]" />
                        <div wire:loading wire:target="newPhotos" class="text-xs text-gray-400 mt-1">ກຳລັງ ອັບໂຫຼດ…</div>
                        @error('newPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @error('newPhotos')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] shadow-sm hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Inspection modal (ແທັບ 2) --}}
    @if ($showInspectionModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="ins-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingInspectionId ? 'ແກ້ໄຂ ການ ກວດກາ' : 'ບັນທຶກ ການ ກວດກາ' }}</h3>
                    <button wire:click="$set('showInspectionModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-4 overflow-y-auto">
                @include('partials._form-errors')

                {{-- ເລືອກ ເຄື່ອງ --}}
                @if (! $insEquipmentId)
                    <div class="relative">
                        <label class="block text-sm text-gray-600 mb-1">ເລືອກ ເຄື່ອງ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.300ms="insSearch" placeholder="ຄົ້ນຫາ ຊື່/ລະຫັດ…" class="w-full rounded-md border-gray-300 text-sm" />
                        @if ($insResults->isNotEmpty())
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @foreach ($insResults as $eq)
                                    <button type="button" wire:click="pickInspectionEquipment({{ $eq->id }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">{{ $eq->name }} <span class="font-mono text-xs text-gray-400">{{ $eq->asset_code }}</span></button>
                                @endforeach
                            </div>
                        @endif
                        @error('insEquipmentId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded px-3 py-2 text-sm">
                        <span class="font-medium text-gray-700">{{ $insEquipmentLabel }} <span class="text-xs text-gray-400">(ຈຳນວນ {{ $insEquipmentQty }})</span></span>
                        <button wire:click="$set('insEquipmentId', null)" class="text-xs text-sky-600 hover:underline">ປ່ຽນ</button>
                    </div>
                @endif

                {{-- ແມ່ແບບ + ເຊັກລິສ --}}
                @if ($insEquipmentId)
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ແມ່ແບບ ກວດກາ</label>
                        <select wire:model.live="insTemplateId" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">— ບໍ່ ໃຊ້ ແມ່ແບບ —</option>
                            @foreach ($insTemplateOptions as $t)<option value="{{ $t->id }}">{{ $t->matches ? '★ ' : '' }}{{ $t->name }}@if ($t->category) ({{ $t->category }})@endif</option>@endforeach
                        </select>
                        @if ($insTemplateOptions->isEmpty())
                            <p class="text-xs text-gray-400 mt-1">ຍັງ ບໍ່ ມີ ແມ່ແບບ ໃດ@unless ($deptScoped) — <a href="{{ route('equipment.templates') }}" wire:navigate class="text-sky-600 hover:underline">ສ້າງ ແມ່ແບບ</a>@endunless</p>
                        @else
                            <p class="text-xs text-gray-400 mt-1">★ = ກົງ ປະເພດ ເຄື່ອງ · ເລືອກ ອັນ ໃດ ກໍ ໄດ້.</p>
                        @endif
                    </div>
                    {{-- ປະເພດ ນ້ຳມັນ (ສະແດງ ເມື່ອ ແມ່ແບບ ມີ ຂໍ້ ຕາມ ປະເພດ) --}}
                    @if ($insTemplateNeedsFuel)
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ປະເພດ / Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-4 text-sm">
                                <label class="inline-flex items-center gap-1.5"><input type="radio" wire:model.live="insFuelType" value="engine" class="text-sky-600"> ນ້ຳມັນ (Engine)</label>
                                <label class="inline-flex items-center gap-1.5"><input type="radio" wire:model.live="insFuelType" value="ev" class="text-sky-600"> ໄຟຟ້າ (EV)</label>
                            </div>
                            @if ($insFuelType === '')
                                <p class="text-xs text-amber-600 mt-1">ເລືອກ ປະເພດ ກ່ອນ ຈຶ່ງ ສະແດງ ລາຍການ ກວດ</p>
                            @endif
                            @error('insFuelType')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    {{-- ຮອບ ການ ກວດ (ສະແດງ ເມື່ອ ແມ່ແບບ ມີ ຂໍ້ ຕິດ ຮອບ) --}}
                    @if ($insTemplateNeedsFreq)
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ຮອບ ການ ກວດ <span class="text-red-500">*</span></label>
                            <select wire:model.live="insFrequency" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">— ເລືອກ ຮອບ —</option>
                                @foreach (\App\Models\InspectionTemplate::FREQ_LABELS as $fk => $fl)<option value="{{ $fk }}">{{ $fl }}</option>@endforeach
                            </select>
                            @if ($insFrequency === '')
                                <p class="text-xs text-amber-600 mt-1">ເລືອກ ຮອບ ກ່ອນ ຈຶ່ງ ສະແດງ ລາຍການ ກວດ ຂອງ ຮອບ ນັ້ນ</p>
                            @endif
                            @error('insFrequency')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    @if (count($insChecklist))
                        <div class="overflow-x-auto border border-gray-200 rounded-md">
                            <table class="w-full text-xs" style="table-layout:fixed;">
                                <colgroup><col style="width:26px"><col><col style="width:96px"><col style="width:104px"></colgroup>
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-1 py-1.5 border-b border-gray-200 text-center font-semibold">#</th>
                                        <th class="px-2 py-1.5 border-b border-gray-200 text-left font-semibold">ລາຍການ ກວດ</th>
                                        <th class="px-1 py-1.5 border-b border-gray-200 text-center font-semibold">ສະຖານະ</th>
                                        <th class="px-2 py-1.5 border-b border-gray-200 text-left font-semibold">ໝາຍເຫດ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($insChecklist as $i => $c)
                                        <tr wire:key="chk-{{ $i }}" class="border-b border-gray-50 last:border-0">
                                            <td class="px-1 py-1 text-center text-gray-400 align-top">{{ $i + 1 }}</td>
                                            <td class="px-2 py-1 text-gray-700 align-top leading-tight">{{ $c['label'] }}</td>
                                            <td class="px-1 py-1 align-top">
                                                <div class="flex gap-1 justify-center">
                                                    <button type="button" wire:click="toggleChecklist({{ $i }}, 'pass')" class="w-9 rounded border py-0.5 {{ ($c['status'] ?? '') === 'pass' ? 'bg-green-50 text-green-700 border-green-300 font-medium' : 'border-gray-300 text-gray-400' }}">OK</button>
                                                    <button type="button" wire:click="toggleChecklist({{ $i }}, 'fail')" class="w-9 rounded border py-0.5 {{ ($c['status'] ?? '') === 'fail' ? 'bg-red-50 text-red-700 border-red-300 font-medium' : 'border-gray-300 text-gray-400' }}">NG</button>
                                                </div>
                                            </td>
                                            <td class="px-1 py-1 align-top"><input type="text" wire:model="insChecklist.{{ $i }}.note" placeholder="—" class="w-full rounded border-gray-300 text-xs py-1" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-400">OK = ຜ່ານ (ໃຊ້ໄດ້) · NG = ບໍ່ຜ່ານ (ຕ້ອງ ສ້ອມ) · ບໍ່ ເລືອກ = N/A (ບໍ່ ກ່ຽວ). ຄິດ % ອັດຕະໂນມັດ ຈາກ OK/NG.</p>
                    @endif
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- ຜູ້ ກວດ (ຈາກ ຜູ້ ລ໋ອກອິນ) + ເວລາ (ສະແຕັມ ອັດຕະໂນມັດ) --}}
                    <div class="md:col-span-2 flex flex-wrap gap-x-6 gap-y-1 text-sm bg-gray-50 border border-gray-100 rounded-md px-3 py-2">
                        <span class="text-gray-500">ຜູ້ ກວດ: <span class="font-medium text-gray-800">{{ auth()->user()->display_name }}</span></span>
                        <span class="text-gray-500">ວັນທີ/ເວລາ:
                            <span class="font-medium text-gray-800">
                                @if ($editingInspectionId){{ $insDate ? \Illuminate\Support\Carbon::parse($insDate)->format('d/m/Y') : '—' }} <span class="text-xs text-gray-400">(ຕາມ ເດີມ)</span>@else ສະແຕັມ ອັດຕະໂນມັດ ຕອນ ບັນທຶກ @endif
                            </span>
                        </span>
                    </div>

                    {{-- ຜົນ ກວດ — ຄິດ ຈາກ ເຊັກລິສ ຖ້າ ມີ, ບໍ່ ດັ່ງນັ້ນ ເລືອກ ເອງ --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຜົນ ກວດ</label>
                        @if ($this->insScore)
                            @php $sc = $this->insScore; $scRes = ['pass' => ['ຜ່ານ', 'bg-green-50 text-green-700 border-green-200'], 'follow_up' => ['ຕ້ອງຕິດຕາມ', 'bg-amber-50 text-amber-700 border-amber-200'], 'fail' => ['ບໍ່ຜ່ານ - ຫ້າມ ໃຊ້ງານ', 'bg-red-50 text-red-700 border-red-200']][$sc['result']]; @endphp
                            <div class="rounded-md border {{ $scRes[1] }} px-3 py-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $scRes[0] }}</span>
                                    <span class="text-sm">ສະພາບ ໂດຍ ລວມ <b>{{ $sc['pct'] }}%</b></span>
                                </div>
                                <div class="mt-1 h-2 rounded bg-white/60 overflow-hidden">
                                    <div class="h-full bg-current opacity-60" style="width: {{ $sc['pct'] }}%"></div>
                                </div>
                                <div class="mt-1 text-xs opacity-80">ຜ່ານ {{ $sc['pass'] }} · ບໍ່ຜ່ານ {{ $sc['fail'] }} · ບໍ່ກ່ຽວ {{ $sc['na'] }} (ຄິດ ຈາກ {{ $sc['considered'] }} ຂໍ້)</div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">ຄິດໄລ່ ອັດຕະໂນມັດ ຈາກ ເຊັກລິສ — 100%=ຜ່ານ · 70–99%=ຕິດຕາມ · ຕ່ຳກວ່າ 70%=ບໍ່ຜ່ານ.</p>
                        @else
                            <select wire:model="insResult" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="pass">ຜ່ານ</option>
                                <option value="fail">ບໍ່ຜ່ານ</option>
                                <option value="follow_up">ຕ້ອງຕິດຕາມ</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">ບໍ່ ໄດ້ ໃຊ້ ເຊັກລິສ — ເລືອກ ຜົນ ເອງ.</p>
                        @endif
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ກຳນົດ ກວດ ຄັ້ງ ໜ້າ</label>
                        <input type="date" wire:model="insNextDue" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ</label>
                        <textarea wire:model="insNotes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>

                    {{-- ຮູບ ຫຼັກຖານ (ຫຼາຍ ໃບ · ຝັງ ວັນທີ+ເວລາ ອັດຕະໂນມັດ) --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຮູບ ຫຼັກຖານ <span class="text-xs text-gray-400">(📷 ກ້ອງ/ແກເລີຣີ · ສູງສຸດ 4 ໃບ · ຝັງ ວັນທີ+ເວລາ ໃຫ້)</span></label>
                        @if (count($insExistingPhotos) || count($insPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($insExistingPhotos as $ei => $p)
                                    <div class="relative" wire:key="insex-{{ $ei }}">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($p) }}" @click="bigImg='{{ \Illuminate\Support\Facades\Storage::url($p) }}'" class="w-16 h-16 rounded object-cover border border-gray-200 cursor-pointer" alt="ຮູບ" />
                                        <button type="button" wire:click="removeExistingInsPhoto({{ $ei }})" wire:confirm="ລຶບ ຮູບ ນີ້?" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center" aria-label="ລຶບ">×</button>
                                    </div>
                                @endforeach
                                @foreach ($insPhotos as $ni => $ph)
                                    <div class="relative" wire:key="insnew-{{ $ni }}">
                                        <img src="{{ $ph->temporaryUrl() }}" class="w-16 h-16 rounded object-cover border border-sky-200" alt="ຮູບ ໃໝ່" />
                                        <button type="button" wire:click="removeInsPhoto({{ $ni }})" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center" aria-label="ລຶບ">×</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <input type="file" wire:model="insPhotos" multiple accept="image/*" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-sky-50 file:text-sky-700 file:min-h-[40px]" />
                        <div wire:loading wire:target="insPhotos" class="text-xs text-gray-400 mt-1">ກຳລັງ ອັບໂຫຼດ…</div>
                        @error('insPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @error('insPhotos')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- ອັບເດດ ສະຖານະ ຈາກ ຜົນ ກວດ --}}
                    <div class="md:col-span-2 border-t border-gray-100 pt-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model.live="insUpdateStatus" class="rounded border-gray-300 text-sky-600"> ອັບເດດ ສະຖານະ ເຄື່ອງ ຈາກ ຜົນ ກວດ
                        </label>
                        @if ($insUpdateStatus)
                            <div class="grid grid-cols-2 gap-3 mt-2">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ຈຳນວນ ຊ່ອມແປງ</label>
                                    <input type="number" min="0" wire:model="insRepair" class="w-full rounded-md border-gray-300 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ຈຳນວນ ຢຸດໃຊ້</label>
                                    <input type="number" min="0" wire:model="insRetired" class="w-full rounded-md border-gray-300 text-sm" />
                                    @error('insRepair')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-span-2 text-xs text-gray-400">ໃຊ້ງານ = {{ $insEquipmentQty }} − ຊ່ອມ − ຢຸດ.</div>
                            </div>
                        @endif
                    </div>
                </div>

                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showInspectionModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="saveInspection" wire:loading.attr="disabled" wire:target="saveInspection,insPhotos" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] shadow-sm hover:bg-sky-700 disabled:opacity-50">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Inspection detail (ເບິ່ງ ລາຍລະອຽດ · read-only) --}}
    @if ($viewingInspection)
        @php
            $vrb = ['pass' => 'bg-green-50 text-green-700', 'fail' => 'bg-red-50 text-red-700', 'follow_up' => 'bg-amber-50 text-amber-700'];
            $vrl = ['pass' => 'ຜ່ານ', 'fail' => 'ບໍ່ຜ່ານ - ຫ້າມ ໃຊ້ງານ', 'follow_up' => 'ຕ້ອງຕິດຕາມ'];
            $vcl = ['pass' => ['OK · ຜ່ານ', 'text-green-700'], 'fail' => ['NG · ບໍ່ຜ່ານ', 'text-red-700'], 'na' => ['N/A', 'text-gray-400']];
        @endphp
        <div class="fixed inset-0 z-[55] flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="ins-view-modal">
            <div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="p-3 space-y-2 overflow-y-auto">
                {{-- ໃບ ກວດ ຮູບແບບ ໃບບິນ ~3.5" (336px) --}}
                <div id="ins-detail-card" class="w-[360px] max-w-full mx-auto bg-white text-gray-800 text-[10px] leading-tight space-y-1.5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">ໃບ ກວດ ສະພາບ ເຄື່ອງ</h3>
                            <div class="text-[11px] text-gray-500">ປະເພດ ເຄື່ອງ: {{ $viewingInspection->equipment?->category ?? '—' }}</div>
                        </div>
                        <button data-noexport wire:click="$set('viewingInspectionId', null)" class="text-gray-400 hover:text-gray-700 p-1 shrink-0" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="border-t border-dashed border-gray-300"></div>

                    @php $vPhotos = $viewingInspection->allPhotos(); @endphp
                    <div class="flex gap-2 items-start">
                        <div class="space-y-0.5 flex-1 min-w-0">
                            <div><span class="text-gray-500">ເຄື່ອງ:</span> {{ $viewingInspection->equipment?->asset_code }} · {{ $viewingInspection->equipment?->name }}</div>
                            <div><span class="text-gray-500">ຜູ້ ກວດ:</span> {{ $viewingInspection->inspector_name ?? '—' }}</div>
                            <div><span class="text-gray-500">ວັນທີ/ເວລາ:</span> {{ $viewingInspection->inspected_at?->format('d/m/Y H:i') }}</div>
                            @if ($viewingInspection->template)
                                <div><span class="text-gray-500">ແມ່ແບບ:</span> {{ $viewingInspection->template->name }}</div>
                            @endif
                            @if ($viewingInspection->fuel_type)
                                <div><span class="text-gray-500">ປະເພດ:</span> {{ ['ev' => 'ໄຟຟ້າ (EV)', 'engine' => 'ນ້ຳມັນ (Engine)'][$viewingInspection->fuel_type] ?? $viewingInspection->fuel_type }}</div>
                            @endif
                            @if ($viewingInspection->frequency)
                                <div><span class="text-gray-500">ຮອບ ກວດ:</span> {{ \App\Models\InspectionTemplate::FREQ_LABELS[$viewingInspection->frequency] ?? $viewingInspection->frequency }}</div>
                            @endif
                            <div class="flex items-center gap-1 pt-0.5 flex-wrap">
                                <span class="text-gray-500">ຜົນ:</span>
                                <span class="rounded px-1.5 py-0.5 font-medium {{ $vrb[$viewingInspection->result] ?? 'bg-gray-100 text-gray-600' }}">{{ $vrl[$viewingInspection->result] ?? $viewingInspection->result }}</span>
                                @if (! is_null($viewingInspection->score))<span class="text-gray-600">ສະພາບ ໂດຍ ລວມ <b>{{ $viewingInspection->score }}%</b></span>@endif
                            </div>
                            @if ($viewingInspection->next_due_date)
                                <div><span class="text-gray-500">ກວດ ຄັ້ງ ໜ້າ:</span> {{ $viewingInspection->next_due_date->format('d/m/Y') }}</div>
                            @endif
                        </div>
                        @if (count($vPhotos))
                            @php
                                $vStamp = $viewingInspection->inspected_at?->format('d/m/Y H:i');
                                // ຝັງ ຮູບ ເປັນ data-URI → html-to-image ບໍ່ ຕ້ອງ ດຶງ ໃໝ່ (export ໄດ້ ແນ່ນອນ).
                                $vDataUri = function ($path) {
                                    try {
                                        $bytes = \Illuminate\Support\Facades\Storage::disk('public')->get($path);
                                        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

                                        return 'data:'.$mime.';base64,'.base64_encode($bytes);
                                    } catch (\Throwable $e) {
                                        return \Illuminate\Support\Facades\Storage::url($path);
                                    }
                                };
                            @endphp
                            <div class="shrink-0" style="width:140px;">
                                <div class="grid {{ count($vPhotos) === 1 ? 'grid-cols-1' : 'grid-cols-2' }} gap-1">
                                    @foreach ($vPhotos as $p)
                                        <div class="relative">
                                            <img src="{{ $vDataUri($p) }}" onclick="window.dispatchEvent(new CustomEvent('wh-lightbox',{detail:'{{ \Illuminate\Support\Facades\Storage::url($p) }}'}))" class="w-full {{ count($vPhotos) === 1 ? 'h-32' : 'h-16' }} rounded object-cover border border-gray-200 cursor-pointer" alt="ຮູບ ຫຼັກຖານ" />
                                            <div class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-center leading-none rounded-b" style="font-size:8px; padding:1px 0;">{{ $vStamp }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    @if (! empty($viewingInspection->checklist))
                        <div class="border border-gray-200 rounded divide-y divide-gray-100">
                            @foreach ($viewingInspection->checklist as $ci => $c)
                                @php $cs = $vcl[$c['status'] ?? 'pass'] ?? ['—', 'text-gray-400']; @endphp
                                <div class="px-1.5 py-1 flex items-start justify-between gap-2">
                                    <div class="text-gray-700 min-w-0 flex-1">{{ $ci + 1 }}. {{ $c['label'] ?? '' }}
                                        @if (! empty($c['note']))<div class="text-[9px] text-gray-400">↳ {{ $c['note'] }}</div>@endif
                                    </div>
                                    <span class="font-medium whitespace-nowrap shrink-0 text-right {{ $cs[1] }}">{{ $cs[0] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($viewingInspection->notes)
                        <div><span class="text-gray-500">ໝາຍເຫດ:</span> {{ $viewingInspection->notes }}</div>
                    @endif
                </div>{{-- /#ins-detail-card --}}

                {{-- ຮັບຊາບ ໂດຍ Manager/Leader → auto-fill ຫ້ອງ ລາຍເຊັນ "ຜູ້ ຮັບຊາບ" ໃນ PDF --}}
                <div class="rounded-md border px-3 py-2 flex items-center justify-between gap-2 flex-wrap {{ $viewingInspection->acknowledged_at ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                    @if ($viewingInspection->acknowledged_at)
                        <div class="text-sm text-green-800 leading-tight">
                            <span class="font-medium">✓ ຮັບຊາບ ແລ້ວ</span>
                            <span class="text-green-700">· {{ $viewingInspection->acknowledged_by_name ?? $viewingInspection->acknowledgedBy?->display_name ?? '—' }}</span>
                            <span class="text-green-600 text-xs">· {{ $viewingInspection->acknowledged_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($canAcknowledge)
                            <button wire:click="unacknowledgeInspection({{ $viewingInspection->id }})" wire:confirm="ຖອນ ການ ຮັບຊາບ ໃບ ກວດ ນີ້?" class="text-xs text-gray-500 underline hover:text-red-600">ຖອນ ຮັບຊາບ</button>
                        @endif
                    @else
                        <span class="text-sm text-gray-500">ຍັງ ບໍ່ ຮັບຊາບ <span class="text-xs text-gray-400">(ຫ້ອງ ລາຍເຊັນ Manager ໃນ PDF ຫວ່າງ)</span></span>
                        @if ($canAcknowledge)
                            <button wire:click="acknowledgeInspection({{ $viewingInspection->id }})" wire:confirm="ຢືນຢັນ ຮັບຊາບ ໃບ ກວດ ນີ້? ຊື່ + ວັນທີ ຂອງ ທ່ານ ຈະ ຂຶ້ນ ໃນ PDF." class="text-sm text-white bg-emerald-600 rounded-md px-3 py-1.5 hover:bg-emerald-700">✅ ຮັບຊາບ (Manager/Leader)</button>
                        @endif
                    @endif
                </div>

                </div>
                <div class="flex flex-wrap justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <a href="{{ route('equipment.inspection.pdf', $viewingInspection->id) }}" target="_blank" class="inline-flex items-center text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] shadow-sm hover:bg-sky-700">⬇ PDF</a>
                    @can('equipment.edit')
                        <button wire:click="editInspection({{ $viewingInspection->id }})" class="text-sm text-sky-700 border border-sky-200 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-50">ແກ້ໄຂ</button>
                    @endcan
                    <button wire:click="$set('viewingInspectionId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ປະຫວັດ/ສະຖານະ ການ ກວດເຊັກ ຂອງ ເຄື່ອງ (ຈາກ ໄອຄ່ອນ ດວງຕາ ໃນ ທະບຽນ) --}}
    @if ($historyEquipment)
        @php
            $hrb = ['pass' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'fail' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'follow_up' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'];
            $hrl = ['pass' => 'ຜ່ານ', 'fail' => 'ບໍ່ຜ່ານ', 'follow_up' => 'ຕ້ອງຕິດຕາມ'];
            $latest = $historyInspections->first();
        @endphp
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="ins-history-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-start gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">ການ ກວດເຊັກ</h3>
                        <div class="text-sm text-gray-500">{{ $historyEquipment->asset_code }} · {{ $historyEquipment->name }}</div>
                    </div>
                    <button wire:click="$set('historyEquipmentId', null)" class="ml-auto text-gray-400 hover:text-gray-700 p-1 shrink-0" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">

                {{-- ສະຖານະ ຫຼ້າສຸດ --}}
                @if ($latest)
                    <div class="rounded-md border border-gray-200 p-3 text-sm">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-gray-500">ຫຼ້າສຸດ:</span>
                            <span class="text-xs rounded-full font-medium px-2 py-0.5 {{ $hrb[$latest->result] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $hrl[$latest->result] ?? $latest->result }}</span>
                            @if (! is_null($latest->score))<span class="text-gray-600">ສະພາບ ໂດຍ ລວມ <b>{{ $latest->score }}%</b></span>@endif
                            <span class="text-gray-400">· {{ $latest->inspected_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($latest->next_due_date)
                            <div class="mt-1 text-xs {{ $latest->next_due_date->isPast() ? 'text-red-600 font-medium' : 'text-gray-500' }}">ກຳນົດ ກວດ ຄັ້ງ ໜ້າ: {{ $latest->next_due_date->format('d/m/Y') }}{{ $latest->next_due_date->isPast() ? ' (ເກີນ ກຳນົດ)' : '' }}</div>
                        @endif
                    </div>
                @else
                    <div class="rounded-md bg-gray-50 border border-gray-100 p-3 text-sm text-gray-500">ຍັງ ບໍ່ ເຄີຍ ກວດເຊັກ ເຄື່ອງ ນີ້.</div>
                @endif

                @can('equipment.edit')
                    <button wire:click="inspectEquipment({{ $historyEquipment->id }})" class="w-full text-sm text-white bg-sky-600 rounded-md px-4 py-2 min-h-[40px] hover:bg-sky-700">＋ ກວດເຊັກ ໃໝ່ ໃຫ້ ເຄື່ອງ ນີ້</button>
                @endcan

                {{-- ປະຫວັດ ທັງ ໝົດ --}}
                @if ($historyInspections->count())
                    <div class="border border-gray-200 rounded-md divide-y divide-gray-100">
                        @foreach ($historyInspections as $h)
                            <div wire:key="hist-{{ $h->id }}" class="px-3 py-2 flex items-center justify-between gap-2 text-sm">
                                <div class="min-w-0">
                                    <div class="text-gray-700">{{ $h->inspected_at?->format('d/m/Y H:i') }} <span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $hrb[$h->result] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $hrl[$h->result] ?? $h->result }}</span>@if (! is_null($h->score))<span class="text-xs text-gray-400 ml-1">{{ $h->score }}%</span>@endif</div>
                                    <div class="text-xs text-gray-400 truncate">{{ $h->inspector_name ?? '—' }}@if ($h->frequency) · ຮອບ: {{ \App\Models\InspectionTemplate::FREQ_LABELS[$h->frequency] ?? $h->frequency }}@endif</div>
                                </div>
                                <button wire:click="viewInspection({{ $h->id }})" class="text-gray-400 hover:text-sky-700 p-1 shrink-0" title="ເບິ່ງ ໃບ ກວດ">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                </div>
                <div class="flex justify-end px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('historyEquipmentId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ລາຍລະອຽດ ເຄື່ອງ (ຈາກ ໄອຄ່ອນ ດວງຕາ ໃນ ຄໍລັມ "ຈັດການ") --}}
    @if ($viewingItem)
        <div class="fixed inset-0 z-[55] flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="item-view-modal">
            <div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-start gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 truncate">{{ $viewingItem->name }}</h3>
                        <div class="text-xs text-gray-500 font-mono">{{ $viewingItem->asset_code }}@if ($viewingItem->fixed_asset_no) · FA: {{ $viewingItem->fixed_asset_no }}@endif</div>
                    </div>
                    <button wire:click="$set('viewingItemId', null)" class="ml-auto text-gray-400 hover:text-gray-700 p-1 shrink-0" aria-label="ປິດ">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-4 space-y-3 overflow-y-auto">

                @if ($viewingItem->photos->count())
                    <div class="flex gap-1.5 flex-wrap">
                        @foreach ($viewingItem->photos as $p)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($p->path) }}" @click="bigImg='{{ \Illuminate\Support\Facades\Storage::url($p->path) }}'"
                                 class="w-20 h-20 rounded object-cover border border-gray-200 cursor-pointer hover:ring-2 hover:ring-sky-400" alt="ຮູບ" />
                        @endforeach
                    </div>
                @endif

                @php $vbd = $viewingItem->statusBreakdown(); $vbw = $viewingItem->currentBorrowers(); @endphp
                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                    <div><dt class="text-xs text-gray-500">ປະເພດ</dt><dd class="text-gray-800">{{ $viewingItem->category ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">ຍີ່ຫໍ້ / ລຸ້ນ</dt><dd class="text-gray-800">{{ $viewingItem->brand_model ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Serial</dt><dd class="text-gray-800 break-words">{{ $viewingItem->serial_no ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Owner (ພະແນກ)</dt><dd class="text-gray-800">{{ $viewingItem->department?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Loc-Bin</dt><dd class="text-gray-800 break-words">{{ $viewingItem->loc_bin ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">ສະຖານທີ່</dt><dd class="text-gray-800">{{ $viewingItem->location ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">ຜູ້ຮັບຜິດຊອບ</dt><dd class="text-gray-800">{{ $viewingItem->responsibleLabel() ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500">ຈຳນວນ</dt><dd class="text-gray-800">{{ $viewingItem->quantity }} {{ $viewingItem->unit?->name }}</dd></div>
                    @if ($viewingItem->purchase_date)
                        <div><dt class="text-xs text-gray-500">ວັນທີ ຊື້</dt><dd class="text-gray-800">{{ $viewingItem->purchase_date->format('d/m/Y') }}</dd></div>
                    @endif
                </dl>

                <div>
                    <div class="text-xs text-gray-500 mb-1">ສະຖານະ</div>
                    <div class="flex flex-wrap gap-1">
                        @foreach (['active', 'repair', 'retired'] as $s)
                            @if ($vbd[$s] > 0)<span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $badge($s) }}">{{ $vbd[$s] }} {{ $statusLabel[$s] }}</span>@endif
                        @endforeach
                    </div>
                </div>

                @if (count($vbw))
                    <div class="text-sm"><span class="text-xs text-gray-500">ຜູ້ຢືມ ປັດຈຸບັນ:</span> <span class="text-amber-700">{{ implode(', ', $vbw) }}</span></div>
                @endif

                @if ($viewingItem->notes)
                    <div class="text-sm"><span class="text-xs text-gray-500 block mb-0.5">ໝາຍເຫດ</span>{{ $viewingItem->notes }}</div>
                @endif

                </div>
                <div class="flex flex-wrap justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    @can('disposal.create')
                        <a href="{{ route('disposal.create', ['add' => 'equipment:' . $viewingItem->id]) }}" wire:navigate class="text-sm text-red-700 border border-red-200 rounded-lg px-4 py-2 min-h-[40px] inline-flex items-center hover:bg-red-50" title="ຂໍ ຈຳໜ່າຍ ເຄື່ອງ ນີ້">🗑 → Disposal</a>
                    @endcan
                    @can('equipment.edit')
                        <button wire:click="editItem({{ $viewingItem->id }})" class="text-sm text-sky-700 border border-sky-200 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-50">ແກ້ໄຂ</button>
                    @endcan
                    <button wire:click="$set('viewingItemId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (ບັງຄັບ) → soft-delete ໄປ Deleted Log --}}
    @if ($deletingId)
        <div class="fixed inset-0 z-[56] flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="del-modal">
            <div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="p-4 space-y-3 overflow-y-auto">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900">ລຶບ ເຄື່ອງ ນີ້?</h3>
                        <p class="text-sm text-gray-500 truncate">{{ $deletingItem?->asset_code }} · {{ $deletingItem?->name }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ເຫດຜົນ ການ ລຶບ <span class="text-red-500">*</span></label>
                    <textarea wire:model="deleteReason" rows="3" class="w-full rounded-md border-gray-300 text-sm focus:border-red-400 focus:ring-red-400" placeholder="ເຊັ່ນ: ເສຍ/ຊຳລຸດ ໃຊ້ ບໍ່ ໄດ້, ຂາຍ/ໂອນ ຕໍ່, ບັນທຶກ ຊ້ຳ…"></textarea>
                    @error('deleteReason')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="text-xs text-gray-400">ຈະ ຍ້າຍ ໄປ ບັນທຶກ ການ ລຶບ (ກູ້ຄືນ ໄດ້) ພ້ອມ ຈົດ ຜູ້ລຶບ + ວັນ​ເວລາ.</div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('deletingId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="deleteRecord" wire:loading.attr="disabled" wire:target="deleteRecord" class="text-sm text-white bg-red-600 rounded-lg px-4 py-2 min-h-[40px] shadow-sm hover:bg-red-700 disabled:opacity-50">🗑 ຢືນຢັນ ລຶບ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ຢືນຢັນ ລຶບ ໃບ ກວດ + ເຫດຜົນ (ບັງຄັບ) → soft-delete ໄປ Deleted Log --}}
    @if ($deletingInsId)
        <div class="fixed inset-0 z-[56] flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="del-ins-modal">
            <div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="p-4 space-y-3 overflow-y-auto">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900">ລຶບ ໃບ ກວດ ນີ້?</h3>
                        <p class="text-sm text-gray-500 truncate">{{ $deletingInspection?->equipment?->asset_code }} · {{ $deletingInspection?->inspected_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ເຫດຜົນ ການ ລຶບ <span class="text-red-500">*</span></label>
                    <textarea wire:model="insDeleteReason" rows="3" class="w-full rounded-md border-gray-300 text-sm focus:border-red-400 focus:ring-red-400" placeholder="ເຊັ່ນ: ບັນທຶກ ຊ້ຳ, ໃສ່ ຂໍ້ມູນ ຜິດ…"></textarea>
                    @error('insDeleteReason')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="text-xs text-gray-400">ຈະ ຍ້າຍ ໄປ ບັນທຶກ ການ ລຶບ (ກູ້ຄືນ ໄດ້) ພ້ອມ ຈົດ ຜູ້ລຶບ + ວັນ​ເວລາ.</div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('deletingInsId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="deleteInspectionRecord" wire:loading.attr="disabled" wire:target="deleteInspectionRecord" class="text-sm text-white bg-red-600 rounded-lg px-4 py-2 min-h-[40px] shadow-sm hover:bg-red-700 disabled:opacity-50">🗑 ຢືນຢັນ ລຶບ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Lightbox — ກົດ ຮູບ ເບິ່ງ ໃຫຍ່ --}}
    <div x-show="bigImg" @click="bigImg=null" @keydown.escape.window="bigImg=null"
         class="fixed inset-0 z-[60] bg-black/80 flex items-center justify-center p-4" style="display:none">
        <img :src="bigImg" class="max-w-full max-h-full rounded shadow-lg" alt="ຮູບ ໃຫຍ່" />
        <button @click="bigImg=null" class="absolute top-4 right-4 text-white text-4xl leading-none" aria-label="ປິດ">&times;</button>
    </div>
</div>
