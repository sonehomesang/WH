<div class="pb-6" x-data="{ tab: 'replace' }">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        @php $editable = auth()->user()->can('settings.edit'); @endphp

        <div class="bg-white border border-gray-100 rounded-lg p-3 text-sm text-gray-600">
            ແກ້ ຄຳສັບ (ລາວ-ໄທ-ອັງກິດ) ໃນແອັບ ໂດຍບໍ່ຕ້ອງແກ້ code. <b>ແທນຄຳ (replace)</b> = ປ່ຽນ ຄຳ ທົ່ວທຸກໜ້າ — replace ສະເພາະ <b>ຂໍ້ຄວາມທີ່ເຫັນ</b> (ບໍ່ແຕະ class/script/option value). <b>Term key</b> = ຄ່າ ສຳລັບ <code class="bg-gray-100 px-1 rounded">@term('key')</code>.
            <span class="text-xs text-gray-400">ການປ່ຽນ ມີຜົນຫຼັງ ໂຫຼດໜ້າໃໝ່.</span>
        </div>

        {{-- sub-tabs --}}
        <div class="flex gap-1 border-b border-gray-200">
            <button @click="tab='replace'" :class="tab==='replace' ? 'border-sky-600 text-sky-700' : 'border-transparent text-gray-500'" class="px-3 py-2 text-sm border-b-2 -mb-px">ແທນຄຳ (replace)</button>
            <button @click="tab='term'" :class="tab==='term' ? 'border-sky-600 text-sky-700' : 'border-transparent text-gray-500'" class="px-3 py-2 text-sm border-b-2 -mb-px">Term keys</button>
        </div>

        {{-- REPLACE --}}
        <div x-show="tab==='replace'" class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຄຳ…" class="w-52 rounded-md border-gray-300 text-sm" />
                <select wire:model.live="groupFilter" class="rounded-md border-gray-300 text-sm max-w-[16rem]">
                    <option value="">ທຸກ ໝວດ (ໜ້າ) — {{ $totalReplace }} ຄຳ</option>
                    @foreach ($groups as $g)<option value="{{ $g->group }}">{{ $g->group }} ({{ $g->c }})</option>@endforeach
                </select>
                <label class="flex items-center gap-1.5 text-xs text-gray-600"><input type="checkbox" wire:model.live="changedOnly" class="rounded border-gray-300 text-sky-600" /> ສະເພາະ ທີ່ແກ້ແລ້ວ</label>
                <span class="text-xs text-gray-400">ໜ້າ {{ $repPage }}/{{ $repPages }} · ທັງໝົດ {{ $repTotal }} ຄຳ</span>
            </div>

            <div class="bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-20rem)]">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 text-gray-700 border-b border-gray-200 shadow-sm text-xs">
                        <tr>
                            <th class="text-left px-3 py-2 font-semibold">ໝວດ</th>
                            <th class="text-left px-3 py-2 font-medium w-1/3">ຄຳ ເດີມ (ໃນແອັບ)</th>
                            <th class="text-left px-3 py-2 font-medium w-1/4">ລາວ (ແກ້ຄຳ)</th>
                            <th class="text-left px-3 py-2 font-medium w-1/4">🇬🇧 English</th>
                            <th class="text-center px-3 py-2 font-medium">ໃຊ້</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($rep as $i => $r)
                            <tr wire:key="rep-{{ $r['id'] ?? 'new'.$i }}" class="{{ ($r['target'] ?? '') !== ($r['source'] ?? '') ? 'bg-amber-50/40' : '' }}">
                                <td class="px-3 py-1.5 text-[11px] text-gray-400 whitespace-nowrap align-top pt-3">{{ $r['group'] }}</td>
                                <td class="px-3 py-1.5"><input type="text" wire:model="rep.{{ $i }}.source" @disabled(! $editable) class="w-full rounded border-gray-200 text-sm bg-gray-50" dir="auto" readonly /></td>
                                <td class="px-3 py-1.5"><input type="text" wire:model.lazy="rep.{{ $i }}.target" @disabled(! $editable) class="w-full rounded border-gray-200 text-sm" dir="auto" /></td>
                                <td class="px-3 py-1.5"><input type="text" wire:model.lazy="rep.{{ $i }}.target_en" @disabled(! $editable) class="w-full rounded border-gray-200 text-sm" dir="ltr" placeholder="English…" /></td>
                                <td class="px-3 py-1.5 text-center align-middle"><input type="checkbox" wire:model="rep.{{ $i }}.is_active" @disabled(! $editable) class="rounded border-gray-300 text-sky-600" /></td>
                                <td class="px-3 py-1.5 text-right">@if ($editable)<button wire:click="remove('replace', {{ $i }})" class="text-red-400 hover:text-red-600 text-xs">✕</button>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400">ບໍ່ພົບ — ລອງ ປ່ຽນ ໝວດ/ຄົ້ນຫາ</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($repPages > 1)
                    <div class="flex items-center justify-center gap-1 p-2 border-t border-gray-50">
                        <button wire:click="goRep({{ $repPage - 1 }})" @disabled($repPage <= 1) class="px-2.5 py-1 rounded border border-gray-200 text-gray-600 disabled:opacity-40 hover:bg-gray-50">‹ ກ່ອນ</button>
                        <span class="px-2 text-xs text-gray-500">ໜ້າ {{ $repPage }} / {{ $repPages }}</span>
                        <button wire:click="goRep({{ $repPage + 1 }})" @disabled($repPage >= $repPages) class="px-2.5 py-1 rounded border border-gray-200 text-gray-600 disabled:opacity-40 hover:bg-gray-50">ຖັດໄປ ›</button>
                    </div>
                @endif
                @if ($editable)
                    <div class="flex flex-wrap items-center gap-2 p-3 border-t border-gray-50">
                        <button wire:click="syncTerms" wire:loading.attr="disabled" wire:target="syncTerms" wire:confirm="ດຶງ ຄຳ hard-coded ໃໝ່ ຈາກ ໜ້າ ຕ່າງໆ ເຂົ້າ catalogue? (ບໍ່ ທັບ ຄຳ ແປ ເກົ່າ)" class="text-sm text-emerald-700 border border-emerald-200 rounded-md px-3 py-1.5 hover:bg-emerald-50 disabled:opacity-50" title="ສະແກນ ໜ້າ ທັງໝົດ → ເພີ່ມ ຄຳ ໃໝ່ ໃຫ້ ແປ">
                            <span wire:loading.remove wire:target="syncTerms">🔄 ດຶງ ຄຳ ໃໝ່</span>
                            <span wire:loading wire:target="syncTerms">⏳ ກຳລັງ ດຶງ…</span>
                        </button>
                        <button wire:click="addRep" class="text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-1.5 hover:bg-sky-50">+ ເພີ່ມ ຄູ່ຄຳ ເອງ</button>
                        <button wire:click="save('replace')" wire:loading.attr="disabled" wire:target="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-1.5 hover:bg-sky-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="save">💾 ບັນທຶກ</span>
                            <span wire:loading wire:target="save">⏳ ກຳລັງບັນທຶກ…</span>
                        </button>
                        @if ($savedMsg)
                            <span wire:loading.remove wire:target="save" class="text-sm font-medium {{ $savedOk ? 'text-green-700' : 'text-red-600' }}">{{ $savedMsg }}</span>
                        @else
                            <span class="text-xs text-gray-400">ແກ້ຊ່ອງ "ແກ້ເປັນ" ໃຫ້ຕ່າງຈາກ ເດີມ → middleware ປ່ຽນທົ່ວແອັບ</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- TERM --}}
        <div x-show="tab==='term'" x-cloak class="bg-white border border-gray-100 rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium w-1/4">Key</th>
                        <th class="text-left px-3 py-2 font-medium w-1/4">ລາວ (value)</th>
                        <th class="text-left px-3 py-2 font-medium w-1/4">🇬🇧 English</th>
                        <th class="text-left px-3 py-2 font-medium">ໝາຍເຫດ</th>
                        <th class="text-center px-3 py-2 font-medium">ໃຊ້</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($term as $i => $r)
                        <tr wire:key="term-{{ $i }}">
                            <td class="px-3 py-1.5"><input type="text" wire:model="term.{{ $i }}.source" @disabled(! $editable) class="w-full rounded border-gray-200 text-sm font-mono" placeholder="status.draft" /></td>
                            <td class="px-3 py-1.5"><input type="text" wire:model="term.{{ $i }}.target" @disabled(! $editable) class="w-full rounded border-gray-200 text-sm" dir="auto" /></td>
                            <td class="px-3 py-1.5"><input type="text" wire:model="term.{{ $i }}.target_en" @disabled(! $editable) class="w-full rounded border-gray-200 text-sm" dir="ltr" placeholder="English…" /></td>
                            <td class="px-3 py-1.5"><input type="text" wire:model="term.{{ $i }}.note" @disabled(! $editable) class="w-full rounded border-gray-200 text-sm" /></td>
                            <td class="px-3 py-1.5 text-center"><input type="checkbox" wire:model="term.{{ $i }}.is_active" @disabled(! $editable) class="rounded border-gray-300 text-sky-600" /></td>
                            <td class="px-3 py-1.5 text-right">@if ($editable)<button wire:click="remove('term', {{ $i }})" class="text-red-400 hover:text-red-600 text-xs">✕</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400">ຍັງບໍ່ມີ term</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($editable)
                <div class="flex flex-wrap items-center gap-2 p-3 border-t border-gray-50">
                    <button wire:click="addTerm" class="text-sm text-sky-700 border border-sky-200 rounded-md px-3 py-1.5 hover:bg-sky-50">+ ເພີ່ມ term</button>
                    <button wire:click="save('term')" wire:loading.attr="disabled" wire:target="save" class="text-sm text-white bg-sky-600 rounded-md px-4 py-1.5 hover:bg-sky-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="save">💾 ບັນທຶກ</span>
                        <span wire:loading wire:target="save">⏳ ກຳລັງບັນທຶກ…</span>
                    </button>
                    @if ($savedMsg)<span wire:loading.remove wire:target="save" class="text-sm font-medium {{ $savedOk ? 'text-green-700' : 'text-red-600' }}">{{ $savedMsg }}</span>@endif
                </div>
            @endif
        </div>
    </div>
</div>
