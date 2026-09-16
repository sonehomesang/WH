<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- frozen: ແທັບ ຫຼັກ 3 ອັນ (ໜ້າ ນີ້ ຢູ່ ໃຕ້ "ບຳລຸງຮັກສາ") + toolbar --}}
        <div class="sticky top-16 z-30 bg-gray-100">
            <div class="flex gap-1 border-b border-gray-200">
                <a href="{{ route('equipment', ['tab' => 'register']) }}" wire:navigate class="px-4 py-2 text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700">ທະບຽນ ເຄື່ອງ</a>
                <a href="{{ route('equipment', ['tab' => 'inspection']) }}" wire:navigate class="px-4 py-2 text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700">ການ ກວດກາ</a>
                <a href="{{ route('equipment', ['tab' => 'maintenance']) }}" wire:navigate class="px-4 py-2 text-sm border-b-2 border-sky-600 text-sky-700 font-medium">ບຳລຸງຮັກສາ</a>
            </div>
            <div class="flex flex-wrap items-center gap-2 py-2">
                <span class="text-xs text-gray-500 mr-1 whitespace-nowrap">ແມ່ແບບ ບຳລຸງ:</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຊື່ ແມ່ແບບ/ເຄື່ອງ…" class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm w-52" />
                <select wire:model.live="categoryFilter" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">ທຸກ ປະເພດ</option>
                    @foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                </select>
                <div class="flex-1"></div>
                <span class="text-xs text-gray-400">{{ $templates->count() }} ແມ່ແບບ</span>
                @if ($canManageDeleted)
                    <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('equipment.create')
                    <button wire:click="newTemplate" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>+ ແມ່ແບບ ໃໝ່</button>
                @endcan
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg overflow-x-hidden overflow-y-auto max-h-[calc(100vh-16rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-600 text-xs border-b border-gray-200 shadow-sm">
                    <tr>
                        <th class="text-left px-3 py-2 font-semibold">ຊື່ ແມ່ແບບ</th>
                        <th class="text-left px-3 py-2 font-semibold">ເຄື່ອງ</th>
                        <th class="text-left px-3 py-2 font-semibold">ຈຳນວນ ຂໍ້</th>
                        <th class="text-left px-3 py-2 font-semibold">ໃຊ້</th>
                        <th class="px-3 py-2 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($templates as $t)
                        <tr wire:key="mtpl-{{ $t->id }}">
                            <td class="px-3 py-2 font-medium text-gray-800">
                                {{ $t->name }}
                                @if ($showDeleted)
                                    <div class="text-[11px] font-normal text-red-600 mt-0.5">🗑 ລຶບ: {{ $t->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $t->deletedBy?->display_name ?? '—' }}@if ($t->deleted_reason) · ເຫດຜົນ: {{ $t->deleted_reason }}@endif</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">
                                @if ($t->equipment)
                                    <div class="text-gray-800">{{ $t->equipment->asset_code }} · {{ $t->equipment->name }}</div>
                                    @if ($t->category)<div class="text-xs text-gray-400">{{ $t->category }}</div>@endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ count($t->items ?? []) }}</td>
                            <td class="px-3 py-2"><span class="text-xs rounded-full font-medium px-2 py-0.5 {{ $t->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200' }}">{{ $t->is_active ? 'ເປີດ' : 'ປິດ' }}</span></td>
                            <td class="px-3 py-2 pr-5 text-right whitespace-nowrap text-gray-500">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $t->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <button wire:click="viewTemplate({{ $t->id }})" class="hover:text-sky-700 p-1" title="ເບິ່ງ">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </button>
                                    @can('equipment.edit')
                                        <button wire:click="editTemplate({{ $t->id }})" class="hover:text-gray-800 p-1" title="ແກ້ໄຂ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                        </button>
                                    @endcan
                                    @can('equipment.delete')
                                        <button wire:click="openDelete({{ $t->id }})" class="hover:text-red-600 p-1" title="ລຶບ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ແມ່ແບບ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ ແມ່ແບບ — ກົດ "+ ແມ່ແບບ ໃໝ່"' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ແມ່ແບບ ນີ້?', 'subtitle' => $this->deletingRecord?->name])

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="mtpl-modal">
            <div class="bg-white w-full md:max-w-2xl rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ ແມ່ແບບ ບຳລຸງ' : 'ແມ່ແບບ ບຳລຸງ ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ ແມ່ແບບ <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="tName" placeholder="ເຊັ່ນ ບຳລຸງ Forklift ຕາມ ຮອບ" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('tName')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ໃຊ້ ກັບ <span class="text-red-500">*</span></label>
                        <select wire:model.live="tScope" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="equipment">ເຄື່ອງ ໜຶ່ງ ໜ່ວຍ (ລຸ້ນ ສະເພາະ)</option>
                            <option value="category">ໝົດ ໃນ ປະເພດ (ທຸກ ເຄື່ອງ ປະເພດ ດຽວກັນ)</option>
                            <option value="general">ທົ່ວໄປ (ທຸກ ເຄື່ອງ)</option>
                        </select>
                    </div>
                    @if ($tScope === 'equipment')
                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-600 mb-1">ເລືອກ ເຄື່ອງ <span class="text-red-500">*</span></label>
                            <select wire:model.live="tEquipmentId" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">— ເລືອກ ເຄື່ອງ ຈາກ ທະບຽນ —</option>
                                @foreach ($equipmentOptions as $eq)
                                    <option value="{{ $eq->id }}">{{ $eq->asset_code }} · {{ $eq->name }}</option>
                                @endforeach
                            </select>
                            @error('tEquipmentId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            <p class="text-xs text-gray-400 mt-1">ປະເພດ: {{ $selectedCategory ?: '—' }} (ດຶງ ຈາກ ເຄື່ອງ)</p>
                        </div>
                    @elseif ($tScope === 'category')
                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-600 mb-1">ປະເພດ ເຄື່ອງ <span class="text-red-500">*</span></label>
                            <select wire:model="tCategory" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">— ເລືອກ ປະເພດ —</option>
                                @foreach ($categories as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                            </select>
                            @error('tCategory')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            <p class="text-xs text-gray-400 mt-1">ໃຊ້ ໄດ້ ກັບ ທຸກ ເຄື່ອງ ໃນ ປະເພດ ນີ້.</p>
                        </div>
                    @else
                        <div class="md:col-span-2 text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-md px-3 py-2">ໃຊ້ ໄດ້ ກັບ ທຸກ ເຄື່ອງ (ບໍ່ ຜູກ ເຄື່ອງ/ປະເພດ).</div>
                    @endif
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model="tActive" class="rounded border-gray-300 text-sky-600"> ເປີດ ໃຊ້
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ວິທີ / ໝາຍເຫດ ບຳລຸງ</label>
                        <textarea wire:model="tMethod" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm text-gray-600">ລາຍການ ເຊັກລິສ ບຳລຸງ</label>
                        <span class="text-[11px] text-gray-400">ກົດ ຮອບ ເພື່ອ ສະຫຼັບ: — → <b class="text-sky-700">C ກວດ</b> → <b class="text-amber-700">X ປ່ຽນ</b></span>
                    </div>
                    @php
                        $tGroupLabels = \App\Models\MaintenanceTemplate::GROUPS;
                        $tGroupTone = [
                            'engine' => 'bg-amber-50 text-amber-800',
                            'powertrain' => 'bg-violet-50 text-violet-800',
                            'steering' => 'bg-sky-50 text-sky-800',
                            'mast' => 'bg-emerald-50 text-emerald-800',
                            'hydraulic' => 'bg-teal-50 text-teal-800',
                            'electrical' => 'bg-rose-50 text-rose-800',
                            'other' => 'bg-gray-100 text-gray-700',
                        ];
                        // ຈັດ index ຂໍ້ ຕາມ ໝວດ ປັດຈຸບັນ (ຮຽງ ຕາມ ລຳດັບ GROUPS; ຂໍ້ ໃໝ່/ບໍ່ ຮັບຮອງ → ອື່ນໆ)
                        $tByGroup = [];
                        foreach ($tItems as $i => $it) {
                            $g = $it['group'] ?? 'other';
                            if (! array_key_exists($g, $tGroupLabels)) { $g = 'other'; }
                            $tByGroup[$g][] = $i;
                        }
                    @endphp
                    <div class="space-y-2">
                        @foreach ($tGroupLabels as $gk => $gl)
                            @if (! empty($tByGroup[$gk]))
                                <div wire:key="tgrp-{{ $gk }}">
                                    <div class="flex items-center gap-2 px-2 py-1 rounded-t-md text-xs {{ $tGroupTone[$gk] }}">
                                        <span class="font-medium">{{ $gl }}</span>
                                        <span class="opacity-70">· {{ count($tByGroup[$gk]) }} ຂໍ້</span>
                                    </div>
                                    <div class="space-y-1.5 border border-t-0 border-gray-100 rounded-b-md p-1.5">
                                        @foreach ($tByGroup[$gk] as $i)
                                            @php $item = $tItems[$i]; @endphp
                                            <div wire:key="mtit-{{ $i }}" class="border border-gray-100 rounded-md p-1.5">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs text-gray-400 w-5 text-right shrink-0">{{ $i + 1 }}.</span>
                                                    <input type="text" wire:model="tItems.{{ $i }}.label" placeholder="ວຽກ ບຳລຸງ…" class="flex-1 min-w-0 rounded-md border-gray-300 text-xs py-1" />
                                                    <input type="text" wire:model="tItems.{{ $i }}.remark" placeholder="ໝາຍເຫດ/ອາໄຫຼ່…" class="w-28 shrink-0 rounded-md border-gray-200 text-xs py-1 text-gray-500" />
                                                    <button type="button" wire:click="removeChecklistItem({{ $i }})" class="text-red-500 px-1 shrink-0" title="ລຶບ ຂໍ້">×</button>
                                                </div>
                                                <div class="flex items-center gap-1.5 flex-wrap mt-1 pl-6">
                                                    <span class="text-[11px] text-gray-400 mr-0.5">ໝວດ:</span>
                                                    <select wire:model="tItems.{{ $i }}.group" class="rounded-md border-gray-200 text-[11px] py-1 text-gray-600">
                                                        @foreach ($tGroupLabels as $ggk => $ggl)<option value="{{ $ggk }}">{{ $ggl }}</option>@endforeach
                                                    </select>
                                                    <span class="text-[11px] text-gray-400 mr-0.5">ໃຊ້ກັບ:</span>
                                                    <select wire:model="tItems.{{ $i }}.applies" class="rounded-md border-gray-200 text-[11px] py-1 text-gray-600 mr-1" title="ໃຊ້ ກັບ ປະເພດ ລົດ ໃດ">
                                                        <option value="both">ທັງ 2</option>
                                                        <option value="engine">ນ້ຳມັນ</option>
                                                        <option value="ev">ໄຟຟ້າ</option>
                                                    </select>
                                                    <span class="text-[11px] text-gray-400 mr-0.5">ຮອບ:</span>
                                                    @foreach (\App\Models\MaintenanceTemplate::FREQ_LABELS as $fk => $fl)
                                                        @php $st = $item['cycles'][$fk] ?? ''; @endphp
                                                        <button type="button" wire:click="bumpCycle({{ $i }}, '{{ $fk }}')"
                                                                class="inline-flex flex-col items-center leading-none px-2 py-1 rounded border text-[10px] transition-colors
                                                                {{ $st === 'C' ? 'bg-sky-50 border-sky-300 text-sky-700' : ($st === 'X' ? 'bg-amber-50 border-amber-300 text-amber-700' : 'bg-white border-gray-200 text-gray-400 hover:border-gray-300') }}"
                                                                title="{{ $fl }} · {{ \App\Models\MaintenanceTemplate::FREQ_HOURS[$fk] }}">
                                                            <span>{{ $fl }}</span>
                                                            <span class="font-bold text-xs mt-0.5">{{ $st ?: '—' }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-1">ໃສ່ <b>ໝົດທຸກ ລາຍການ</b> ແລ້ວ ໝາຍ ແຕ່ລະ ຮອບ ວ່າ <b class="text-sky-700">C</b>=ກວດ ຫຼື <b class="text-amber-700">X</b>=ປ່ຽນ (ວ່າງ = ບໍ່ ເຮັດ ຮອບ ນັ້ນ). ຕອນ ບຳລຸງ ຈິງ ຈະ ຄັດ ລິສ ຕາມ ຮອບ ທີ່ ເລືອກ ພ້ອມ ສະແດງ C/X.</p>
                    <button type="button" wire:click="addChecklistItem" class="mt-2 text-sm text-sky-600">+ ເພີ່ມ ຂໍ້</button>
                </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] shadow-sm hover:bg-sky-700">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- View modal (read-only) --}}
    @if ($viewing)
        @php
            $vItems = $viewing->normalizedItems();
            $vFreqs = \App\Models\MaintenanceTemplate::FREQ_LABELS;
            $vHours = \App\Models\MaintenanceTemplate::FREQ_HOURS;
        @endphp
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="mtpl-view">
            <div class="bg-white w-full md:max-w-2xl rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-start gap-3 border-b border-gray-200 bg-gradient-to-b from-emerald-200 to-emerald-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🛠️</span>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">{{ $viewing->name }}</h3>
                        <div class="text-sm text-gray-500">
                            {{ $viewing->equipment ? $viewing->equipment->asset_code.' · '.$viewing->equipment->name : '— ຍັງ ບໍ່ ຜູກ ເຄື່ອງ —' }}
                            @if ($viewing->category) · {{ $viewing->category }}@endif
                            · <span class="text-xs rounded-full font-medium px-1.5 py-0.5 {{ $viewing->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200' }}">{{ $viewing->is_active ? 'ເປີດ' : 'ປິດ' }}</span>
                        </div>
                    </div>
                    <div class="ml-auto flex items-center gap-1 shrink-0">
                        @can('equipment.edit')
                            <button wire:click="editTemplate({{ $viewing->id }})" class="text-sm text-sky-700 border border-sky-200 rounded-md px-2.5 py-1.5 hover:bg-sky-50">ແກ້ໄຂ</button>
                        @endcan
                        <button wire:click="$set('viewingId', null)" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">

                @if ($viewing->method)
                    <div class="text-xs text-gray-600 bg-gray-50 border border-gray-100 rounded-md px-3 py-2">{{ $viewing->method }}</div>
                @endif

                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>{{ count($vItems) }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }}</span>
                    <span><b class="text-sky-700">C</b>=ກວດ · <b class="text-amber-700">X</b>=ປ່ຽນ · —=ບໍ່ ເຮັດ</span>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-md">
                    <table class="w-full text-xs" style="min-width:560px">
                        <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                            <tr>
                                <th class="px-2 py-1.5 text-left font-semibold w-6">#</th>
                                <th class="px-2 py-1.5 text-left font-semibold">ລາຍການ</th>
                                @foreach ($vFreqs as $fk => $fl)
                                    <th class="px-1 py-1.5 text-center font-semibold w-14">{{ $fl }}<span class="block text-[9px] font-normal text-gray-400 font-mono">{{ $vHours[$fk] }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vItems as $i => $c)
                                <tr wire:key="vw-{{ $i }}" class="border-b border-gray-50 last:border-0">
                                    <td class="px-2 py-1 text-gray-400 align-top">{{ $i + 1 }}</td>
                                    <td class="px-2 py-1 align-top">
                                        <div class="text-gray-700 leading-tight">{{ $c['label'] }}</div>
                                        @if (! empty($c['remark']))<div class="text-[11px] text-gray-400 font-mono">{{ $c['remark'] }}</div>@endif
                                    </td>
                                    @foreach ($vFreqs as $fk => $fl)
                                        @php $act = $c['cycles'][$fk] ?? ''; @endphp
                                        <td class="px-1 py-1 text-center align-top">
                                            @if ($act)
                                                <span class="text-[10px] font-bold rounded-full px-1 py-0.5 {{ $act === 'X' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' }}">{{ $act }}</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">ຍັງ ບໍ່ ມີ ລາຍການ</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                </div>
                <div class="flex justify-end px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('viewingId', null)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                </div>
            </div>
        </div>
    @endif
</div>
