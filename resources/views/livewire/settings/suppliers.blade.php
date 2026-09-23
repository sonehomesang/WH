@php
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgPower = 'M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9';
    $svgTrash = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div class="sticky top-16 z-30 bg-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
            <div class="flex items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຊື່/ຜູ້ຕິດຕໍ່…" class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                @if ($canManageDeleted)
                    <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 bg-white hover:bg-gray-50' }}">
                        {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('supplier.create')<button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>+ Create</button>@endcan
            </div>
        </div>

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1">ບັນທຶກແລ້ວ ✓</div>

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-15rem)]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-500 shadow-sm">
                    <tr>
                        <th class="text-left font-medium px-4 py-2 w-full">Supplier</th>
                        <th class="text-left font-medium px-4 py-2">Contact</th>
                        <th class="text-left font-medium px-4 py-2 whitespace-nowrap">Currency</th>
                        <th class="text-left font-medium px-4 py-2 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2 whitespace-nowrap"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $s)
                        <tr wire:key="sup-{{ $s->id }}" class="border-t border-gray-100">
                            <td class="px-4 py-2 w-full">
                                <div class="font-medium text-gray-800 {{ $s->is_active ? '' : 'opacity-50' }}">{{ $s->name }}</div>
                                @if ($s->name_en)<div class="text-xs text-gray-400">{{ $s->name_en }}</div>@endif
                                @if ($showDeleted)
                                    <div class="text-[11px] text-red-600 mt-0.5">🗑 ລຶບ: {{ $s->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $s->deletedBy?->display_name ?? '—' }}@if ($s->deleted_reason) · ເຫດຜົນ: {{ $s->deleted_reason }}@endif</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-600">{{ $s->contact_person ?: '—' }}@if ($s->contact_phone)<div class="text-xs text-gray-400">{{ $s->contact_phone }}</div>@endif</td>
                            <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $s->default_currency }}</td>
                            <td class="px-4 py-2 whitespace-nowrap"><span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $s->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200' }}">{{ $s->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="px-4 py-2 text-right whitespace-nowrap text-gray-500">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $s->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <a href="{{ route('settings.suppliers.show', $s->id) }}" wire:navigate class="p-1 hover:text-sky-700 inline-block" title="Contracts / VAT"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></a>
                                    @canany(['supplier.activate', 'supplier.deactivate'])<button wire:click="toggle({{ $s->id }})" wire:confirm="ຢືນຢັນ ປ່ຽນ ສະຖານະ ໃຊ້ງານ (ເປີດ/ປິດ) ຂອງ ຜູ້ສະໜອງ ນີ້?" class="p-1 {{ $s->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $s->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                    @can('supplier.edit')<button wire:click="editItem({{ $s->id }})" class="p-1 hover:text-gray-800" aria-label="Edit"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('supplier.delete')<button wire:click="openDelete({{ $s->id }})" class="p-1 hover:text-red-600" aria-label="Delete"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ supplier ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ supplier — ກົດ + Create' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($items as $s)
                <div wire:key="m-{{ $s->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="font-medium text-gray-800 {{ $s->is_active ? '' : 'opacity-50' }}">{{ $s->name }}</div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $s->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200' }}">{{ $s->is_active ? 'active' : 'inactive' }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ $s->contact_person ?: '—' }} @if($s->contact_phone)· {{ $s->contact_phone }}@endif · {{ $s->default_currency }}</div>
                    @if ($showDeleted)
                        <div class="text-[11px] text-red-600 mt-1">🗑 ລຶບ: {{ $s->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $s->deletedBy?->display_name ?? '—' }}@if ($s->deleted_reason) · {{ $s->deleted_reason }}@endif</div>
                        <div class="flex gap-2 mt-2">
                            <button wire:click="restore({{ $s->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 min-h-[36px] hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                        </div>
                    @else
                        <div class="flex gap-2 mt-2">
                            <a href="{{ route('settings.suppliers.show', $s->id) }}" wire:navigate class="text-xs border rounded px-2 py-1 min-h-[36px] inline-flex items-center">Contracts</a>
                            @canany(['supplier.activate', 'supplier.deactivate'])<button wire:click="toggle({{ $s->id }})" wire:confirm="ຢືນຢັນ ປ່ຽນ ສະຖານະ ໃຊ້ງານ (ເປີດ/ປິດ) ຂອງ ຜູ້ສະໜອງ ນີ້?" class="text-xs border rounded px-2 py-1 min-h-[36px]">{{ $s->is_active ? 'Disable' : 'Enable' }}</button>@endcanany
                            @can('supplier.edit')<button wire:click="editItem({{ $s->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Edit</button>@endcan
                            @can('supplier.delete')<button wire:click="openDelete({{ $s->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Delete</button>@endcan
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">{{ $showDeleted ? 'ບໍ່ ມີ supplier ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີ supplier' }}</div>
            @endforelse
        </div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ supplier ນີ້?', 'subtitle' => $this->deletingRecord?->name])

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="sup-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🏬</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ supplier' : 'ສ້າງ supplier ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ (Name) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="block text-sm text-gray-600 mb-1">ຊື່ອັງກິດ (name_en)</label><input type="text" wire:model="name_en" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ສະກຸນເງິນ</label><select wire:model="default_currency" class="w-full rounded-md border-gray-300 text-sm"><option>LAK</option><option>THB</option><option>USD</option></select></div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">VAT (%) <span class="text-xs text-gray-400">· ວ່າງ = global</span></label>
                        <input type="number" step="0.01" min="0" max="100" wire:model="vat_rate" placeholder="global" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('vat_rate')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    @if ($editingId)
                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-600 mb-1">ເຫດຜົນປ່ຽນ VAT <span class="text-xs text-gray-400">(ບັງຄັບຖ້າປ່ຽນ rate)</span></label>
                            <input type="text" wire:model="vat_reason" placeholder="ເຊັ່ນ: ສັນຍາໃໝ່ 2026 / ນະໂຍບາຍລັດ" class="w-full rounded-md border-gray-300 text-sm" />
                            @error('vat_reason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div><label class="block text-sm text-gray-600 mb-1">ຜູ້ຕິດຕໍ່</label><input type="text" wire:model="contact_person" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ໂທ</label><input type="text" wire:model="contact_phone" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Email</label><input type="email" wire:model="contact_email" class="w-full rounded-md border-gray-300 text-sm" />@error('contact_email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm text-gray-600 mb-1">Tax ID</label><input type="text" wire:model="tax_id" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="md:col-span-2"><label class="block text-sm text-gray-600 mb-1">ທີ່ຢູ່</label><input type="text" wire:model="address" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="md:col-span-2"><label class="block text-sm text-gray-600 mb-1">ເງື່ອນໄຂຊຳລະ (payment terms)</label><input type="text" wire:model="payment_terms" placeholder="Net 30, COD…" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="md:col-span-2"><label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ</label><textarea wire:model="notes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 md:col-span-2"><input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> Active</label>

                    @if ($editingId)
                        <div class="md:col-span-2 border-t border-gray-100 pt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ເຫດຜົນ ການ ປ່ຽນແປງ <span class="text-red-500">*</span></label>
                            <textarea wire:model="changeReason" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-sky-500 focus:ring-sky-500" placeholder="ເປັນຫຍັງຈຶ່ງແກ້? (ຈະຖືກເກັບໃນ Audit log ແລະ ແຈ້ງ admin)"></textarea>
                            @error('changeReason')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-700 shadow-sm">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
