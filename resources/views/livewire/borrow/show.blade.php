@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-50 text-gray-600 ring-1 ring-gray-200', 'acknowledged' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
        'approved' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200', 'active' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',
        'overdue' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'returned' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'cancelled' => 'bg-gray-50 text-gray-400 ring-1 ring-gray-200', default => 'bg-gray-50 text-gray-600 ring-1 ring-gray-200',
    };
    $typeLabel = match ($record->borrow_type) {
        'new_inventory' => 'ຢືມເຄື່ອງໃໝ່ ຢູ່ໃນ Inventory Item', 'tools_equipment' => 'ເຄື່ອງມື/ອຸປະກອນ (ບໍ່ມີໃນລະບົບ)',
        'deposited_tools' => 'ເຄື່ອງຝາກຄືນ', 'others' => 'ອື່ນໆ', default => $record->borrow_type,
    };
    $st = $record->display_status;
    $strip = match ($st) {
        'returned' => 'from-emerald-500 to-teal-500',
        'active' => 'from-indigo-500 to-violet-500',
        'approved' => 'from-sky-500 to-cyan-500',
        'acknowledged' => 'from-blue-500 to-indigo-500',
        'overdue' => 'from-rose-500 to-red-500',
        'cancelled' => 'from-gray-300 to-gray-400',
        default => 'from-indigo-500 to-violet-500',
    };
    $od = $record->days_left !== null && $record->days_left < 0 ? abs($record->days_left) : 0;
    $itemNames = $record->items->pluck('item_name')->implode(', ');
    $reminding = "{$record->borrower_name} ກະລຸນາສົ່ງ {$record->items->count()} ລາຍການ ({$itemNames}) ກັບຄືນ ໃນວັນທີ ".($record->planned_return_date?->format('M d, Y') ?? '—').'.';
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- ══ ONE frozen identity row (back · icon · record# · status · facts · actions) ══ --}}
        <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-2.5 bg-gradient-to-b from-indigo-100 to-white border-b border-indigo-200/70 backdrop-blur flex items-center gap-3 flex-wrap">
            <a href="{{ route('borrow') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                <span class="hidden sm:inline">ລາຍການ ຢືມ</span>
            </a>
            <span class="w-px h-5 bg-indigo-200 shrink-0"></span>
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white grid place-items-center text-lg shadow-sm shrink-0">🔄</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-base font-bold text-gray-900">{{ $record->request_number }}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $badge($st) }}">{{ strtoupper($st) }}</span>
                    @if ($record->days_left !== null)<span class="text-xs font-medium {{ $record->days_left < 0 ? 'text-rose-600' : 'text-gray-400' }}">{{ $record->days_left < 0 ? 'ເກີນ '.abs($record->days_left).' ມື້' : 'ອີກ '.$record->days_left.' ມື້' }}</span>@endif
                </div>
                <div class="hidden md:flex items-center gap-x-3 gap-y-0.5 flex-wrap text-[11px] text-gray-500 mt-0.5">
                    <span class="inline-flex items-center gap-1">👤 {{ $record->borrower_name }}@if ($record->unit) · {{ $record->unit->name }}@endif</span>
                    <span class="inline-flex items-center gap-1">🔄 {{ $record->items->count() }} ລາຍການ · {{ $record->items->sum('qty') }} ໜ່ວຍ</span>
                    <span class="inline-flex items-center gap-1">📅 ຢືມ {{ $record->borrow_date?->format('d/m/Y') }}</span>
                    <span class="inline-flex items-center gap-1">↩ ກຳນົດ {{ $record->planned_return_date?->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                @if ($editable)<button type="button" wire:click="openEdit" class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-amber-100 transition">✏️ ແກ້ໄຂ</button>@endif
                <button type="button" onclick="exportJpg('borrow-detail', 'borrow-{{ $record->request_number }}.jpg')" class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-gray-50 transition">🖼 JPG</button>
                <a href="{{ route('borrow.pdf', $record) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-gray-50 transition">📄 PDF</a>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ $message }}</div>@enderror

        @php
            $dt = fn ($d) => $d?->format('d/m/Y H:i') ?? '—';
            $d1 = fn ($d) => $d?->format('d/m/Y') ?? '—';
            $statusLao = match ($st) {
                'draft' => 'Draft (ຮ່າງ)', 'acknowledged' => 'Acknowledged (ລໍ approve)', 'approved' => 'Approved (ລໍຮັບເຄື່ອງ)',
                'active' => 'Active (ກຳລັງນຳໃຊ້)', 'overdue' => 'Overdue (ເກີນກຳນົດ)', 'returned' => 'Returned (ສົ່ງຄືນແລ້ວ)',
                'cancelled' => 'Cancelled (ຍົກເລີກ)', default => $st,
            };
            $rqty = $record->items->sum(fn ($i) => $i->return_qty ?? 0);
            $rcond = $record->items->pluck('condition_on_return')->filter()->implode(' · ') ?: '—';
            $rets = $record->items->flatMap(fn ($i) => $i->photos->where('kind', 'return'));
            // auto-signature: ຊື່ + ເວລາກົດດຳເນີນການ
            $admName = $record->warehouse_staff_name ?? $record->approver_name;
            $admAt = $record->returned_at ?? $record->taken_at ?? $record->approved_at;
        @endphp
        <div id="borrow-detail" class="bg-white border border-black p-6 text-sm space-y-6">
            <div class="text-center bg-gray-200 border border-black p-3 font-bold text-lg uppercase tracking-wide">ບັນທຶກລາຍລະອຽດ ການຢືມເຄື່ອງ (Borrowing Record Details)</div>

            {{-- ① ຂໍ້ມູນພື້ນຖານ --}}
            <table class="w-full text-sm" style="border-collapse:collapse">
                @php $bd = 'border border-black px-2 py-1.5'; $lbl = $bd.' bg-gray-50 font-bold align-top'; @endphp
                <tr>
                    <td class="{{ $lbl }}" style="width:20%">ໃບຢືມເລກທີ / REQ No.</td><td class="{{ $bd }} font-bold text-indigo-700" style="width:30%">{{ $record->request_number }}</td>
                    <td class="{{ $lbl }}" style="width:20%">ປະເພດຢືມ / Type</td><td class="{{ $bd }}" style="width:30%">{{ $typeLabel }}</td>
                </tr>
                <tr>
                    <td class="{{ $lbl }}">ຜູ້ຢືມ / Borrower</td><td class="{{ $bd }}">{{ $record->borrower_name }}</td>
                    <td class="{{ $lbl }}">ເບີໂທ / WhatsApp</td><td class="{{ $bd }}">{{ $record->borrower?->phone_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="{{ $lbl }}">ໜ່ວຍງານ / Unit</td><td class="{{ $bd }}">{{ $record->unit?->name ?? '—' }}</td>
                    <td class="{{ $lbl }}">ພະແນກ / Department</td><td class="{{ $bd }}">{{ $record->department?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="{{ $lbl }}">ຢືມວັນທີ / Borrow Date</td><td class="{{ $bd }}">{{ $d1($record->borrow_date) }}</td>
                    <td class="{{ $lbl }}">ຈຸດປະສົງ / Purpose</td><td class="{{ $bd }}">{{ $record->purpose ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="{{ $lbl }}">ກຳນົດວັນທີສົ່ງ / Due</td><td class="{{ $bd }}">{{ $d1($record->planned_return_date) }} <span class="text-gray-500">({{ $record->period_days }} ມື້)</span></td>
                    <td class="{{ $lbl }}">ສະຖານະ / Status</td><td class="{{ $bd }} font-bold {{ $st === 'overdue' ? 'text-rose-600' : ($st === 'returned' ? 'text-gray-600' : 'text-emerald-600') }}">{{ $statusLao }}</td>
                </tr>
            </table>

            {{-- ② ລາຍການເຄື່ອງ --}}
            <div>
                <div class="font-bold mb-2">ລາຍການເຄື່ອງທີ່ຢືມ (Borrowed Items)</div>
                <table class="w-full text-sm text-center" style="border-collapse:collapse">
                    <thead class="bg-gray-100 font-bold">
                        <tr>
                            <th class="{{ $bd }}" style="width:5%">#</th>
                            <th class="{{ $bd }}" style="width:16%">ລະຫັດເຄື່ອງ<br><span class="text-[10px] font-normal">Item ID</span></th>
                            <th class="{{ $bd }} text-left">ລາຍລະອຽດອຸປະກອນ<br><span class="text-[10px] font-normal">Description</span></th>
                            <th class="{{ $bd }}" style="width:20%">ຮູບພາບ<br><span class="text-[10px] font-normal">Photo</span></th>
                            <th class="{{ $bd }}" style="width:9%">ຈຳນວນ<br><span class="text-[10px] font-normal">Qty</span></th>
                            <th class="{{ $bd }}" style="width:9%">ໜ່ວຍ<br><span class="text-[10px] font-normal">Unit</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($record->items as $it)
                            @php $photo = $it->inventoryItem?->primaryPhoto; $take = $it->photos->where('kind', 'take'); @endphp
                            <tr>
                                <td class="{{ $bd }} align-top">{{ $loop->iteration }}</td>
                                <td class="{{ $bd }} align-top font-mono">{{ $it->inventoryItem?->slug ?? '—' }}</td>
                                <td class="{{ $bd }} align-top text-left">{{ $it->item_name }}</td>
                                <td class="{{ $bd }} align-top"><div class="flex gap-1 justify-center flex-wrap">@if ($photo)<img src="{{ $photo->url }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-10 h-10 shrink-0 object-cover border border-gray-300 rounded cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endif @foreach ($take as $p)<img src="{{ $p->url }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-10 h-10 shrink-0 object-cover border border-gray-300 rounded cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endforeach</div></td>
                                <td class="{{ $bd }} align-top font-bold">{{ $it->qty }}</td>
                                <td class="{{ $bd }} align-top">{{ $it->inventoryItem?->unit ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ③ ການອະນຸມັດ & ຮັບເຄື່ອງ --}}
            <div>
                <div class="font-bold mb-2">ການຢັ້ງຢືນ ການອະນຸມັດ ແລະ ຮັບເຄື່ອງ (Approvals &amp; Pickup)</div>
                <table class="w-full text-sm" style="border-collapse:collapse">
                    <tr>
                        <td class="{{ $lbl }}" style="width:20%">ຜູ້ອະນຸມັດ / Manager</td><td class="{{ $bd }}" style="width:30%">{{ $record->acknowledge_name ?? ($record->requires_acknowledge ? 'ລໍຖ້າ' : '—') }}</td>
                        <td class="{{ $lbl }}" style="width:20%">ສາງອະນຸມັດ / WH Admin</td><td class="{{ $bd }}" style="width:30%">{{ $record->approver_name ?? '—' }} @if ($record->approved_at)<span class="text-gray-500 text-xs">· {{ $dt($record->approved_at) }}</span>@endif</td>
                    </tr>
                    <tr>
                        <td class="{{ $lbl }}">ຜູ້ຢືມຮັບເຄື່ອງແລ້ວ / User Took</td>
                        <td class="{{ $bd }}" colspan="3">@if ($record->taken_at)<span class="text-emerald-600 font-bold">✓ ຮັບເຄື່ອງແລ້ວ</span> <span class="text-gray-500 text-xs">· {{ $dt($record->taken_at) }} · {{ $record->warehouse_staff_name }}</span>@else <span class="text-gray-400">ລໍຖ້າ</span>@endif</td>
                    </tr>
                </table>
            </div>

            {{-- ④ ການຕໍ່ເວລາ --}}
            <div>
                <div class="font-bold mb-2">ການຕໍ່ເວລາ (Time Extension)</div>
                <table class="w-full text-sm" style="border-collapse:collapse">
                    <tr>
                        <td class="{{ $lbl }}" style="width:20%">ສະຖານະຂໍຕໍ່ເວລາ</td>
                        <td class="{{ $bd }}" style="width:30%">@php $em = ['pending' => ['Pending', 'text-amber-600'], 'approved' => ['Approved', 'text-emerald-600'], 'rejected' => ['Rejected', 'text-rose-600'], 'none' => ['—', 'text-gray-400']]; [$et, $ec] = $em[$record->extension_status] ?? ['—', '']; @endphp<span class="font-bold {{ $ec }}">{{ $et }}</span></td>
                        <td class="{{ $lbl }}" style="width:20%">ວັນທີຂໍຕໍ່ໄປຫາ</td><td class="{{ $bd }}" style="width:30%">{{ $d1($record->extension_proposed_date) }}</td>
                    </tr>
                    <tr><td class="{{ $lbl }}">ເຫດຜົນຂໍຕໍ່ເວລາ</td><td class="{{ $bd }} italic text-gray-700" colspan="3">{{ $record->extension_reason ?? '—' }}</td></tr>
                </table>
            </div>

            {{-- ⑤ ລາຍລະອຽດການສົ່ງຄືນ --}}
            <div>
                <div class="text-center bg-gray-200 border border-black p-2 font-bold mb-3">ລາຍລະອຽດການສົ່ງຄືນ (Return Details)</div>
                <table class="w-full text-sm" style="border-collapse:collapse">
                    <tr>
                        <td class="{{ $lbl }}" style="width:25%">ສະຖານະສາງຮັບຄືນ / WH Return</td>
                        <td class="{{ $bd }}" style="width:25%">@if ($record->returned_at)<span class="text-emerald-600 font-bold">✓ ຮັບຄືນແລ້ວ</span> <span class="text-gray-500 text-xs">{{ $dt($record->returned_at) }}</span>@else<span class="text-amber-600 font-bold">ລໍຖ້າຮັບເຄື່ອງ</span>@endif</td>
                        <td class="{{ $lbl }}" style="width:25%">ຈຳນວນທີ່ສົ່ງຄືນ / Return Qty</td><td class="{{ $bd }} font-bold" style="width:25%">{{ $record->returned_at ? $rqty : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="{{ $lbl }}">ສະພາບເຄື່ອງ / Condition</td><td class="{{ $bd }}">{{ $rcond }}</td>
                        <td class="{{ $lbl }}">ໝາຍເຫດສົ່ງຄືນ / Return Remarks</td><td class="{{ $bd }} text-gray-700">{{ $record->return_remarks ?? '—' }}</td>
                    </tr>
                    <tr><td class="{{ $lbl }}">ໝາຍເຫດຈາກສາງ / Admin Notes</td><td class="{{ $bd }} text-gray-700" colspan="3">{{ $record->admin_notes ?? '—' }}</td></tr>
                    <tr>
                        <td class="{{ $lbl }}">ຮູບພາບຕອນສົ່ງຄືນ / Return Photos</td>
                        <td class="{{ $bd }}" colspan="3">@if ($rets->count())<div class="flex gap-2 flex-wrap">@foreach ($rets as $p)<img src="{{ $p->url }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-16 h-16 object-cover border border-gray-300 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endforeach</div>@else <span class="text-gray-400">—</span>@endif</td>
                    </tr>
                </table>
            </div>

            {{-- ⑥ ປະຫວັດ ການ ຮັບຄືນ ເປັນ ຄັ້ງ (ທະຍອຍ ຮັບ) --}}
            @if ($record->returnEvents->count())
                <div>
                    <div class="text-center bg-gray-200 border border-black p-2 font-bold mb-3">ປະຫວັດ ການ ຮັບຄືນ (Return Events)</div>
                    <div class="space-y-2">
                        @foreach ($record->returnEvents as $ev)
                            <div class="border border-gray-200 rounded-md p-3">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                    <span class="font-bold text-sky-700">ຄັ້ງ {{ $ev->seq }}</span>
                                    <span class="text-gray-500 text-xs">📅 {{ $ev->returned_on?->format('d/m/Y') }}</span>
                                    <span class="text-gray-500 text-xs">ຮັບໂດຍ: {{ $ev->received_by_name ?? '—' }}</span>
                                    @if ($ev->remarks)<span class="text-gray-400 text-xs">· {{ $ev->remarks }}</span>@endif
                                </div>
                                <ul class="mt-1 text-xs text-gray-700 list-disc pl-5">
                                    @foreach ($ev->lines as $ln)
                                        <li>{{ $ln->item?->item_name ?? '—' }} — ຮັບ <b>{{ $ln->qty }}</b>@if ($ln->condition) <span class="text-gray-400">({{ $ln->condition }})</span>@endif</li>
                                    @endforeach
                                </ul>
                                @if ($ev->photos->count())
                                    <div class="flex gap-2 flex-wrap mt-2">@foreach ($ev->photos as $p)<img src="{{ $p->url }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-14 h-14 object-cover border border-gray-300 rounded cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endforeach</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ລາຍເຊັນ (auto: ຊື່ + ວັນທີ ເວລາ ກົດດຳເນີນການ) --}}
            <div class="grid grid-cols-2 gap-8 pt-4">
                <div class="text-center">
                    <div class="font-bold mb-10">ລາຍເຊັນ ຜູ້ຢືມ (Borrower)</div>
                    <div class="border-b border-black w-48 mx-auto mb-1"></div>
                    <div class="font-medium text-gray-700">{{ $record->borrower_name }}</div>
                    <div class="text-xs text-gray-400">{{ $dt($record->created_at) }}</div>
                </div>
                <div class="text-center">
                    <div class="font-bold mb-10">ລາຍເຊັນ ທີມສາງ / Admin</div>
                    <div class="border-b border-black w-48 mx-auto mb-1"></div>
                    <div class="font-medium text-gray-700">{{ $admName ?? '—' }}</div>
                    <div class="text-xs text-gray-400">{{ $admAt ? $dt($admAt) : '' }}</div>
                </div>
            </div>

            @if ($record->cancel_reason)<div class="text-rose-600 text-xs">ເຫດຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif
        </div>

        {{-- actions --}}
        <div class="bg-white/95 backdrop-blur rounded-xl border border-gray-200 px-5 py-3 flex flex-wrap gap-2 text-sm items-center sticky bottom-4 z-20 shadow-lg">
            @if ($record->status === 'draft')
                <button wire:click="submit" class="inline-flex items-center gap-1.5 text-white bg-sky-600 font-medium rounded-lg px-4 py-2 hover:bg-sky-700 transition shadow-sm">📤 ສົ່ງຂໍອະນຸມັດ</button>
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif ($record->status === 'acknowledged')
                @if ($steps['acknowledge'] && ! $record->acknowledged_at)<button wire:click="acknowledge" class="inline-flex items-center gap-1.5 text-white bg-blue-600 font-medium rounded-lg px-4 py-2 hover:bg-blue-700 transition shadow-sm">✓ Acknowledge</button>@endif
                @if ($steps['approve'])<button wire:click="approve" class="inline-flex items-center gap-1.5 text-white bg-sky-600 font-medium rounded-lg px-4 py-2 hover:bg-sky-700 transition shadow-sm">✓ Approve</button>@endif
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">Reject/ຍົກເລີກ</button>
            @elseif ($record->status === 'approved')
                <button wire:click="openTake" class="inline-flex items-center gap-1.5 text-white bg-emerald-600 font-medium rounded-lg px-4 py-2 hover:bg-emerald-700 transition shadow-sm">📤 ມອບເຄື່ອງ</button>
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif (in_array($record->status, ['active', 'overdue']))
                @if (! $record->borrower_return_ack)
                    @if ($isBorrower || $editable)<button wire:click="openRequestReturn" class="inline-flex items-center gap-1.5 text-white bg-amber-600 font-medium rounded-lg px-4 py-2 hover:bg-amber-700 transition shadow-sm">📩 ແຈ້ງສົ່ງຄືນ</button>@endif
                @else
                    <span class="inline-flex items-center gap-1 text-xs bg-amber-50 text-amber-700 border border-amber-200 rounded-lg px-2.5 py-1.5">📩 ຜູ້ຢືມແຈ້ງສົ່ງຄືນແລ້ວ{{ $record->borrower_return_date ? ' ('.$record->borrower_return_date->format('d/m/Y').')' : '' }} — ລໍ Warehouse ຢືນຢັນ</span>
                @endif
                @if ($partial)
                    @if ($record->is_partially_returned)<span class="inline-flex items-center gap-1 text-xs bg-sky-50 text-sky-700 border border-sky-200 rounded-lg px-2.5 py-1.5">📦 ຮັບຄືນ ບາງ ສ່ວນ ແລ້ວ · ຍັງ ຄ້າງ {{ $record->outstanding_qty }}</span>@endif
                    @if ($editable)<button wire:click="openReceive" class="inline-flex items-center gap-1.5 text-white bg-sky-600 font-medium rounded-lg px-4 py-2 hover:bg-sky-700 transition shadow-sm">📦 ທະຍອຍ ຮັບຄືນ (ຄ້າງ {{ $record->outstanding_qty }})</button>@endif
                @else
                    @if ($editable)<button wire:click="openReturn" class="inline-flex items-center gap-1.5 text-white bg-sky-600 font-medium rounded-lg px-4 py-2 hover:bg-sky-700 transition shadow-sm">✅ ຢືນຢັນຮັບຄືນ</button>@endif
                @endif
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif

            @if ($deletable)
                <span class="ml-auto"></span>
                <button wire:click="openDelete" class="text-rose-600 border border-rose-200 rounded-lg px-3 py-2 hover:bg-rose-50 transition">🗑 ລຶບ</button>
            @endif
        </div>

        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🔄</span>
                        <h3 class="text-base font-semibold text-gray-800">ເຫດຜົນ (optional)</h3>
                        <button wire:click="$set('showCancel', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        <textarea wire:model="cancelReason" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showCancel', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button>
                        <button wire:click="cancel" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-rose-700">ຢືນຢັນຍົກເລີກ</button>
                    </div>
                </div>
            </div>
        @endif

        @php $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-50 file:text-sky-700 file:font-medium file:cursor-pointer hover:file:bg-sky-100'; @endphp

        {{-- confirmTake modal --}}
        @if ($showTake)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🔄</span>
                        <h3 class="text-base font-semibold text-gray-800">ມອບເຄື່ອງ — ຖ່າຍຮູບສະພາບ (ບັງຄັບ)</h3>
                        <button wire:click="$set('showTake', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                    @error('takePhotos')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    @foreach ($record->items as $it)
                        <div class="border border-gray-200 rounded-md p-3 space-y-2">
                            <div class="text-sm font-medium text-gray-700">{{ $it->item_name }} <span class="text-gray-400">×{{ $it->qty }}</span></div>
                            <input type="file" x-data="photoInput('takePhotos.{{ $it->id }}')" x-on:change="pick($event)" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                            <div wire:loading wire:target="takePhotos.{{ $it->id }}" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                            @if (! empty($takePhotos[$it->id]))<div class="flex gap-1 flex-wrap">@foreach ($takePhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-12 h-12 rounded object-cover border border-sky-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endif @endforeach</div>@endif
                            <textarea wire:model="takeCondition.{{ $it->id }}" rows="1" placeholder="ໝາຍເຫດສະພາບ (optional)…" class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                        </div>
                    @endforeach
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showTake', false)" class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button>
                        <button wire:click="confirmTake" wire:loading.attr="disabled" wire:target="confirmTake,takePhotos" class="bg-sky-600 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-sky-700">ຢືນຢັນມອບ</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- confirmReturn modal --}}
        @if ($showReturn)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🔄</span>
                        <h3 class="text-base font-semibold text-gray-800">ຮັບຄືນ — ຈຳນວນ + ຖ່າຍຮູບ (ບັງຄັບ)</h3>
                        <button wire:click="$set('showReturn', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                    @error('returnPhotos')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    @foreach ($record->items as $it)
                        <div class="border border-gray-200 rounded-md p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium text-gray-700">{{ $it->item_name }} <span class="text-gray-400">/ ຢືມ {{ $it->qty }}</span></div>
                                <label class="text-xs text-gray-500 flex items-center gap-1">ຄືນ <input type="number" min="0" max="{{ $it->outstanding_qty }}" wire:model="returnQty.{{ $it->id }}" class="w-16 rounded-lg border-gray-300 text-xs" /></label>
                            </div>
                            <input type="file" x-data="photoInput('returnPhotos.{{ $it->id }}')" x-on:change="pick($event)" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                            <div wire:loading wire:target="returnPhotos.{{ $it->id }}" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                            @if (! empty($returnPhotos[$it->id]))<div class="flex gap-1 flex-wrap">@foreach ($returnPhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-12 h-12 rounded object-cover border border-sky-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endif @endforeach</div>@endif
                            <textarea wire:model="returnCondition.{{ $it->id }}" rows="1" placeholder="ໝາຍເຫດສະພາບ (optional)…" class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                        </div>
                    @endforeach
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showReturn', false)" class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button>
                        <button wire:click="confirmReturn" wire:loading.attr="disabled" wire:target="confirmReturn,returnPhotos" class="bg-sky-600 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-sky-700">ຢືນຢັນຮັບຄືນ</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ທະຍອຍ ຮັບຄືນ (partial receive) modal --}}
        @if ($showReceive)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📦</span>
                        <h3 class="text-base font-semibold text-gray-800">📦 ທະຍອຍ ຮັບຄືນ — ຮັບ ເປັນ ລາຍການ/ຄັ້ງ</h3>
                        <button wire:click="$set('showReceive', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                    <p class="text-xs text-gray-500">ໃສ່ ຈຳນວນ ທີ່ ຮັບ ຄືນ ຄັ້ງ ນີ້ ຕໍ່ ລາຍການ (ຄ້າງ ໄວ້ ໄດ້). ໃບ ຈະ ປິດ ອັດຕະໂນມັດ ເມື່ອ ຄືນ ຄົບ ທຸກ ລາຍການ.</p>
                    @error('receiveQty')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    @error('receivePhotos')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    @foreach ($record->items as $it)
                        @php $out = $it->outstanding_qty; @endphp
                        <div class="border {{ $out > 0 ? 'border-gray-200' : 'border-emerald-200 bg-emerald-50/40' }} rounded-md p-3 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-sm font-medium text-gray-700">{{ $it->item_name }}
                                    <span class="text-gray-400 text-xs">/ ຢືມ {{ $it->qty }} · ຄືນ ແລ້ວ {{ $it->received_qty }} · ຄ້າງ {{ $out }}</span>
                                </div>
                                @if ($out > 0)
                                    <label class="text-xs text-gray-500 flex items-center gap-1">ຮັບ ຄັ້ງ ນີ້ <input type="number" min="0" max="{{ $out }}" wire:model="receiveQty.{{ $it->id }}" class="w-16 rounded-lg border-gray-300 text-xs" /></label>
                                @else
                                    <span class="text-xs text-emerald-600 font-medium">✓ ຄືນ ຄົບ ແລ້ວ</span>
                                @endif
                            </div>
                            @if ($out > 0)
                                <input type="file" x-data="photoInput('receivePhotos.{{ $it->id }}')" x-on:change="pick($event)" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                                <div wire:loading wire:target="receivePhotos.{{ $it->id }}" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                                @if (! empty($receivePhotos[$it->id]))<div class="flex gap-1 flex-wrap">@foreach ($receivePhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-12 h-12 rounded object-cover border border-sky-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endif @endforeach</div>@endif
                                <textarea wire:model="receiveCondition.{{ $it->id }}" rows="1" placeholder="ໝາຍເຫດສະພາບ (optional)…" class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                            @endif
                        </div>
                    @endforeach
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-xs text-gray-500 mb-1">ວັນທີ ຮັບ</label><input type="date" wire:model="receiveDate" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs text-gray-500 mb-1">ໝາຍເຫດ ຄັ້ງ ນີ້</label><input wire:model="receiveRemarks" placeholder="optional" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showReceive', false)" class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button>
                        <button wire:click="receiveReturn" wire:loading.attr="disabled" wire:target="receiveReturn,receivePhotos" class="bg-sky-600 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-sky-700">📦 ບັນທຶກ ການ ຮັບ</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- extension request modal --}}
        @if ($showExtension)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🔄</span>
                        <h3 class="text-base font-semibold text-gray-800">ຂໍຂະຫຍາຍເວລາ</h3>
                        <button wire:click="$set('showExtension', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        @error('action')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        <div><label class="block text-sm text-gray-600 mb-1">ວັນທີສົ່ງໃໝ່</label><input type="date" wire:model="extProposedDate" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-sm text-gray-600 mb-1">ເຫດຜົນ</label><textarea wire:model="extReason" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showExtension', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="requestExtension" class="bg-sky-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-sky-700">ສົ່ງຄຳຂໍ</button></div>
                </div>
            </div>
        @endif

        {{-- borrower ແຈ້ງສົ່ງຄືນ (step 1) modal --}}
        @if ($showRequestReturn)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📩</span>
                        <h3 class="text-base font-semibold text-gray-800">📩 ແຈ້ງສົ່ງຄືນ</h3>
                        <button wire:click="$set('showRequestReturn', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        <p class="text-xs text-gray-500">ແຈ້ງວ່າຈະສົ່ງເຄື່ອງຄືນ — Warehouse ຈະຢືນຢັນຮັບຄືນ ແລະ ກວດສະພາບອີກຄັ້ງ.</p>
                        @error('action')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        <div><label class="block text-sm text-gray-600 mb-1">ວັນທີສົ່ງຄືນ</label><input type="date" wire:model="rrDate" class="w-full rounded-lg border-gray-300 text-sm" />@error('rrDate')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="block text-sm text-gray-600 mb-1">ໝາຍເຫດ (optional)</label><textarea wire:model="rrRemarks" rows="2" placeholder="ເຊັ່ນ: ສົ່ງບໍ່ຄົບ, ສະພາບ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showRequestReturn', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="requestReturn" class="bg-sky-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-sky-700">ຢືນຢັນແຈ້ງສົ່ງຄືນ</button></div>
                </div>
            </div>
        @endif

        {{-- delete (soft) modal --}}
        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑</span>
                        <h3 class="text-base font-semibold text-rose-700">🗑 ລຶບລາຍການຢືມ</h3>
                        <button wire:click="$set('showDelete', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        <p class="text-xs text-gray-500">ລາຍການຈະຖູກຍ້າຍໄປ Deleted Log (ສາມາດກູ້ຄືນໄດ້). ກະລຸນາໃສ່ເຫດຜົນ.</p>
                        <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດຜົນການລຶບ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        @error('deleteReason')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDelete', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="deleteRecord" wire:loading.attr="disabled" wire:target="deleteRecord" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-rose-700">ຢືນຢັນລຶບ</button></div>
                </div>
            </div>
        @endif

        {{-- admin edit modal --}}
        @if ($showEdit)
            <div class="fixed inset-0 z-50 flex items-start md:items-center justify-center bg-black/40 md:p-4 overflow-y-auto">
                <div class="bg-white w-full md:max-w-2xl rounded-2xl border border-gray-300 shadow-lg overflow-hidden my-4 max-h-[92vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-indigo-200 to-indigo-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 text-white grid place-items-center text-lg shadow-sm shrink-0">✏️</span>
                        <h3 class="text-base font-semibold text-gray-800">✏️ ແກ້ໄຂ (Admin) — {{ $record->request_number }}</h3>
                        <button wire:click="$set('showEdit', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-4 overflow-y-auto">

                    <div class="grid sm:grid-cols-2 gap-3 text-sm">
                        <div><label class="block text-gray-600 mb-1">ຜູ້ຢືມ *</label><input wire:model="ef.borrower_name" class="w-full rounded-lg border-gray-300 text-sm" />@error('ef.borrower_name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="block text-gray-600 mb-1">ຈຸດປະສົງ</label><input wire:model="ef.purpose" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ຢືມວັນທີ</label><input type="date" wire:model="ef.borrow_date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ກຳນົດສົ່ງ</label><input type="date" wire:model="ef.planned_return_date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ໄລຍະ (ມື້)</label><input type="number" wire:model="ef.period_days" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">ວັນທີຜູ້ຢືມແຈ້ງສົ່ງ</label><input type="date" wire:model="ef.borrower_return_date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">Approver</label><input wire:model="ef.approver_name" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-gray-600 mb-1">Manager (ack)</label><input wire:model="ef.acknowledge_name" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div class="sm:col-span-2"><label class="block text-gray-600 mb-1">ໝາຍເຫດສົ່ງຄືນ (Return remarks)</label><textarea wire:model="ef.return_remarks" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                        <div class="sm:col-span-2"><label class="block text-gray-600 mb-1">ໝາຍເຫດຈາກສາງ (Admin notes)</label><textarea wire:model="ef.admin_notes" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                    </div>

                    <div class="border-t pt-3 space-y-3">
                        <div class="font-medium text-sm text-gray-700">ລາຍການ + ຮູບ</div>
                        @foreach ($record->items as $it)
                            <div class="border border-gray-200 rounded-md p-3 space-y-2">
                                <div class="grid grid-cols-12 gap-2 text-sm">
                                    <input wire:model="ei.{{ $it->id }}.item_name" class="col-span-6 rounded-lg border-gray-300 text-sm" placeholder="ຊື່ລາຍການ" />
                                    <input type="number" wire:model="ei.{{ $it->id }}.qty" class="col-span-3 rounded-lg border-gray-300 text-sm" placeholder="ຈຳນວນ" />
                                    <input type="number" wire:model="ei.{{ $it->id }}.return_qty" class="col-span-3 rounded-lg border-gray-300 text-sm" placeholder="ຄືນ" />
                                </div>
                                @if ($it->photos->count())
                                    <div class="flex gap-1 flex-wrap">
                                        @foreach ($it->photos as $p)
                                            <div class="relative" wire:key="ep-{{ $p->id }}">
                                                <img src="{{ $p->url }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-12 h-12 object-cover border border-gray-300 rounded cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                                                <span class="absolute -bottom-1 left-0 right-0 text-center text-[8px] bg-gray-700 text-white">{{ $p->kind }}</span>
                                                <button wire:click="removePhoto({{ $p->id }})" wire:confirm="ລຶບຮູບນີ້?" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-rose-600 text-white rounded-full text-[10px] leading-none">×</button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div><label class="text-gray-500">+ ຮູບ take</label><input type="file" x-data="photoInput('ep.take.{{ $it->id }}')" x-on:change="pick($event)" multiple accept="image/*" class="block w-full text-xs" /></div>
                                    <div><label class="text-gray-500">+ ຮູບ return</label><input type="file" x-data="photoInput('ep.return.{{ $it->id }}')" x-on:change="pick($event)" multiple accept="image/*" class="block w-full text-xs" /></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showEdit', false)" class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                        <button wire:click="saveEdit" wire:loading.attr="disabled" wire:target="saveEdit,ep" class="bg-sky-600 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-sky-700">ບັນທຶກ</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
