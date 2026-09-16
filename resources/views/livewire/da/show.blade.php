@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700'],
        'purchasing_review' => ['PURCHASING REVIEW', 'bg-violet-50 text-violet-700'],
        'pending_approval' => ['PENDING APPROVAL', 'bg-amber-50 text-amber-700'],
        'resolved' => ['RESOLVED', 'bg-emerald-100 text-emerald-800'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    [$slbl, $scls] = $statusMeta($record->status);
    $lbl = 'px-3 py-2 text-gray-500 text-xs bg-gray-50 border border-gray-200';
    $bd = 'px-3 py-2 border border-gray-200';
    $typeLabel = ['incorrect_supplied' => 'ສົ່ງຜິດ', 'oversupplied' => 'ສົ່ງເກີນ', 'undersupplied' => 'ສົ່ງຂາດ', 'damaged' => 'ເສຍຫາຍ', 'no_paperwork' => 'ບໍ່ມີເອກະສານ', 'other' => 'ອື່ນໆ'];
    $kindLabel = ['overview' => 'ພາບຮວມ', 'defect' => 'ຈຸດເສຍ', 'comparison' => 'ປຽບທຽບ'];
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- ══ ONE frozen identity row (back · icon · record# · status · facts · actions) ══ --}}
        <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-2.5 bg-gradient-to-b from-indigo-100 to-white border-b border-indigo-200/70 backdrop-blur flex items-center gap-3 flex-wrap">
            <a href="{{ route('da') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                <span class="hidden sm:inline">ລາຍການ DA</span>
            </a>
            <span class="w-px h-5 bg-indigo-200 shrink-0"></span>
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🧾</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-base font-bold text-gray-900">{{ $record->da_number }}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $scls }}">{{ $slbl }}</span>
                </div>
                <div class="hidden md:flex items-center gap-x-3 gap-y-0.5 flex-wrap text-[11px] text-gray-500 mt-0.5">
                    <span class="inline-flex items-center gap-1">🏢 {{ $record->supplier_name ?? '—' }}</span>
                    <span class="inline-flex items-center gap-1">PO {{ $record->po_number ?? '—' }}</span>
                    <span class="inline-flex items-center gap-1">🗓 {{ $record->date?->format('d/m/Y') ?? '—' }}</span>
                    <span class="inline-flex items-center gap-1">📦 {{ $record->items->count() }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }}</span>
                    @if ($record->next_step === 'oga')<span class="inline-flex items-center gap-1 text-sky-700">→ OGA</span>@endif
                </div>
            </div>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                <a href="{{ route('da.pdf', $record) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-1.5 min-h-[36px] hover:bg-gray-50">📄 PDF</a>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $message }}</div>@enderror
        @if ($record->reject_reason && $record->status === 'purchasing_review')<div class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">⚠ Leader ສົ່ງກັບ: {{ $record->reject_reason }}</div>@endif
        @if ($record->status === 'cancelled' && $record->cancel_reason)<div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">ຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif

        <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4 text-sm">
            <div class="flex items-start justify-between border-b border-gray-200 pb-3">
                <div>
                    <div class="text-xs text-gray-400">NAM THEUN 2 — WAREHOUSE INFORMATION SYSTEM</div>
                    <div class="text-lg font-bold text-gray-800">ໃບແຈ້ງຄວາມຜິດ / DISCREPANCY ADVICE</div>
                </div>
                <div class="text-right">
                    <div class="font-mono font-bold text-gray-800">{{ $record->da_number }}</div>
                    <span class="inline-flex items-center gap-1 text-xs font-medium rounded-full px-2.5 py-1 mt-1 {{ $scls }}">{{ $slbl }}</span>
                    @if ($record->next_step === 'oga')<div class="text-xs text-sky-600 mt-1">→ ສ້າງ OGA</div>@endif
                </div>
            </div>

            {{-- A --}}
            <div class="text-xs font-semibold text-gray-500 uppercase">A · Warehouse</div>
            <table class="w-full border-collapse">
                <tbody>
                    <tr><td class="{{ $lbl }}" style="width:22%">Supplier</td><td class="{{ $bd }}" style="width:28%">{{ $record->supplier_name ?? '—' }}</td><td class="{{ $lbl }}" style="width:22%">PO</td><td class="{{ $bd }}" style="width:28%">{{ $record->po_number ?? '—' }} @if ($record->po_date)({{ $record->po_date->format('d/m/Y') }})@endif</td></tr>
                    <tr><td class="{{ $lbl }}">ປະເພດຄວາມຜິດ</td><td class="{{ $bd }}" colspan="3">{{ collect($record->discrepancy_types ?? [])->map(fn ($t) => $typeLabel[$t] ?? $t)->implode(', ') ?: '—' }}</td></tr>
                    <tr><td class="{{ $lbl }}">Packing list</td><td class="{{ $bd }}">{{ $record->vendor_packing_list ?? '—' }}</td><td class="{{ $lbl }}">Vendor invoice</td><td class="{{ $bd }}">{{ $record->vendor_invoice_number ?? '—' }}</td></tr>
                    @if ($record->warehouse_recommendation_kind || $record->warehouse_recommendation_text)<tr><td class="{{ $lbl }}">ຄຳແນະນຳ</td><td class="{{ $bd }}" colspan="3">{{ $record->warehouse_recommendation_kind }} — {{ $record->warehouse_recommendation_text }}</td></tr>@endif
                    @if ($record->comments)<tr><td class="{{ $lbl }}">ໝາຍເຫດ</td><td class="{{ $bd }}" colspan="3">{{ $record->comments }}</td></tr>@endif
                </tbody>
            </table>

            {{-- items --}}
            <table class="w-full border-collapse">
                <thead><tr class="text-xs text-gray-500"><th class="{{ $bd }}">#</th><th class="{{ $bd }} text-left">Stock</th><th class="{{ $bd }} text-left">ລາຍລະອຽດ</th><th class="{{ $bd }}">ສັ່ງ</th><th class="{{ $bd }}">ສົ່ງ</th><th class="{{ $bd }}">ຮັບ</th></tr></thead>
                <tbody>
                    @foreach ($record->items as $idx => $it)
                        <tr><td class="{{ $bd }} text-center">{{ $idx + 1 }}</td><td class="{{ $bd }}">{{ $it->stock_code ?? '—' }}</td><td class="{{ $bd }}">{{ $it->description }}</td><td class="{{ $bd }} text-center">{{ $it->qty_ordered }}</td><td class="{{ $bd }} text-center">{{ $it->qty_delivered }}</td><td class="{{ $bd }} text-center">{{ $it->qty_received }}</td></tr>
                    @endforeach
                </tbody>
            </table>

            {{-- photos --}}
            @if ($record->photos->count())
                <div class="flex gap-3 flex-wrap">
                    @foreach ($record->photos as $p)
                        <div class="text-center"><img src="{{ $p->url }}" alt="" class="w-24 h-24 rounded object-cover border border-gray-200" /><div class="text-xs text-gray-400 mt-0.5">{{ $kindLabel[$p->kind] ?? $p->kind }}</div></div>
                    @endforeach
                </div>
            @endif

            {{-- B --}}
            @if (in_array($record->status, ['pending_approval', 'resolved'], true) || $record->purchasing_decisions)
                <div class="text-xs font-semibold text-gray-500 uppercase pt-2 border-t border-gray-100">B · Purchasing</div>
                <table class="w-full border-collapse">
                    <tbody>
                        <tr><td class="{{ $lbl }}" style="width:22%">ການຕັດສິນໃຈ</td><td class="{{ $bd }}" colspan="3">{{ collect($record->purchasing_decisions ?? [])->implode(', ') ?: '—' }}</td></tr>
                        @if ($record->purchasing_note)<tr><td class="{{ $lbl }}">ໝາຍເຫດ</td><td class="{{ $bd }}" colspan="3">{{ $record->purchasing_note }}</td></tr>@endif
                        @if ($record->return_transport_account)<tr><td class="{{ $lbl }}">ການສົ່ງຄືນ</td><td class="{{ $bd }}" colspan="3">{{ $record->return_transport_account }} / {{ $record->return_transport_mode }} · {{ $record->return_carrier_name }} {{ $record->return_carrier_phone }}</td></tr>@endif
                    </tbody>
                </table>
            @endif

            {{-- C --}}
            @if ($record->status === 'resolved')
                <div class="text-xs font-semibold text-gray-500 uppercase pt-2 border-t border-gray-100">C · Leader</div>
                <table class="w-full border-collapse">
                    <tbody>
                        <tr><td class="{{ $lbl }}" style="width:22%">Resolution</td><td class="{{ $bd }}" colspan="3">{{ $record->resolution_action ?? '—' }}</td></tr>
                        <tr><td class="{{ $lbl }}">ອະນຸມັດ</td><td class="{{ $bd }}">{{ $record->approved_by_name }} ({{ $record->approved_title }})</td><td class="{{ $lbl }}">Next</td><td class="{{ $bd }}">{{ $record->next_step }}</td></tr>
                    </tbody>
                </table>
            @endif

            @if ($linkedOgas->count())
                <div class="border border-sky-200 bg-sky-50/50 rounded-md p-3">
                    <div class="font-semibold text-gray-700 mb-1">🚚 OGA ທີ່ສ້າງຈາກ DA ນີ້</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($linkedOgas as $o)
                            <a href="{{ route('oga.show', $o->id) }}" wire:navigate class="font-mono text-xs text-sky-700 border border-sky-200 bg-white rounded px-2 py-1 hover:bg-sky-100">{{ $o->oga_number }} <span class="text-gray-400">({{ $o->status }})</span></a>
                        @endforeach
                    </div>
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
                <button wire:click="submit" class="text-white bg-sky-600 rounded px-3 py-1.5">ສົ່ງ</button>
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'submitted')
                @if ($canAct)<button wire:click="purchasingStart" class="text-white bg-violet-600 rounded px-3 py-1.5">ເລີ່ມ Purchasing review</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'purchasing_review')
                @if ($canAct)<button wire:click="$set('showDecide', true)" class="text-white bg-amber-600 rounded px-3 py-1.5">ບັນທຶກການຕັດສິນໃຈ</button>@endif
                <button wire:click="$set('showCancel', true)" class="border rounded px-3 py-1.5">ຍົກເລີກ</button>
            @elseif ($record->status === 'pending_approval')
                @if ($canAct)<button wire:click="$set('showApprove', true)" class="text-white bg-emerald-600 rounded px-3 py-1.5">ອະນຸມັດ</button><button wire:click="$set('showReject', true)" class="text-amber-700 border border-amber-200 bg-amber-50 rounded px-3 py-1.5">ສົ່ງກັບ</button>@endif
            @elseif ($record->status === 'resolved' && $record->next_step === 'oga' && $editable)
                <a href="{{ route('oga.create', ['da' => $record->id]) }}" wire:navigate class="inline-flex items-center gap-1 text-white bg-sky-600 rounded px-3 py-1.5 hover:bg-sky-700">🚚 ສ້າງ OGA ຈາກ DA</a>
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif
            @if ($deletable)<span class="ml-auto"></span><button wire:click="openDelete" class="text-red-600 border border-red-200 rounded px-3 py-1.5 hover:bg-red-50">🗑 ລຶບ</button>@endif
        </div>

        {{-- modals --}}
        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🧾</span>
                    <h3 class="text-base font-semibold text-gray-800">ຍົກເລີກ DA</h3>
                    <button wire:click="$set('showCancel', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showCancel', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="cancel" class="bg-red-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showDecide)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🧾</span>
                    <h3 class="text-base font-semibold text-gray-800">B · ການຕັດສິນໃຈ Purchasing</h3>
                    <button wire:click="$set('showDecide', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <div class="space-y-1">@foreach ($decisionOptions as $d)<label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $d }}" wire:model="decisions" class="rounded border-gray-300 text-sky-600" /> {{ $d }}</label>@endforeach</div>
                    <textarea wire:model="purchasingNote" rows="2" placeholder="ໝາຍເຫດ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    <div class="grid grid-cols-2 gap-2">
                        <select wire:model="transportAccount" class="rounded-md border-gray-300 text-sm"><option value="">ບັນຊີຂົນສົ່ງ</option><option value="vendor">vendor</option><option value="ntpc">ntpc</option></select>
                        <select wire:model="transportMode" class="rounded-md border-gray-300 text-sm"><option value="">ວິທີ</option><option value="road">road</option><option value="air">air</option></select>
                        <input type="text" wire:model="carrierName" placeholder="Carrier" class="rounded-md border-gray-300 text-sm" />
                        <input type="text" wire:model="carrierPhone" placeholder="Phone" class="rounded-md border-gray-300 text-sm" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDecide', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="purchasingDecide" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showApprove)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🧾</span>
                    <h3 class="text-base font-semibold text-gray-800">C · ອະນຸມັດ (Leader)</h3>
                    <button wire:click="$set('showApprove', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <textarea wire:model="resolution" rows="2" placeholder="Resolution action…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    <input type="text" wire:model="approvedTitle" placeholder="ຕຳແໜ່ງ (e.g. Leader Warehousing)" class="w-full rounded-md border-gray-300 text-sm" />
                    <div><label class="block text-sm text-gray-600 mb-1">Next step</label><select wire:model="nextStep" class="w-full rounded-md border-gray-300 text-sm"><option value="finished">ປິດ (finished)</option><option value="oga">ສ້າງ OGA ຕໍ່</option></select></div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showApprove', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="approve" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm">ຢືນຢັນອະນຸມັດ</button></div>
            </div></div>
        @endif
        @if ($showReject)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🧾</span>
                    <h3 class="text-base font-semibold text-gray-800">ສົ່ງກັບ Purchasing</h3>
                    <button wire:click="$set('showReject', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    @error('action')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <textarea wire:model="rejectReason" rows="3" placeholder="ເຫດຜົນ…" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showReject', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="reject" class="bg-amber-600 hover:bg-amber-700 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ຢືນຢັນ</button></div>
            </div></div>
        @endif
        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑</span>
                    <h3 class="text-base font-semibold text-red-700">🗑 ລຶບ DA</h3>
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
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🧾</span>
                    <h3 class="text-base font-semibold text-gray-800">✏️ ແກ້ ລາຍການ (ຮ່າງ)</h3>
                    <button wire:click="$set('showItems', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                <div class="space-y-2">
                    @foreach ($di as $i => $row)
                        <div wire:key="di-{{ $i }}" class="border border-gray-100 rounded-lg p-2 grid grid-cols-12 gap-2 items-start">
                            <input type="text" wire:model="di.{{ $i }}.stock_code" placeholder="Stock code" class="col-span-3 rounded-md border-gray-300 text-xs" />
                            <input type="text" wire:model="di.{{ $i }}.description" placeholder="ລາຍລະອຽດ *" class="col-span-5 rounded-md border-gray-300 text-xs" />
                            <input type="number" min="0" wire:model="di.{{ $i }}.qty_ordered" title="ສັ່ງ" class="col-span-1 rounded-md border-gray-300 text-xs" />
                            <input type="number" min="0" wire:model="di.{{ $i }}.qty_delivered" title="ສົ່ງ" class="col-span-1 rounded-md border-gray-300 text-xs" />
                            <input type="number" min="0" wire:model="di.{{ $i }}.qty_received" title="ຮັບ" class="col-span-1 rounded-md border-gray-300 text-xs" />
                            <button wire:click="removeDiItem({{ $i }})" class="col-span-1 text-red-400 hover:text-red-600 text-sm">✕</button>
                        </div>
                    @endforeach
                </div>
                <button wire:click="addDiItem" class="text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-1.5 hover:bg-sky-50">+ ເພີ່ມ ລາຍການ</button>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showItems', false)" class="bg-white border rounded-lg px-3 py-1.5 text-sm min-h-[40px]">ປິດ</button><button wire:click="saveItems" class="bg-sky-600 hover:bg-sky-700 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm">ບັນທຶກ</button></div>
            </div></div>
        @endif
    </div>
</div>
