@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-600',
        'in_review', 'committee_review', 'technical_review', 'manager_review', 'executive_review' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'approved' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200', 'disposed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'cancelled' => 'bg-gray-100 text-gray-400',
        default => 'bg-gray-100 text-gray-600',
    };
    // ແຖບ ສີ ດ້ານ ເທິງ hero + ສີ ໄອຄອນ ຕາມ ສະຖານະ
    $strip = match (true) {
        $record->status === 'approved' => 'from-sky-500 to-indigo-500',
        $record->status === 'disposed' => 'from-emerald-500 to-teal-500',
        in_array($record->status, ['rejected', 'cancelled']) => 'from-rose-400 to-red-500',
        $record->status === 'draft' => 'from-slate-300 to-slate-400',
        default => 'from-amber-400 to-orange-500',
    };
    $dt = fn ($d) => $d?->format('d/m/Y') ?? '';
    $initials = fn ($n) => mb_strtoupper(mb_substr(trim((string) $n) ?: '?', 0, 2));
    $done = $record->signoffs->whereNotNull('user_id')->whereNotNull('signed_at')->count();
    $tot = $record->signoffs->whereNotNull('user_id')->count();
    $pct = $tot > 0 ? round($done / $tot * 100) : 0;
    $inReview = in_array($record->status, ['in_review', 'committee_review', 'technical_review', 'manager_review', 'executive_review']);
@endphp

<div class="pb-10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        {{-- ══ ONE frozen identity row (back · icon · record# · status · facts · actions) ══ --}}
        <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-2.5 bg-gradient-to-b from-rose-100 to-white border-b border-rose-200/70 backdrop-blur flex items-center gap-3 flex-wrap">
            <a href="{{ route('disposal') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                <span class="hidden sm:inline">ລາຍການ</span>
            </a>
            <span class="w-px h-5 bg-rose-200 shrink-0"></span>
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-rose-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑️</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-base font-bold text-gray-900">{{ $record->request_number }}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $badge($record->status) }}">{{ $record->statusLabel() }}</span>
                    @if ($tot > 0)<span class="text-xs font-medium {{ $done === $tot ? 'text-emerald-600' : 'text-amber-600' }}">{{ $done }}/{{ $tot }} ເຊັນ ແລ້ວ</span>@endif
                </div>
                <div class="hidden md:flex items-center gap-x-3 gap-y-0.5 flex-wrap text-[11px] text-gray-500 mt-0.5">
                    <span class="inline-flex items-center gap-1">📝 {{ $record->title ?: 'ໃບ ຈຳໜ່າຍ ເຄື່ອງ ຊຳລຸດ' }}</span>
                    @if ($record->department)<span class="inline-flex items-center gap-1">🏢 {{ $record->department->name }}</span>@endif
                    <span class="inline-flex items-center gap-1">📦 {{ $record->items->count() }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }} · {{ $record->items->sum('qty') }} ໜ່ວຍ</span>
                    @if ($record->preparedBy)<span class="inline-flex items-center gap-1">👤 {{ $record->preparedBy->display_name ?? $record->preparedBy->email }}</span>@endif
                    <span class="inline-flex items-center gap-1">📅 {{ $dt($record->created_at) }}</span>
                    @if ($record->original_deposit_date)<span class="inline-flex items-center gap-1 text-amber-700">📦 ຝາກເດີມ {{ $record->original_deposit_date->format('d/m/Y') }}</span>@endif
                    @if ($record->original_receiver)<span class="inline-flex items-center gap-1 text-amber-700">🙎 ຮັບຝາກ {{ $record->original_receiver }}</span>@endif
                </div>
            </div>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                @php $firstItem = $record->items->first(); @endphp
                @if ($firstItem)
                    <a href="{{ route('disposal.item.preview', [$record, $firstItem]) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-sky-600 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-sky-700 transition">👁 ພຣີວິວ</a>
                    <a href="{{ route('disposal.item.pdf', [$record, $firstItem]) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-gray-50 transition">📄 PDF</a>
                @endif
                @if ($canEdit && ! $editing)<button wire:click="openEdit" class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 min-h-[36px] hover:bg-amber-100 transition">✏️ ແກ້ໄຂ</button>@endif
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5 flex items-center gap-2">{{ session('ok') }}</div>@endif
        @error('action')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ $message }}</div>@enderror
        @if ($record->reject_reason && $record->status === 'draft')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">↩ ຖືກ ຕີ ກັບ: {{ $record->reject_reason }}</div>@endif

        @if (! $editing)
        {{-- ① ລາຍການ --}}
        <div>
            <div class="flex items-center gap-2.5 mb-2.5">
                <span class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-sm">📦</span>
                <h3 class="text-sm font-semibold text-gray-700">ລາຍການ ຈຳໜ່າຍ <span class="text-gray-400 font-normal">({{ $record->items->count() }})</span></h3>
            </div>
            <div class="space-y-2.5">
                @foreach ($record->items as $it)
                    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            @php $ph = $it->photos[0] ?? null; @endphp
                            @if ($ph)<img src="{{ \Illuminate\Support\Facades\Storage::url($ph) }}" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-16 h-16 rounded-lg object-cover border border-gray-200 shrink-0 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                            @else<div class="w-16 h-16 rounded-lg bg-gray-50 border border-gray-200 shrink-0 flex items-center justify-center text-gray-300 text-2xl">🗑️</div>@endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2 flex-wrap">
                                    <div class="min-w-0">
                                        <div class="font-semibold text-gray-800">{{ $loop->iteration }}. {{ $it->item_name }} <span class="text-gray-400 text-sm font-normal">×{{ $it->qty }} {{ $it->unit }}</span></div>
                                        <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                            @if ($it->asset_code)<span class="font-mono text-xs bg-gray-50 text-gray-600 border border-gray-200 rounded px-1.5 py-0.5">{{ $it->asset_code }}</span>@endif
                                            @if ($it->fixed_asset_no)<span class="font-mono text-xs bg-gray-50 text-gray-600 border border-gray-200 rounded px-1.5 py-0.5">{{ $it->fixed_asset_no }}</span>@endif
                                            <span class="text-[10px] uppercase font-semibold tracking-wide text-sky-600 bg-sky-50 rounded px-1.5 py-0.5">{{ $it->source_type }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 text-xs shrink-0">
                                        @if ($it->reason)<span class="inline-flex items-center rounded-full bg-rose-50 text-rose-600 px-2 py-0.5 font-medium">{{ $it->reason }}</span>@endif
                                        @if ($it->recommendation)<span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 text-indigo-600 px-2 py-0.5 font-medium">→ {{ $it->recommendation }}</span>@endif
                                    </div>
                                </div>
                                @if ($it->condition)<div class="text-xs text-gray-500 mt-1.5">ສະພາບ: <span class="text-gray-700">{{ $it->condition }}</span></div>@endif
                            </div>
                        </div>

                        @if (! empty($it->history))
                            <div class="mt-3 text-xs bg-slate-50 border border-slate-100 rounded-lg p-2.5 space-y-1">
                                <div class="text-slate-500 font-medium">ປະຫວັດ ບັນຫາ/ສ້ອມ ({{ count($it->history) }})</div>
                                @foreach ($it->history as $h)<div class="text-slate-600 flex gap-2"><span class="font-mono text-slate-400 shrink-0">{{ $h['date'] ?? '' }}</span><span>{{ $h['problem'] ?? '' }} → {{ $h['action'] ?? '' }}</span></div>@endforeach
                            </div>
                        @endif
                        @if (! empty($it->photos) && count($it->photos) > 1)
                            <div class="mt-3 flex gap-1.5 flex-wrap">@foreach (array_slice($it->photos, 1) as $p)<img src="{{ \Illuminate\Support\Facades\Storage::url($p) }}" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-14 h-14 rounded-lg object-cover border border-gray-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />@endforeach</div>
                        @endif

                        {{-- ຕໍ່ ລາຍການ: ສະແດງ ສະເພາະ ເມື່ອ ມີ ຫຼາຍ ລາຍການ (ໃບ ລາຍການ ດຽວ ໃຊ້ ປຸ່ມ ເທິງ ຫົວ) --}}
                        @if ($record->items->count() > 1)
                            <div class="mt-3 pt-3 border-t border-gray-100 flex gap-2">
                                <a href="{{ route('disposal.item.preview', [$record, $it]) }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-medium text-white bg-sky-600 rounded-lg px-3 py-1.5 hover:bg-sky-700 transition">👁 ພຣີວິວ ລາຍການ ນີ້</a>
                                <a href="{{ route('disposal.item.pdf', [$record, $it]) }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition">📄 PDF ລາຍການ ນີ້</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ② ຄຳ ແນະນຳ & ຮັບຮອງ --}}
        <div>
            <div class="flex items-center justify-between gap-2 mb-2.5">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">✍️</span>
                    <h3 class="text-sm font-semibold text-gray-700">ຄຳ ແນະນຳ &amp; ຮັບຮອງ <span class="text-gray-400 font-normal">/ Endorsement</span></h3>
                </div>
                @if ($tot > 0)<span class="text-xs font-semibold rounded-full px-2.5 py-1 {{ $done === $tot ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $done }}/{{ $tot }}</span>@endif
            </div>

            <div class="space-y-2.5">
                @foreach ($stages as $key => $st)
                    @php
                        $s = $record->signoffs->firstWhere('role_key', $key);
                        $state = ! $s || ! $s->user_id ? 'none' : ($s->decision === 'rejected' ? 'rejected' : ($s->signed_at ? 'signed' : 'pending'));
                        $av = ['none' => 'bg-gray-100 text-gray-300 border border-dashed border-gray-300', 'pending' => 'bg-amber-100 text-amber-700', 'signed' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-rose-100 text-rose-700'][$state];
                        $me = $this->canEndorse($key);
                    @endphp
                    <div class="bg-white border rounded-xl p-3.5 shadow-sm transition {{ $me ? 'border-emerald-300 ring-1 ring-emerald-100' : 'border-gray-200' }}" wire:key="endo-{{ $key }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0 {{ $av }}">
                                {{ $state === 'none' ? $st['order'] : $initials($s->name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-gray-800">{{ $st['label'] }}@if ($me)<span class="ml-1.5 text-[10px] font-semibold text-emerald-600 bg-emerald-50 rounded px-1.5 py-0.5 align-middle">ຊ່ອງ ຂອງ ຂ້ອຍ</span>@endif</div>
                                @if ($s && $s->user_id)<div class="text-xs text-gray-500 truncate">{{ $s->name }}@if ($s->title) · {{ $s->title }}@endif</div>
                                @else<div class="text-xs text-gray-400 italic">ຍັງ ບໍ່ ໄດ້ ມອບໝາຍ (ໃສ່ ຕອນ ແກ້ໄຂ)</div>@endif
                            </div>
                            <div class="shrink-0">
                                @if ($state === 'rejected')<span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 bg-rose-50 text-rose-700">✗ ຕີ ກັບ</span>
                                @elseif ($state === 'signed')<span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 bg-emerald-50 text-emerald-700">✓ ຮັບຮອງ</span>
                                @elseif ($state === 'pending')<span class="inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 bg-amber-50 text-amber-700">⏳ ລໍ</span>@endif
                            </div>
                        </div>

                        @if ($s && $s->signed_at)
                            <div class="mt-2 ml-[52px] text-xs text-gray-500">
                                @if ($s->recommendation)<span class="inline-flex items-center rounded bg-indigo-50 text-indigo-600 px-1.5 py-0.5 font-medium mr-1">{{ $s->recommendation }}</span>@endif
                                @if ($s->comment)<span class="text-gray-600">{{ $s->comment }}</span>@endif
                                <span class="text-gray-400">· {{ $dt($s->signed_at) }} {{ $s->signed_at?->format('H:i') }}</span>
                            </div>
                        @endif

                        @if ($me)
                            @if ($endorsingRole === $key && ! $endorseRejectMode)
                                <div class="mt-3 space-y-2.5 bg-emerald-50/60 border border-emerald-200 rounded-lg p-3.5">
                                    <div class="grid gap-2.5 sm:grid-cols-2">
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">ຄຳ ແນະນຳ ຕໍ່ C-Level</label>
                                            <select wire:model="endRecommendation" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— ເລືອກ —</option>@foreach ($recommendations as $rc)<option value="{{ $rc }}">{{ $rc }}</option>@endforeach</select></div>
                                        <div><label class="block text-xs font-medium text-gray-600 mb-1">ຄຳ ເຫັນ / Comment</label>
                                            <input type="text" wire:model="endComment" placeholder="ຄຳ ເຫັນ ຮັບຮອງ ຂອງ ທ່ານ…" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="confirmEndorse" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-emerald-600 rounded-lg px-4 py-2 hover:bg-emerald-700 transition">✓ ຢືນຢັນ ຮັບຮອງ</button>
                                        <button wire:click="cancelEndorse" class="text-sm text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
                                    </div>
                                </div>
                            @elseif ($endorsingRole === $key && $endorseRejectMode)
                                <div class="mt-3 space-y-2.5 bg-rose-50/60 border border-rose-200 rounded-lg p-3.5">
                                    <label class="block text-xs font-medium text-gray-600">ເຫດຜົນ ຕີ ກັບ</label>
                                    <textarea wire:model="endRejectReason" rows="2" placeholder="ບອກ ເຫດຜົນ ໃຫ້ ຜູ້ ເຮັດລິສ ແກ້ໄຂ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                                    @error('endRejectReason')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                                    <div class="flex gap-2">
                                        <button wire:click="confirmEndorseReject" class="text-sm font-medium text-white bg-rose-600 rounded-lg px-4 py-2 hover:bg-rose-700 transition">ຢືນຢັນ ຕີ ກັບ</button>
                                        <button wire:click="cancelEndorse" class="text-sm text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
                                    </div>
                                </div>
                            @else
                                <div class="mt-3 flex gap-2">
                                    <button wire:click="openEndorse('{{ $key }}')" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-emerald-600 rounded-lg px-4 py-2 hover:bg-emerald-700 transition shadow-sm">✍ ຮັບຮອງ ຊ່ອງ ຂອງ ຂ້ອຍ</button>
                                    <button wire:click="openEndorseReject('{{ $key }}')" class="text-sm font-medium text-rose-600 border border-rose-200 rounded-lg px-3 py-2 hover:bg-rose-50 transition">ຕີ ກັບ</button>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($record->status === 'approved')
                <div class="mt-3 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 p-4 flex items-center justify-between gap-2 flex-wrap">
                    <span class="text-sm font-medium text-emerald-800 inline-flex items-center gap-2">🎉 ຮັບຮອງ ຄົບ ທຸກ ຄົນ ແລ້ວ — ພ້ອມ ຈຳໜ່າຍ</span>
                    @can('disposal.activate')<button wire:click="openDispose" class="text-sm font-medium text-white bg-emerald-700 rounded-lg px-4 py-2 hover:bg-emerald-800 transition shadow-sm">ຢືນຢັນ ຈຳໜ່າຍ</button>@endcan
                </div>
            @elseif ($record->status === 'disposed')
                <div class="mt-3 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700 inline-flex items-center gap-2">✓ ຈຳໜ່າຍ ແລ້ວ{{ $record->registers_updated_at ? ' · ອັບເດດ ທະບຽນ ຕົ້ນທາງ '.$dt($record->registers_updated_at) : '' }}</div>
            @endif
        </div>

        {{-- actions --}}
        <div class="bg-white/95 backdrop-blur border border-gray-200 rounded-xl px-5 py-3 flex flex-wrap gap-2 items-center text-sm sticky bottom-4 shadow-lg">
            @if ($record->status === 'draft')
                @if (auth()->user()->can('disposal.create'))<button wire:click="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 text-white bg-sky-600 font-medium rounded-lg px-4 py-2 hover:bg-sky-700 transition shadow-sm">📨 ສົ່ງ ຂໍ ຮັບຮອງ</button>@endif
                <button wire:click="openCancel" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            @elseif (! in_array($record->status, ['disposed', 'cancelled', 'rejected']))
                <button wire:click="openCancel" class="text-gray-600 border border-gray-200 rounded-lg px-3 py-2 hover:bg-gray-50">ຍົກເລີກ ໃບ</button>
            @else
                <span class="text-gray-400 text-sm">— {{ $record->statusLabel() }}</span>
            @endif
            @can('disposal.delete')<span class="ml-auto"></span><button wire:click="openDelete" class="text-rose-600 border border-rose-200 rounded-lg px-3 py-2 hover:bg-rose-50 transition">🗑 ລຶບ</button>@endcan
        </div>
        @else
        {{-- ══ EDIT MODE ══ --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800 flex gap-2.5">
            <span class="text-lg leading-none">✏️</span>
            <span>ໂໝດ ແກ້ໄຂ — ໃບ ນີ້ ຍັງ ດຳເນີນ ຢູ່ ({{ $record->statusLabel() }}) ຈຶ່ງ ແກ້ໄຂ ໄດ້. ຕື່ມ ຂໍ້ມູນ + ມອບໝາຍ <b>ຜູ້ ຮັບຮອງ</b> → 💾 ບັນທຶກ → 👁 ພຣີວິວ ກ່ອນ ອອກ PDF. ເມື່ອ ຈຳໜ່າຍ ສຳເລັດ ຈະ ຖືກ ລັອກ.</span>
        </div>

        {{-- ຂໍ້ມູນ ໃບ --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-xs">📋</span><h3 class="text-sm font-semibold text-gray-700">ຂໍ້ມູນ ໃບ</h3></div>
            <div class="p-4 grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">ຫົວຂໍ້ / Title</label>
                    <input type="text" wire:model="editTitle" class="w-full rounded-lg border-gray-300 text-sm" placeholder="ຫົວຂໍ້ ໃບ ຈຳໜ່າຍ (optional)" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ພະແນກ / Department</label>
                    <select wire:model="editDept" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">ໝາຍເຫດ / Note</label>
                    <input type="text" wire:model="editNote" class="w-full rounded-lg border-gray-300 text-sm" placeholder="optional" />
                </div>
                <div class="sm:col-span-2 border-t border-dashed border-gray-200 pt-3">
                    <div class="text-xs font-semibold text-amber-700 mb-2 flex items-center gap-1.5"><span>📦</span> ເຄື່ອງຝາກເກົ່າ ທີ່ ຄ້າງ ໄວ້ ດົນ <span class="text-gray-400 font-normal">(ບໍ່ ບັງຄັບ)</span></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ວັນທີຝາກເດີມ / Original deposit date</label>
                            <input type="date" wire:model="editOrigDate" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ຜູ້ຮັບຝາກເດີມ / Original receiver</label>
                            <input type="text" wire:model="editOrigReceiver" class="w-full rounded-lg border-gray-300 text-sm" placeholder="ຊື່ ຜູ້ ຮັບ ຝາກ ຕອນ ນັ້ນ" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ຜູ້ ຮັບຮອງ --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">✍️</span>
                <h3 class="text-sm font-semibold text-gray-700">ຜູ້ ຮັບຮອງ / Endorsers</h3>
                <span class="text-xs text-gray-400">ມອບໝາຍ ຄົນ → ລະບົບ ສົ່ງ ອີເມລ ລິ້ງ ໃຫ້ ມາ ຮັບຮອງ (ອິດສະລະ ລຳດັບ)</span>
            </div>
            <div class="p-4 space-y-2.5">
                @foreach ($stages as $key => $st)
                    <div class="flex items-center gap-3" wire:key="assign-{{ $key }}">
                        <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-bold shrink-0">{{ $st['order'] }}</div>
                        <div class="grid gap-2 sm:grid-cols-2 flex-1 min-w-0">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $st['label'] }}</label>
                                <select wire:model="assignees.{{ $key }}.user_id" class="w-full rounded-lg border-gray-300 text-sm">
                                    <option value="">— ບໍ່ ມອບໝາຍ —</option>
                                    @foreach ($ownerUsers as $ou)<option value="{{ $ou->id }}">{{ $ou->display_name ?: $ou->email }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">ຕຳແໜ່ງ / Title (optional)</label>
                                <input type="text" wire:model="assignees.{{ $key }}.title" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <datalist id="edit-uoms">@foreach ($uoms as $u)<option value="{{ $u->name }}"></option>@endforeach</datalist>

        {{-- ລາຍການ --}}
        <div class="flex items-center gap-2.5 pt-1">
            <span class="w-6 h-6 rounded-md bg-sky-50 text-sky-600 flex items-center justify-center text-xs">📦</span>
            <h3 class="text-sm font-semibold text-gray-700">ລາຍການ ຈຳໜ່າຍ <span class="text-gray-400 font-normal">({{ count($ef) }})</span></h3>
        </div>
        @foreach ($ef as $i => $row)
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 space-y-3" wire:key="ef-{{ $i }}">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-gray-100 text-gray-500 flex items-center justify-center text-xs">{{ $i + 1 }}</span>
                        <span class="text-xs font-semibold rounded-full px-2 py-0.5 {{ ($row['source_type'] ?? 'new') === 'new' ? 'bg-gray-100 text-gray-500' : 'bg-sky-50 text-sky-700' }}">{{ ($row['source_type'] ?? 'new') === 'new' ? 'ໃໝ່ / new' : strtoupper($row['source_type']) }}</span>
                    </div>
                    <button wire:click="removeEditItem({{ $i }})" class="text-xs font-medium text-rose-600 border border-rose-200 rounded-lg px-2.5 py-1 hover:bg-rose-50 transition">✕ ຖອດ ອອກ</button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">ຊື່ ເຄື່ອງ / Item name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="ef.{{ $i }}.item_name" class="w-full rounded-lg border-gray-300 text-sm" />
                        @error("ef.$i.item_name")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ລະຫັດ ເຄື່ອງ / Asset code</label>
                        <input type="text" wire:model="ef.{{ $i }}.asset_code" class="w-full rounded-lg border-gray-300 text-sm font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ທະບຽນ ຊັບສິນ / Fixed asset no.</label>
                        <input type="text" wire:model="ef.{{ $i }}.fixed_asset_no" class="w-full rounded-lg border-gray-300 text-sm font-mono" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ຈຳນວນ / Qty <span class="text-rose-500">*</span></label>
                            <input type="number" min="1" wire:model="ef.{{ $i }}.qty" class="w-full rounded-lg border-gray-300 text-sm" />
                            @error("ef.$i.qty")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ໜ່ວຍ / Unit</label>
                            <input type="text" list="edit-uoms" wire:model="ef.{{ $i }}.unit" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ສະພາບ / Condition</label>
                        <input type="text" wire:model="ef.{{ $i }}.condition" class="w-full rounded-lg border-gray-300 text-sm" placeholder="ເຊັ່ນ: ຮ່າງກາຍ ແຕກ, ບໍ່ ຕິດ ໄຟ…" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">ສະຖານະ ການ ໃຊ້ງານ / Functional</label>
                        <select wire:model="ef.{{ $i }}.functional_status" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">— ເລືອກ —</option>
                            <option value="usable">✅ ໃຊ້ ໄດ້ ປົກກະຕິ</option>
                            <option value="partial">⚠️ ໃຊ້ ໄດ້ ບາງ ສ່ວນ</option>
                            <option value="unusable">⛔ ໃຊ້ ບໍ່ ໄດ້</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ເຫດຜົນ / Reason</label>
                            <select wire:model="ef.{{ $i }}.reason" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($reasons as $rz)<option value="{{ $rz }}">{{ $rz }}</option>@endforeach</select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ລາຍລະອຽດ ເຫດຜົນ</label>
                            <input type="text" wire:model="ef.{{ $i }}.reason_detail" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ຄຳ ແນະນຳ / Recommendation</label>
                            <select wire:model="ef.{{ $i }}.recommendation" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($recommendations as $rc)<option value="{{ $rc }}">{{ $rc }}</option>@endforeach</select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ລາຍລະອຽດ ຄຳ ແນະນຳ</label>
                            <input type="text" wire:model="ef.{{ $i }}.recommendation_detail" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ມູນຄ່າ ຄົງ ເຫຼືອ / Value</label>
                            <input type="number" step="0.01" min="0" wire:model="ef.{{ $i }}.estimated_value" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">ສະກຸນ ເງິນ / Currency</label>
                            <select wire:model="ef.{{ $i }}.currency" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option><option value="LAK">LAK</option><option value="THB">THB</option><option value="USD">USD</option></select>
                        </div>
                    </div>
                </div>

                {{-- photos --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">ຮູບ ຫຼັກຖານ / Photos</label>
                    <div class="flex flex-wrap gap-2 items-center">
                        @foreach (($row['photos'] ?? []) as $p => $path)
                            <div class="relative group" wire:key="ef-{{ $i }}-ph-{{ $p }}">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-16 h-16 rounded-lg object-cover border border-gray-200 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" />
                                <button wire:click="removeExistingPhoto({{ $i }}, {{ $p }})" type="button" class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white rounded-full w-5 h-5 text-xs leading-none shadow hover:bg-rose-700">✕</button>
                            </div>
                        @endforeach
                        @if (isset($newPhotos[$i]))
                            @foreach ($newPhotos[$i] as $f)<img src="{{ $f->temporaryUrl() }}" @click.stop.prevent="$dispatch('open-lightbox', { src: $el.src })" class="w-16 h-16 rounded-lg object-cover border-2 border-emerald-300 cursor-zoom-in hover:ring-2 hover:ring-sky-300 transition" wire:key="ef-{{ $i }}-np-{{ $loop->index }}" />@endforeach
                        @endif
                        <label class="w-16 h-16 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xl cursor-pointer hover:border-sky-400 hover:text-sky-500 hover:bg-sky-50/50 transition">
                            +<input type="file" wire:model="newPhotos.{{ $i }}" multiple accept="image/*" class="hidden" />
                        </label>
                    </div>
                    <div wire:loading wire:target="newPhotos.{{ $i }}" class="text-xs text-sky-500 mt-1.5">⏳ ກຳລັງ ອັບ ໂຫຼດ…</div>
                    @error("newPhotos.$i.*")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        @endforeach

        <button wire:click="addEditItem" class="w-full text-sm font-medium text-sky-700 border border-dashed border-sky-300 rounded-xl px-3 py-2.5 hover:bg-sky-50 transition">+ ເພີ່ມ ລາຍການ ໃໝ່</button>

        {{-- save / cancel --}}
        <div class="bg-white/95 backdrop-blur border border-gray-200 rounded-xl px-5 py-3 flex flex-wrap gap-2 items-center text-sm sticky bottom-4 shadow-lg">
            <button wire:click="saveEdit" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 text-white bg-emerald-600 font-medium rounded-lg px-5 py-2 hover:bg-emerald-700 transition shadow-sm">💾 ບັນທຶກ</button>
            <button wire:click="cancelEdit" class="text-gray-600 border border-gray-200 rounded-lg px-4 py-2 hover:bg-gray-50">ຍົກເລີກ</button>
            <span wire:loading wire:target="saveEdit" class="text-xs text-gray-400">ກຳລັງ ບັນທຶກ…</span>
        </div>
        @endif
    </div>

    {{-- cancel modal --}}
    @if ($showCancel)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-rose-200 to-rose-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-rose-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑️</span>
                    <h3 class="text-base font-semibold text-gray-800">ຍົກເລີກ ໃບ ຈຳໜ່າຍ</h3>
                    <button wire:click="$set('showCancel', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <textarea wire:model="cancelReason" rows="2" placeholder="ເຫດຜົນ (optional)" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showCancel', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="confirmCancel" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-rose-700">ຢືນຢັນ</button></div>
            </div>
        </div>
    @endif

    {{-- dispose (confirm registers) modal --}}
    @if ($showDispose)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-rose-200 to-rose-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-rose-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑️</span>
                    <h3 class="text-base font-semibold text-gray-800">ຢືນຢັນ ຈຳໜ່າຍ</h3>
                    <button wire:click="$set('showDispose', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="updateRegisters" class="mt-0.5 rounded border-gray-300 text-emerald-600" />
                        <span>ອັບເດດ ທະບຽນ ຕົ້ນທາງ ນຳ <span class="block text-xs text-gray-400">Equipment → retired · Inventory → ปิด ໃຊ້ · Deposit → disposed</span></span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDispose', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="confirmDispose" class="bg-sky-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-sky-700">ຢືນຢັນ ຈຳໜ່າຍ</button></div>
            </div>
        </div>
    @endif

    {{-- delete modal --}}
    @if ($showDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white w-full max-w-sm rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-rose-200 to-rose-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-500 to-rose-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🗑️</span>
                    <h3 class="text-base font-semibold text-rose-700">🗑 ລຶບ ໃບ ຈຳໜ່າຍ</h3>
                    <button wire:click="$set('showDelete', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">✕</button>
                </div>
                <div class="p-5 space-y-3 overflow-y-auto">
                    <p class="text-xs text-gray-500">ຍ້າຍ ໄປ Deleted Log (ກູ້ຄືນ ໄດ້).</p>
                    <textarea wire:model="deleteReason" rows="3" placeholder="ເຫດຜົນ ການ ລຶບ…" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    @error('deleteReason')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0"><button wire:click="$set('showDelete', false)" class="bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-h-[40px] hover:bg-gray-50">ປິດ</button><button wire:click="deleteRecord" class="bg-rose-600 text-white rounded-lg px-3 py-1.5 text-sm min-h-[40px] shadow-sm hover:bg-rose-700">ຢືນຢັນ ລຶບ</button></div>
            </div>
        </div>
    @endif
</div>
