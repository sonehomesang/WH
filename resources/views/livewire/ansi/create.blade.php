@php $u = auth()->user(); @endphp
<div class="pb-10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col gap-4">
        <x-page-subheader :back="route('ansi')" back-label="ANSI list" />

        @include('partials._form-errors')

        {{-- General information --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-sm font-bold">1</span><h3 class="text-sm font-semibold text-gray-700">General Information</h3></div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Originator <span class="text-[10px] font-semibold text-sky-700 bg-sky-100 rounded px-1.5 py-0.5">auto · from your profile</span></label>
                    <input type="text" value="{{ $u->display_name ?? $u->email }}" disabled class="w-full rounded-lg border-gray-200 bg-sky-50 text-sm text-gray-700" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">HoS / TL <span class="text-rose-500">*</span></label>
                    <select wire:model="hos_user_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— select —</option>@foreach ($people as $p)<option value="{{ $p->id }}">{{ $p->display_name ?: $p->email }}</option>@endforeach</select>
                    @error('hos_user_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Branch / Unit <span class="text-[10px] font-semibold text-sky-700 bg-sky-100 rounded px-1.5 py-0.5">auto</span></label>
                    <input type="text" value="{{ $u->unit?->name ?? '—' }}" disabled class="w-full rounded-lg border-gray-200 bg-sky-50 text-sm text-gray-700" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Manager <span class="text-rose-500">*</span></label>
                    <select wire:model="manager_user_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— select —</option>@foreach ($people as $p)<option value="{{ $p->id }}">{{ $p->display_name ?: $p->email }}</option>@endforeach</select>
                    @error('manager_user_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Department <span class="text-[10px] font-semibold text-sky-700 bg-sky-100 rounded px-1.5 py-0.5">auto</span></label>
                    <input type="text" value="{{ $u->department?->name ?? '—' }}" disabled class="w-full rounded-lg border-gray-200 bg-sky-50 text-sm text-gray-700" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Section / Team</label>
                    <input type="text" wire:model="section_team" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Phone</label>
                    <input type="text" wire:model="phone" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Date</label>
                    <input type="date" wire:model="app_date" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sub Assembly</label>
                    <input type="text" wire:model="sub_assembly" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Functional System</label>
                    <input type="text" wire:model="functional_system" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Purpose</label>
                    <textarea wire:model="purpose" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
            </div>
        </div>

        {{-- Detail of new stock items --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5"><span class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-sm font-bold">2</span><h3 class="text-sm font-semibold text-gray-700">Detail of New Stock Items</h3></div>
                <button wire:click="addItem" type="button" class="text-sm font-medium text-sky-700 bg-sky-50 border border-sky-200 rounded-lg px-3 py-1.5 hover:bg-sky-100 transition">+ Add item</button>
            </div>
            @error('items')<p class="text-xs text-rose-600 px-4 pt-2">{{ $message }}</p>@enderror
            <div class="overflow-x-auto">
                <table class="w-full text-xs min-w-[980px]">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr class="text-[10.5px] uppercase tracking-wide text-left">
                            <th class="px-2 py-2 font-semibold">#</th><th class="px-2 py-2 font-semibold">Stock</th>
                            <th class="px-2 py-2 font-semibold">Description <span class="text-emerald-600 normal-case font-normal">(Noun, Detail, Model, Brand)</span></th>
                            <th class="px-2 py-2 font-semibold">Price USD</th><th class="px-2 py-2 font-semibold">Qty</th><th class="px-2 py-2 font-semibold">Unit</th>
                            <th class="px-2 py-2 font-semibold">Min</th><th class="px-2 py-2 font-semibold">Max</th><th class="px-2 py-2 font-semibold">Suggested Supplier</th>
                            <th class="px-2 py-2 font-semibold">Hazard</th><th class="px-2 py-2 font-semibold">Critical</th><th class="px-2 py-2 font-semibold">Sp. Storage</th><th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($items as $i => $row)
                            <tr wire:key="ai-{{ $i }}">
                                <td class="px-2 py-1.5 text-center font-bold text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-2 py-1.5"><select wire:model="items.{{ $i }}.stock" class="w-full rounded border-gray-300 text-xs"><option value="0">No</option><option value="1">Yes</option></select></td>
                                <td class="px-2 py-1.5"><input type="text" wire:model="items.{{ $i }}.description" class="w-full rounded border-gray-300 text-xs" />@error("items.$i.description")<span class="text-[10px] text-rose-600">{{ $message }}</span>@enderror</td>
                                <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" wire:model="items.{{ $i }}.price_usd" class="w-20 rounded border-gray-300 text-xs" /></td>
                                <td class="px-2 py-1.5"><input type="number" min="1" wire:model="items.{{ $i }}.qty_order" class="w-16 rounded border-gray-300 text-xs" /></td>
                                <td class="px-2 py-1.5"><select wire:model="items.{{ $i }}.unit" class="w-20 rounded border-gray-300 text-xs"><option value="">—</option>@foreach ($uoms as $uom)<option value="{{ $uom->name }}">{{ $uom->name }}</option>@endforeach</select></td>
                                <td class="px-2 py-1.5"><input type="number" min="0" wire:model="items.{{ $i }}.min_qty" class="w-16 rounded border-gray-300 text-xs" /></td>
                                <td class="px-2 py-1.5"><input type="number" min="0" wire:model="items.{{ $i }}.max_qty" class="w-16 rounded border-gray-300 text-xs" /></td>
                                <td class="px-2 py-1.5"><input type="text" wire:model="items.{{ $i }}.suggested_supplier" class="w-full rounded border-gray-300 text-xs" /></td>
                                <td class="px-2 py-1.5"><select wire:model="items.{{ $i }}.hazardous" class="w-16 rounded border-gray-300 text-xs"><option value="0">No</option><option value="1">Yes</option></select></td>
                                <td class="px-2 py-1.5"><select wire:model="items.{{ $i }}.criticality" class="w-16 rounded border-gray-300 text-xs"><option value="0">No</option><option value="1">Yes</option></select></td>
                                <td class="px-2 py-1.5"><select wire:model="items.{{ $i }}.special_storage" class="w-28 rounded border-gray-300 text-xs"><option value="Normal">Normal</option><option value="Air Cond room">Air Cond room</option></select></td>
                                <td class="px-2 py-1.5 text-center">@if (count($items) > 1)<button wire:click="removeItem({{ $i }})" type="button" class="text-gray-400 hover:text-rose-600">✕</button>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Attachment --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">📎 Attachment <span class="text-gray-400 font-normal text-xs">(images, PDF, Office · optional)</span></label>
            <input type="file" wire:model="files" multiple class="block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-50 file:text-sky-700 file:font-medium file:cursor-pointer hover:file:bg-sky-100" />
            <div wire:loading wire:target="files" class="text-xs text-sky-500 mt-1">⏳ uploading…</div>
            @if (! empty($files))
                <ul class="mt-2 flex flex-col gap-1">
                    @foreach ($files as $j => $f)
                        <li class="flex items-center gap-2 text-xs text-gray-600"><span>📄 {{ $f->getClientOriginalName() }}</span><button wire:click="removeFile({{ $j }})" type="button" class="text-rose-500">✕</button></li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- action bar --}}
        <div class="bg-white/95 backdrop-blur border border-gray-200 rounded-xl px-5 py-3 flex items-center justify-end gap-2 sticky bottom-4 shadow-lg">
            <button wire:click="save(false)" wire:loading.attr="disabled" wire:target="save,files" class="text-sm font-medium text-gray-700 border border-gray-300 rounded-lg px-4 py-2.5 hover:bg-gray-50 disabled:opacity-50">💾 Save draft</button>
            <button wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,files" class="inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-sky-600 rounded-lg px-5 py-2.5 hover:bg-sky-700 disabled:opacity-50 transition shadow-sm">Submit → HoS/TL</button>
        </div>
    </div>
</div>
