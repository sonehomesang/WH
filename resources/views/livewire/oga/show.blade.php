@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'dispatched' => ['DISPATCHED', 'bg-amber-50 text-amber-700'],
        'delivered' => ['DELIVERED', 'bg-emerald-100 text-emerald-800'],
        'returned' => ['RETURNED', 'bg-red-50 text-red-700'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    [$slbl, $scls] = $statusMeta($record->status);
    $lbl = 'px-3 py-2 text-gray-500 text-xs bg-gray-50 border border-gray-200';
    $bd = 'px-3 py-2 border border-gray-200';
    $kindLabel = ['loaded' => 'ໂຫລດ', 'sealed' => 'ຜະນຶກ', 'paper_pli' => 'ເອກະສານ', 'delivered' => 'ສົ່ງເຖິງ', 'handover' => 'ມອບ', 'receipt' => 'ໃບຮັບ'];
    $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700';
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- ══ ONE frozen identity row (back · icon · record# · status · facts · actions) ══ --}}
        <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-2.5 bg-gradient-to-b from-amber-100 to-white border-b border-amber-200/70 backdrop-blur flex items-center gap-3 flex-wrap">
            <a href="{{ route('oga') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                <span class="hidden sm:inline">ລາຍການ OGA</span>
            </a>
            <span class="w-px h-5 bg-amber-200 shrink-0"></span>
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📤</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-base font-bold text-gray-900">{{ $record->oga_number }}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $scls }}">{{ $slbl }}</span>
                </div>
                <div class="hidden md:flex items-center gap-x-3 gap-y-0.5 flex-wrap text-[11px] text-gray-500 mt-0.5">
                    <span class="inline-flex items-center gap-1">📍 {{ $record->dispatch_to_name ?? '—' }}</span>
                    <span class="inline-flex items-center gap-1">🚚 {{ strtoupper($record->ship_via ?? '—') }}</span>
                    <span class="inline-flex items-center gap-1">🗓 {{ $record->date?->format('d/m/Y') ?? '—' }}</span>
                    <span class="inline-flex items-center gap-1">📦 {{ $record->items->count() }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }}</span>
                    @if ($record->source_da_number)<span class="inline-flex items-center gap-1 text-sky-700">DA {{ $record->source_da_number }}</span>@endif
                </div>
            </div>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                <a href="{{ route('oga.pdf', $record) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-gray-50">📄 PDF</a>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror
        @if ($record->status === 'returned' && $record->reject_reason)<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">ສົ່ງກັບ: {{ $record->reject_reason }}</div>@endif
        @if ($record->status === 'cancelled' && $record->cancel_reason)<div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">ຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif

        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4 text-sm">
            <div class="flex items-start justify-between border-b border-gray-200 pb-3">
                <div>
                    <div class="text-xs text-gray-400">NAM THEUN 2 — WAREHOUSE INFORMATION SYSTEM</div>
                    <div class="text-lg font-bold text-gray-800">ໃບສົ່ງເຄື່ອງອອກ / OUTWARDS GOODS ADVICE</div>
                    @if ($record->source_da_number)<div class="text-xs text-sky-600 mt-0.5">ມາຈາກ DA {{ $record->source_da_number }}</div>@endif
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-gray-800">{{ $record->oga_number }}</div>
                    <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 mt-1 {{ $scls }}">{{ $slbl }}</span>
                </div>
            </div>

            <table class="w-full border-collapse">
                <tbody>
                    <tr><td class="{{ $lbl }}" style="width:22%">ປາຍທາງ</td><td class="{{ $bd }}" style="width:28%">{{ $record->dispatch_to_name ?? '—' }}</td><td class="{{ $lbl }}" style="width:22%">Ship via</td><td class="{{ $bd }}" style="width:28%">{{ strtoupper($record->ship_via ?? '—') }}</td></tr>
                    <tr><td class="{{ $lbl }}">ທີ່ຢູ່</td><td class="{{ $bd }}" colspan="3">{{ $record->dispatch_to_address ?? '—' }} {{ $record->dispatch_to_phone }}</td></tr>
                    <tr><td class="{{ $lbl }}">ສິນຄ້າ</td><td class="{{ $bd }}" colspan="3">{{ $record->goods_consigned ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">ຂະໜາດ / ນ້ຳໜັກ</td><td class="{{ $bd }}">{{ $record->dimension ?? '—' }} · {{ $record->total_weight_kg ?? $record->gross_weight_kg ?? '—' }} kg</td><td class="{{ $lbl }}">PO</td><td class="{{ $bd }}">{{ $record->po_number ?? '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">ຄົນຂັບ / ລົດ</td><td class="{{ $bd }}" colspan="3">{{ $record->driver_name ?? '—' }} · {{ $record->truck_plate_number ?? '—' }}</td></tr>
                    @if ($record->reason_of_despatch)<tr><td class="{{ $lbl }}">ເຫດຜົນ</td><td class="{{ $bd }}" colspan="3">{{ $record->reason_of_despatch }}</td></tr>@endif
                </tbody>
            </table>

            <table class="w-full border-collapse">
                <thead><tr class="text-xs text-gray-500"><th class="{{ $bd }}">#</th><th class="{{ $bd }} text-left">ລາຍລະອຽດ</th><th class="{{ $bd }}">ໜ່ວຍ</th><th class="{{ $bd }}">ຈຳນວນ</th><th class="{{ $bd }} text-right">ນ້ຳໜັກ</th></tr></thead>
                <tbody>
                    @foreach ($record->items as $idx => $it)
                        <tr><td class="{{ $bd }} text-center">{{ $idx + 1 }}</td><td class="{{ $bd }}">{{ $it->description }}</td><td class="{{ $bd }} text-center">{{ $it->unit ?? '—' }}</td><td class="{{ $bd }} text-center">{{ $it->qty }}</td><td class="{{ $bd }} text-right">{{ $it->total_weight_kg ?? '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            @if ($record->photos->count())
                <div class="flex gap-3 flex-wrap">
                    @foreach ($record->photos as $p)
                        <div class="text-center"><img src="{{ $p->url }}" alt="" class="w-20 h-20 rounded object-cover border border-gray-200" /><div class="text-xs text-gray-400 mt-0.5">{{ $kindLabel[$p->kind] ?? $p->kind }}</div></div>
                    @endforeach
                </div>
            @endif

            @if ($record->history->count())
                <div><div class="font-semibold text-gray-700 mb-1">ປະຫວັດ / History</div>
                    <ol class="text-xs text-gray-500 space-y-0.5">@foreach ($record->history as $h)<li><span class="font-mono text-gray-700">{{ $h->status }}</span> · {{ $h->user_name }} · {{ $h->created_at?->format('d/m/Y H:i') }}@if ($h->comment) — {{ $h->comment }}@endif</li>@endforeach</ol>
                </div>
            @endif
        </div>

        {{-- actions --}}
        <div class="bg-white rounded-lg border border-gray-100 px-5 py-3 flex flex-wrap gap-2 text-sm items-center sticky bottom-4 z-20 shadow-lg">
            <span class="text-gray-400 mr-1">Actions:</span>
            @if ($record->status === 'draft')
                @if ($editable)<button wire:click="openItems" class="text-sky-700 border border-sky-200 bg-sky-50 rounded px-3 py-1.5">✏️ ແກ້ ລາຍການ</button>@endif
                @if ($editable)<button wire:click="openDispatch" class="text-white bg-amber-600 rounded px-3 py-1.5">Dispatch</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'dispatched')
                @if ($editable)<button wire:click="openDelivery" class="text-white bg-emerald-600 rounded px-3 py-1.5">ຢືນຢັນສົ່ງເຖິງ (delivered)</button><button wire:click="$set('showReturn', true)" class="text-red-700 border border-red-200 bg-red-50 rounded px-3 py-1.5">ສົ່ງກັບ (returned)</button>@endif
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif
            @if ($deletable)<span class="ml-auto"></span><button wire:click="openDelete" class="text-red-600 border border-red-200 rounded px-3 py-1.5 hover:bg-red-50">🗑 ລຶບ</button>@endif
        </div>

        {{-- modals --}}
        @if ($showDispatch)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-amber-200 to-amber-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📤</span>
                    <h3 class="text-base font-semibold text-gray-800">Dispatch — ຄົນຂັບ/ລົດ</h3>
                    <button wire:click="$set('showDispatch', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <input type="text" wire:model="driverName" placeholder="ຄົນຂັບ" class="w-full rounded-md border-gray-300 text-sm" />
                    <input type="text" wire:model="truckPlate" placeholder="ປ້າຍລົດ" class="w-full rounded-md border-gray-300 text-sm" />
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDispatch', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="confirmDispatch" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm">ຢືນຢັນ Dispatch</button></div>
            </div></div>
        @endif
        @if ($showDelivery)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-amber-200 to-amber-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📤</span>
                    <h3 class="text-base font-semibold text-gray-800">ຢືນຢັນສົ່ງເຖິງ — ຮູບ (delivery)</h3>
                    <button wire:click="$set('showDelivery', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <div><label class="block text-xs text-gray-500 mb-1">ສົ່ງເຖິງ (delivered)</label><input type="file" wire:model="photoDelivered" accept="image/*" class="{{ $fileCls }}" /></div>
                    <div><label class="block text-xs text-gray-500 mb-1">ມອບເຄື່ອງ (handover)</label><input type="file" wire:model="photoHandover" accept="image/*" class="{{ $fileCls }}" /></div>
                    <div><label class="block text-xs text-gray-500 mb-1">ໃບຮັບ (receipt)</label><input type="file" wire:model="photoReceipt" accept="image/*" class="{{ $fileCls }}" /></div>
                    <div wire:loading wire:target="photoDelivered,photoHandover,photoReceipt" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDelivery', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="confirmDelivery" wire:loading.attr="disabled" wire:target="confirmDelivery,photoDelivered,photoHandover,photoReceipt" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm disabled:opacity-50">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showReturn)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-amber-200 to-amber-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📤</span>
                    <h3 class="text-base font-semibold text-gray-800">ສົ່ງກັບ (returned)</h3>
                    <button wire:click="$set('showReturn', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <textarea wire:model="returnReason" rows="3" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showReturn', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="returnRejected" class="bg-red-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-amber-200 to-amber-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📤</span>
                    <h3 class="text-base font-semibold text-gray-800">ຍົກເລີກ OGA</h3>
                    <button wire:click="$set('showCancel', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showCancel', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="cancel" class="bg-red-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-amber-200 to-amber-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑</span>
                    <h3 class="text-base font-semibold text-red-700">🗑 ລຶບ OGA</h3>
                    <button wire:click="$set('showDelete', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    @error('deleteReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDelete', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="deleteRecord" class="bg-red-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ຢືນຢັນລຶບ</button></div>
            </div></div>
        @endif
        @if ($showItems)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4"><div class="bg-white w-full md:max-w-2xl rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-amber-200 to-amber-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📤</span>
                    <h3 class="text-base font-semibold text-gray-800">✏️ ແກ້ ລາຍການ (ຮ່າງ)</h3>
                    <button wire:click="$set('showItems', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                <div class="space-y-2">
                    @foreach ($oi as $i => $row)
                        <div wire:key="oi-{{ $i }}" class="border border-gray-100 rounded-lg p-2 grid grid-cols-12 gap-2 items-start">
                            <input type="text" wire:model="oi.{{ $i }}.description" placeholder="ລາຍລະອຽດ *" class="col-span-6 rounded-md border-gray-300 text-xs" />
                            <input type="text" wire:model="oi.{{ $i }}.unit" placeholder="ໜ່ວຍ" class="col-span-2 rounded-md border-gray-300 text-xs" />
                            <input type="number" min="1" wire:model="oi.{{ $i }}.qty" title="ຈຳນວນ" class="col-span-1 rounded-md border-gray-300 text-xs" />
                            <input type="number" step="0.01" min="0" wire:model="oi.{{ $i }}.unit_weight_kg" title="ໜັກ/ໜ່ວຍ (kg)" placeholder="kg" class="col-span-2 rounded-md border-gray-300 text-xs" />
                            <button wire:click="removeOiItem({{ $i }})" class="col-span-1 text-red-400 hover:text-red-600 text-sm">✕</button>
                        </div>
                    @endforeach
                </div>
                <button wire:click="addOiItem" class="text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-1.5 hover:bg-sky-50">+ ເພີ່ມ ລາຍການ</button>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showItems', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="saveItems" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm">ບັນທຶກ</button></div>
            </div></div>
        @endif
    </div>
</div>
