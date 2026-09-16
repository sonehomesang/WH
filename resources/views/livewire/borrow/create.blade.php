<div class="pb-10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('borrow')" back-label="ລາຍການ ຢືມ" />

        @include('partials._form-errors')

        {{-- ① type --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span><h3 class="text-sm font-semibold text-gray-700">ປະເພດ ການ ຢືມ</h3></div>
            <div class="p-4 space-y-3">
                <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm flex-wrap">
                    @foreach (['new_inventory' => 'ສາງ / Inventory', 'tools_equipment' => 'ເຄື່ອງມື / Tools', 'others' => 'ອື່ນໆ / Others'] as $v => $l)
                        <button type="button" wire:click="$set('borrow_type', '{{ $v }}')" class="px-4 py-2 transition {{ $borrow_type === $v ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ ! $loop->first ? 'border-l border-gray-200' : '' }}">{{ $l }}</button>
                    @endforeach
                </div>
                @if ($borrow_type === 'others')
                    <input type="text" wire:model="others_detail" placeholder="ລາຍລະອຽດ (ບັງຄັບ)" class="w-full rounded-lg border-gray-300 text-sm" />
                    @error('others_detail')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                @endif
            </div>
        </div>

        {{-- ② items --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span><h3 class="text-sm font-semibold text-gray-700">ລາຍການ ເຄື່ອງ</h3></div>
            <div class="p-4 space-y-2">
                @if ($borrow_type === 'new_inventory')
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="itemSearch" placeholder="ຄົ້ນຫາ inventory (ຊື່/Material No.)…" class="w-full pl-8 rounded-lg border-gray-300 text-sm" />
                        @if ($invResults->isNotEmpty())
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-xl max-h-56 overflow-y-auto">
                                @foreach ($invResults as $inv)
                                    <button type="button" wire:click="addInventoryItem({{ $inv->id }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-indigo-50">{{ $inv->name }} <span class="font-mono text-xs text-gray-400">{{ $inv->slug }}</span></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @elseif ($borrow_type === 'tools_equipment')
                    <div class="relative">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="equipmentSearch" placeholder="ຄົ້ນຫາ ທະບຽນ ເຄື່ອງ (ຊື່/ລະຫັດ/ຊັບສິນ)…" class="w-full pl-8 rounded-lg border-gray-300 text-sm" />
                        @if ($eqResults->isNotEmpty())
                            <div class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-xl max-h-56 overflow-y-auto">
                                @foreach ($eqResults as $eq)
                                    <button type="button" wire:click="addEquipmentItem({{ $eq->id }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-indigo-50">{{ $eq->name }} <span class="font-mono text-xs text-gray-400">{{ $eq->asset_code }}@if ($eq->fixed_asset_no) · {{ $eq->fixed_asset_no }}@endif</span></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <button type="button" wire:click="addFreeItem" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">+ ເພີ່ມແຖວ</button>
                @endif

                <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 text-sm overflow-hidden">
                    @forelse ($items as $i => $it)
                        <div wire:key="it-{{ $i }}" class="flex items-center gap-2 p-2.5 hover:bg-gray-50/60">
                            @if (! empty($it['code']))<span class="font-mono text-xs bg-gray-50 border border-gray-200 rounded px-1.5 py-0.5 text-gray-500 shrink-0 truncate max-w-[6rem]">{{ $it['code'] }}</span>@endif
                            @if ($borrow_type === 'new_inventory' || $borrow_type === 'tools_equipment')
                                <span class="flex-1 truncate text-gray-800">{{ $it['item_name'] }}</span>
                            @else
                                <input type="text" wire:model="items.{{ $i }}.item_name" placeholder="ຊື່ເຄື່ອງ" class="flex-1 rounded-lg border-gray-300 text-sm" />
                            @endif
                            <input type="number" min="1" wire:model="items.{{ $i }}.qty" class="w-16 rounded-lg border-gray-300 text-sm" />
                            <label class="shrink-0 cursor-pointer" title="ຖ່າຍ/ໃສ່ ຮູບ (ບໍ່ ບັງຄັບ)">
                                @if (! empty($itemPhotos[$i]))
                                    <img src="{{ $itemPhotos[$i]->temporaryUrl() }}" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-9 h-9 rounded-lg object-cover border-2 border-indigo-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" alt="ຮູບ" />
                                @else
                                    <span class="inline-flex w-9 h-9 items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-indigo-600 hover:border-indigo-300 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" /></svg>
                                    </span>
                                @endif
                                <input type="file" x-data="photoInputOne('itemPhotos.{{ $i }}')" x-on:change="pick($event)" accept="image/*" class="hidden" />
                            </label>
                            <button type="button" wire:click="removeItem({{ $i }})" class="text-gray-400 hover:text-rose-600 px-1 transition">×</button>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-400 text-xs">ຍັງບໍ່ມີລາຍການ</div>
                    @endforelse
                </div>
                <div wire:loading wire:target="itemPhotos" class="text-xs text-indigo-500">⏳ ກຳລັງ ອັບໂຫຼດ ຮູບ…</div>
                @error('items')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                @error('items.*.item_name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                @error('itemPhotos.*')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ③ purpose + period --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">3</span><h3 class="text-sm font-semibold text-gray-700">ຈຸດ ປະສົງ &amp; ໄລຍະ</h3></div>
            <div class="p-4 grid grid-cols-2 gap-3 text-sm">
                <div class="col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">ຈຸດປະສົງ <span class="text-rose-500">*</span></label><input type="text" wire:model="purpose" class="w-full rounded-lg border-gray-300" />@error('purpose')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror</div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີຢືມ <span class="text-rose-500">*</span></label><input type="date" wire:model.live="borrow_date" class="w-full rounded-lg border-gray-300" /></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-xs font-medium text-gray-500 mb-1">ໄລຍະ (ມື້) <span class="text-rose-500">*</span></label><input type="number" min="1" max="365" wire:model.live="period_days" class="w-full rounded-lg border-gray-300" /></div>
                    <div><label class="block text-xs font-medium text-gray-500 mb-1">ສົ່ງຄືນ</label><input disabled value="{{ $returnDate }}" class="w-full rounded-lg border-gray-200 bg-gray-50 text-gray-500" /></div>
                </div>
                <div class="col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">ໝາຍເຫດ</label><input type="text" wire:model="remark" class="w-full rounded-lg border-gray-300" /></div>
            </div>
        </div>

        {{-- ④ approval --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">4</span><h3 class="text-sm font-semibold text-gray-700">ສາຍອະນຸມັດ</h3></div>
            <div class="p-4 space-y-3">
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="requires_acknowledge" class="rounded border-gray-300 text-indigo-600"> ຕ້ອງ acknowledge ໂດຍ Line Manager?</label>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    @if ($requires_acknowledge)
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">Line Manager <span class="text-rose-500">*</span></label><select wire:model="acknowledge_user_id" class="w-full rounded-lg border-gray-300"><option value="">—</option>@foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->display_name ?? $u->email }}</option>@endforeach</select>@error('acknowledge_user_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror</div>
                    @endif
                    <div><label class="block text-xs font-medium text-gray-500 mb-1">Approver <span class="text-rose-500">*</span></label><select wire:model="approver_user_id" class="w-full rounded-lg border-gray-300"><option value="">—</option>@foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->display_name ?? $u->email }}</option>@endforeach</select>@error('approver_user_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror</div>
                </div>
            </div>
        </div>

        {{-- sticky save bar --}}
        <div class="bg-white/95 backdrop-blur border border-gray-200 rounded-xl px-5 py-3 flex items-center justify-end gap-2 sticky bottom-4 shadow-lg">
            <a href="{{ route('borrow') }}" wire:navigate class="text-sm text-gray-600 border border-gray-200 rounded-lg px-4 py-2 hover:bg-gray-50 flex items-center transition">ຍົກເລີກ</a>
            <button wire:click="save(false)" class="text-sm font-medium text-gray-700 border border-gray-200 rounded-lg px-4 py-2 hover:bg-gray-50 transition">💾 ບັນທຶກ draft</button>
            <button wire:click="save(true)" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg px-4 py-2 hover:bg-sky-700 transition shadow-sm">ສົ່ງຂໍອະນຸມັດ →</button>
        </div>
    </div>
</div>
