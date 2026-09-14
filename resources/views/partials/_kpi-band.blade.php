{{-- Shared KPI band for list pages (identity is in the app top bar — no duplicate title).
     $tiles: array of ['label'=>, 'value'=>, 'hint'=>?, 'tone'=>?] (up to 5). --}}
<div class="rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden mb-3">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-px bg-gray-100">
        @foreach ($tiles as $t)
            <div class="bg-white px-4 py-4">
                <p class="text-sm text-gray-500 truncate">{{ $t['label'] }}</p>
                <p class="text-2xl font-bold tabular-nums leading-tight mt-1 {{ $t['tone'] ?? 'text-gray-800' }}">{{ number_format($t['value']) }}</p>
                <p class="text-sm text-gray-500 truncate mt-0.5">{{ $t['hint'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
</div>
