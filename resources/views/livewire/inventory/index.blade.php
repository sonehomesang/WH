@php
    $svgEdit = 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z';
    $svgPower = 'M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9';
    $svgTrash = 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16';
    $statusBadge = fn ($s) => match ($s) {
        'available' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'borrowed' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
        'maintenance' => 'bg-gray-50 text-gray-600 ring-1 ring-gray-200',
        'low-stock' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        default => 'bg-gray-50 text-gray-600 ring-1 ring-gray-200',
    };
    // stock state derived from qty vs min_quantity (matches the component filter)
    $stockState = fn ($it) => $it->quantity <= 0 ? 'out' : ($it->quantity <= $it->min_quantity ? 'low' : 'ok');
    $stockMeta = [
        'out' => ['ໝົດ', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
        'low' => ['ຕ່ຳ', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'ok' => ['ພຽງພໍ', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
    ];
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{ colsOpen: false, cols: $persist({ materialNo: true, brand: false, category: false, qty: true, stock: true, location: true, status: true }).as('wh_inv_cols_v2') }">
        @php
            $columns = [
                'materialNo' => 'Material No.',
                'brand' => 'Brand',
                'category' => 'Category',
                'qty' => 'Qty',
                'stock' => 'Stock',
                'location' => 'Location',
                'status' => 'Status',
            ];
        @endphp

        {{-- frozen header group: toolbar freezes; publish its bottom edge as
             --freeze-top so the table header sticks just under it --}}
        <div class="sticky top-16 z-30 bg-gray-100 pt-3" x-data
             x-init="const root = document.documentElement;
                     const set = () => root.style.setProperty('--freeze-top', (64 + $el.offsetHeight) + 'px');
                     set(); new ResizeObserver(set).observe($el);">
        {{-- total item count → teleported up into the app header top bar --}}
        <template x-teleport="#page-header-slot">
            <span class="hidden lg:inline-flex items-baseline gap-1.5 ml-2 px-2.5 py-1 rounded-lg bg-white/70 border border-white/70 shadow-sm">
                <span class="text-xl font-bold tabular-nums leading-none text-gray-800">{{ number_format($kpi['items']) }}</span>
                <span class="text-xs text-gray-500 whitespace-nowrap">ລາຍການ ທັງໝົດ</span>
            </span>
        </template>

        {{-- toolbar --}}
        <div class="flex flex-col gap-2 py-3 sm:py-0 sm:min-h-[52px] sm:flex-row sm:items-center sm:justify-between sm:gap-3">
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:flex-1 sm:min-w-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ Material No./ຊື່/description…" class="w-full sm:flex-1 sm:min-w-[12rem] sm:max-w-md rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                <select wire:model.live="prefixFilter" class="shrink-0 rounded-md border-gray-300 text-sm" title="ໝວດຕາມ Material No.">
                    <option value="">ທຸກໝວດ (Material No.)</option>
                    @foreach ($prefixCounts as $pc)
                        <option value="{{ $pc->prefix }}">{{ $pc->prefix }}xxxxxx ({{ number_format($pc->total) }})</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter" class="shrink-0 rounded-md border-gray-300 text-sm">
                    <option value="">ທຸກ status</option>
                    <option value="available">available</option>
                    <option value="borrowed">borrowed</option>
                    <option value="maintenance">maintenance</option>
                    <option value="low-stock">low-stock</option>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <select wire:model.live="perPage" class="shrink-0 rounded-md border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                    @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} {{ app()->getLocale() === 'en' ? 'rows' : 'ແຖວ' }}</option>@endforeach
                </select>
                <div class="relative hidden md:block" x-on:click.outside="colsOpen = false">
                    <button type="button" x-on:click="colsOpen = !colsOpen" class="text-sm text-gray-700 border border-gray-300 bg-white rounded-md px-3 py-2 min-h-[40px] hover:bg-gray-50 whitespace-nowrap">⚙ Columns</button>
                    <div x-show="colsOpen" x-cloak x-transition class="absolute right-0 z-20 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg p-2 space-y-1">
                        @foreach ($columns as $key => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 px-2 py-1 rounded hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" x-model="cols.{{ $key }}" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                @if ($canManageDeleted)
                    <button wire:click="toggleDeleted" class="text-sm border rounded-md px-3 py-2 min-h-[40px] whitespace-nowrap {{ $showDeleted ? 'bg-gray-700 text-white border-gray-700' : 'text-gray-600 border-gray-300 bg-white hover:bg-gray-50' }}">
                        {{ $showDeleted ? '← ລາຍການ ປົກກະຕິ' : '🗑 ບັນທຶກ ການ ລຶບ' }}
                    </button>
                @endif
                @can('inventory.create')<button wire:click="openImport" class="text-sm text-sky-700 border border-sky-300 bg-white rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-50 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>↥ Import CSV</button>@endcan
                @can('inventory.create')<button wire:click="newItem" class="text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap" @if ($showDeleted) style="display:none" @endif>+ Add</button>@endcan
            </div>
        </div>

        </div>{{-- /frozen header group --}}

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- Desktop table --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg">
            <table class="wh-list w-full text-sm">
                <thead class="sticky z-20 bg-slate-100 text-slate-600 border-b-2 border-slate-300 shadow-sm" style="top: var(--freeze-top, 14rem)">
                    <tr class="text-xs font-semibold uppercase tracking-wide">
                        <th x-show="cols.materialNo" x-cloak class="text-left font-semibold px-4 py-2 whitespace-nowrap">Material No.</th>
                        <th class="text-left font-semibold px-4 py-2 w-full">Item</th>
                        <th x-show="cols.brand" x-cloak class="text-left font-semibold px-4 py-2 whitespace-nowrap">Brand</th>
                        <th x-show="cols.category" x-cloak class="text-left font-semibold px-4 py-2 whitespace-nowrap">Category</th>
                        <th x-show="cols.qty" x-cloak class="text-left font-semibold px-4 py-2 whitespace-nowrap">Qty</th>
                        <th x-show="cols.stock" x-cloak class="text-left font-semibold px-4 py-2 whitespace-nowrap">Stock</th>
                        <th x-show="cols.location" x-cloak class="text-left font-semibold px-4 py-2 whitespace-nowrap">Location</th>
                        <th x-show="cols.status" x-cloak class="text-left font-semibold px-4 py-2 whitespace-nowrap">Status</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $it)
                        <tr wire:key="inv-{{ $it->id }}" class="border-t border-gray-200">
                            <td x-show="cols.materialNo" x-cloak class="px-4 py-2 font-mono text-sm font-bold text-gray-800 whitespace-nowrap">{{ $it->slug }}</td>
                            <td class="px-4 py-2 w-full">
                                <div class="flex items-center gap-2">
                                    @if ($photo = $it->primaryPhoto)
                                        <img src="{{ $photo->url }}" alt="" class="w-9 h-9 rounded object-cover border border-gray-200 shrink-0" />
                                    @endif
                                    <div>
                                        <div class="font-medium text-gray-800 {{ $it->is_active ? '' : 'opacity-50' }}" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;max-width:52ch" title="{{ $it->name }}">{{ $it->name }}</div>
                                        @if ($showDeleted)
                                            <div class="text-[11px] text-red-600 mt-0.5">🗑 ລຶບ: {{ $it->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $it->deletedBy?->display_name ?? '—' }}@if ($it->deleted_reason) · ເຫດຜົນ: {{ $it->deleted_reason }}@endif</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td x-show="cols.brand" x-cloak class="px-4 py-2 text-xs text-gray-600 whitespace-nowrap">{{ $it->brand ?: '—' }}</td>
                            <td x-show="cols.category" x-cloak class="px-4 py-2 text-xs text-gray-600 whitespace-nowrap">{{ $it->category ?: '—' }}</td>
                            <td x-show="cols.qty" x-cloak class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $it->quantity }}@if ($it->unit) {{ $it->unit }}@endif</td>
                            <td x-show="cols.stock" x-cloak class="px-4 py-2 whitespace-nowrap">@php $ss = $stockState($it); @endphp<span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $stockMeta[$ss][1] }}">{{ $stockMeta[$ss][0] }}</span></td>
                            <td x-show="cols.location" x-cloak class="px-4 py-2 text-gray-600 text-xs whitespace-nowrap">{{ collect([$it->location?->name, $it->building?->name, $it->room?->name, $it->shelf_label])->filter()->implode(' / ') ?: '—' }}</td>
                            <td x-show="cols.status" x-cloak class="px-4 py-2 whitespace-nowrap"><span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $statusBadge($it->status) }}">{{ $it->status }}</span>@if ($it->condition_status && $it->condition_status !== 'in_service')<span class="block mt-1 text-xs rounded-full px-2 py-0.5 {{ \App\Support\ConditionStatus::badge($it->condition_status) }}">{{ \App\Support\ConditionStatus::shortLabel($it->condition_status) }}</span>@endif</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap text-gray-500">
                                @if ($showDeleted)
                                    <button wire:click="restore({{ $it->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                                @else
                                    @canany(['inventory.activate', 'inventory.deactivate'])<button wire:click="toggle({{ $it->id }})" wire:confirm="ຢືນຢັນ ປ່ຽນ ສະຖານະ ໃຊ້ງານ (ເປີດ/ປິດ) ຂອງ item ນີ້?" class="p-1 {{ $it->is_active ? 'text-green-600 hover:text-gray-400' : 'text-gray-300 hover:text-green-600' }}" title="{{ $it->is_active ? 'Disable' : 'Enable' }}"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgPower }}" /></svg></button>@endcanany
                                    @can('inventory.edit')<button wire:click="editItem({{ $it->id }})" class="p-1 hover:text-gray-800" aria-label="Edit"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgEdit }}" /></svg></button>@endcan
                                    @can('disposal.create')<a href="{{ route('disposal.create', ['add' => 'inventory:' . $it->id]) }}" wire:navigate class="p-1 hover:text-red-600 inline-block" title="ຂໍ ຈຳໜ່າຍ (→ Disposal)"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg></a>@endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400">{{ $showDeleted ? 'ບໍ່ ມີ item ທີ່ ຖືກ ລຶບ' : 'ບໍ່ມີ item' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2">
            @forelse ($items as $it)
                <div wire:key="m-{{ $it->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if ($photo = $it->primaryPhoto)
                                <img src="{{ $photo->url }}" alt="" class="w-8 h-8 rounded object-cover border border-gray-200 shrink-0" />
                            @endif
                            <div class="font-medium text-gray-800 {{ $it->is_active ? '' : 'opacity-50' }}" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden" title="{{ $it->name }}">{{ $it->name }}</div>
                        </div>
                    </div>
                    <div class="text-[13px] text-sky-800 font-semibold mt-1"><span class="font-mono">{{ $it->slug }}</span> · {{ $it->unit }} · {{ collect([$it->location?->name, $it->building?->name, $it->room?->name, $it->shelf_label])->filter()->implode(' / ') ?: '—' }}</div>
                    @if ($showDeleted)
                        <div class="text-[11px] text-red-600 mt-1">🗑 ລຶບ: {{ $it->deleted_at?->format('d/m/Y H:i') }} · ໂດຍ {{ $it->deletedBy?->display_name ?? '—' }}@if ($it->deleted_reason) · {{ $it->deleted_reason }}@endif</div>
                        <div class="flex gap-2 mt-2">
                            <button wire:click="restore({{ $it->id }})" class="text-xs text-emerald-700 border border-emerald-200 rounded px-2 py-1 min-h-[36px] hover:bg-emerald-50">↩ ກູ້ຄືນ</button>
                        </div>
                    @else
                        <div class="flex gap-2 mt-2">
                            @canany(['inventory.activate', 'inventory.deactivate'])<button wire:click="toggle({{ $it->id }})" wire:confirm="ຢືນຢັນ ປ່ຽນ ສະຖານະ ໃຊ້ງານ (ເປີດ/ປິດ) ຂອງ item ນີ້?" class="text-xs border rounded px-2 py-1 min-h-[36px]">{{ $it->is_active ? 'Disable' : 'Enable' }}</button>@endcanany
                            @can('inventory.edit')<button wire:click="editItem({{ $it->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Edit</button>@endcan
                            @can('inventory.delete')<button wire:click="openDelete({{ $it->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Delete</button>@endcan
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">{{ $showDeleted ? 'ບໍ່ ມີ item ທີ່ ຖືກ ລຶບ' : 'ບໍ່ມີ item' }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $items->links() }}</div>
    </div>

    {{-- ຢືນຢັນ ລຶບ + ເຫດຜົນ (shared partial + trait SoftDeletesWithReason) --}}
    @include('partials._delete-modal', ['title' => 'ລຶບ item ນີ້?', 'subtitle' => $this->deletingRecord?->name])

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="inv-modal">
            <div class="bg-white w-full md:max-w-2xl rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">📦</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂ item' : 'ເພີ່ມ item ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="p-5 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ (Name) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 text-sm" />
                        @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="block text-sm text-gray-600 mb-1">Category</label><input type="text" wire:model="category" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Brand</label><input type="text" wire:model="brand" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Model</label><input type="text" wire:model="model" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Serial number</label><input type="text" wire:model="serial_number" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ຈຳນວນ (Qty) <span class="text-red-500">*</span></label><input type="number" min="0" wire:model="quantity" class="w-full rounded-md border-gray-300 text-sm" />@error('quantity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-sm text-gray-600 mb-1">Min qty <span class="text-red-500">*</span></label><input type="number" min="0" wire:model="min_quantity" class="w-full rounded-md border-gray-300 text-sm" />@error('min_quantity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                        <div><label class="block text-sm text-gray-600 mb-1">ໜ່ວຍ</label><select wire:model="unit" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($uoms as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach</select></div>
                    </div>
                    <div><label class="block text-sm text-gray-600 mb-1">Location</label><select wire:model.live="location_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Building</label><select wire:model.live="building_id" @disabled(! $location_id) class="w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50"><option value="">—</option>@foreach ($formBuildings as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Room</label><select wire:model="room_id" @disabled(! $building_id) class="w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50"><option value="">—</option>@foreach ($formRooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">Shelf</label><input type="text" wire:model="shelf_label" placeholder="A-3-2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ພະແນກ ເຈົ້າ ຂອງ / Department <span class="text-xs text-gray-400">(ໃຊ້ ຄຸມ ສິດ ຈຳໜ່າຍ)</span></label><select wire:model="department_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}@if ($d->unit) · {{ $d->unit->name }}@endif</option>@endforeach</select>@error('department_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm text-gray-600 mb-1">Status</label><select wire:model="status" class="w-full rounded-md border-gray-300 text-sm"><option value="available">available</option><option value="borrowed">borrowed</option><option value="maintenance">maintenance</option><option value="low-stock">low-stock</option></select></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ສະຖານະພາບ (Condition)</label><select wire:model="condition_status" class="w-full rounded-md border-gray-300 text-sm">@foreach (\App\Support\ConditionStatus::options() as $cv => $cl)<option value="{{ $cv }}">{{ $cl }}</option>@endforeach</select>@error('condition_status')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div class="md:col-span-2"><label class="block text-sm text-gray-600 mb-1">ລາຍລະອຽດ</label><textarea wire:model="description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea></div>

                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຮູບ (ສູງສຸດ {{ \App\Livewire\Inventory\Index::MAX_PHOTOS }} ໃບ · ≤4MB/ໃບ)</label>
                        @if (count($existingPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($existingPhotos as $p)
                                    <div wire:key="ep-{{ $p['id'] }}" class="relative">
                                        <img src="{{ $p['url'] }}" alt="" class="w-20 h-20 rounded-md object-cover border border-gray-200" />
                                        <button type="button" wire:click="removePhoto({{ $p['id'] }})" wire:confirm="ລຶບຮູບນີ້?" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs leading-none flex items-center justify-center" aria-label="ລຶບຮູບ">×</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if (count($newPhotos))
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach ($newPhotos as $i => $ph)
                                    <div wire:key="np-{{ $i }}">
                                        @if ($ph->isPreviewable())
                                            <img src="{{ $ph->temporaryUrl() }}" alt="" class="w-20 h-20 rounded-md object-cover border border-sky-300" />
                                        @else
                                            <div class="w-20 h-20 rounded-md border border-red-300 bg-red-50 text-red-500 text-[10px] flex items-center justify-center text-center px-1">ບໍ່ແມ່ນຮູບ</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <input type="file" wire:model="newPhotos" multiple accept="image/*" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-sky-50 file:text-sky-700 file:min-h-[40px]" />
                        <div wire:loading wire:target="newPhotos" class="text-xs text-gray-400 mt-1">ກຳລັງອັບໂຫລດ…</div>
                        @error('newPhotos.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @error('newPhotos')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700 md:col-span-2"><input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> Active</label>
                </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 border border-gray-300 bg-white rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-700 shadow-sm">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showImport)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="inv-import-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">↥</span>
                    <h3 class="text-base font-semibold text-gray-800">Import inventory ຈາກ CSV</h3>
                    <button wire:click="$set('showImport', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">

                <div class="text-xs text-gray-500 leading-relaxed bg-gray-50 rounded-md p-3">
                    Header ທີ່ຮອງຮັບ: <code class="text-gray-700">name*, description, category, brand, model, serial_number, quantity, min_quantity, unit, shelf_label, status, slug</code><br>
                    location/building/room = ປ່ອຍວ່າງ (NULL) · row ບໍ່ມີ name → ຂ້າມ · slug ຊ້ຳ → ຂ້າມ
                </div>

                <div>
                    <input type="file" wire:model="csvFile" accept=".csv,.txt" class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-sky-50 file:text-sky-700 file:min-h-[40px]" />
                    <div wire:loading wire:target="csvFile" class="text-xs text-gray-400 mt-1">ກຳລັງອັບໂຫລດ…</div>
                    @error('csvFile')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                @if ($importResult)
                    <div class="text-sm rounded-md border border-green-200 bg-green-50 p-3 space-y-1">
                        <div class="text-green-800 font-medium">✓ ນຳເຂົ້າ {{ $importResult['imported'] }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }} · ຂ້າມ {{ $importResult['skipped'] }}</div>
                        @if (count($importResult['errors']))
                            <details class="text-xs text-gray-600">
                                <summary class="cursor-pointer">ລາຍລະອຽດ {{ count($importResult['errors']) }} ຂໍ້ຄວາມ</summary>
                                <ul class="mt-1 list-disc list-inside max-h-40 overflow-y-auto">
                                    @foreach (array_slice($importResult['errors'], 0, 100) as $e)<li>{{ $e }}</li>@endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endif

                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showImport', false)" class="text-sm text-gray-700 border border-gray-300 bg-white rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ປິດ</button>
                    <button wire:click="importCsv" wire:loading.attr="disabled" wire:target="importCsv" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-700 shadow-sm disabled:opacity-50">
                        <span wire:loading.remove wire:target="importCsv">ນຳເຂົ້າ</span>
                        <span wire:loading wire:target="importCsv">ກຳລັງນຳເຂົ້າ…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
