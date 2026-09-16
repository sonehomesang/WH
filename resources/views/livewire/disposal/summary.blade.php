<div class="pb-8">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">
        <x-page-subheader :back="route('disposal')" back-label="ລາຍການ ຈຳໜ່າຍ">
            <x-slot:actions>
                <a href="{{ route('disposal.summary.pdf', ['from' => $from, 'to' => $to, 'department_id' => $department_id, 'status' => $status]) }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition">📄 PDF</a>
            </x-slot>
        </x-page-subheader>

        {{-- ══ HERO ══ --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
            <div class="p-5 flex items-center gap-4 flex-wrap">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white flex items-center justify-center text-2xl shadow-sm shrink-0">📊</div>
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-gray-900">ລາຍງານ ລວມ ການ ຈຳໜ່າຍ</h2>
                    <div class="text-sm text-gray-500">Disposal summary report <span>@if ($from || $to)· {{ $from ?: '…' }} → {{ $to ?: '…' }}@endif</span></div>
                </div>
            </div>
        </div>

        {{-- ══ STAT TILES ══ --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">📦</div>
                <div class="min-w-0"><div class="text-2xl font-bold text-gray-900 tabular-nums leading-none">{{ number_format($items->count()) }}</div><div class="text-xs text-gray-500 mt-1">ລາຍການ</div></div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">🔢</div>
                <div class="min-w-0"><div class="text-2xl font-bold text-gray-900 tabular-nums leading-none">{{ number_format($totalQty) }}</div><div class="text-xs text-gray-500 mt-1">ໜ່ວຍ ລວມ</div></div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm flex items-center gap-3 col-span-2 sm:col-span-1">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">💰</div>
                <div class="min-w-0"><div class="text-2xl font-bold text-gray-900 tabular-nums leading-none">{{ number_format($totalValue) }} <span class="text-sm font-medium text-gray-400">ກີບ</span></div><div class="text-xs text-gray-500 mt-1">ມູນຄ່າ ຄົງ ເຫຼືອ ລວມ</div></div>
            </div>
        </div>

        {{-- ══ FILTERS ══ --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex flex-wrap items-end gap-3 text-sm">
            <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 pr-1 pb-2"><span class="w-6 h-6 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center">🔍</span> ກັ່ນຕອງ</div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">ແຕ່ ວັນທີ</label><input type="date" wire:model.live="from" class="rounded-lg border-gray-300 text-sm" /></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">ຫາ ວັນທີ</label><input type="date" wire:model.live="to" class="rounded-lg border-gray-300 text-sm" /></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">ພະແນກ</label><select wire:model.live="department_id" class="rounded-lg border-gray-300 text-sm"><option value="">ທັງໝົດ</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div><label class="block text-xs font-medium text-gray-500 mb-1">ສະຖານະ</label><select wire:model.live="status" class="rounded-lg border-gray-300 text-sm"><option value="disposed">ຈຳໜ່າຍ ແລ້ວ</option><option value="approved">ອະນຸມັດ ແລ້ວ</option><option value="all">ອະນຸມັດ + ຈຳໜ່າຍ</option></select></div>
        </div>

        {{-- ══ TABLE ══ --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-auto max-h-[calc(100vh-24rem)]">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-slate-500 border-b border-gray-200">
                        <tr class="text-[11px] uppercase tracking-wide">
                            <th class="text-left font-semibold px-3 py-2.5 whitespace-nowrap">DS</th>
                            <th class="text-left font-semibold px-3 py-2.5 w-full">ລາຍການ</th>
                            <th class="text-left font-semibold px-3 py-2.5 whitespace-nowrap">ພະແນກ</th>
                            <th class="text-right font-semibold px-3 py-2.5 whitespace-nowrap">ຈຳນວນ</th>
                            <th class="text-left font-semibold px-3 py-2.5 whitespace-nowrap">ເຫດຜົນ</th>
                            <th class="text-left font-semibold px-3 py-2.5 whitespace-nowrap">ຄຳແນະນຳ</th>
                            <th class="text-right font-semibold px-3 py-2.5 whitespace-nowrap">ມູນຄ່າ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($items as $it)
                            <tr class="hover:bg-sky-50/40 transition">
                                <td class="px-3 py-2.5 align-top whitespace-nowrap"><a href="{{ route('disposal.show', $it->record_id) }}" wire:navigate class="font-mono text-indigo-600 hover:underline">{{ $it->record?->request_number }}</a></td>
                                <td class="px-3 py-2.5 align-top"><span class="font-medium text-gray-800">{{ $it->item_name }}</span>@if ($it->asset_code)<span class="ml-1.5 font-mono text-xs bg-gray-50 text-gray-500 border border-gray-200 rounded px-1.5 py-0.5">{{ $it->asset_code }}</span>@endif</td>
                                <td class="px-3 py-2.5 align-top whitespace-nowrap text-gray-600">{{ $it->record?->department?->name ?? '—' }}</td>
                                <td class="px-3 py-2.5 align-top text-right whitespace-nowrap tabular-nums font-medium text-gray-800">{{ $it->qty }} <span class="text-xs text-gray-400 font-normal">{{ $it->unit }}</span></td>
                                <td class="px-3 py-2.5 align-top whitespace-nowrap">@if ($it->reason)<span class="inline-flex items-center rounded-full bg-rose-50 text-rose-600 px-2 py-0.5 text-xs font-medium">{{ $it->reason }}</span>@else<span class="text-gray-300">—</span>@endif</td>
                                <td class="px-3 py-2.5 align-top whitespace-nowrap">@if ($it->recommendation)<span class="inline-flex items-center rounded-full bg-indigo-50 text-indigo-600 px-2 py-0.5 text-xs font-medium">{{ $it->recommendation }}</span>@else<span class="text-gray-300">—</span>@endif</td>
                                <td class="px-3 py-2.5 align-top text-right whitespace-nowrap tabular-nums text-gray-700">{{ $it->estimated_value ? number_format($it->estimated_value) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                <div class="text-3xl mb-2">📭</div>ບໍ່ ມີ ລາຍການ ໃນ ໄລຍະ ນີ້
                            </td></tr>
                        @endforelse
                    </tbody>
                    @if ($items->count())
                        <tfoot class="bg-gradient-to-r from-emerald-50 to-teal-50 font-semibold text-gray-800 border-t-2 border-emerald-200 sticky bottom-0">
                            <tr>
                                <td class="px-3 py-2.5" colspan="3">ລວມ {{ number_format($items->count()) }} {{ app()->getLocale() === 'en' ? 'items' : 'ລາຍການ' }}</td>
                                <td class="px-3 py-2.5 text-right whitespace-nowrap tabular-nums">{{ number_format($totalQty) }}</td>
                                <td colspan="2"></td>
                                <td class="px-3 py-2.5 text-right whitespace-nowrap tabular-nums text-emerald-700">{{ number_format($totalValue) }} ກີບ</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
