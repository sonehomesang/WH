<div class="pb-10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('disposal')" back-label="ລາຍການ ຈຳໜ່າຍ">
            <x-slot:hint>ບັນທຶກ ຮ່າງ → ມອບໝາຍ ຜູ້ ຮັບຮອງ → ສົ່ງ ຂໍ ຮັບຮອງ</x-slot>
        </x-page-subheader>

        @include('partials._form-errors')

        {{-- ຂໍ້ມູນ ໃບ --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-xs">📋</span><h3 class="text-sm font-semibold text-gray-700">ຂໍ້ມູນ ໃບ</h3></div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-4 gap-3 text-sm">
                <div class="sm:col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">ຫົວຂໍ້ / ຊຸດ</label><input type="text" wire:model="title" placeholder="ເຊັ່ນ: ຈຳໜ່າຍ ເຄື່ອງມື ຊຳລຸດ ໄຕມາດ 3" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">ພະແນກ</label><select wire:model="department_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">ໝາຍເຫດ</label><input type="text" wire:model="note" class="w-full rounded-lg border-gray-300 text-sm" placeholder="optional" /></div>

                {{-- ເຄື່ອງຝາກເກົ່າ ທີ່ ຄ້າງ ໄວ້ ດົນ (optional) --}}
                <div class="sm:col-span-4 border-t border-dashed border-gray-200 pt-3 mt-1">
                    <div class="text-xs font-semibold text-amber-700 mb-2 flex items-center gap-1.5"><span>📦</span> ເຄື່ອງຝາກເກົ່າ ທີ່ ຄ້າງ ໄວ້ ດົນ <span class="text-gray-400 font-normal">(ຖ້າ ເປັນ ການ ຈຳໜ່າຍ ເຄື່ອງ ຝາກ — ບໍ່ ບັງຄັບ)</span></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີຝາກເດີມ (Original deposit date)</label><input type="date" wire:model="original_deposit_date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ຜູ້ຮັບຝາກເດີມ (Original receiver)</label><input type="text" wire:model="original_receiver" placeholder="ຊື່ ຜູ້ ຮັບ ຝາກ ຕອນ ນັ້ນ" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                </div>
            </div>
        </div>

        @if (session('pullOk'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5">✓ {{ session('pullOk') }}</div>@endif

        {{-- ລາຍການ --}}
        <div class="flex items-center justify-between gap-2 pt-1">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-sm">📦</span>
                <h3 class="text-sm font-semibold text-gray-700">ລາຍການ ຈຳໜ່າຍ <span class="text-gray-400 font-normal">({{ count($items) }})</span></h3>
            </div>
            @can('disposal.create')<button type="button" wire:click="openPull" class="inline-flex items-center gap-1.5 text-sm font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-1.5 hover:bg-rose-100 transition whitespace-nowrap">🗑 ດຶງ ອັດຕະໂນມັດ ຕາມ ສະຖານະພາບ</button>@endcan
        </div>

        <div class="space-y-2.5">
            @foreach ($items as $i => $row)
                @php $src = $row['source_type'] ?? 'equipment'; @endphp
                <div wire:key="di-{{ $i }}" class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 space-y-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="w-6 h-6 rounded-md bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-bold">{{ $i + 1 }}</span>
                        <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-xs ml-auto">
                            @foreach (['inventory' => 'Inventory', 'equipment' => 'Equipment', 'deposit' => 'Deposit', 'new' => '+ ໃໝ່'] as $sv => $sl)
                                <button type="button" wire:click="$set('items.{{ $i }}.source_type', '{{ $sv }}')" class="px-2.5 py-1.5 transition {{ $src === $sv ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ ! $loop->first ? 'border-l border-gray-200' : '' }}">{{ $sl }}</button>
                            @endforeach
                        </div>
                        @if (count($items) > 1)<button type="button" wire:click="removeItem({{ $i }})" class="text-gray-400 hover:text-rose-600 p-1 transition" title="ລຶບ">✕</button>@endif
                    </div>

                    @unless ($src === 'new')
                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-500 mb-1">🔎 ຄົ້ນ ຈາກ ທະບຽນ {{ ['inventory' => 'Inventory', 'equipment' => 'Equipment & Tools', 'deposit' => 'ເຄື່ອງ ຝາກ'][$src] ?? '' }}</label>
                            <input type="text" wire:model.live.debounce.350ms="items.{{ $i }}.asset_code" placeholder="ພິມ ລະຫັດ/ຊື່ ເພື່ອ ຄົ້ນ…" autocomplete="off" class="w-full rounded-lg border-gray-300 text-sm" />
                            @if (! empty($assetMatches[$i]))
                                <ul class="absolute z-30 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-auto text-xs">
                                    @foreach ($assetMatches[$i] as $m)
                                        <li><button type="button" wire:click="pickAsset({{ $i }}, '{{ $m['source'] }}', {{ $m['id'] }})" class="w-full text-left px-2.5 py-2 hover:bg-sky-50 border-b border-gray-50 last:border-0">
                                            <span class="inline-block text-[9px] font-semibold rounded px-1 mr-0.5 {{ $m['source'] === 'equipment' ? 'bg-amber-50 text-amber-700' : ($m['source'] === 'inventory' ? 'bg-emerald-50 text-emerald-700' : 'bg-sky-50 text-sky-700') }}">{{ ['equipment' => 'EQ', 'inventory' => 'INV', 'deposit' => 'DEP'][$m['source']] }}</span>
                                            <span class="font-mono text-sky-700">{{ $m['code'] }}</span> · {{ $m['name'] }}@if ($m['fixed'])<span class="text-gray-400"> · {{ $m['fixed'] }}</span>@endif
                                        </button></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endunless

                    <div class="grid grid-cols-2 sm:grid-cols-6 gap-2.5 text-sm">
                        <div class="col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">ຊື່ເຄື່ອງ <span class="text-rose-500">*</span></label><input type="text" wire:model="items.{{ $i }}.item_name" class="w-full rounded-lg border-gray-300 text-sm" />@error("items.$i.item_name")<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ລະຫັດເຄື່ອງ</label><input type="text" wire:model="items.{{ $i }}.asset_code" class="w-full rounded-lg border-gray-300 text-sm font-mono" /></div>
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ຊັບສິນ</label><input type="text" wire:model="items.{{ $i }}.fixed_asset_no" class="w-full rounded-lg border-gray-300 text-sm font-mono" /></div>
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ຈຳນວນ <span class="text-rose-500">*</span></label><input type="number" min="1" wire:model="items.{{ $i }}.qty" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ໜ່ວຍ</label><select wire:model="items.{{ $i }}.unit" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($uoms as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach @if (! empty($row['unit']) && ! $uoms->contains('name', $row['unit']))<option value="{{ $row['unit'] }}">{{ $row['unit'] }} (ເກົ່າ)</option>@endif</select></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ສະພາບ ປັດຈຸບັນ</label><input type="text" wire:model="items.{{ $i }}.condition" placeholder="ເຊັ່ນ: ມໍເຕີ ໄໝ້ · ໃຊ້ ບໍ່ ໄດ້" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ສະຖານະ ການ ໃຊ້ງານ (Functional) <span class="text-gray-400 font-normal">— ດຶງ ຈາກ ເຄື່ອງຝາກ ຖ້າ ມີ</span></label>
                            <select wire:model="items.{{ $i }}.functional_status" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">— ເລືອກ —</option>
                                <option value="usable">✅ ໃຊ້ ໄດ້ ປົກກະຕິ · Functional</option>
                                <option value="partial">⚠️ ໃຊ້ ໄດ້ ບາງ ສ່ວນ · Partial</option>
                                <option value="unusable">⛔ ໃຊ້ ບໍ່ ໄດ້ · Non-functional</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-lg bg-rose-50/40 border border-rose-100 p-2.5">
                            <label class="block text-xs font-semibold text-rose-600 mb-1">ເຫດຜົນ ຈຳໜ່າຍ</label>
                            <select wire:model="items.{{ $i }}.reason" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— ເລືອກ —</option>@foreach ($reasons as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select>
                            <input type="text" wire:model="items.{{ $i }}.reason_detail" placeholder="ລາຍລະອຽດ ເພີ່ມ (optional)" class="w-full rounded-lg border-gray-300 text-xs mt-1.5" />
                        </div>
                        <div class="rounded-lg bg-indigo-50/40 border border-indigo-100 p-2.5">
                            <label class="block text-xs font-semibold text-indigo-600 mb-1">ຄຳແນະນຳ</label>
                            <select wire:model="items.{{ $i }}.recommendation" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— ເລືອກ —</option>@foreach ($recommendations as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select>
                            <input type="text" wire:model="items.{{ $i }}.recommendation_detail" placeholder="ລາຍລະອຽດ ເພີ່ມ (optional)" class="w-full rounded-lg border-gray-300 text-xs mt-1.5" />
                        </div>
                    </div>

                    @if (! empty($row['history']))
                        <div class="border border-sky-200 rounded-lg overflow-hidden">
                            <div class="flex items-center gap-2 px-3 py-2 border-b border-sky-100 text-xs bg-sky-50/60">
                                <span class="font-semibold text-gray-700">🕘 ປະຫວັດ ບັນຫາ &amp; ສ້ອມ/ປ່ຽນ ຂອງ ເຄື່ອງ ນີ້</span>
                                <span class="text-gray-400 ml-auto">ດຶງ ອັດຕະໂນມັດ · {{ count($row['history']) }} ຄັ້ງ</span>
                            </div>
                            @foreach ($row['history'] as $j => $h)
                                @php $kc = ['maintenance' => ['ບຳລຸງ', 'bg-sky-50 text-sky-700'], 'inspection' => ['ກວດ ບໍ່ຜ່ານ', 'bg-amber-50 text-amber-700'], 'repair' => ['ສ້ອມ CM', 'bg-rose-50 text-rose-700']][$h['kind'] ?? 'maintenance'] ?? ['—', 'bg-gray-50 text-gray-600']; @endphp
                                <label class="flex items-start gap-2 px-3 py-2 border-b border-gray-50 last:border-0 text-xs cursor-pointer hover:bg-gray-50/60">
                                    <input type="checkbox" wire:model="items.{{ $i }}.history.{{ $j }}.include" class="mt-0.5 rounded border-gray-300 text-sky-600" />
                                    <span class="flex-1">
                                        <span class="text-gray-500 font-mono">{{ $h['date'] ?? '—' }}</span>
                                        <span class="rounded px-1.5 py-0.5 mx-1 font-medium {{ $kc[1] }}">{{ $kc[0] }}</span>
                                        {{ $h['problem'] ?? '' }} <span class="text-gray-400">→</span> <span class="text-gray-700">{{ $h['action'] ?? '' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400">ⓘ ຂໍ້ ທີ່ ຕິກ ຈະ ຂຶ້ນ ໃນ ໃບ Disposal + PDF ເປັນ ຫຼັກຖານ ປະກອບ</p>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">ຮູບ ຫຼັກຖານ</label>
                        <input type="file" x-data="photoInput('photos.{{ $i }}')" x-on:change="pick($event)" multiple accept="image/*" capture="environment" class="block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-50 file:text-sky-700 file:font-medium file:cursor-pointer hover:file:bg-sky-100" />
                        <div wire:loading wire:target="photos.{{ $i }}" class="text-xs text-sky-500 mt-1">⏳ ກຳລັງ ອັບ…</div>
                        @if (! empty($photos[$i]))<div class="flex gap-1.5 flex-wrap mt-2">@foreach ($photos[$i] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-14 h-14 rounded-lg object-cover border-2 border-sky-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endif @endforeach</div>@endif
                    </div>
                </div>
            @endforeach

            <button type="button" wire:click="addItem" class="w-full text-sm font-medium text-sky-700 border border-dashed border-sky-300 rounded-xl px-3 py-2.5 hover:bg-sky-50 transition">+ ເພີ່ມ ລາຍການ</button>
        </div>

        {{-- sticky save bar --}}
        <div class="bg-white/95 backdrop-blur border border-gray-200 rounded-xl px-5 py-3 flex items-center justify-between gap-2 sticky bottom-4 shadow-lg">
            <span class="text-xs text-gray-400 hidden sm:inline">ບັນທຶກ ຮ່າງ ກ່ອນ ແລ້ວ ຄ່ອຍ ມອບໝາຍ ຜູ້ ຮັບຮອງ + ສົ່ງ ຂໍ ຮັບຮອງ</span>
            <div class="flex gap-2 ml-auto">
                <button wire:click="save(false)" wire:loading.attr="disabled" wire:target="save,photos" class="text-sm font-medium text-gray-700 border border-gray-200 rounded-lg px-4 py-2 hover:bg-gray-50 disabled:opacity-50 transition">ບັນທຶກ ຮ່າງ</button>
                <button wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,photos" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg px-4 py-2 hover:bg-sky-700 disabled:opacity-50 transition shadow-sm">📨 ສົ່ງ ຂໍ ຮັບຮອງ</button>
            </div>
        </div>

        {{-- auto-pull dialog --}}
        @if ($showPull)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-lg rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-rose-200 to-rose-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-rose-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑️</span>
                        <h3 class="text-base font-semibold text-gray-800">🗑 ດຶງ ໄປ ຈຳໜ່າຍ ອັດຕະໂນມັດ</h3>
                        <button wire:click="$set('showPull', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="px-5 py-4 space-y-4 overflow-y-auto text-sm">
                        <div>
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">① ດຶງ ຈາກ ແຫຼ່ງ</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['inventory' => 'Inventory', 'equipment' => 'Equipment', 'deposit' => 'Deposit'] as $sk => $sl)
                                    <label class="inline-flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-1.5 cursor-pointer hover:bg-gray-50 transition">
                                        <input type="checkbox" wire:model.live="pullSources.{{ $sk }}" class="rounded border-gray-300 text-sky-600" /> {{ $sl }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">② ດຶງ ເຄື່ອງ ທີ່ ຢູ່ ໃນ ສະຖານະ</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach (\App\Support\ConditionStatus::disposable() as $cs)
                                    <label class="inline-flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-1.5 cursor-pointer hover:bg-gray-50 transition">
                                        <input type="checkbox" wire:model.live="pullStatuses.{{ $cs }}" class="rounded border-gray-300 text-sky-600" /> {{ \App\Support\ConditionStatus::shortLabel($cs) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="text-xs text-gray-600 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2.5">🔎 ພົບ <b class="text-sky-700">{{ $pullCount }}</b> ລາຍການ ທີ່ ກົງ ເງື່ອນ ໄຂ — ພ້ອມ ດຶງ ເຂົ້າ ໃບ ຈຳໜ່າຍ.</div>
                        <div class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">ℹ️ ເຄື່ອງ ຝາກ: <b>ເຄື່ອງຝາກເກົ່າ (legacy)</b> ດຶງ ໄດ້ ເລີຍ ຖ້າ ສະຖານະພາບ ກົງ · <b>ຝາກ ທຳມະດາ</b> ຕ້ອງ “ຮັບ ເຂົ້າ ສາງ ແລ້ວ” (accepted/stored) ກ່ອນ.</div>
                        @error('pull')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showPull', false)" class="text-sm bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50 transition">ຍົກເລີກ</button>
                        <button wire:click="autoPull" wire:loading.attr="disabled" wire:target="autoPull" class="text-sm font-medium text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50 transition shadow-sm">ດຶງ ອອກ {{ $pullCount }} ລາຍການ →</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
