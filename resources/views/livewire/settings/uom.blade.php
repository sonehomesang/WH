@php
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgPower = 'M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9';
    $svgTrash = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
            <div class="flex items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ…" class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                @if ($canManageDeleted)
                    <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 bg-white hover:bg-gray-50' }}">
                        {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('units.create')<button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>+ Add</button>@endcan
            </div>
        </div>

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1">ບັນທຶກແລ້ວ ✓</div>

        <div class="bg-white border border-gray-100 rounded-lg overflow-hidden md:max-w-xl">
            <ul class="text-sm">
                @forelse ($items as $m)
                    <li wire:key="uom-{{ $m->id }}" class="flex items-center justify-between px-4 py-2 border-b border-gray-100 min-h-[44px]">
                        <span class="text-gray-700 {{ $m->is_active ? '' : 'opacity-50' }}">
                            {{ $m->name }}@if ($m->name_en)<span class="text-xs text-gray-400"> · {{ $m->name_en }}</span>@endif
                            @if ($showDeleted)
                                <span class="block text-[11px] text-red-600">🗑 {{ $m->deleted_at?->format('d/m/Y H:i') }} · {{ $m->deletedBy?->display_name ?? '—' }}@if ($m->deleted_reason) · {{ $m->deleted_reason }}@endif</span>
                            @endif
                        </span>
                        <span class="flex items-center gap-1">
                            @if ($showDeleted)
                                <button wire:click="restore({{ $m->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                            @else
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $m->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200' }}">{{ $m->is_active ? 'active' : 'inactive' }}</span>
                                @canany(['units.activate', 'units.deactivate'])<button wire:click="toggle({{ $m->id }})" wire:confirm="{{ $m->is_active ? 'ປິດໃຊ້ ໜ່ວຍວັດ ນີ້?' : 'ເປີດໃຊ້ ໜ່ວຍວັດ ນີ້?' }}" class="p-1 {{ $m->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $m->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                @can('units.edit')<button wire:click="editItem({{ $m->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                @can('units.delete')<button wire:click="openDelete({{ $m->id }})" class="text-gray-400 hover:text-red-600 p-1" aria-label="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                            @endif
                        </span>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ໜ່ວຍວັດ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ ໜ່ວຍວັດ — ກົດ + Add' }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ໜ່ວຍວັດ ນີ້?', 'subtitle' => $this->deletingRecord?->name])

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="uom-modal">
            <div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">📏</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ' : 'ເພີ່ມ' }} ໜ່ວຍວັດ</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                @include('partials._form-errors')
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຊື່ (Name) <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" placeholder="pcs, kg, m, ກ່ອງ…" class="w-full rounded-md border-gray-300 text-sm" />
                    @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຊື່ອັງກິດ (name_en)</label>
                    <input type="text" wire:model="name_en" class="w-full rounded-md border-gray-300 text-sm" />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> Active</label>
                @if ($editingId)
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ເຫດຜົນ ການ ປ່ຽນແປງ <span class="text-red-500">*</span></label>
                        <textarea wire:model="changeReason" rows="2" placeholder="ເປັນຫຍັງຈຶ່ງແກ້ໄຂ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                        @error('changeReason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
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
