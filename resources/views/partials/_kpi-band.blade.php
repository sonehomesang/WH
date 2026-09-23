{{-- Shared KPI band for list pages (identity is in the app top bar — no duplicate title).
     $tiles: array of ['label'=>, 'value'=>, 'hint'=>?, 'tone'=>?] (up to 5). --}}
<div class="rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden mb-3">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-px bg-gray-100">
        @foreach ($tiles as $t)
            <div class="bg-white px-3 py-2">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-bold tabular-nums leading-none {{ $t['tone'] ?? 'text-gray-800' }}">{{ number_format($t['value']) }}</span>
                    <span class="text-xs text-gray-400 truncate">{{ $t['hint'] ?? '' }}</span>
                </div>
                <p class="text-xs text-gray-500 truncate mt-0.5">{{ $t['label'] }}</p>
            </div>
        @endforeach
    </div>
</div>
