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

        <div class="flex flex-col md:flex-row gap-3 items-start">
            {{-- Locations --}}
            <div class="w-full md:flex-1 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-3 py-2.5 border-b border-gray-100">
                    <span class="font-medium text-sm text-gray-700">Locations</span>
                    <span class="flex items-center gap-1">
                        @if ($this->canManageDeletedType('location'))
                            <button wire:click="toggleDeletedLog('location')" class="text-xs border rounded-md px-2 py-1 min-h-[32px] {{ $showDelLoc ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-500 border-gray-300 hover:bg-gray-50' }}" title="ບັນທຶກ ການ ລຶບ">{{ $showDelLoc ? '← ປົກກະຕິ' : '🗑' }}</button>
                        @endif
                        @can('locations.create')<button wire:click="newLocation" @if ($showDelLoc) style="display:none" @endif class="text-xs border border-gray-300 rounded-md px-2 py-1 min-h-[32px] hover:bg-gray-50">+ Add</button>@endcan
                    </span>
                </div>
                <ul class="text-sm">
                    @forelse ($locations as $loc)
                        <li wire:key="loc-{{ $loc->id }}" class="flex items-center justify-between border-b border-gray-100 {{ ! $showDelLoc && $selectedLocationId === $loc->id ? 'bg-sky-50' : 'hover:bg-gray-50' }}">
                            @if ($showDelLoc)
                                <span class="flex-1 px-3 py-2 text-gray-600">{{ $loc->name }}
                                    <span class="block text-[11px] text-red-600">🗑 {{ $loc->deleted_at?->format('d/m/Y H:i') }} · {{ $loc->deletedBy?->display_name ?? '—' }}@if ($loc->deleted_reason) · {{ $loc->deleted_reason }}@endif</span>
                                </span>
                                <button wire:click="restoreRecord('location', {{ $loc->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50 mr-1">↩ ກູ້ຄືນ</button>
                            @else
                                <button wire:click="selectLocation({{ $loc->id }})" class="flex-1 text-left px-3 py-2 min-h-[40px] {{ $selectedLocationId === $loc->id ? 'text-sky-700 font-medium' : 'text-gray-700' }} {{ $loc->is_active ? '' : 'opacity-50' }}">{{ $loc->name }}@unless ($loc->is_active)<span class="text-xs text-gray-400"> (off)</span>@endunless</button>
                                <span class="flex items-center gap-0.5 pr-1"><span class="text-xs text-gray-400 mr-0.5">{{ $loc->buildings_count }}</span>
                                    @canany(['locations.activate', 'locations.deactivate'])<button wire:click="toggleLocation({{ $loc->id }})" wire:confirm="{{ $loc->is_active ? 'ປິດໃຊ້ ບ່ອນຕັ້ງ ນີ້?' : 'ເປີດໃຊ້ ບ່ອນຕັ້ງ ນີ້?' }}" class="p-1 {{ $loc->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $loc->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                    @can('locations.edit')<button wire:click="editLocation({{ $loc->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('locations.delete')<button wire:click="openDelete('location', {{ $loc->id }})" class="text-gray-400 hover:text-red-600 p-1" aria-label="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                                </span>
                            @endif
                        </li>
                    @empty
                        <li class="px-3 py-6 text-center text-gray-400 text-sm">{{ $showDelLoc ? 'ບໍ່ ມີ location ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ location' }}</li>
                    @endforelse
                </ul>
            </div>

            {{-- Buildings --}}
            <div class="w-full md:flex-1 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-3 py-2.5 border-b border-gray-100">
                    <span class="font-medium text-sm text-gray-700">Buildings @if ($selectedLocation)— {{ $selectedLocation->name }}@endif</span>
                    <span class="flex items-center gap-2">
                        @if ($this->canManageDeletedType('building'))
                            <button wire:click="toggleDeletedLog('building')" class="text-xs border rounded-md px-2 py-1 min-h-[32px] {{ $showDelBld ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-500 border-gray-300 hover:bg-gray-50' }}" title="ບັນທຶກ ການ ລຶບ">{{ $showDelBld ? '← ປົກກະຕິ' : '🗑' }}</button>
                        @endif
                        @canany(['buildings.create', 'buildings.edit'])<button wire:click="openTypesManager" @if ($showDelBld) style="display:none" @endif class="text-xs text-gray-500 hover:text-gray-700" title="Manage building types">⚙ Types</button>@endcanany
                        @can('buildings.create')<button wire:click="newBuilding" @if ($showDelBld) style="display:none" @endif @disabled(! $selectedLocationId) class="text-xs text-white bg-sky-600 rounded-md px-2 py-1 min-h-[32px] hover:bg-sky-700 disabled:opacity-40">+ Add</button>@endcan
                    </span>
                </div>
                <ul class="text-sm">
                    @forelse ($buildings as $b)
                        <li wire:key="bld-{{ $b->id }}" class="flex items-center justify-between border-b border-gray-100 {{ ! $showDelBld && $selectedBuildingId === $b->id ? 'bg-sky-50' : 'hover:bg-gray-50' }}">
                            @if ($showDelBld)
                                <span class="flex-1 px-3 py-2 text-gray-600">{{ $b->name }}
                                    <span class="block text-[11px] text-red-600">🗑 {{ $b->deleted_at?->format('d/m/Y H:i') }} · {{ $b->deletedBy?->display_name ?? '—' }}@if ($b->deleted_reason) · {{ $b->deleted_reason }}@endif</span>
                                </span>
                                <button wire:click="restoreRecord('building', {{ $b->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50 mr-1">↩ ກູ້ຄືນ</button>
                            @else
                                <button wire:click="selectBuilding({{ $b->id }})" class="flex-1 text-left px-3 py-2 min-h-[40px] {{ $selectedBuildingId === $b->id ? 'text-sky-700 font-medium' : 'text-gray-700' }} {{ $b->is_active ? '' : 'opacity-50' }}">{{ $b->name }} <span class="text-xs text-gray-400">@if($b->code)· {{ $b->code }} @endif· {{ $b->buildingType?->name ?? '—' }}</span></button>
                                <span class="flex items-center gap-0.5 pr-1"><span class="text-xs text-gray-400 mr-0.5">{{ $b->rooms_count }}</span>
                                    @canany(['buildings.activate', 'buildings.deactivate'])<button wire:click="toggleBuilding({{ $b->id }})" wire:confirm="{{ $b->is_active ? 'ປິດໃຊ້ ອາຄານ ນີ້?' : 'ເປີດໃຊ້ ອາຄານ ນີ້?' }}" class="p-1 {{ $b->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $b->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                    @can('buildings.edit')<button wire:click="editBuilding({{ $b->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('buildings.delete')<button wire:click="openDelete('building', {{ $b->id }})" class="text-gray-400 hover:text-red-600 p-1" aria-label="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                                </span>
                            @endif
                        </li>
                    @empty
                        <li class="px-3 py-6 text-center text-gray-400 text-sm">{{ $showDelBld ? 'ບໍ່ ມີ building ທີ່ ຖືກ ລຶບ' : ($selectedLocationId ? 'ຍັງບໍ່ມີ building' : 'ເລືອກ location') }}</li>
                    @endforelse
                </ul>
            </div>

            {{-- Rooms --}}
            <div class="w-full md:flex-1 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-3 py-2.5 border-b border-gray-100">
                    <span class="font-medium text-sm text-gray-700">Rooms @if ($selectedBuilding)— {{ $selectedBuilding->name }}@endif</span>
                    <span class="flex items-center gap-1">
                        @if ($this->canManageDeletedType('room'))
                            <button wire:click="toggleDeletedLog('room')" class="text-xs border rounded-md px-2 py-1 min-h-[32px] {{ $showDelRoom ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-500 border-gray-300 hover:bg-gray-50' }}" title="ບັນທຶກ ການ ລຶບ">{{ $showDelRoom ? '← ປົກກະຕິ' : '🗑' }}</button>
                        @endif
                        @can('rooms.create')<button wire:click="newRoom" @if ($showDelRoom) style="display:none" @endif @disabled(! $selectedBuildingId) class="text-xs text-white bg-sky-600 rounded-md px-2 py-1 min-h-[32px] hover:bg-sky-700 disabled:opacity-40">+ Add</button>@endcan
                    </span>
                </div>
                <ul class="text-sm">
                    @forelse ($rooms as $r)
                        <li wire:key="rm-{{ $r->id }}" class="flex items-center justify-between px-3 py-2 border-b border-gray-100 min-h-[40px]">
                            @if ($showDelRoom)
                                <span class="text-gray-600">{{ $r->name }}
                                    <span class="block text-[11px] text-red-600">🗑 {{ $r->deleted_at?->format('d/m/Y H:i') }} · {{ $r->deletedBy?->display_name ?? '—' }}@if ($r->deleted_reason) · {{ $r->deleted_reason }}@endif</span>
                                </span>
                                <button wire:click="restoreRecord('room', {{ $r->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                            @else
                                <span class="text-gray-700 {{ $r->is_active ? '' : 'opacity-50' }}">{{ $r->name }} @if($r->function)<span class="text-xs text-gray-400">· {{ $r->function }}</span>@endif</span>
                                <span class="flex items-center gap-0.5">
                                    @canany(['rooms.activate', 'rooms.deactivate'])<button wire:click="toggleRoom({{ $r->id }})" wire:confirm="{{ $r->is_active ? 'ປິດໃຊ້ ຫ້ອງ ນີ້?' : 'ເປີດໃຊ້ ຫ້ອງ ນີ້?' }}" class="p-1 {{ $r->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $r->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                    @can('rooms.edit')<button wire:click="editRoom({{ $r->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('rooms.delete')<button wire:click="openDelete('room', {{ $r->id }})" class="text-gray-400 hover:text-red-600 p-1" aria-label="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                                </span>
                            @endif
                        </li>
                    @empty
                        <li class="px-3 py-6 text-center text-gray-400 text-sm">{{ $showDelRoom ? 'ບໍ່ ມີ room ທີ່ ຖືກ ລຶບ' : ($selectedBuildingId ? 'ຍັງບໍ່ມີ room' : 'ເລືອກ building') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait MultiSoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ລາຍການ ນີ້?', 'subtitle' => $this->deletingRecord?->name])

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="fac-modal">
            <div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🏗️</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ' : 'ເພີ່ມ' }} {{ ucfirst($type) }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>

                <div class="p-5 space-y-3 overflow-y-auto">
                @include('partials._form-errors')

                @if ($type === 'building')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Location <span class="text-red-500">*</span></label>
                        <select wire:model="parentId" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach ($locationOptions as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                        </select>
                        @error('parentId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @elseif ($type === 'room')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Building <span class="text-red-500">*</span></label>
                        <select wire:model="parentId" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach ($buildingOptions as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                        </select>
                        @error('parentId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຊື່ (Name) <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 text-sm" />
                    @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                @if ($type !== 'room')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ອັງກິດ (name_en)</label>
                        <input type="text" wire:model="name_en" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                @endif

                @if ($type === 'location')
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ທີ່ຢູ່ (address)</label>
                        <input type="text" wire:model="address" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                @elseif ($type === 'building')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Code</label>
                            <input type="text" wire:model="code" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Type <span class="text-red-500">*</span></label>
                            <select wire:model="buildingTypeId" class="w-full rounded-md border-gray-300 text-sm">
                                <option value="">—</option>
                                @foreach ($buildingTypes as $bt)<option value="{{ $bt->id }}">{{ $bt->name }}</option>@endforeach
                            </select>
                            @error('buildingTypeId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @else
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Function</label>
                        <input type="text" wire:model="function" placeholder="storage, office, tools…" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                @endif

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> Active
                </label>
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

    {{-- Building types manager --}}
    @if ($showTypesModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="types-modal">
            <div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🏢</span>
                    <h3 class="text-base font-semibold text-gray-800">Building types</h3>
                    <button wire:click="$set('showTypesModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                @include('partials._form-errors')
                @error('typeRow')<div class="text-xs text-red-700 bg-red-50 border border-red-200 rounded px-2 py-1">{{ $message }}</div>@enderror
                <div class="flex gap-2">
                    <input type="text" wire:model="typeName" placeholder="{{ $typeEditingId ? 'ແກ້ຊື່ type' : 'ຊື່ type ໃໝ່' }}" class="flex-1 rounded-md border-gray-300 text-sm" />
                    <button wire:click="saveType" class="text-sm text-white bg-sky-600 rounded-md px-3 min-h-[40px] hover:bg-sky-700">{{ $typeEditingId ? 'Update' : 'Add' }}</button>
                </div>
                @error('typeName')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <ul class="text-sm border-t border-gray-100">
                    @foreach ($allTypes as $t)
                        <li wire:key="bt-{{ $t->id }}" class="flex items-center justify-between py-2 border-b border-gray-100">
                            <span class="{{ $t->is_active ? 'text-gray-700' : 'text-gray-400' }}">{{ $t->name }}@unless ($t->is_active) (off)@endunless</span>
                            <span class="flex items-center gap-1">
                                @canany(['buildings.activate', 'buildings.deactivate'])<button wire:click="toggleType({{ $t->id }})" class="p-1 {{ $t->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $t->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                @can('buildings.edit')<button wire:click="editType({{ $t->id }})" class="text-gray-400 hover:text-gray-700 p-1" aria-label="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                @can('buildings.delete')<button wire:click="deleteType({{ $t->id }})" wire:confirm="ລຶບ type ນີ້?" class="text-gray-400 hover:text-red-600 p-1" aria-label="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                            </span>
                        </li>
                    @endforeach
                </ul>
                </div>
                <div class="flex justify-end px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showTypesModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button></div>
            </div>
        </div>
    @endif
</div>
