@php $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-50 file:text-sky-700 file:font-medium file:cursor-pointer hover:file:bg-sky-100'; @endphp

<div class="pb-10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col gap-4">
        <x-page-subheader :back="route('deposit')" back-label="ລາຍການ ຝາກ" />

        @include('partials._form-errors')
        @error('action')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ $message }}</div>@enderror
        @error('photos')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ $message }}</div>@enderror

        {{-- request type --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex items-center gap-3 flex-wrap">
            <div class="text-sm font-semibold text-gray-700">ປະເພດ ການ ຝາກ</div>
            <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm ml-auto">
                @foreach (['walk_in' => 'Walk-in · ນຳມາແລ້ວ', 'pre_request' => 'Pre-request · ສົ່ງລ່ວງໜ້າ', 'legacy' => 'ເຄື່ອງຝາກເກົ່າ · ຄ້າງ ດົນ'] as $v => $l)
                    <button type="button" wire:click="$set('request_type', '{{ $v }}')" class="px-4 py-2 transition {{ $request_type === $v ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ ! $loop->first ? 'border-l border-gray-200' : '' }}">{{ $l }}</button>
                @endforeach
            </div>
        </div>

        {{-- ຜູ້ ເອົາ ມາ ຝາກ — ເຫັນ ຕອນ ຝາກ (ຄົນ ເອົາ ເຄື່ອງ ມາ ຢູ່ ໜ້າງານ); record-level, ບໍ່ ບັງຄັບ --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex items-center gap-3 flex-wrap">
            <label class="text-sm font-semibold text-gray-700 shrink-0">👤 ຜູ້ ເອົາ ມາ ຝາກ <span class="text-gray-400 font-normal text-xs">/ Depositor · ບໍ່ ບັງຄັບ</span></label>
            <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm shrink-0">
                <button type="button" wire:click="$set('depositor_mode', 'internal')" class="px-3 py-1.5 transition {{ $depositor_mode === 'internal' ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">ຄົນ ພາຍ ໃນ</button>
                <button type="button" wire:click="$set('depositor_mode', 'external')" class="px-3 py-1.5 border-l border-gray-200 transition {{ $depositor_mode === 'external' ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">ຄົນ ນອກ</button>
            </div>
            @if ($depositor_mode === 'internal')
                <select wire:model="depositor_user_id" class="flex-1 min-w-[240px] rounded-lg border-gray-300 text-sm"><option value="">— ເລືອກ ຈາກ ຜູ້ ໃຊ້ —</option>@foreach ($ownerUsers as $ou)<option value="{{ $ou->id }}">{{ $ou->display_name ?: $ou->email }}</option>@endforeach</select>
            @else
                <input type="text" wire:model="depositor_name" placeholder="ຊື່ ຜູ້ ເອົາ ເຄື່ອງ ມາ ຝາກ (ຄົນ ນອກ ລະບົບ)…" class="flex-1 min-w-[240px] rounded-lg border-gray-300 text-sm" />
            @endif
        </div>

        {{-- ຂັ້ນ 2 (ຂໍ້ມູນ ທົ່ວໄປ) ຍ້າຍ ໄປ ໜ້າ ແກ້ໄຂ — ຫົວໜ້າ ຕື່ມ ຕໍ່. ຄົງ markup ໄວ້ (ບໍ່ render). --}}
        @if (false)
        <div class="order-2 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-7 h-7 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center text-sm font-bold">2</span><div><h3 class="text-sm font-semibold text-gray-700">ຂໍ້ມູນ ທົ່ວໄປ</h3><p class="text-[11px] text-gray-400">ຫ້ອງການ · ຫົວໜ້າ ທີມ ເພີ່ມ ຕໍ່ (ບໍ່ ບັງຄັບ ຕອນ ບັນທຶກ ຮ່າງ)</p></div></div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ປະເພດເຄື່ອງ (Category) <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="item_category" class="w-full rounded-lg border-gray-300 text-sm" />
                    @error('item_category')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ແຫຼ່ງທີ່ມາ (Origin/Source) <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="origin_source" class="w-full rounded-lg border-gray-300 text-sm" />
                    @error('origin_source')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ສະຖານະ ການ ໃຊ້ງານ (Functional)</label>
                    <select wire:model="functional_status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">— ເລືອກ —</option>
                        <option value="usable">✅ ໃຊ້ ໄດ້ ປົກກະຕິ · Functional</option>
                        <option value="partial">⚠️ ໃຊ້ ໄດ້ ບາງ ສ່ວນ · Partial</option>
                        <option value="unusable">⛔ ໃຊ້ ບໍ່ ໄດ້ · Non-functional</option>
                    </select>
                    @error('functional_status')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                @if ($request_type === 'legacy')
                    <div class="md:col-span-2 rounded-lg bg-amber-50/50 border border-amber-100 p-3">
                        <div class="text-xs font-semibold text-amber-700 mb-2 flex items-center gap-1.5"><span>📦</span> ເຄື່ອງຝາກເກົ່າ ທີ່ ຄ້າງ ໄວ້ ດົນ <span class="text-gray-400 font-normal">(ຂໍ້ມູນ ການ ຝາກ ເດີມ — ບໍ່ ບັງຄັບ)</span></div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີຝາກເດີມ (Original deposit date)</label>
                                <input type="date" wire:model="original_deposit_date" class="w-full rounded-lg border-gray-300 text-sm" />
                                @error('original_deposit_date')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">ຜູ້ຮັບຝາກເດີມ (Original receiver)</label>
                                <input type="text" wire:model="original_receiver" placeholder="ຊື່ ຜູ້ ຮັບ ຝາກ ຕອນ ນັ້ນ" class="w-full rounded-lg border-gray-300 text-sm" />
                                @error('original_receiver')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ຜູ້ຮັບ / ຜູ້ບັນທຶກ ຂໍ້ມູນ / Receiver <span class="text-gray-400 font-normal">(ດຶງ ຈາກ ຜູ້ ໃຊ້)</span></label>
                    <select wire:model="owner_user_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($ownerUsers as $ou)<option value="{{ $ou->id }}">{{ $ou->display_name ?: $ou->email }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ຜູ້ ເອົາ ມາ ຝາກ / Depositor <span class="text-gray-400 font-normal">(ບໍ່ ບັງຄັບ)</span></label>
                    <input type="text" wire:model="depositor_name" placeholder="ຊື່ ຜູ້ ເອົາ ເຄື່ອງ ມາ ຝາກ…" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ພະແນກ ເຈົ້າ ຂອງ / Department <span class="text-gray-400 font-normal">(ຄຸມ ສິດ ຈຳໜ່າຍ)</span></label>
                    <select wire:model="owner_dept_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}@if ($d->unit) · {{ $d->unit->name }}@endif</option>@endforeach</select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ໄລຍະເວລາທີ່ຄາດ (Duration) <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="expected_duration" placeholder="ເຊັ່ນ: 7 ມື້, 1 ເດືອນ" class="w-full rounded-lg border-gray-300 text-sm" />
                    @error('expected_duration')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີຝາກ (Deposit date) <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model="deposit_date" class="w-full rounded-lg border-gray-300 text-sm" />
                    @error('deposit_date')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                @if ($request_type === 'pre_request')
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ຄາດຈະນຳມາ (Expected arrival)</label>
                        <input type="date" wire:model="expected_arrival" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ຄາດຈະມາເອົາຄືນ (Expected claim)</label>
                    <input type="date" wire:model="expected_claim_date" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">ເຫດຜົນການຝາກ (Reason) <span class="text-rose-500">*</span></label>
                    <textarea wire:model="deposit_reason" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    @error('deposit_reason')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">ໝາຍເຫດ (Remark, optional)</label>
                    <textarea wire:model="remark" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
            </div>
        </div>
        @endif

        {{-- ══ ຂັ້ນຕອນ 1 · ໜ້າງານ: ລາຍການ ເຄື່ອງ + ຮູບ ══ --}}
        <div class="order-1 flex flex-col gap-3">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-sky-600 text-white flex items-center justify-center text-sm font-bold">1</span>
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">ລາຍການ ເຄື່ອງ + ຮູບ <span class="text-gray-400 font-normal">({{ count($items) }})</span></h3>
                    <p class="text-[11px] text-gray-400">ໜ້າງານ · ໄວ: ຊື່ · ລະຫັດ · ຖ່າຍ ຮູບ · ຈຳນວນ · ສະຖານະ → ບັນທຶກ ຮ່າງ</p>
                </div>
            </div>
            <button wire:click="addItem" type="button" class="text-sm font-medium text-sky-700 bg-sky-50 border border-sky-200 rounded-lg px-3 py-1.5 hover:bg-sky-100 transition">+ ເພີ່ມລາຍການ</button>
        </div>
        @error('items')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

        <div class="space-y-2.5">
            @foreach ($items as $i => $row)
                <div wire:key="item-{{ $i }}" class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-bold shrink-0">{{ $i + 1 }}</span>
                        <span class="text-xs font-semibold text-gray-500">ລາຍການ ທີ {{ $i + 1 }}</span>
                        @if (count($items) > 1)<button wire:click="removeItem({{ $i }})" type="button" class="ml-auto text-gray-400 hover:text-rose-600 p-1 transition" title="ລຶບ">✕</button>@endif
                    </div>

                    {{-- ① ຊື່ · ລະຫັດ · ຊັບສິນ --}}
                    <div class="grid grid-cols-1 sm:grid-cols-6 gap-2.5">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">ຊື່ເຄື່ອງ <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="items.{{ $i }}.item_name" class="w-full rounded-lg border-gray-300 text-sm" />
                            @error("items.$i.item_name")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2 relative">
                            @php $src = $row['asset_source'] ?? 'inventory'; @endphp
                            <div class="flex items-center justify-between mb-1 gap-2">
                                <label class="text-xs font-medium text-gray-500 whitespace-nowrap">ລະຫັດ (ສາງ/ອຸປະກອນ)</label>
                                <div class="flex rounded-lg border border-gray-200 overflow-hidden text-[10px] leading-none shrink-0">
                                    @foreach (['inventory' => 'Inventory', 'equipment' => 'Equipment', 'new' => '+ ໃໝ່'] as $sv => $sl)
                                        <button type="button" wire:click="$set('items.{{ $i }}.asset_source', '{{ $sv }}')" class="px-1.5 py-1 border-l first:border-l-0 border-gray-200 transition {{ $src === $sv ? 'bg-sky-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">{{ $sl }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <input type="text" wire:model.live.debounce.350ms="items.{{ $i }}.asset_code" placeholder="{{ $src === 'new' ? 'ພິມ ລະຫັດ ເອງ…' : 'ພິມ ລະຫັດ/ຊື່ ເພື່ອ ຄົ້ນ…' }}" autocomplete="off" class="w-full rounded-lg border-gray-300 text-sm font-mono" />
                            @unless ($src === 'new')<div wire:loading.flex wire:target="items.{{ $i }}.asset_code" class="absolute right-2 bottom-2 text-gray-300"><svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg></div>@endunless
                            @if (! empty($assetMatches[$i]))
                                <ul class="absolute z-30 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-auto text-xs">
                                    @foreach ($assetMatches[$i] as $m)
                                        <li><button type="button" wire:click="pickAsset({{ $i }}, '{{ $m['source'] }}', {{ $m['id'] }})" class="w-full text-left px-2.5 py-2 hover:bg-sky-50 border-b border-gray-50 last:border-0">
                                            <span class="inline-block text-[9px] font-semibold rounded px-1 mr-0.5 {{ $m['source'] === 'equipment' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $m['source'] === 'equipment' ? 'EQ' : 'INV' }}</span>
                                            <span class="font-mono text-sky-700">{{ $m['code'] }}</span> · {{ $m['name'] }}@if ($m['fixed'])<span class="text-gray-400"> · {{ $m['fixed'] }}</span>@endif
                                        </button></li>
                                    @endforeach
                                </ul>
                            @endif
                            @error("items.$i.asset_code")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">ທະບຽນຊັບສິນ</label>
                            <input type="text" wire:model="items.{{ $i }}.fixed_asset_no" placeholder="Fixed asset no." class="w-full rounded-lg border-gray-300 text-sm font-mono" />
                            @error("items.$i.fixed_asset_no")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- ② ຖ່າຍ ຮູບ (ໜ້າງານ) — ແຍກ 3 ມູມ · ແຕ່ ລະ ຊ່ອງ ຮັບ ຈາກ ກ້ອງ ຫຼື ແກເລີຣີ --}}
                    <div class="rounded-lg bg-sky-50/40 border border-sky-100 p-3">
                        <label class="block text-xs font-semibold text-sky-700 mb-2">📸 ຮູບ ຫຼັກຖານ — <span class="text-gray-400 font-normal">ແຍກ 3 ມູມ · ຖ່າຍ ຫຼື ເລືອກ ຈາກ ຄັງ ໄດ້ ທຸກ ຊ່ອງ</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            @foreach (\App\Livewire\Deposit\Create::PHOTO_SLOTS as $slot => $meta)
                                @php $slotFiles = $photos[$i][$slot] ?? []; @endphp
                                <div wire:key="ph-{{ $i }}-{{ $slot }}" x-data="photoSlot('camUpload.{{ $i }}', 'galUpload.{{ $i }}', '{{ $slot }}')" class="rounded-lg border border-sky-100 bg-white p-2.5 flex flex-col gap-2">
                                    <div class="text-[11px] font-semibold text-gray-600 flex items-center gap-1 min-h-[2rem]"><span class="text-base leading-none">{{ $meta[1] }}</span><span>{{ $meta[0] }}</span></div>
                                    <div class="flex gap-1.5">
                                        <label class="flex-1 cursor-pointer inline-flex items-center justify-center gap-1 text-xs font-medium text-white bg-sky-600 rounded-lg px-2 py-1.5 hover:bg-sky-700 transition">
                                            📷 <span class="hidden lg:inline">ຖ່າຍ</span>
                                            <input type="file" x-on:change="upload($event, 'cam')" accept="image/*" capture="environment" multiple class="hidden" />
                                        </label>
                                        <label class="flex-1 cursor-pointer inline-flex items-center justify-center gap-1 text-xs font-medium text-sky-700 bg-sky-50 border border-sky-200 rounded-lg px-2 py-1.5 hover:bg-sky-100 transition">
                                            🖼 <span class="hidden lg:inline">{{ app()->getLocale() === 'en' ? 'Gallery' : 'ຄັງ' }}</span>
                                            <input type="file" x-on:change="upload($event, 'gal')" accept="image/*" multiple class="hidden" />
                                        </label>
                                    </div>
                                    <div x-show="busy" class="text-[10px] text-sky-500">⏳ ກຳລັງ ຫຍໍ້ + ອັບ…</div>
                                    @if (! empty($slotFiles))
                                        <div class="flex gap-1.5 flex-wrap">
                                            @foreach ($slotFiles as $j => $f)
                                                @if ($f->isPreviewable())
                                                    <div class="relative group">
                                                        <img src="{{ $f->temporaryUrl() }}" alt="" @click="$dispatch('open-lightbox', { src: $el.src })" class="w-14 h-14 rounded-lg object-cover border-2 border-sky-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                                                        <button type="button" wire:click="removePhoto({{ $i }}, '{{ $slot }}', {{ $j }})" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-rose-600 text-white rounded-full text-xs leading-none shadow" title="ລຶບ ຮູບ">×</button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-[10px] text-gray-300 italic">ຍັງ ບໍ່ ມີ ຮູບ</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ③ ຈຳນວນ · ບ່ອນຈັດເກັບ · ສະຖານະ (+ optional ທີ່ admin ເປີດ) --}}
                    <div class="grid grid-cols-2 sm:grid-cols-6 gap-2.5">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ຈຳນວນ <span class="text-rose-500">*</span></label>
                            <input type="number" min="1" wire:model="items.{{ $i }}.qty" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ໜ່ວຍ</label>
                            <select wire:model="items.{{ $i }}.unit" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($uoms as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach</select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">📍 ບ່ອນ ຈັດ ເກັບ ໄວ້</label>
                            <input type="text" wire:model="items.{{ $i }}.storage_location" placeholder="ເຊັ່ນ: ສາງ A · ຊັ້ນ 2" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">ສະຖານະພາບ (Condition)</label>
                            <select wire:model="items.{{ $i }}.condition_status" class="w-full rounded-lg border-gray-300 text-sm">@foreach (\App\Support\ConditionStatus::options() as $cv => $cl)<option value="{{ $cv }}">{{ $cl }}</option>@endforeach</select>
                        </div>
                        @if ($fieldVisible['condition_on_deposit'] ?? false)
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-500 mb-1">ສະພາບ ຕອນ ຝາກ</label>
                                <input type="text" wire:model="items.{{ $i }}.condition_on_deposit" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                        @endif
                        @if ($fieldVisible['estimated_value'] ?? false)
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-500 mb-1">ມູນຄ່າ (ປະມານ)</label>
                                <input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.estimated_value" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                        @endif
                        @if ($fieldVisible['currency'] ?? false)
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">ສະກຸນເງິນ</label>
                                <select wire:model="items.{{ $i }}.currency" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option><option value="LAK">LAK</option><option value="THB">THB</option><option value="USD">USD</option></select>
                            </div>
                        @endif
                        @if ($fieldVisible['description'] ?? false)
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-gray-500 mb-1">ລາຍລະອຽດ (Description)</label>
                                <input type="text" wire:model="items.{{ $i }}.description" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        </div>{{-- /ຂັ້ນຕອນ 1 --}}

        {{-- sticky save bar --}}
        <div class="order-3 bg-white/95 backdrop-blur border border-gray-200 rounded-xl px-5 py-3 flex items-center justify-end gap-2 sticky bottom-4 shadow-lg">
            <span class="text-xs text-gray-400 hidden sm:inline mr-auto">ບັນທຶກ ແລ້ວ → ຟອມ ໃໝ່ ຂຶ້ນ ໃຫ້ ເພີ່ມ ລາຍການ ຕໍ່ · ຫົວໜ້າ ຕື່ມ ຂໍ້ມູນ + ສົ່ງ ໃນ ໜ້າ ແກ້ໄຂ</span>
            <button wire:click="save(false)" wire:loading.attr="disabled" wire:target="save,camUpload,galUpload" class="inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-sky-600 rounded-lg px-5 py-2.5 hover:bg-sky-700 disabled:opacity-50 transition shadow-sm">💾 ບັນທຶກ + ເພີ່ມ ໃໝ່</button>
        </div>
    </div>
</div>
