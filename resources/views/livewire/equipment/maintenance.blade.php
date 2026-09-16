<div x-data="{ big: null }">
    @php
        $tLabels = \App\Models\EquipmentMaintenance::TYPES;
        $sLabels = \App\Models\EquipmentMaintenance::STATUSES;
        $fLabels = \App\Models\EquipmentMaintenance::FREQ_LABELS;
        $tBadge = ['preventive' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200', 'repair' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200', 'service' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200', 'other' => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200'];
        $sBadge = ['planned' => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200', 'in_progress' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200', 'done' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'];
    @endphp

    <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
         class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

    <div class="sticky top-[6.5rem] z-20 bg-gray-100 flex flex-wrap items-center gap-2 py-2">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ເຄື່ອງ/ວຽກ/ຜູ້ເຮັດ…"
               class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm w-56" />
        <select wire:model.live="typeFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">ທຸກ ປະເພດ</option>
            @foreach ($tLabels as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
        </select>
        <select wire:model.live="statusFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">ທຸກ ສະຖານະ</option>
            @foreach ($sLabels as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
        </select>
        <div class="flex-1"></div>
        <span class="text-xs text-gray-400">ທັງໝົດ {{ $records->total() }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }}</span>
        @if ($canManageDeleted)
            <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
            </button>
        @endif
        @can('equipment.edit')
            @unless ($deptScoped)
                <a href="{{ route('equipment.maintenance-templates') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-50 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" /></svg>
                    ເບິ່ງ/ສ້າງ ເທມເພລດ ບຳລຸງ
                </a>
            @endunless
            <button wire:click="newPlan" class="inline-flex items-center gap-1.5 text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-50 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008Z" /></svg>
                ວາງແຜນ ບຳລຸງ
            </button>
            <button wire:click="openRecordChoice" class="inline-flex items-center gap-1.5 text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085" /></svg>
                ລົງມື ບຳລຸງ
            </button>
        @endcan
    </div>

    {{-- Desktop table (freeze: internal scroll + sticky thead ຄື ແທັບ ທະບຽນເຄື່ອງ) --}}
    <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-x-hidden overflow-y-auto max-h-[calc(100vh-16rem)]">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10 bg-gray-50 text-gray-600 text-xs border-b border-gray-200 shadow-sm">
                <tr>
                    <th class="text-left px-3 py-2 font-semibold w-24">ວັນທີ</th>
                    <th class="text-left px-3 py-2 font-semibold">ເຄື່ອງ · ວຽກ</th>
                    <th class="text-left px-3 py-2 font-semibold w-28">ປະເພດ</th>
                    <th class="text-right px-3 py-2 font-semibold w-28">ຄ່າ (ກີບ)</th>
                    <th class="text-left px-3 py-2 font-semibold w-24">ສະຖານະ</th>
                    <th class="text-left px-3 py-2 font-semibold w-28">Service ໜ້າ</th>
                    <th class="px-3 py-2 w-24 text-center font-semibold">ຈັດການ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($records as $m)
                    <tr wire:key="mr-{{ $m->id }}">
                        <td class="px-3 py-2 whitespace-nowrap text-gray-600">{{ $m->maintenance_date?->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-800 truncate">{{ $m->title }}</div>
                            <div class="text-xs text-gray-400 truncate">{{ $m->equipment?->asset_code }} · {{ $m->equipment?->name }}@if ($m->performed_by) · {{ $m->performed_by }}@endif</div>
                            @if ($m->checklist)
                                @php $ck = collect($m->checklist); $ng = $ck->where('status', 'ng')->count(); @endphp
                                <div class="text-[11px] mt-0.5"><span class="text-gray-500">☑ ເຊັກລິສ {{ $ck->count() }} ຂໍ້</span>@if ($ng) <span class="text-red-600 font-medium">· {{ $ng }} ບັນຫາ</span>@endif</div>
                            @endif
                            @if ($showDeleted)
                                <div class="text-[11px] text-red-600 mt-0.5">🗑 ລຶບ: {{ $m->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $m->deletedBy?->display_name ?? '—' }}@if ($m->deleted_reason) · ເຫດຜົນ: {{ $m->deleted_reason }}@endif</div>
                            @endif
                        </td>
                        <td class="px-3 py-2"><span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $tBadge[$m->type] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $tLabels[$m->type] ?? $m->type }}</span></td>
                        <td class="px-3 py-2 text-right whitespace-nowrap text-gray-700">{{ $m->cost !== null ? number_format($m->cost) : '—' }}</td>
                        <td class="px-3 py-2"><span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $sBadge[$m->status] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $sLabels[$m->status] ?? $m->status }}</span></td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @if ($m->next_service_date)
                                <span class="{{ $m->next_service_date->isPast() ? 'text-red-600 font-medium' : ($m->next_service_date->lte(now()->addDays(14)) ? 'text-amber-600' : 'text-gray-600') }}">{{ $m->next_service_date->format('d/m/Y') }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 pr-4 text-right whitespace-nowrap text-gray-500">
                            @if ($showDeleted)
                                <button wire:click="restore({{ $m->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                            @else
                                <button wire:click="viewRecord({{ $m->id }})" class="hover:text-sky-700 p-1" title="ເບິ່ງ">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </button>
                                @php $ngc = collect($m->checklist ?? [])->where('status', 'ng')->count(); @endphp
                                @if ($ngc && $m->type !== 'repair')
                                    @can('equipment.create')
                                        <button wire:click="createRepairsFromNg({{ $m->id }})" wire:confirm="ສ້າງ ໃບ ສ້ອມ (CM) ຈາກ {{ $ngc }} ຂໍ້ ບັນຫາ (NG)?" class="text-amber-600 hover:text-amber-700 p-1" title="ສ້າງ ໃບ ສ້ອມ (CM) ຈາກ NG">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" /></svg>
                                        </button>
                                    @endcan
                                @endif
                                <button wire:click="viewHistory({{ $m->equipment_id }})" class="hover:text-sky-700 p-1" title="ປະຫວັດ ບຳລຸງ ຂອງ ເຄື່ອງ ນີ້">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                </button>
                                @can('equipment.edit')
                                    <button wire:click="editMaintenance({{ $m->id }})" class="hover:text-gray-800 p-1" title="ແກ້ໄຂ">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                    </button>
                                @endcan
                                @can('equipment.delete')
                                    <button wire:click="openDelete({{ $m->id }})" class="hover:text-red-600 p-1" title="ລຶບ">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ບັນທຶກ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ ບັນທຶກ ບຳລຸງ — ກົດ "ວາງແຜນ ບຳລຸງ" ຫຼື "ລົງມື ບຳລຸງ"' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="md:hidden space-y-2">
        @forelse ($records as $m)
            <div wire:key="mm-{{ $m->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="font-medium text-gray-800">{{ $m->title }}</div>
                    <span class="text-xs rounded-full font-medium px-1.5 py-0.5 shrink-0 {{ $sBadge[$m->status] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $sLabels[$m->status] ?? $m->status }}</span>
                </div>
                <div class="text-xs text-gray-400">{{ $m->equipment?->asset_code }} · {{ $m->maintenance_date?->format('d/m/Y') }} · {{ $tLabels[$m->type] ?? $m->type }}</div>
                <div class="text-xs text-gray-600 mt-1">ຄ່າ: {{ $m->cost !== null ? number_format($m->cost).' ກີບ' : '—' }}@if ($m->next_service_date) · service: {{ $m->next_service_date->format('d/m/Y') }}@endif</div>
                @if ($m->checklist)
                    @php $ckM = collect($m->checklist); $ngM = $ckM->where('status', 'ng')->count(); @endphp
                    <div class="text-[11px] mt-0.5"><span class="text-gray-500">☑ ເຊັກລິສ {{ $ckM->count() }} ຂໍ້</span>@if ($ngM) <span class="text-red-600 font-medium">· {{ $ngM }} ບັນຫາ</span>@endif</div>
                @endif
                @if ($showDeleted)
                    <div class="text-[11px] text-red-600 mt-1">🗑 ລຶບ: {{ $m->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $m->deletedBy?->display_name ?? '—' }}@if ($m->deleted_reason) · ເຫດຜົນ: {{ $m->deleted_reason }}@endif</div>
                @endif
                <div class="flex gap-2 mt-2">
                    @if ($showDeleted)
                        <button wire:click="restore({{ $m->id }})" class="text-xs border border-emerald-200 rounded px-2 py-1 min-h-[36px] text-emerald-700">↩ ກູ້ຄືນ</button>
                    @else
                        <button wire:click="viewRecord({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">👁 ເບິ່ງ</button>
                        @php $ngc = collect($m->checklist ?? [])->where('status', 'ng')->count(); @endphp
                        @if ($ngc && $m->type !== 'repair')
                            @can('equipment.create')<button wire:click="createRepairsFromNg({{ $m->id }})" wire:confirm="ສ້າງ ໃບ ສ້ອມ (CM) ຈາກ {{ $ngc }} ຂໍ້ NG?" class="text-xs border border-amber-300 text-amber-700 rounded px-2 py-1 min-h-[36px]">🔧 ສ້າງ CM ({{ $ngc }})</button>@endcan
                        @endif
                        @can('equipment.edit')<button wire:click="editMaintenance({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">ແກ້ໄຂ</button>@endcan
                        @can('equipment.delete')<button wire:click="openDelete({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px] text-red-600">ລຶບ</button>@endcan
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center text-gray-400 py-6">{{ $showDeleted ? 'ບໍ່ ມີ ບັນທຶກ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ ບັນທຶກ ບຳລຸງ' }}</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- ຖາມ ຕອນ "ລົງມື ບຳລຸງ": ຕາມ ແຜນ ທີ່ ວາງໄວ້ ຫຼື ໃໝ່ ຕາມ ໜ້າງານ --}}
    @if ($showRecordChoice)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="m-choice">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <h3 class="text-base font-semibold text-gray-800">ລົງມື ບຳລຸງ — ເລືອກ ວິທີ</h3>
                    <button wire:click="$set('showRecordChoice', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">

                {{-- ໃໝ່ ຕາມ ໜ້າງານ --}}
                <button wire:click="newMaintenance" class="w-full flex items-start gap-3 text-left border border-gray-200 rounded-md p-3 hover:border-sky-400 hover:bg-sky-50">
                    <svg class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    <div>
                        <div class="text-sm font-medium text-gray-800">ໃໝ່ ຕາມ ໜ້າງານ</div>
                        <div class="text-xs text-gray-500">ເລືອກ ເຄື່ອງ ຈາກ ທະບຽນ + ແມ່ແບບ ແລ້ວ ລົງມື ເລີຍ (ຖ້າ ເຄື່ອງ ບໍ່ ມີ → ໄປ ສ້າງ ໃນ ທະບຽນເຄື່ອງ ກ່ອນ)</div>
                    </div>
                </button>

                {{-- ຕາມ ແຜນ ທີ່ ວາງໄວ້ --}}
                <div class="border border-gray-200 rounded-md">
                    <div class="px-3 py-2 border-b border-gray-100 text-sm font-medium text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        ຕາມ ແຜນ ທີ່ ວາງໄວ້ ({{ $plannedRecords->count() }})
                    </div>
                    <div class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
                        @forelse ($plannedRecords as $p)
                            <button wire:click="executeFromPlan({{ $p->id }})" wire:key="plan-{{ $p->id }}" class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left hover:bg-amber-50">
                                <div class="min-w-0">
                                    <div class="text-sm text-gray-800 truncate">{{ $p->title }}</div>
                                    <div class="text-xs text-gray-400 truncate">{{ $p->equipment?->asset_code }} · {{ $p->equipment?->name }} · ນັດ {{ $p->maintenance_date?->format('d/m/Y') }}</div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                            </button>
                        @empty
                            <div class="px-3 py-6 text-center text-xs text-gray-400">ຍັງ ບໍ່ ມີ ແຜນ ທີ່ ວາງໄວ້ — ໃຊ້ "ໃໝ່ ຕາມ ໜ້າງານ" ຫຼື ໄປ "ວາງແຜນ ບຳລຸງ" ກ່ອນ</div>
                        @endforelse
                    </div>
                </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Record modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="m-modal">
            <div class="bg-white w-full md:max-w-xl rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ ບຳລຸງ' : ($planning ? 'ວາງແຜນ ບຳລຸງ ລ່ວງໜ້າ' : 'ລົງມື ບຳລຸງ') }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">

                @include('partials._form-errors')

                {{-- ເລືອກ ເຄື່ອງ --}}
                @if (! $mEquipmentId)
                    <div class="relative">
                        <label class="block text-sm text-gray-600 mb-1">ເລືອກ ເຄື່ອງ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.300ms="mSearch" placeholder="ຄົ້ນຫາ ຊື່/ລະຫັດ…" class="w-full rounded-md border-gray-300 text-sm" />
                        @if ($searchResults->isNotEmpty())
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                                @foreach ($searchResults as $eq)
                                    <button type="button" wire:click="pickEquipment({{ $eq->id }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">{{ $eq->name }} <span class="font-mono text-xs text-gray-400">{{ $eq->asset_code }}</span></button>
                                @endforeach
                            </div>
                        @endif
                        @error('mEquipmentId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded px-3 py-2 text-sm">
                        <span class="font-medium text-gray-700">{{ $mEquipmentLabel }}</span>
                        <button wire:click="$set('mEquipmentId', null)" class="text-xs text-sky-600 hover:underline">ປ່ຽນ</button>
                    </div>
                @endif

                {{-- ໃຊ້ ແມ່ແບບ ເຊັກລິສ — ຄັດ ຕາມ ເຄື່ອງ/ປະເພດ/ທົ່ວໄປ; ຕິກ "ໂຊ ທັງໝົດ" ໄດ້ --}}
                @if ($mEquipmentId)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm text-gray-600">ໃຊ້ ແມ່ແບບ ເຊັກລິສ <span class="text-xs text-gray-400">(ຖ້າ ຕ້ອງການ)</span></label>
                            <label class="inline-flex items-center gap-1.5 text-xs text-gray-500"><input type="checkbox" wire:model.live="mShowAllTemplates" class="rounded border-gray-300 text-sky-600 w-3.5 h-3.5"> ໂຊ ທັງໝົດ</label>
                        </div>
                        <select wire:model.live="mTemplateId" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">— ບໍ່ ໃຊ້ ແມ່ແບບ —</option>
                            @foreach ($templateOptions as $tpl)<option value="{{ $tpl->id }}">{{ $tpl->name }}</option>@endforeach
                        </select>
                        @if ($templateOptions->isEmpty())
                            <p class="text-xs text-gray-400 mt-1">ບໍ່ ມີ ແມ່ແບບ ກົງ ກັບ ເຄື່ອງ ນີ້ — ຕິກ "ໂຊ ທັງໝົດ" ຫຼື ສ້າງ ແມ່ແບບ ໃໝ່ (ຕໍ່ ປະເພດ/ທົ່ວໄປ).</p>
                        @elseif ($mTemplateId && $mFrequency === '')
                            <p class="text-xs text-amber-600 mt-1">ເລືອກ "ຮອບ Service" ຂ້າງລຸ່ມ ເພື່ອ ໃຫ້ ລາຍການ ເຊັກລິສ ຂຶ້ນ ຕາມ ຮອບ</p>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- ແຖວ 1: ຮອບ Service · ວັນທີ ນັດ ບຳລຸງ --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຮອບ Service</label>
                        <select wire:model.live="mFrequency" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">— ບໍ່ ກຳນົດ (ຄັ້ງ ດຽວ) —</option>
                            @foreach ($fLabels as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">{{ $planning ? 'ວັນທີ ນັດ ບຳລຸງ' : 'ວັນທີ' }} <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="mDate" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('mDate')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- ແຖວ 2: ປະເພດ · ສະຖານະ --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ປະເພດ <span class="text-red-500">*</span></label>
                        <select wire:model="mType" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach ($tLabels as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ສະຖານະ <span class="text-red-500">*</span></label>
                        <select wire:model="mStatus" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach ($sLabels as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>

                    {{-- ແຖວ 3: ຜູ້ໃຫ້ ບໍລິການ · ລາຄາ · Service ຄັ້ງ ໜ້າ --}}
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ຜູ້ໃຫ້ ບໍລິການ <span class="text-xs text-gray-400">(ຖ້າ ມີ)</span></label>
                            <input type="text" wire:model="mPerformedBy" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ລາຄາ (ກີບ) <span class="text-xs text-gray-400">(ຖ້າ ມີ)</span></label>
                            <input type="number" min="0" step="1000" wire:model="mCost" class="w-full rounded-md border-gray-300 text-sm" />
                            @error('mCost')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Service ຄັ້ງ ໜ້າ</label>
                            <input type="date" wire:model="mNextService" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>

                    {{-- ຂໍ້ມູນ ອື່ນໆ --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຫົວຂໍ້ ວຽກ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="mTitle" placeholder="ເຊັ່ນ ປ່ຽນ ນ້ຳມັນ ເຄື່ອງ" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('mTitle')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ລາຍລະອຽດ ວຽກ</label>
                        <textarea wire:model="mDescription" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ</label>
                        <textarea wire:model="mNotes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>

                    {{-- ຮູບ ຫຼັກຖານ --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຮູບ ຫຼັກຖານ <span class="text-xs text-gray-400">(📷 ກ້ອງ/ແກເລີຣີ · ສູງສຸດ 4 ໃບ)</span></label>
                        @if (count($mExistingPhotos) || count($mPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($mExistingPhotos as $ei => $p)
                                    <div class="relative" wire:key="mex-{{ $ei }}">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($p) }}" @click="big='{{ \Illuminate\Support\Facades\Storage::url($p) }}'" class="w-16 h-16 rounded object-cover border border-gray-200 cursor-pointer" alt="ຮູບ" />
                                        <button type="button" wire:click="removeExistingPhoto({{ $ei }})" wire:confirm="ລຶບ ຮູບ ນີ້?" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center" aria-label="ລຶບ">×</button>
                                    </div>
                                @endforeach
                                @foreach ($mPhotos as $ni => $ph)
                                    <div class="relative" wire:key="mnew-{{ $ni }}">
                                        <img src="{{ $ph->temporaryUrl() }}" class="w-16 h-16 rounded object-cover border border-sky-200" alt="ຮູບ ໃໝ່" />
                                        <button type="button" wire:click="removePhoto({{ $ni }})" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center" aria-label="ລຶບ">×</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <input type="file" x-data="photoInput('mPhotos')" x-on:change="pick($event)" multiple accept="image/*" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-sky-50 file:text-sky-700 file:min-h-[40px]" />
                        <div wire:loading wire:target="mPhotos" class="text-xs text-gray-400 mt-1">ກຳລັງ ອັບໂຫຼດ…</div>
                        @error('mPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- ຊັ້ນ 1: ປະເພດ ລົດ (EV/Engine) — ຄັດ ເຊັກລິສ ໃຫ້ ຖືກ ກັບ ລົດ (ເຊື່ອງ ຕອນ ແກ້ໄຂ/ລົງມື ຕາມ ແຜນ: checklist ຖືກ ກຽມ ໄວ້ ແລ້ວ) --}}
                @if (! $editingId && $mTemplateId && $mTemplateHasFuelTypes)
                    <div class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-sky-800 font-medium">ປະເພດ ລົດ <span class="text-red-500">*</span></span>
                            <button type="button" wire:click="$set('mFuelType', 'ev')" class="text-sm border rounded-md px-3 py-1.5 min-h-[36px] {{ $mFuelType === 'ev' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-gray-700 border-gray-300' }}">ໄຟຟ້າ (EV)</button>
                            <button type="button" wire:click="$set('mFuelType', 'engine')" class="text-sm border rounded-md px-3 py-1.5 min-h-[36px] {{ $mFuelType === 'engine' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-gray-700 border-gray-300' }}">ນ້ຳມັນ (Engine)</button>
                        </div>
                        @error('mFuelType')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @if ($mFuelType === '' && ! count($mChecklist))
                            <p class="text-xs text-amber-700 mt-1">ເລືອກ ປະເພດ ລົດ ກ່ອນ ເພື່ອ ໃຫ້ ເຊັກລິສ ຂຶ້ນ ຕາມ ລົດ</p>
                        @endif
                    </div>
                @endif

                {{-- ໂໝດ ວາງແຜນ: ບໍ່ ຕິກ checklist ຕອນນີ້ — ພຽງ ກຽມ ໄວ້ ໃຫ້ ລົງມື ພາຍຫຼັງ --}}
                @if ($planning && count($mChecklist))
                    <div class="rounded-md border border-sky-100 bg-sky-50 px-3 py-2 text-xs text-sky-700 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <span>ຈະ ກຽມ ເຊັກລິສ <b>{{ count($mChecklist) }} ຂໍ້</b> ໄວ້ — ຕິກ ✓/✗ + ຖ່າຍ ຮູບ/ຄລິບ ຕອນ <b>"ລົງມື ບຳລຸງ ຕາມ ແຜນ"</b>.</span>
                    </div>
                @endif

                {{-- ເຊັກລິສ ຈາກ ແມ່ແບບ — ຄັດ ຕາມ ຮອບ (mFrequency), ແຍກ ໝວດ + filter (ໂໝດ ລົງມື ເທົ່ານັ້ນ) --}}
                @if (count($mChecklist) && ! $planning)
                    @php
                        $groupLabels = \App\Models\MaintenanceTemplate::GROUPS;
                        $groupTone = [
                            'engine' => 'bg-amber-50 text-amber-800',
                            'powertrain' => 'bg-violet-50 text-violet-800',
                            'steering' => 'bg-sky-50 text-sky-800',
                            'mast' => 'bg-emerald-50 text-emerald-800',
                            'hydraulic' => 'bg-teal-50 text-teal-800',
                            'electrical' => 'bg-rose-50 text-rose-800',
                            'other' => 'bg-gray-100 text-gray-700',
                        ];
                        $ckShown = collect($checklistGroups)->sum(fn ($r) => count($r));
                        $chipBase = 'px-2 py-1 rounded-full text-[11px] border min-h-[30px] whitespace-nowrap';
                    @endphp
                    <div class="border border-gray-200 rounded-md overflow-hidden">
                        <div class="bg-gray-50 px-3 py-1.5 text-xs border-b border-gray-200 flex items-center justify-between gap-2 flex-wrap">
                            <span class="text-gray-600 font-medium">ເຊັກລິສ ບຳລຸງ — ຮອບ {{ $fLabels[$mFrequency] ?? $mFrequency }}</span>
                            <span class="text-gray-400 whitespace-nowrap">C=ກວດ · X=ປ່ຽນ · ✓=ແລ້ວ · ✗=ບັນຫາ</span>
                        </div>

                        {{-- filter chips --}}
                        <div class="flex flex-wrap items-center gap-1.5 px-3 py-2 bg-white border-b border-gray-100">
                            <span class="text-[11px] text-gray-400 mr-0.5">ຄັດ:</span>
                            <button type="button" wire:click="$set('ckAction', '')" class="{{ $chipBase }} {{ $ckAction === '' ? 'bg-sky-600 text-white border-sky-600' : 'bg-white text-gray-600 border-gray-300' }}">ທັງໝົດ</button>
                            <button type="button" wire:click="$set('ckAction', 'C')" class="{{ $chipBase }} {{ $ckAction === 'C' ? 'bg-sky-600 text-white border-sky-600' : 'bg-white text-gray-600 border-gray-300' }}">C ກວດ</button>
                            <button type="button" wire:click="$set('ckAction', 'X')" class="{{ $chipBase }} {{ $ckAction === 'X' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-600 border-gray-300' }}">X ປ່ຽນ</button>
                            <button type="button" wire:click="$toggle('ckNg')" class="{{ $chipBase }} {{ $ckNg ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-300' }}">✗ ບັນຫາ@if ($checklistNgCount) ({{ $checklistNgCount }})@endif</button>
                            <div class="flex-1"></div>
                            <span class="text-[11px] text-gray-400">ສະແດງ {{ $ckShown }}/{{ $checklistTotal }}</span>
                        </div>

                        {{-- grouped rows --}}
                        @forelse ($checklistGroups as $gk => $rows)
                            <div x-data="{ open: true }" wire:key="ckg-{{ $gk }}" class="border-b border-gray-100 last:border-0">
                                <button type="button" @click="open = ! open" class="w-full flex items-center gap-2 px-3 py-1.5 text-xs {{ $groupTone[$gk] ?? $groupTone['other'] }}">
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    <span class="font-medium">{{ $groupLabels[$gk] ?? $gk }}</span>
                                    <span class="opacity-70">· {{ count($rows) }} ຂໍ້</span>
                                </button>
                                <div x-show="open" class="divide-y divide-gray-50">
                                    @foreach ($rows as $c)
                                        @php $i = $c['i']; @endphp
                                        <div wire:key="mck-{{ $i }}" class="px-2 py-1.5">
                                            <div class="flex items-start gap-2">
                                                <span class="text-gray-400 w-5 text-right text-xs pt-0.5 shrink-0">{{ $i + 1 }}</span>
                                                <span class="shrink-0 text-[10px] font-bold rounded-full px-1 py-0.5 {{ ($c['action'] ?? '') === 'X' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' }}">{{ $c['action'] ?? '' }}</span>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-gray-700 leading-tight text-xs">{{ $c['label'] }}</div>
                                                    @if (! empty($c['remark']))<div class="text-[11px] text-gray-400 font-mono">{{ $c['remark'] }}</div>@endif
                                                </div>
                                                <div class="flex gap-1 shrink-0">
                                                    <button type="button" wire:click="toggleMaintChecklist({{ $i }}, 'ok')" class="w-8 rounded border py-0.5 text-xs {{ ($c['status'] ?? '') === 'ok' ? 'bg-green-50 text-green-700 border-green-300 font-medium' : 'border-gray-300 text-gray-400' }}" title="ແລ້ວ">✓</button>
                                                    <button type="button" wire:click="toggleMaintChecklist({{ $i }}, 'ng')" class="w-8 rounded border py-0.5 text-xs {{ ($c['status'] ?? '') === 'ng' ? 'bg-red-50 text-red-700 border-red-300 font-medium' : 'border-gray-300 text-gray-400' }}" title="ມີ ບັນຫາ">✗</button>
                                                </div>
                                            </div>

                                            {{-- ໝາຍເຫດ/ອ້າງອີງ ການປະຕິບັດ (ທຸກ ຂໍ້, ທາງເລືອກ) --}}
                                            <div class="pl-7 mt-1">
                                                <input type="text" wire:model="mChecklist.{{ $i }}.note" placeholder="ໝາຍເຫດ/ອ້າງອີງ ການປະຕິບັດ (ຖ້າ ມີ)…" class="w-full rounded border border-gray-200 text-[11px] px-2 py-1 text-gray-600 focus:border-sky-400 focus:ring-0" />
                                            </div>

                                            {{-- ຫຼັກຖານ ຮູບ/ຄລິບ — ຂຶ້ນ ສະເພາະ ຕອນ ໝາຍ ✗ (ມີ ບັນຫາ) ເທົ່ານັ້ນ --}}
                                            @php $st = $c['status'] ?? 'na'; @endphp
                                            @if ($st === 'ng')
                                                @php
                                                    $slots = ['problem' => ['label' => 'ຫຼັກຖານ ບັນຫາ', 'model' => 'itemPhotoProblem']];
                                                    $isVid = fn ($p) => in_array(strtolower(pathinfo((string) $p, PATHINFO_EXTENSION)), ['mp4', 'mov', 'webm', '3gp', 'm4v']);
                                                @endphp
                                                <div class="flex flex-wrap gap-2 mt-1.5 pl-7">
                                                    @foreach ($slots as $slot => $meta)
                                                        @php
                                                            $savedPath = $c['photo_'.$slot] ?? null;
                                                            $store = data_get($this, $meta['model'].'.'.$i);
                                                        @endphp
                                                        <div class="w-24">
                                                            <div class="text-[10px] {{ $st === 'ng' ? 'text-red-500' : 'text-gray-400' }} mb-0.5">📷 {{ $meta['label'] }}</div>
                                                            @if ($savedPath)
                                                                <div class="relative">
                                                                    @if ($isVid($savedPath))
                                                                        <video src="{{ \Illuminate\Support\Facades\Storage::url($savedPath) }}" class="w-full h-16 rounded object-cover border border-gray-200 bg-black" controls preload="metadata"></video>
                                                                    @else
                                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($savedPath) }}" @click="big='{{ \Illuminate\Support\Facades\Storage::url($savedPath) }}'" class="w-full h-16 rounded object-cover border border-gray-200 cursor-pointer" alt="{{ $meta['label'] }}" />
                                                                    @endif
                                                                    <button type="button" wire:click="clearItemPhoto({{ $i }}, '{{ $slot }}')" class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-red-600 text-white text-[10px] leading-none flex items-center justify-center">×</button>
                                                                </div>
                                                            @elseif ($store)
                                                                <div class="relative">
                                                                    @if ($store->isPreviewable())
                                                                        <img src="{{ $store->temporaryUrl() }}" class="w-full h-16 rounded object-cover border border-sky-300" alt="{{ $meta['label'] }}" />
                                                                    @else
                                                                        <div class="w-full h-16 rounded border border-sky-300 bg-sky-50 text-sky-600 text-[10px] flex flex-col items-center justify-center"><span class="text-base leading-none">🎬</span>ຄລິບ ພ້ອມ</div>
                                                                    @endif
                                                                    <button type="button" wire:click="clearItemPhoto({{ $i }}, '{{ $slot }}')" class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-red-600 text-white text-[10px] leading-none flex items-center justify-center">×</button>
                                                                </div>
                                                            @else
                                                                <label class="flex flex-col items-center justify-center h-16 rounded border border-dashed {{ $st === 'ng' ? 'border-red-300 text-red-400 hover:border-red-500' : 'border-gray-300 text-gray-400 hover:border-sky-400 hover:text-sky-500' }} cursor-pointer">
                                                                    <span class="text-base leading-none">📷</span>
                                                                    <span class="text-[10px] mt-0.5">ຖ່າຍ/ອັບ</span>
                                                                    <input type="file" x-data="photoInputOne('{{ $meta['model'] }}.{{ $i }}')" x-on:change="pick($event)" accept="image/*,video/*" capture="environment" class="hidden" />
                                                                </label>
                                                            @endif
                                                            <div wire:loading wire:target="{{ $meta['model'] }}.{{ $i }}" class="text-[10px] text-gray-400 mt-0.5">ອັບ…</div>
                                                            @error($meta['model'].'.'.$i)<div class="text-[10px] text-red-600 mt-0.5">{{ $message }}</div>@enderror
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="px-3 py-6 text-center text-xs text-gray-400">ບໍ່ ມີ ຂໍ້ ກົງ ກັບ ຕົວ ຄັດ — <button type="button" wire:click="resetChecklistFilter" class="text-sky-600 hover:underline">ລ້າງ ຕົວ ຄັດ</button></div>
                        @endforelse

                        <p class="text-[11px] text-gray-400 px-3 py-1.5 border-t border-gray-100">ໝາຍ ✓ (ແລ້ວ) / ✗ (ບັນຫາ) ຕໍ່ ຂໍ້ · ຄ່າ ຕັ້ງຕົ້ນ = ຍັງ ບໍ່ ໝາຍ. ໝາຍ **✗ ບັນຫາ** ເທົ່ານັ້ນ → ຖ່າຍ ຮູບ/ຄລິບ ຫຼັກຖານ. ກົດ ຫົວ ໝວດ ເພື່ອ ຍໍ່/ຂະຫຍາຍ.</p>
                    </div>
                @endif

                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save,mPhotos" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] shadow-sm hover:bg-sky-700 disabled:opacity-50">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- History modal (per equipment) --}}
    @if ($historyEquipment)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="m-history">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-start gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">ປະຫວັດ ບຳລຸງຮັກສາ</h3>
                        <div class="text-sm text-gray-500">{{ $historyEquipment->asset_code }} · {{ $historyEquipment->name }}</div>
                    </div>
                    <button wire:click="$set('historyEquipmentId', null)" class="ml-auto text-gray-400 hover:text-gray-700 p-1 shrink-0" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                <div class="rounded-md bg-gray-50 border border-gray-100 p-2 text-sm text-gray-600">ຄ່າ ໃຊ້ຈ່າຍ ລວມ: <b>{{ number_format((float) $history->sum('cost')) }}</b> ກີບ · {{ $history->count() }} ຄັ້ງ</div>
                @if ($history->count())
                    <div class="border border-gray-200 rounded-md divide-y divide-gray-100">
                        @foreach ($history as $h)
                            <div wire:key="mh-{{ $h->id }}" class="px-3 py-2 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="font-medium text-gray-800">{{ $h->title }}</div>
                                    <span class="text-xs rounded-full font-medium px-1.5 py-0.5 shrink-0 {{ $tBadge[$h->type] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $tLabels[$h->type] ?? $h->type }}</span>
                                </div>
                                <div class="text-xs text-gray-400">{{ $h->maintenance_date?->format('d/m/Y') }}@if ($h->performed_by) · {{ $h->performed_by }}@endif@if ($h->cost !== null) · {{ number_format($h->cost) }} ກີບ@endif</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-gray-500">ຍັງ ບໍ່ ເຄີຍ ບຳລຸງ ເຄື່ອງ ນີ້.</div>
                @endif
                </div>
                <div class="flex justify-end px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('historyEquipmentId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- View record (read-only) --}}
    @if ($viewing)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="m-view">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-start gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-semibold text-gray-800">{{ $viewing->title }}</h3>
                            <span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $tBadge[$viewing->type] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $tLabels[$viewing->type] ?? $viewing->type }}</span>
                            <span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $sBadge[$viewing->status] ?? 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $sLabels[$viewing->status] ?? $viewing->status }}</span>
                        </div>
                        <div class="text-sm text-gray-500 mt-0.5">{{ $viewing->equipment?->asset_code }} · {{ $viewing->equipment?->name }}</div>
                    </div>
                    <div class="ml-auto flex items-center gap-1 shrink-0">
                        <a href="{{ route('equipment.maintenance.pdf', $viewing->id) }}" target="_blank" class="inline-flex items-center gap-1 text-sm text-gray-700 border border-gray-200 rounded-md px-2.5 py-1.5 hover:bg-gray-50" title="ພິມ / PDF">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z" /></svg>
                            PDF
                        </a>
                        @can('equipment.edit')<button wire:click="editMaintenance({{ $viewing->id }})" class="text-sm text-sky-700 border border-sky-200 rounded-md px-2.5 py-1.5 hover:bg-sky-50">ແກ້ໄຂ</button>@endcan
                        <button wire:click="$set('viewingId', null)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">

                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div><span class="text-gray-400 text-xs block">ວັນທີ</span>{{ $viewing->maintenance_date?->format('d/m/Y') ?? '—' }}</div>
                    <div><span class="text-gray-400 text-xs block">ຮອບ</span>{{ $viewing->frequency ? (\App\Models\EquipmentMaintenance::FREQ_LABELS[$viewing->frequency] ?? $viewing->frequency) : '—' }}</div>
                    <div><span class="text-gray-400 text-xs block">ຄ່າ ໃຊ້ຈ່າຍ</span>{{ $viewing->cost !== null ? number_format($viewing->cost).' ກີບ' : '—' }}</div>
                    <div><span class="text-gray-400 text-xs block">Service ໜ້າ</span>{{ $viewing->next_service_date?->format('d/m/Y') ?? '—' }}</div>
                    <div class="col-span-2"><span class="text-gray-400 text-xs block">ຜູ້ ເຮັດ</span>{{ $viewing->performed_by ?: '—' }}</div>
                </div>

                @if ($viewing->description)<div class="text-xs text-gray-600 bg-gray-50 border border-gray-100 rounded-md px-3 py-2">{{ $viewing->description }}</div>@endif

                @if ($viewing->checklist && count($viewing->checklist))
                    @php $vck = collect($viewing->checklist); $vng = $vck->where('status', 'ng')->count(); @endphp
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">ເຊັກລິສ {{ $vck->count() }} ຂໍ້</span>
                        @if ($vng)<span class="text-red-600 font-medium">{{ $vng }} ບັນຫາ (NG)</span>@else<span class="text-green-600">ຜ່ານ ໝົດ</span>@endif
                    </div>
                    <div class="border border-gray-200 rounded-md divide-y divide-gray-50 text-xs">
                        @foreach ($viewing->checklist as $c)
                            @php $st = $c['status'] ?? 'na'; @endphp
                            <div class="px-3 py-1.5 flex items-start gap-2 {{ $st === 'ng' ? 'bg-red-50' : '' }}">
                                <span class="shrink-0 w-5 text-center font-bold {{ $st === 'ok' ? 'text-green-600' : ($st === 'ng' ? 'text-red-600' : 'text-gray-300') }}">{{ $st === 'ok' ? '✓' : ($st === 'ng' ? '✗' : '—') }}</span>
                                <div class="min-w-0">
                                    <div class="text-gray-700 leading-tight">{{ $c['label'] ?? '' }}@if (! empty($c['action'])) <span class="text-[10px] rounded-full px-1 {{ ($c['action'] ?? '') === 'X' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' }}">{{ $c['action'] }}</span>@endif</div>
                                    @if (! empty($c['remark']))<div class="text-[11px] text-gray-400 font-mono">{{ $c['remark'] }}</div>@endif
                                    @if (! empty($c['note']))<div class="text-[11px] text-sky-700 mt-0.5">📝 {{ $c['note'] }}</div>@endif
                                    @if (! empty($c['photo_problem']) || ! empty($c['photo_before']) || ! empty($c['photo_after']))
                                        @php $isVidV = fn ($p) => in_array(strtolower(pathinfo((string) $p, PATHINFO_EXTENSION)), ['mp4', 'mov', 'webm', '3gp', 'm4v']); @endphp
                                        <div class="flex gap-2 mt-1">
                                            @foreach (['problem' => 'ຫຼັກຖານ', 'before' => 'ກ່ອນ', 'after' => 'ຫຼັງ'] as $slot => $slotLabel)
                                                @if (! empty($c['photo_'.$slot]))
                                                    <div class="text-center">
                                                        @if ($isVidV($c['photo_'.$slot]))
                                                            <video src="{{ \Illuminate\Support\Facades\Storage::url($c['photo_'.$slot]) }}" class="w-16 h-16 rounded object-cover border border-gray-200 bg-black" controls preload="metadata"></video>
                                                        @else
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($c['photo_'.$slot]) }}" @click="big='{{ \Illuminate\Support\Facades\Storage::url($c['photo_'.$slot]) }}'" class="w-16 h-16 rounded object-cover border border-gray-200 cursor-pointer" alt="{{ $slotLabel }}" />
                                                        @endif
                                                        <div class="text-[10px] {{ $slot === 'problem' ? 'text-red-500' : 'text-gray-400' }}">{{ $slotLabel }}</div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ຮັບຊາບ ໂດຍ Manager/Leader → auto-fill ຫ້ອງ ລາຍເຊັນ "ຜູ້ ຮັບຊາບ" ໃນ PDF --}}
                <div class="rounded-md border px-3 py-2 flex items-center justify-between gap-2 flex-wrap {{ $viewing->acknowledged_at ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                    @if ($viewing->acknowledged_at)
                        <div class="text-sm text-green-800 leading-tight">
                            <span class="font-medium">✓ ຮັບຊາບ ແລ້ວ</span>
                            <span class="text-green-700">· {{ $viewing->acknowledged_by_name ?? $viewing->acknowledgedBy?->display_name ?? '—' }}</span>
                            <span class="text-green-600 text-xs">· {{ $viewing->acknowledged_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($canAcknowledge)
                            <button wire:click="unacknowledgeMaintenance({{ $viewing->id }})" wire:confirm="ຖອນ ການ ຮັບຊາບ ໃບ ນີ້?" class="text-xs text-gray-500 underline hover:text-red-600">ຖອນ ຮັບຊາບ</button>
                        @endif
                    @else
                        <span class="text-sm text-gray-500">ຍັງ ບໍ່ ຮັບຊາບ <span class="text-xs text-gray-400">(ຫ້ອງ ລາຍເຊັນ Manager ໃນ PDF ຫວ່າງ)</span></span>
                        @if ($canAcknowledge)
                            <button wire:click="acknowledgeMaintenance({{ $viewing->id }})" wire:confirm="ຢືນຢັນ ຮັບຊາບ ໃບ ບຳລຸງ ນີ້? ຊື່ + ວັນທີ ຂອງ ທ່ານ ຈະ ຂຶ້ນ ໃນ PDF." class="text-sm text-white bg-emerald-600 rounded-md px-3 py-1.5 hover:bg-emerald-700">✅ ຮັບຊາບ (Manager/Leader)</button>
                        @endif
                    @endif
                </div>

                </div>
                <div class="flex justify-end px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('viewingId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ບັນທຶກ ບຳລຸງ ນີ້?', 'subtitle' => $this->deletingRecord?->title])

    {{-- Lightbox --}}
    <div x-show="big" @click="big=null" @keydown.escape.window="big=null" class="fixed inset-0 z-[60] bg-black/80 flex items-center justify-center p-4" style="display:none">
        <img :src="big" class="max-w-full max-h-full rounded shadow-lg" alt="ຮູບ ໃຫຍ່" />
    </div>
</div>
