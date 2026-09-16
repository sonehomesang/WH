@php
    $statusMeta = fn ($s) => match ($s) {
        'draft' => ['DRAFT', 'bg-gray-100 text-gray-600'],
        'submitted' => ['SUBMITTED', 'bg-blue-50 text-blue-700 ring-1 ring-blue-200'],
        'accepted' => ['ACCEPTED', 'bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200'],
        'stored' => ['STORED', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'needs_fix' => ['NEEDS FIX', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'claimed' => ['CLAIMED', 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200'],
        'cancelled' => ['CANCELLED', 'bg-gray-100 text-gray-400'],
        default => [strtoupper($s), 'bg-gray-100 text-gray-600'],
    };
    [$slbl, $scls] = $statusMeta($record->status);
    $strip = match ($record->status) {
        'stored', 'claimed' => 'from-emerald-500 to-teal-500',
        'accepted' => 'from-cyan-500 to-sky-500',
        'submitted' => 'from-blue-500 to-indigo-500',
        'needs_fix' => 'from-amber-400 to-orange-500',
        'cancelled' => 'from-gray-300 to-gray-400',
        default => 'from-sky-500 to-cyan-500',
    };
    $kindTag = ['deposit' => 'D', 'stored' => 'S', 'claim' => 'C'];
    $slotTag = ['overall' => '🔍 ລວມ', 'id' => '🏷️ ລະຫັດ', 'damage' => '⚠️ ເປເພ'];
    $fileCls = 'block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sky-50 file:text-sky-700 file:font-medium file:cursor-pointer hover:file:bg-sky-100';
    $kv = fn ($label, $value) => '<div><div class="text-xs text-gray-400">'.$label.'</div><div class="text-gray-800 mt-0.5">'.($value ?: '—').'</div></div>';
@endphp

<div class="pb-10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- ══ ONE frozen identity row (back · icon · record# · status · facts · actions) ══ --}}
        <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-2.5 bg-gradient-to-b from-sky-100 to-white border-b border-sky-200/70 backdrop-blur flex items-center gap-3 flex-wrap">
            <a href="{{ route('deposit') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                <span class="hidden sm:inline">ລາຍການ ຝາກ</span>
            </a>
            <span class="w-px h-5 bg-sky-200 shrink-0"></span>
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📥</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-base font-bold text-gray-900">{{ $record->request_number }}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $scls }}">{{ $slbl }}</span>
                </div>
                <div class="hidden md:flex items-center gap-x-3 gap-y-0.5 flex-wrap text-[11px] text-gray-500 mt-0.5">
                    <span class="inline-flex items-center gap-1">👤 {{ $record->owner_name }}@if ($record->unit) · {{ $record->unit->name }}@endif</span>
                    <span class="inline-flex items-center gap-1">🏷️ {{ ['walk_in' => 'Walk-in', 'pre_request' => 'Pre-request', 'legacy' => 'ເຄື່ອງຝາກເກົ່າ'][$record->request_type] ?? 'Walk-in' }}</span>
                    <span class="inline-flex items-center gap-1">📦 {{ $record->items->count() }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }} · {{ $record->items->sum('qty') }} ໜ່ວຍ</span>
                    <span class="inline-flex items-center gap-1">📅 ຝາກ {{ $record->deposit_date?->format('d/m/Y') }}</span>
                    @if ($record->storage_location || $record->storage_shelf_label)<span class="inline-flex items-center gap-1">📍 {{ collect([$record->storage_location, $record->storage_shelf_label])->filter()->implode(' / ') }}</span>@endif
                </div>
            </div>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                @if ($editable)<button wire:click="openEdit" class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-amber-100 transition">✏️ ແກ້ໄຂ</button>@endif
                @if (auth()->user()->is_super_admin)<button wire:click="openStatusReset" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-700 bg-slate-100 border border-slate-200 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-slate-200 transition" title="ຄືນ ສະຖານະ (admin)">🔧 ສະຖານະ</button>@endif
                <a href="{{ route('deposit.pdf', $record) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-gray-50 transition">📄 PDF</a>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ $message }}</div>@enderror
        @if ($record->needsOfficeInfo() && ! $showEdit)
            <div class="flex items-center gap-3 text-sm bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                <span class="text-xl">📝</span>
                <div>
                    <div class="font-semibold text-amber-700">ຂັ້ນ 2 — ຍັງ ຄ້າງ ຕື່ມ ຂໍ້ມູນ</div>
                    <div class="text-xs text-amber-700/80">ໜ້າງານ ບັນທຶກ ລາຍການ + ຮູບ ແລ້ວ. ກະລຸນາ ຕື່ມ: ປະເພດ · ແຫຼ່ງທີ່ມາ · ເຈົ້າຂອງ · ພະແນກ · ໄລຍະ · ເຫດຜົນ → ແລ້ວ ກົດ ສົ່ງ.</div>
                </div>
                @if ($editable)<button wire:click="openEdit" class="ml-auto text-xs font-semibold text-white bg-amber-600 rounded-lg px-3 py-2 hover:bg-amber-700 transition whitespace-nowrap">✏️ ຕື່ມ ຂໍ້ມູນ</button>@endif
            </div>
        @endif
        @if ($record->status === 'needs_fix' && $record->needs_fix_reason)<div class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2.5">⚠ ຕ້ອງປັບແກ້: {{ $record->needs_fix_reason }}</div>@endif
        @if ($record->status === 'cancelled' && $record->cancel_reason)<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">ຍົກເລີກ: {{ $record->cancel_reason }}</div>@endif
        @if (in_array($record->status, ['disposal', 'disposed'], true))<div class="text-sm {{ $record->status === 'disposed' ? 'text-gray-100 bg-gray-800 border-gray-800' : 'text-rose-700 bg-rose-50 border-rose-200' }} border rounded-xl px-4 py-2.5">🗑 {{ $record->status === 'disposed' ? 'ຈຳໜ່າຍ ແລ້ວ — ເຄື່ອງ ບໍ່ ມີ ຕົວຕົນ ແລ້ວ (ລິສ ຕາຍ · ລັອກ).' : 'ຖືກ ດຶງ ໄປ ໃບ ຈຳໜ່າຍ — ລັອກ ການ ແກ້ໄຂ.' }}</div>@endif

        <div id="deposit-detail" class="space-y-4">
            {{-- ① general info --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-xs">📋</span><h3 class="text-sm font-semibold text-gray-700">ຂໍ້ມູນ ທົ່ວໄປ / Deposit record</h3></div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    {!! $kv('ຜູ້ຮັບ / ຜູ້ບັນທຶກ ຂໍ້ມູນ / Receiver', e($record->owner_name)) !!}
                    {!! $kv('Email', e($record->owner_email)) !!}
                    {!! $kv('ຜູ້ ເອົາ ມາ ຝາກ / Depositor', e($record->depositor_name)) !!}
                    {!! $kv('ໜ່ວຍງານ ເຈົ້າຂອງ / Unit\'s Owner', e($record->unit?->name)) !!}
                    {!! $kv('ພະແນກ / Dept', e($record->department?->name)) !!}
                    {!! $kv('ປະເພດ / Category', e($record->item_category)) !!}
                    {!! $kv('ແຫຼ່ງທີ່ມາ / Origin', e($record->origin_source)) !!}
                    @if ($record->functional_status){!! $kv('ສະຖານະ ໃຊ້ງານ / Functional', e(['usable' => '✅ ໃຊ້ ໄດ້ ປົກກະຕິ', 'partial' => '⚠️ ໃຊ້ ໄດ້ ບາງ ສ່ວນ', 'unusable' => '⛔ ໃຊ້ ບໍ່ ໄດ້'][$record->functional_status] ?? $record->functional_status)) !!}@endif
                    @if ($record->original_deposit_date){!! $kv('ວັນທີຝາກເດີມ / Orig. deposit', e($record->original_deposit_date?->format('d/m/Y'))) !!}@endif
                    @if ($record->original_receiver){!! $kv('ຜູ້ຮັບຝາກເດີມ / Orig. receiver', e($record->original_receiver)) !!}@endif
                    {!! $kv('ວັນທີຝາກ / Deposit', e($record->deposit_date?->format('d/m/Y'))) !!}
                    {!! $kv('ໄລຍະ / Duration', e($record->expected_duration)) !!}
                    {!! $kv('ຄາດເອົາຄືນ / Exp. claim', e($record->expected_claim_date?->format('d/m/Y'))) !!}
                    {!! $kv('ເອົາຄືນຈິງ / Claimed', e($record->actual_claim_date?->format('d/m/Y'))) !!}
                    <div class="sm:col-span-2"><div class="text-xs text-gray-400">ເຫດຜົນ / Reason</div><div class="text-gray-800 mt-0.5">{{ $record->deposit_reason ?: '—' }}</div></div>
                    @if ($record->remark)<div class="sm:col-span-2"><div class="text-xs text-gray-400">ໝາຍເຫດ / Remark</div><div class="text-gray-800 mt-0.5">{{ $record->remark }}</div></div>@endif
                </div>
            </div>

            {{-- ② storage --}}
            @if ($record->storage_location || $record->storage_shelf_label || $record->warehouse_instructions)
                <div class="bg-gradient-to-br from-cyan-50 to-sky-50 border border-cyan-200 rounded-xl p-4">
                    <div class="flex items-center gap-2 text-sm font-semibold text-cyan-800 mb-1.5">📍 ສະຖານທີ່ ຈັດ ເກັບ / Storage</div>
                    <div class="text-gray-800 font-medium">{{ collect([$record->storage_location, $record->storage_shelf_label])->filter()->implode(' / ') ?: '—' }}</div>
                    @if ($record->warehouse_instructions)<div class="text-xs text-gray-500 mt-1">ຄຳແນະນຳ: {{ $record->warehouse_instructions }}</div>@endif
                    @if ($record->accepted_at)<div class="text-xs text-gray-400 mt-1">ຮັບຝາກເມື່ອ: {{ $record->accepted_at->format('d/m/Y H:i') }} · {{ $record->warehouse_staff_name }}</div>@endif
                </div>
            @endif

            {{-- ③ items --}}
            <div>
                <div class="flex items-center gap-2.5 mb-2.5">
                    <span class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-sm">📦</span>
                    <h3 class="text-sm font-semibold text-gray-700">ລາຍການ ເຄື່ອງ <span class="text-gray-400 font-normal">({{ $record->items->count() }})</span></h3>
                </div>
                <div class="space-y-2.5">
                    @foreach ($record->items as $idx => $it)
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <div class="font-semibold text-gray-800">{{ $idx + 1 }}. {{ $it->item_name }} <span class="text-gray-400 font-normal">×{{ $it->qty }}{{ $it->unit ? ' '.$it->unit : '' }}</span>@if ($it->condition_status && $it->condition_status !== 'in_service') <span class="text-xs rounded-full px-2 py-0.5 {{ \App\Support\ConditionStatus::badge($it->condition_status) }}">{{ \App\Support\ConditionStatus::shortLabel($it->condition_status) }}</span>@endif</div>
                                @if ($it->estimated_value)<div class="text-xs text-gray-500 tabular-nums">{{ number_format($it->estimated_value, 2) }} {{ $it->currency }}</div>@endif
                            </div>
                            @if ($it->asset_code || $it->fixed_asset_no)
                                <div class="flex items-center gap-1.5 flex-wrap mt-1.5">
                                    @if ($it->asset_code)<span class="font-mono text-xs bg-gray-50 text-gray-600 border border-gray-200 rounded px-1.5 py-0.5">{{ $it->asset_code }}</span>@endif
                                    @if ($it->fixed_asset_no)<span class="font-mono text-xs bg-gray-50 text-gray-600 border border-gray-200 rounded px-1.5 py-0.5">{{ $it->fixed_asset_no }}</span>@endif
                                </div>
                            @endif
                            @if ($it->description)<div class="text-xs text-gray-500 mt-1.5">{{ $it->description }}</div>@endif
                            @if ($it->storage_location)<div class="text-xs text-gray-500 mt-1">📍 ບ່ອນ ເກັບ: <span class="text-gray-700">{{ $it->storage_location }}</span></div>@endif
                            @if ($it->condition_on_deposit || $it->condition_on_claim)
                                <div class="text-xs text-gray-500 mt-1">@if ($it->condition_on_deposit)ຝາກ: <span class="text-gray-700">{{ $it->condition_on_deposit }}</span>@endif @if ($it->condition_on_claim)· ເອົາຄືນ: <span class="text-gray-700">{{ $it->condition_on_claim }}</span>@endif</div>
                            @endif
                            @if ($it->photos->count())
                                <div class="flex gap-1.5 flex-wrap mt-2.5">
                                    @foreach ($it->photos as $p)
                                        <div class="relative">
                                            <img src="{{ $p->url }}" alt="" @click="$dispatch('open-lightbox', { src: $el.src })" class="w-16 h-16 rounded-lg object-cover border border-gray-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                                            <span class="absolute top-0 left-0 bg-black/60 text-white text-[9px] font-semibold px-1 rounded-br-md rounded-tl-lg">{{ ($slotTag[$p->slot] ?? null) ?: ($kindTag[$p->kind] ?? $p->kind) }}</span>
                                            @if ($editable)<button wire:click="removePhoto({{ $p->id }})" wire:confirm="ລຶບຮູບນີ້?" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-rose-600 text-white rounded-full text-[10px] leading-none shadow hover:bg-rose-700">×</button>@endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @can('disposal.create')
                                @if (in_array($record->status, ['stored', 'needs_fix']))
                                    <div class="mt-3 pt-3 border-t border-gray-100"><a href="{{ route('disposal.create', ['add' => 'deposit:' . $it->id]) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-2.5 py-1 hover:bg-rose-100 transition" title="ຂໍ ຈຳໜ່າຍ ເຄື່ອງ ຝາກ ນີ້">🗑 → Disposal</a></div>
                                @endif
                            @endcan
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ④ history --}}
            @if ($record->history->count())
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-xs">🕘</span><h3 class="text-sm font-semibold text-gray-700">ປະຫວັດ / History</h3></div>
                    <ol class="p-4 space-y-2">
                        @foreach ($record->history as $h)
                            <li class="flex gap-2.5 text-xs">
                                <span class="w-2 h-2 rounded-full bg-sky-400 mt-1 shrink-0"></span>
                                <span class="text-gray-600"><span class="font-mono font-medium text-gray-800">{{ $h->status }}</span> · {{ $h->user_name }} · {{ $h->created_at?->format('d/m/Y H:i') }}@if ($h->comment) — {{ $h->comment }}@endif</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>

        {{-- actions --}}
        <div class="bg-white/95 backdrop-blur rounded-xl border border-gray-200 px-5 py-3 flex flex-wrap gap-2 text-sm items-center sticky bottom-4 z-20 shadow-lg">
            @if ($record->status === 'draft')
                @if ($isOwner || $editable)<button wire:click="submit" class="inline-flex items-center gap-1.5 text-white bg-sky-600 font-medium rounded-lg px-4 py-2 hover:bg-sky-700 transition shadow-sm">📤 ສົ່ງຄຳຂໍ</button>@endif
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif ($record->status === 'submitted')
                @if ($editable)<button wire:click="openAccept" class="inline-flex items-center gap-1.5 text-white bg-emerald-600 font-medium rounded-lg px-4 py-2 hover:bg-emerald-700 transition shadow-sm">✓ ຮັບຝາກ + ກຳນົດບ່ອນເກັບ</button>@endif
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif ($record->status === 'accepted')
                @if ($editable)<button wire:click="openStore('confirmStored')" class="inline-flex items-center gap-1.5 text-white bg-emerald-700 font-medium rounded-lg px-4 py-2 hover:bg-emerald-800 transition shadow-sm">📦 ຢືນຢັນເກັບເຂົ້າ (ຮູບ)</button>@endif
                <button wire:click="$set('showCancel', true)" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif ($record->status === 'stored')
                @if ($editable)
                    <button wire:click="openClaim" class="inline-flex items-center gap-1.5 text-white bg-sky-700 font-medium rounded-lg px-4 py-2 hover:bg-sky-800 transition shadow-sm">↥ ບັນທຶກການເອົາຄືນ</button>
                    <button wire:click="openFlag" class="text-amber-700 border border-amber-200 bg-amber-50 rounded-lg px-3 py-2 hover:bg-amber-100 transition">⚠ ແຈ້ງບັນຫາ</button>
                @endif
            @elseif ($record->status === 'needs_fix')
                @if ($editable)<button wire:click="openStore('confirmFixed')" class="inline-flex items-center gap-1.5 text-white bg-cyan-600 font-medium rounded-lg px-4 py-2 hover:bg-cyan-700 transition shadow-sm">🔧 ຢືນຢັນແກ້ແລ້ວ → stored</button>@endif
            @else
                <span class="text-gray-400">— ບໍ່ມີ action ({{ $record->status }})</span>
            @endif

            @if ($deletable)
                <span class="ml-auto"></span>
                <button wire:click="openDelete" class="text-rose-600 border border-rose-200 rounded-lg px-3 py-2 hover:bg-rose-50 transition">🗑 ລຶບ</button>
            @endif
        </div>

        {{-- ── modals ── --}}
        @if ($showCancel)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📦</span>
                        <h3 class="text-base font-semibold text-gray-800">ຍົກເລີກການຝາກ</h3>
                        <button wire:click="$set('showCancel', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດຜົນ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showCancel', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="cancel" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-rose-700">ຢືນຢັນຍົກເລີກ</button></div>
                </div>
            </div>
        @endif

        @if ($showAccept)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-md rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📦</span>
                        <h3 class="text-base font-semibold text-gray-800">ຮັບຝາກ — ກຳນົດບ່ອນເກັບ</h3>
                        <button wire:click="$set('showAccept', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        @error('action')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ບ່ອນເກັບ (Location)</label><input type="text" wire:model="afLocation" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ປ້າຍຊັ້ນວາງ (Shelf label)</label><input type="text" wire:model="afShelf" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                        <p class="text-xs text-gray-400">* ຕ້ອງມີ ບ່ອນເກັບ ຫຼື ປ້າຍຊັ້ນວາງ ຢ່າງໜ້ອຍ 1 ຢ່າງ</p>
                        <div><label class="block text-xs font-medium text-gray-500 mb-1">ຄຳແນະນຳ (Instructions)</label><textarea wire:model="afInstructions" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showAccept', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="accept" class="bg-sky-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-sky-700">ຢືນຢັນຮັບຝາກ</button></div>
                </div>
            </div>
        @endif

        @if ($showStore)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📦</span>
                        <h3 class="text-base font-semibold text-gray-800">{{ $storeMode === 'confirmFixed' ? 'ຢືນຢັນແກ້ແລ້ວ — ຖ່າຍຮູບ' : 'ຢືນຢັນເກັບເຂົ້າ — ຖ່າຍຮູບ (ບັງຄັບ)' }}</h3>
                        <button wire:click="$set('showStore', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                    @error('storePhotos')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    @foreach ($record->items as $it)
                        <div class="border border-gray-200 rounded-xl p-3 space-y-2">
                            <div class="text-sm font-medium text-gray-700">{{ $it->item_name }} <span class="text-gray-400">×{{ $it->qty }}</span></div>
                            <input type="file" x-data="photoInput('storePhotos.{{ $it->id }}')" x-on:change="pick($event)" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                            <div wire:loading wire:target="storePhotos.{{ $it->id }}" class="text-xs text-sky-500">⏳ ກຳລັງອັບ…</div>
                            @if (! empty($storePhotos[$it->id]))<div class="flex gap-1.5 flex-wrap">@foreach ($storePhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-12 h-12 rounded-lg object-cover border-2 border-sky-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endif @endforeach</div>@endif
                        </div>
                    @endforeach
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showStore', false)" class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button>
                        <button wire:click="confirmStore" wire:loading.attr="disabled" wire:target="confirmStore,storePhotos" class="bg-sky-600 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-sky-700">ຢືນຢັນ</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($showFlag)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">⚠️</span>
                        <h3 class="text-base font-semibold text-gray-800">ແຈ້ງບັນຫາ (needs fix)</h3>
                        <button wire:click="$set('showFlag', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        @error('action')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                        <textarea wire:model="flagReason" rows="3" placeholder="ເຫດຜົນ / ບັນຫາ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showFlag', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="flagIssue" class="bg-sky-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-sky-700">ຢືນຢັນ</button></div>
                </div>
            </div>
        @endif

        @if ($showClaim)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">📦</span>
                        <h3 class="text-base font-semibold text-gray-800">ບັນທຶກການເອົາຄືນ — ຖ່າຍຮູບ (ບັງຄັບ)</h3>
                        <button wire:click="$set('showClaim', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                    @error('claimPhotos')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    <div><label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີເອົາຄືນ</label><input type="date" wire:model="claimDate" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    @foreach ($record->items as $it)
                        <div class="border border-gray-200 rounded-xl p-3 space-y-2">
                            <div class="text-sm font-medium text-gray-700">{{ $it->item_name }} <span class="text-gray-400">×{{ $it->qty }}</span></div>
                            <input type="file" x-data="photoInput('claimPhotos.{{ $it->id }}')" x-on:change="pick($event)" multiple accept="image/*" capture="environment" class="{{ $fileCls }}" />
                            <div wire:loading wire:target="claimPhotos.{{ $it->id }}" class="text-xs text-sky-500">⏳ ກຳລັງອັບ…</div>
                            @if (! empty($claimPhotos[$it->id]))<div class="flex gap-1.5 flex-wrap">@foreach ($claimPhotos[$it->id] as $f)@if ($f->isPreviewable())<img src="{{ $f->temporaryUrl() }}" alt="" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-12 h-12 rounded-lg object-cover border-2 border-sky-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endif @endforeach</div>@endif
                            <textarea wire:model="claimCondition.{{ $it->id }}" rows="1" placeholder="ສະພາບຕອນເອົາຄືນ (optional)…" class="w-full rounded-lg border-gray-300 text-xs"></textarea>
                        </div>
                    @endforeach
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showClaim', false)" class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button>
                        <button wire:click="confirmClaim" wire:loading.attr="disabled" wire:target="confirmClaim,claimPhotos" class="bg-sky-600 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-sky-700">ຢືນຢັນເອົາຄືນ</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($showDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑</span>
                        <h3 class="text-base font-semibold text-rose-700">🗑 ລຶບລາຍການຝາກ</h3>
                        <button wire:click="$set('showDelete', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        <p class="text-xs text-gray-500">ຍ້າຍໄປ Deleted Log (ກູ້ຄືນໄດ້). ໃສ່ເຫດຜົນ.</p>
                        <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດຜົນການລຶບ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        @error('deleteReason')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDelete', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="deleteRecord" wire:loading.attr="disabled" wire:target="deleteRecord" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-rose-700">ຢືນຢັນລຶບ</button></div>
                </div>
            </div>
        @endif

        {{-- admin: ຄືນ ສະຖານະ ໃບ (super_admin) --}}
        @if ($showStatusReset)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🔧</span>
                        <h3 class="text-base font-semibold text-slate-700">🔧 ຄືນ ສະຖານະ ໃບ (admin)</h3>
                        <button wire:click="$set('showStatusReset', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-3 overflow-y-auto">
                        <p class="text-xs text-gray-500">ສຳລັບ ແກ້ ຄວາມ ຜິດ / ທົດສອບ ເທົ່ານັ້ນ. ຕັ້ງ ສະຖານະ ໃບ ໃໝ່ ໂດຍ ກົງ (ບໍ່ ຜ່ານ ໂຟລ).</p>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ສະຖານະ ໃໝ່</label>
                            <select wire:model="resetStatus" class="w-full rounded-lg border-gray-300 text-sm">
                                @foreach (['draft' => 'draft', 'submitted' => 'submitted (ລໍ ຮັບ)', 'accepted' => 'accepted (ຮັບ ແລ້ວ)', 'stored' => 'stored (ເກັບ ໄວ້)', 'needs_fix' => 'needs_fix (ຕ້ອງ ແກ້)', 'claimed' => 'claimed (ເອົາ ຄືນ ແລ້ວ)', 'cancelled' => 'cancelled (ຍົກເລີກ)', 'disposal' => 'disposal (ກຳລັງ ຈຳໜ່າຍ)', 'disposed' => 'disposed (ຈຳໜ່າຍ ແລ້ວ)'] as $sv => $sl)
                                    <option value="{{ $sv }}">{{ $sl }}</option>
                                @endforeach
                            </select>
                            @error('resetStatus')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showStatusReset', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="applyStatusReset" class="bg-sky-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-sky-700">ຕັ້ງ ສະຖານະ</button></div>
                </div>
            </div>
        @endif

        @if ($showEdit)
            <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
                <div class="bg-white w-full md:max-w-2xl rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">✏️</span>
                        <h3 class="text-base font-semibold text-gray-800">✏️ ແກ້ໄຂ (Admin) — <span class="font-mono">{{ $record->request_number }}</span></h3>
                        <button wire:click="$set('showEdit', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                    </div>
                    <div class="p-5 space-y-4 overflow-y-auto">
                    {{-- ══ ① ລາຍການ ເຄື່ອງ + ຮູບ (ນຳ ໜ້າ — ຄອນເຊັບ ໃໝ່ ຄື ໜ້າ ສ້າງ) ══ --}}
                    <div class="space-y-2">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-sky-600 text-white flex items-center justify-center text-sm font-bold">1</span>
                            <h4 class="text-sm font-semibold text-gray-700">ລາຍການ ເຄື່ອງ + ຮູບ <span class="text-gray-400 font-normal">({{ $record->items->count() }})</span></h4>
                        </div>
                        @foreach ($record->items as $it)
                            <div wire:key="ei-{{ $it->id }}" class="border border-gray-200 rounded-xl p-3 space-y-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-md bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-bold shrink-0">{{ $loop->iteration }}</span>
                                    <span class="text-xs font-semibold text-gray-500">ລາຍການ ທີ {{ $loop->iteration }}</span>
                                </div>

                                {{-- essentials: ຊື່ · ລະຫັດ · ຊັບສິນ --}}
                                <div class="grid grid-cols-1 sm:grid-cols-6 gap-2.5">
                                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ຊື່ເຄື່ອງ <span class="text-rose-500">*</span></label><input type="text" wire:model="ei.{{ $it->id }}.item_name" class="w-full rounded-lg border-gray-300 text-sm" />@error("ei.{$it->id}.item_name")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror</div>
                                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ລະຫັດ (ສາງ/ອຸປະກອນ)</label><input type="text" wire:model="ei.{{ $it->id }}.asset_code" placeholder="ເຊັ່ນ EL-T001-1" class="w-full rounded-lg border-gray-300 text-sm font-mono" /></div>
                                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ທະບຽນຊັບສິນ</label><input type="text" wire:model="ei.{{ $it->id }}.fixed_asset_no" placeholder="Fixed asset no." class="w-full rounded-lg border-gray-300 text-sm font-mono" /></div>
                                </div>

                                {{-- photos: 3 ມູມ --}}
                                <div>
                                    <div class="rounded-lg bg-sky-50/40 border border-sky-100 p-3">
                                        <label class="block text-xs font-semibold text-sky-700 mb-2">📸 ຮູບ ຫຼັກຖານ — <span class="text-gray-400 font-normal">ແຍກ 3 ມູມ · ຮູບ ເກົ່າ ລຶບ ໄດ້ (×) · ເພີ່ມ ໃໝ່ ຈາກ ກ້ອງ/ຄັງ (ຫຍໍ້ ອັດຕະໂນມັດ)</span></label>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                            @foreach (\App\Livewire\Deposit\Create::PHOTO_SLOTS as $slot => $meta)
                                                @php $existing = $it->photos->where('kind', 'deposit')->where('slot', $slot); $pending = $edPhotos[$it->id][$slot] ?? []; @endphp
                                                <div wire:key="edph-{{ $it->id }}-{{ $slot }}" x-data="photoSlot('edCam.{{ $it->id }}', 'edGal.{{ $it->id }}', '{{ $slot }}')" class="rounded-lg border border-sky-100 bg-white p-2.5 flex flex-col gap-2">
                                                    <div class="text-[11px] font-semibold text-gray-600 flex items-center gap-1 min-h-[2rem]"><span class="text-base leading-none">{{ $meta[1] }}</span><span>{{ $meta[0] }}</span></div>
                                                    <div class="flex gap-1.5">
                                                        <label class="flex-1 cursor-pointer inline-flex items-center justify-center gap-1 text-xs font-medium text-white bg-sky-600 rounded-lg px-2 py-1.5 hover:bg-sky-700 transition">
                                                            📷 <span class="hidden lg:inline">ຖ່າຍ</span>
                                                            <input type="file" x-on:change="upload($event, 'cam')" accept="image/*" capture="environment" multiple class="hidden" />
                                                        </label>
                                                        <label class="flex-1 cursor-pointer inline-flex items-center justify-center gap-1 text-xs font-medium text-sky-700 bg-sky-50 border border-sky-200 rounded-lg px-2 py-1.5 hover:bg-sky-100 transition">
                                                            🖼 <span class="hidden lg:inline">ຄັງ</span>
                                                            <input type="file" x-on:change="upload($event, 'gal')" accept="image/*" multiple class="hidden" />
                                                        </label>
                                                    </div>
                                                    <div x-show="busy" class="text-[10px] text-sky-500">⏳ ກຳລັງ ຫຍໍ້ + ອັບ…</div>
                                                    @if ($existing->count() || ! empty($pending))
                                                        <div class="flex gap-1.5 flex-wrap">
                                                            @foreach ($existing as $p)
                                                                <div class="relative" title="ຮູບ ທີ່ ບັນທຶກ ແລ້ວ">
                                                                    <img src="{{ $p->url }}" alt="" @click="$dispatch('open-lightbox', { src: $el.src })" class="w-14 h-14 rounded-lg object-cover border-2 border-emerald-300 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                                                                    <button type="button" wire:click="removePhoto({{ $p->id }})" wire:confirm="ລຶບ ຮູບ ນີ້ ຖາວອນ?" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-rose-600 text-white rounded-full text-xs leading-none shadow" title="ລຶບ ຮູບ ເກົ່າ">×</button>
                                                                </div>
                                                            @endforeach
                                                            @foreach ($pending as $j => $f)
                                                                @if ($f->isPreviewable())
                                                                    <div class="relative" title="ຮູບ ໃໝ່ (ຍັງ ບໍ່ ບັນທຶກ)">
                                                                        <img src="{{ $f->temporaryUrl() }}" alt="" @click="$dispatch('open-lightbox', { src: $el.src })" class="w-14 h-14 rounded-lg object-cover border-2 border-sky-300 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                                                                        <button type="button" wire:click="removeEditPhoto({{ $it->id }}, '{{ $slot }}', {{ $j }})" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-rose-600 text-white rounded-full text-xs leading-none shadow" title="ຍົກເລີກ ຮູບ ໃໝ່">×</button>
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
                                        <p class="text-[10px] text-gray-400 mt-1.5">🟢 ຂອບ ຂຽວ = ບັນທຶກ ແລ້ວ · 🔵 ຂອບ ຟ້າ = ຮູບ ໃໝ່ ຈະ ບັນທຶກ ຕອນ ກົດ “ບັນທຶກ”</p>
                                    </div>
                                </div>

                                {{-- details: ຈຳນວນ · ໜ່ວຍ(dropdown) · ບ່ອນເກັບ · ສະຖານະພາບ (+ optional ທີ່ admin ເປີດ) --}}
                                <div class="grid grid-cols-2 sm:grid-cols-6 gap-2.5">
                                    <div><label class="block text-xs text-gray-500 mb-1">ຈຳນວນ <span class="text-rose-500">*</span></label><input type="number" min="1" wire:model="ei.{{ $it->id }}.qty" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                                    <div><label class="block text-xs text-gray-500 mb-1">ໜ່ວຍ</label><select wire:model="ei.{{ $it->id }}.unit" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($uoms as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach @if ($it->unit && ! $uoms->contains('name', $it->unit))<option value="{{ $it->unit }}">{{ $it->unit }} (ເກົ່າ)</option>@endif</select></div>
                                    <div class="col-span-2"><label class="block text-xs text-gray-500 mb-1">📍 ບ່ອນ ຈັດ ເກັບ ໄວ້</label><input type="text" wire:model="ei.{{ $it->id }}.storage_location" placeholder="ເຊັ່ນ: ສາງ A · ຊັ້ນ 2" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                                    <div class="col-span-2 sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ສະຖານະພາບ (Condition)</label><select wire:model="ei.{{ $it->id }}.condition_status" class="w-full rounded-lg border-gray-300 text-sm">@foreach (\App\Support\ConditionStatus::options() as $cv => $cl)<option value="{{ $cv }}">{{ $cl }}</option>@endforeach</select></div>
                                    <div class="col-span-2 sm:col-span-3"><label class="block text-xs text-gray-500 mb-1">ຄຳ ແນະນຳ (Recommendation) <span class="text-gray-400">· ໃຊ້ ໃນ ບັນຊີ letterhead</span></label><textarea wire:model="ei.{{ $it->id }}.recommendation" rows="2" placeholder="ເຊັ່ນ: ໃຊ້ ງານ ຕໍ່ ໄດ້ / ສະເໜີ ຈຳໜ່າຍ …" class="w-full rounded-lg border-gray-300 text-sm"></textarea>@error("ei.{$it->id}.recommendation")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror</div>
                                    @if ($fieldVisible['condition_on_deposit'] ?? false)
                                        <div class="col-span-2 sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ສະພາບ ຕອນ ຝາກ</label><input type="text" wire:model="ei.{{ $it->id }}.condition_on_deposit" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                                    @endif
                                    @if ($fieldVisible['estimated_value'] ?? false)
                                        <div class="col-span-2 sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">ມູນຄ່າ (ປະມານ)</label><input type="number" step="0.01" min="0" wire:model="ei.{{ $it->id }}.estimated_value" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                                    @endif
                                    @if ($fieldVisible['currency'] ?? false)
                                        <div><label class="block text-xs text-gray-500 mb-1">ສະກຸນເງິນ</label><select wire:model="ei.{{ $it->id }}.currency" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option><option value="LAK">LAK</option><option value="THB">THB</option><option value="USD">USD</option></select></div>
                                    @endif
                                    @if ($fieldVisible['description'] ?? false)
                                        <div class="col-span-2 sm:col-span-3"><label class="block text-xs text-gray-500 mb-1">ລາຍລະອຽດ (Description)</label><input type="text" wire:model="ei.{{ $it->id }}.description" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ══ ② ຂໍ້ມູນ ທົ່ວໄປ (ຕາມ ຫຼັງ) ══ --}}
                    <div class="space-y-2">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-slate-300 text-slate-700 flex items-center justify-center text-sm font-bold">2</span>
                            <h4 class="text-sm font-semibold text-gray-700">ຂໍ້ມູນ ທົ່ວໄປ</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ຜູ້ຮັບ / ຜູ້ບັນທຶກ ຂໍ້ມູນ / Receiver <span class="text-gray-400 font-normal">(ດຶງ ຈາກ ຜູ້ ໃຊ້)</span></label><select wire:model="ef.owner_user_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($ownerUsers as $ou)<option value="{{ $ou->id }}">{{ $ou->display_name ?: $ou->email }}</option>@endforeach</select>@error('ef.owner_user_id')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">ຜູ້ ເອົາ ມາ ຝາກ / Depositor <span class="text-gray-400 font-normal">(ບໍ່ ບັງຄັບ)</span></label>
                                <div class="flex gap-2">
                                    <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-xs shrink-0">
                                        <button type="button" wire:click="$set('ef.depositor_mode', 'internal')" class="px-2.5 py-2 transition {{ ($ef['depositor_mode'] ?? 'external') === 'internal' ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">ຄົນ ໃນ</button>
                                        <button type="button" wire:click="$set('ef.depositor_mode', 'external')" class="px-2.5 py-2 border-l border-gray-200 transition {{ ($ef['depositor_mode'] ?? 'external') === 'external' ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">ຄົນ ນອກ</button>
                                    </div>
                                    @if (($ef['depositor_mode'] ?? 'external') === 'internal')
                                        <select wire:model="ef.depositor_user_id" class="flex-1 rounded-lg border-gray-300 text-sm"><option value="">— ເລືອກ —</option>@foreach ($ownerUsers as $ou)<option value="{{ $ou->id }}">{{ $ou->display_name ?: $ou->email }}</option>@endforeach</select>
                                    @else
                                        <input type="text" wire:model="ef.depositor_name" placeholder="ຊື່ ຄົນ ນອກ…" class="flex-1 rounded-lg border-gray-300 text-sm" />
                                    @endif
                                </div>
                                @error('ef.depositor_name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ພະແນກ ເຈົ້າ ຂອງ / Department <span class="text-gray-400 font-normal">(ຄຸມ ສິດ ຈຳໜ່າຍ)</span></label><select wire:model="ef.owner_dept_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}@if ($d->unit) · {{ $d->unit->name }}@endif</option>@endforeach</select>@error('ef.owner_dept_id')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ປະເພດ</label><input type="text" wire:model="ef.item_category" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ແຫຼ່ງທີ່ມາ</label><input type="text" wire:model="ef.origin_source" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ສະຖານະ ການ ໃຊ້ງານ</label><select wire:model="ef.functional_status" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option><option value="usable">✅ ໃຊ້ ໄດ້ ປົກກະຕິ</option><option value="partial">⚠️ ໃຊ້ ໄດ້ ບາງ ສ່ວນ</option><option value="unusable">⛔ ໃຊ້ ບໍ່ ໄດ້</option></select></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີຝາກເດີມ <span class="text-gray-400 font-normal">(ເຄື່ອງຝາກເກົ່າ)</span></label><input type="date" wire:model="ef.original_deposit_date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ຜູ້ຮັບຝາກເດີມ</label><input type="text" wire:model="ef.original_receiver" placeholder="ຊື່ ຜູ້ ຮັບ ຝາກ ຕອນ ນັ້ນ" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ໄລຍະເວລາ</label><input type="text" wire:model="ef.expected_duration" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີຝາກ</label><input type="date" wire:model="ef.deposit_date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ຄາດເອົາຄືນ</label><input type="date" wire:model="ef.expected_claim_date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ບ່ອນເກັບ (ໃບ)</label><input type="text" wire:model="ef.storage_location" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div><label class="block text-xs font-medium text-gray-500 mb-1">ປ້າຍຊັ້ນວາງ</label><input type="text" wire:model="ef.storage_shelf_label" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                            <div class="sm:col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">ເຫດຜົນ</label><textarea wire:model="ef.deposit_reason" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                            <div class="sm:col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">ຄຳແນະນຳ warehouse</label><textarea wire:model="ef.warehouse_instructions" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                            <div class="sm:col-span-2"><label class="block text-xs font-medium text-gray-500 mb-1">ໝາຍເຫດ</label><textarea wire:model="ef.remark" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                        </div>
                    </div>

                    </div>
                    <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                        <button wire:click="$set('showEdit', false)" class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                        <button wire:click="saveEdit" wire:loading.attr="disabled" wire:target="saveEdit,edCam,edGal" class="bg-sky-600 text-white rounded-lg px-4 py-2 text-sm min-h-[40px] shadow-sm disabled:opacity-50 hover:bg-sky-700">ບັນທຶກ</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
