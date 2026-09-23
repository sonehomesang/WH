{{-- Sub-dashboard status chips (ໃຊ້ຮ່ວມທຸກໂມດູລ). ໃສ່: $chips = [['key','label','count','alert'?]], $current. ທາງເລືອກ: $trailing (ສະແດງ ຕໍ່ທ້າຍ chips, ເຊັ່ນ "44 records"). --}}
@if (! empty($chips))
    <div class="flex flex-wrap items-center gap-1.5 mb-2">
        @foreach ($chips as $c)
            @php $on = ($current ?? '') === $c['key']; $alert = ! empty($c['alert']); @endphp
            <button type="button" wire:click="$set('statusFilter', '{{ $c['key'] }}')"
                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-sm transition
                    {{ $on
                        ? 'bg-sky-600 border-sky-600 text-white'
                        : ($alert && $c['count'] > 0 ? 'bg-red-50 border-red-200 text-red-700 hover:bg-red-100' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50') }}">
                <span>{{ $c['label'] }}</span>
                <span class="rounded-full px-1.5 text-sm font-medium {{ $on ? 'bg-white/25' : 'bg-gray-100 text-gray-600' }}">{{ number_format($c['count']) }}</span>
            </button>
        @endforeach
        {{-- optional non-clickable info pills (e.g. reference counts) — same row as the chips --}}
        @foreach ($pills ?? [] as $p)
            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white text-gray-600 px-2.5 py-1 text-sm">{{ $p['label'] }} <span class="font-semibold tabular-nums">{{ number_format($p['value']) }}</span></span>
        @endforeach
    </div>
@endif
