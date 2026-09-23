@php
    $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700';
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgPower = 'M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9';
    $svgTrash = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
    $svgClock = 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z';
@endphp

<div class="pb-6">
    {{-- box-shadow (not border-bottom) so the sticky column-header divider stays
         glued to the header under border-collapse — a real border is "owned" by
         the first body row and scrolls away. --}}
    <style>.catalog-list thead th { box-shadow: inset 0 -2px 0 #cbd5e1; }</style>
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- frozen header group: toolbar + chips freeze; publish its bottom edge
             as --freeze-top so the table header sticks just under it --}}
        <div class="sticky top-16 z-30 bg-gray-100 pt-3 pb-2" x-data
             x-init="const root = document.documentElement;
                     const set = () => root.style.setProperty('--freeze-top', (64 + $el.offsetHeight) + 'px');
                     set(); new ResizeObserver(set).observe($el);">
        {{-- total material count → teleported up into the app header top bar --}}
        <template x-teleport="#page-header-slot">
            <span class="hidden lg:inline-flex items-baseline gap-1.5 ml-2 px-2.5 py-1 rounded-lg bg-white/70 border border-white/70 shadow-sm">
                <span class="text-xl font-bold tabular-nums leading-none text-gray-800">{{ number_format($kpi[0]['value']) }}</span>
                <span class="text-xs text-gray-500 whitespace-nowrap">ສິນຄ້າ ທັງໝົດ</span>
            </span>
        </template>
        {{-- toolbar --}}
        <div class="flex flex-col gap-2 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:flex-nowrap">
            <div class="flex flex-wrap items-center gap-2 min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ລະຫັດ/ລາຍລະອຽດ/ປະເພດ…" class="w-52 rounded-md border-gray-300 shadow-sm text-sm" />
                <select wire:model.live="supplierFilter" class="w-40 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ Supplier</option>
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
                <select wire:model.live="categoryFilter" class="w-36 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ ປະເພດ</option>
                    @foreach ($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                </select>
                <select wire:model.live="statusFilter" class="w-28 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ status</option>
                    <option value="active">active</option>
                    <option value="inactive">inactive</option>
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <select wire:model.live="perPage" class="shrink-0 rounded-md border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                    @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} {{ app()->getLocale() === 'en' ? 'rows' : 'ແຖວ' }}</option>@endforeach
                </select>
                @if ($canManageDeleted)
                    <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 bg-white hover:bg-gray-50' }}">
                        {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('catalog.create')<button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>+ Add</button>@endcan
            </div>
        </div>

        @include('partials._status-chips', ['chips' => $chips, 'current' => $statusFilter, 'trailing' => number_format($materials->total()).' '.(app()->getLocale() === 'en' ? 'items' : 'ລາຍການ')])

        {{-- reference counts the status chips don't cover (kept from the old KPI band) --}}
        <div class="-mt-0.5 mb-2 flex flex-wrap items-center gap-1.5">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white text-gray-600 px-2.5 py-1 text-sm">🏢 suppliers <span class="font-semibold tabular-nums">{{ number_format($kpi[3]['value']) }}</span></span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white text-gray-600 px-2.5 py-1 text-sm">🗂️ {{ app()->getLocale() === 'en' ? 'categories' : 'ປະເພດ' }} <span class="font-semibold tabular-nums">{{ number_format($kpi[4]['value']) }}</span></span>
        </div>
        </div>{{-- /frozen header group --}}

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 mb-2">{{ session('ok') }}</div>@endif

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg">
            <table class="wh-list catalog-list w-full text-sm">
                <thead class="sticky z-20 bg-slate-100 text-slate-600" style="top: var(--freeze-top, 14rem)">
                    <tr class="text-xs font-semibold uppercase tracking-wide">
                        <th class="text-left font-semibold px-4 py-2">ລະຫັດ <span class="text-gray-400">(Material No.)</span></th>
                        <th class="text-left font-semibold px-4 py-2 w-full">ສິນຄ້າ <span class="text-gray-400">(Item)</span></th>
                        <th class="text-left font-semibold px-4 py-2">Supplier</th>
                        <th class="text-left font-semibold px-4 py-2">ລາຄາ <span class="text-gray-400">(Price)</span></th>
                        <th class="text-left font-semibold px-4 py-2">ສັນຍາ / ອັບເດດ</th>
                        <th class="text-left font-semibold px-4 py-2">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($materials as $m)
                        @php $img = $m->images->first(); @endphp
                        <tr wire:key="mat-{{ $m->id }}" class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $m->material_nbr ?? '—' }}</td>
                            <td class="px-4 py-2 w-full">
                                <div class="flex items-center gap-2">
                                    @if ($img)<img src="{{ $img->url }}" alt="" class="w-9 h-9 rounded object-cover border border-gray-200 shrink-0" />
                                    @else<div class="w-9 h-9 rounded bg-gray-100 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-xs">🏷</div>@endif
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 {{ $m->is_active ? '' : 'opacity-50' }}">{{ Str::limit($m->description, 60) }}</div>
                                        <div class="text-xs text-gray-400">{{ $m->category }}@if ($m->unit) · {{ $m->unit }}@endif</div>
                                        @if ($showDeleted)
                                            <div class="text-[11px] text-red-600 mt-0.5">🗑 ລຶບ: {{ $m->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $m->deletedBy?->display_name ?? '—' }}@if ($m->deleted_reason) · ເຫດຜົນ: {{ $m->deleted_reason }}@endif</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-600">{{ $m->supplier?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-700">
                                @if ($m->unit_price !== null)<span class="font-medium">{{ number_format($m->unit_price, 2) }}</span> <span class="text-xs text-gray-400">{{ $m->currency }}</span>@else—@endif
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500">
                                <div>{{ $m->contract_number ?? '—' }}</div>
                                <div class="text-gray-400">{{ $m->last_price_update?->format('d/m/Y') ?? '' }}</div>
                            </td>
                            <td class="px-4 py-2"><span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $m->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-50 text-gray-500 ring-1 ring-gray-200' }}">{{ $m->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="px-4 py-2 text-right whitespace-nowrap text-gray-500">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $m->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    <button wire:click="openHistory({{ $m->id }})" class="p-1 hover:text-sky-600" title="ປະຫວັດລາຄາ" aria-label="Price history"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgClock }}" /></svg></button>
                                    @canany(['catalog.activate', 'catalog.deactivate'])<button wire:click="toggle({{ $m->id }})" class="p-1 {{ $m->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $m->is_active ? 'Disable' : 'Enable' }}" aria-label="{{ $m->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                    @can('catalog.edit')<button wire:click="editItem({{ $m->id }})" class="p-1 hover:text-gray-800" title="Edit" aria-label="Edit"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('catalog.delete')<button wire:click="openDelete({{ $m->id }})" class="p-1 hover:text-red-600" title="Delete" aria-label="Delete"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgTrash }}" /></svg></button>@endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ ສິນຄ້າ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີສິນຄ້າ' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($materials as $m)
                @php $img = $m->images->first(); @endphp
                <div wire:key="mmat-{{ $m->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex gap-2 min-w-0">
                            @if ($img)<img src="{{ $img->url }}" alt="" class="w-10 h-10 rounded object-cover border border-gray-200 shrink-0" />@endif
                            <div class="min-w-0">
                                <div class="font-mono text-xs text-gray-400">{{ $m->material_nbr ?? '—' }}</div>
                                <div class="font-medium text-gray-800 truncate">{{ Str::limit($m->description, 50) }}</div>
                                <div class="text-xs text-gray-500">{{ $m->supplier?->name }} · {{ $m->unit_price !== null ? number_format($m->unit_price, 2).' '.$m->currency : '—' }}</div>
                            </div>
                        </div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 shrink-0 {{ $m->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-50 text-gray-500 ring-1 ring-gray-200' }}">{{ $m->is_active ? 'active' : 'inactive' }}</span>
                    </div>
                    @if ($showDeleted)
                        <div class="text-[11px] text-red-600 mt-1">🗑 ລຶບ: {{ $m->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $m->deletedBy?->display_name ?? '—' }}@if ($m->deleted_reason) · {{ $m->deleted_reason }}@endif</div>
                        <div class="flex gap-2 mt-2">
                            <button wire:click="restore({{ $m->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 min-h-[36px] hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                        </div>
                    @else
                        <div class="flex gap-2 mt-2">
                            <button wire:click="openHistory({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">ປະຫວັດລາຄາ</button>
                            @can('catalog.edit')<button wire:click="editItem({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Edit</button>@endcan
                            @canany(['catalog.activate', 'catalog.deactivate'])<button wire:click="toggle({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">{{ $m->is_active ? 'Disable' : 'Enable' }}</button>@endcanany
                            @can('catalog.delete')<button wire:click="openDelete({{ $m->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Delete</button>@endcan
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">{{ $showDeleted ? 'ບໍ່ ມີ ສິນຄ້າ ທີ່ ຖືກ ລຶບ' : 'ຍັງບໍ່ມີສິນຄ້າ' }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $materials->links() }}</div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ ສິນຄ້າ ນີ້?', 'subtitle' => $this->deletingRecord?->description])

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="mat-modal">
            <div class="bg-white w-full md:max-w-2xl rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🏷️</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂສິນຄ້າ' : 'ເພີ່ມສິນຄ້າ' }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
                    <div>
                        <label class="block text-gray-600 mb-1">Supplier <span class="text-red-500">*</span></label>
                        <select wire:model="supplier_id" class="w-full rounded-md border-gray-300 text-sm" @disabled($this->isSupplierScoped())>
                            <option value="">— ເລືອກ —</option>
                            @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                        @error('supplier_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ລະຫັດສິນຄ້າ (Material No.)</label>
                        <input type="text" wire:model="material_nbr" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ປະເພດ (Category) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="category" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('category')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ໜ່ວຍ (UoM)</label>
                        <select wire:model="unit" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">—</option>
                            @foreach ($uoms as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-600 mb-1">ລາຍລະອຽດ (Description) <span class="text-red-500">*</span></label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                        @error('description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ລາຄາ (net, ບໍ່ລວມ VAT)</label>
                        <input type="number" step="0.01" min="0" wire:model="unit_price" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('unit_price')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ສະກຸນເງິນ</label>
                        <select wire:model="currency" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="THB">THB (ບາດ)</option>
                            <option value="LAK">LAK (ກີບ)</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">Lead time</label>
                        <input type="text" wire:model="lead_time" placeholder="ເຊັ່ນ: 7-14 ມື້" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ເລກສັນຍາ (Contract No.)</label>
                        <input type="text" wire:model="contract_number" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ວັນທີສັນຍາ</label>
                        <input type="date" wire:model="contract_date" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ສັນຍາ ເລີ່ມ</label>
                        <input type="date" wire:model="contract_start_date" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">ສັນຍາ ສິ້ນສຸດ</label>
                        <input type="date" wire:model="contract_end_date" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-600 mb-1">ໝາຍເຫດ</label>
                        <textarea wire:model="remark" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-gray-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600" /> ເປີດໃຊ້ (active)
                    </label>
                </div>

                {{-- photos --}}
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຮູບສິນຄ້າ (≤10)</label>
                    @if ($editingId)
                        @php $existing = \App\Models\Material::find($editingId)?->images ?? collect(); @endphp
                        @if ($existing->count())
                            <div class="flex gap-1.5 flex-wrap mb-2">
                                @foreach ($existing as $p)
                                    <div class="relative"><img src="{{ $p->url }}" alt="" class="w-14 h-14 rounded object-cover border border-gray-200" />
                                        @can('catalog.edit')<button wire:click="removePhoto({{ $p->id }})" wire:confirm="ລຶບຮູບນີ້?" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-600 text-white rounded-full text-[10px] leading-none">×</button>@endcan
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                    <input type="file" wire:model="newPhotos" multiple accept="image/*" class="{{ $fileCls }}" />
                    <div wire:loading wire:target="newPhotos" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                    @error('newPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    @if (! empty($newPhotos))<div class="flex gap-1 flex-wrap mt-1">@foreach ($newPhotos as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" class="w-12 h-12 rounded object-cover border border-sky-200" />@endif @endforeach</div>@endif
                </div>

                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 bg-white rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save,newPhotos" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-700 shadow-sm disabled:opacity-50">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    {{-- price history modal --}}
    @if ($showHistory && $historyMaterial)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-2xl border border-gray-300 shadow-lg overflow-hidden w-full max-w-md max-h-[80vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🕑</span>
                    <h3 class="text-base font-semibold text-gray-800 truncate">ປະຫວັດລາຄາ — {{ Str::limit($historyMaterial->description, 40) }}</h3>
                    <button wire:click="$set('showHistory', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-1 overflow-y-auto">
                @forelse ($historyMaterial->priceHistory as $h)
                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 text-sm">
                        <div>
                            <span class="font-medium text-gray-800">{{ number_format($h->unit_price, 2) }} {{ $h->currency }}</span>
                            @if ($h->contract_number)<span class="text-xs text-gray-400">· {{ $h->contract_number }}</span>@endif
                        </div>
                        <div class="text-xs text-gray-400">{{ $h->update_date?->format('d/m/Y') }}</div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">ຍັງບໍ່ມີປະຫວັດ</p>
                @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
