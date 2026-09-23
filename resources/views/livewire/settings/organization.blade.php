@php
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgPower = 'M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9';
    $svgTrash = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div class="flex items-start justify-between">
            <div class="space-y-2">
                @include('settings._org-tabs')
            </div>
            <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
                 class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1">ບັນທຶກແລ້ວ ✓</div>
        </div>

        @error('row')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror

        <div class="flex flex-col md:flex-row gap-4 items-start">
            {{-- Units --}}
            <div class="w-full md:w-1/3 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <span class="font-medium text-sm text-gray-700">Units (ໜ່ວຍງານ)</span>
                    <span class="flex items-center gap-1">
                        @if ($this->canManageDeletedType('unit'))
                            <button wire:click="toggleDeletedLog('unit')" class="text-xs border rounded-md px-2 py-1 min-h-[32px] {{ $showDelUnits ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-500 border-gray-300 hover:bg-gray-50' }}" title="ບັນທຶກ ການ ລຶບ">{{ $showDelUnits ? '← ປົກກະຕິ' : '🗑' }}</button>
                        @endif
                        @can('units.create')
                            <button wire:click="newUnit" @if ($showDelUnits) style="display:none" @endif class="inline-flex items-center gap-1 text-xs text-gray-600 border border-gray-300 rounded-md px-2 py-1 min-h-[32px] hover:bg-gray-50">+ Add</button>
                        @endcan
                    </span>
                </div>
                <ul>
                    @forelse ($units as $unit)
                        <li wire:key="unit-{{ $unit->id }}" class="flex items-center justify-between px-2 py-1 border-b border-gray-100 {{ ! $showDelUnits && $selectedUnitId === $unit->id ? 'bg-sky-50' : 'hover:bg-gray-50' }}">
                            @if ($showDelUnits)
                                <span class="flex-1 px-2 py-2 text-sm text-gray-600">
                                    {{ $unit->name }}
                                    <span class="block text-[11px] text-red-600">🗑 {{ $unit->deleted_at?->format('d/m/Y H:i') }} · {{ $unit->deletedBy?->display_name ?? '—' }}@if ($unit->deleted_reason) · {{ $unit->deleted_reason }}@endif</span>
                                </span>
                                <button wire:click="restoreRecord('unit', {{ $unit->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50 mr-1">↩ ກູ້ຄືນ</button>
                            @else
                                <button wire:click="selectUnit({{ $unit->id }})" class="flex-1 text-left px-2 py-2 min-h-[40px] text-sm {{ $selectedUnitId === $unit->id ? 'text-sky-700 font-medium' : 'text-gray-700' }} {{ $unit->is_active ? '' : 'opacity-50' }}">
                                    {{ $unit->name }}
                                    @unless ($unit->is_active)<span class="text-xs text-gray-400">(inactive)</span>@endunless
                                </button>
                                <span class="flex items-center gap-0.5 pr-1">
                                    <span class="text-xs text-gray-400 mr-1">{{ $unit->departments_count }}</span>
                                    @canany(['units.activate', 'units.deactivate'])
                                        <button wire:click="toggleUnit({{ $unit->id }})" wire:confirm="{{ $unit->is_active ? 'ປິດໃຊ້ ໜ່ວຍງານ ນີ້?' : 'ເປີດໃຊ້ ໜ່ວຍງານ ນີ້?' }}" class="p-1 {{ $unit->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $unit->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>
                                    @endcanany
                                    @can('units.edit')<button wire:click="editUnit({{ $unit->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('units.delete')<button wire:click="openDelete('unit', {{ $unit->id }})" class="text-gray-400 hover:text-red-600 p-1" aria-label="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                                </span>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-6 text-sm text-gray-400 text-center">{{ $showDelUnits ? 'ບໍ່ ມີ unit ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ unit' }}</li>
                    @endforelse
                </ul>
            </div>

            {{-- Departments --}}
            <div class="w-full md:flex-1 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <span class="font-medium text-sm text-gray-700">Departments @if ($selectedUnit)— {{ $selectedUnit->name }}@endif</span>
                    <span class="flex items-center gap-1">
                        @if ($this->canManageDeletedType('department'))
                            <button wire:click="toggleDeletedLog('department')" class="text-xs border rounded-md px-2 py-1 min-h-[32px] {{ $showDelDepts ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-500 border-gray-300 hover:bg-gray-50' }}" title="ບັນທຶກ ການ ລຶບ">{{ $showDelDepts ? '← ປົກກະຕິ' : '🗑' }}</button>
                        @endif
                        @can('departments.create')
                            <button wire:click="newDepartment" @if ($showDelDepts) style="display:none" @endif @disabled(! $selectedUnitId) class="inline-flex items-center gap-1 text-xs text-white bg-sky-600 rounded-md px-2 py-1 min-h-[32px] hover:bg-sky-700 disabled:opacity-40">+ Add</button>
                        @endcan
                    </span>
                </div>
                <ul>
                    @forelse ($departments as $d)
                        <li wire:key="dept-{{ $d->id }}" class="flex items-center justify-between px-4 py-2 border-b border-gray-100 min-h-[44px]">
                            @if ($showDelDepts)
                                <span class="text-sm text-gray-600">
                                    {{ $d->name }}
                                    <span class="block text-[11px] text-red-600">🗑 {{ $d->deleted_at?->format('d/m/Y H:i') }} · {{ $d->deletedBy?->display_name ?? '—' }}@if ($d->deleted_reason) · {{ $d->deleted_reason }}@endif</span>
                                </span>
                                <button wire:click="restoreRecord('department', {{ $d->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                            @else
                                <span class="text-sm text-gray-700 {{ $d->is_active ? '' : 'opacity-50' }}">{{ $d->name }}@if ($d->description)<span class="text-xs text-gray-400"> · {{ $d->description }}</span>@endif</span>
                                <span class="flex items-center gap-1">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $d->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200' }}">{{ $d->is_active ? 'active' : 'inactive' }}</span>
                                    @canany(['departments.activate', 'departments.deactivate'])
                                        <button wire:click="toggleDepartment({{ $d->id }})" wire:confirm="{{ $d->is_active ? 'ປິດໃຊ້ ພະແນກ ນີ້?' : 'ເປີດໃຊ້ ພະແນກ ນີ້?' }}" class="p-1 {{ $d->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $d->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>
                                    @endcanany
                                    @can('departments.edit')<button wire:click="editDepartment({{ $d->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('departments.delete')<button wire:click="openDelete('department', {{ $d->id }})" class="text-gray-400 hover:text-red-600 p-1" aria-label="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                                </span>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-6 text-sm text-gray-400 text-center">{{ $showDelDepts ? 'ບໍ່ ມີ department ທີ່ ຖືກ ລຶບ' : ($selectedUnitId ? 'ຍັງບໍ່ມີ department — ກົດ + Add' : 'ເລືອກ unit ກ່ອນ') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait MultiSoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ລາຍການ ນີ້?', 'subtitle' => $this->deletingRecord?->name])

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="org-modal">
            <div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🏢</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ' : 'ເພີ່ມ' }} {{ $type === 'unit' ? 'Unit' : 'Department' }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    @include('partials._form-errors')
                    @if ($type === 'department')
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ໜ່ວຍງານ (Org Unit) <span class="text-red-500">*</span></label>
                            <select wire:model="unitId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                                @foreach ($unitOptions as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">1 Org Unit ມີໄດ້ຫຼາຍ Department · department ຢູ່ໃຕ້ Org Unit</p>
                            @error('unitId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ (Name) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ອັງກິດ (name_en)</label>
                        <input type="text" wire:model="name_en" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ລາຍລະອຽດ</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> Active
                    </label>
                    @if ($editingId)
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">ເຫດຜົນ ການ ປ່ຽນແປງ <span class="text-red-500">*</span></label>
                            <textarea wire:model="changeReason" rows="2" placeholder="ເປັນຫຍັງຈຶ່ງແກ້ໄຂ…" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"></textarea>
                            @error('changeReason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @else
                        <p class="text-xs text-gray-400">slug ຈະສ້າງອັດຕະໂນມັດຈາກຊື່</p>
                    @endif
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-700 shadow-sm">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
