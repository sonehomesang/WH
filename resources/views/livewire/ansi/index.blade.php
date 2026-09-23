@php
    $badge = fn ($s) => match ($s) {
        'draft' => 'bg-gray-100 text-gray-600',
        'pending_hos', 'pending_manager', 'pending_warehouse' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        'cancelled' => 'bg-gray-100 text-gray-400',
        default => 'bg-gray-100 text-gray-600',
    };
    $dim = fn ($s) => in_array($s, ['completed', 'cancelled', 'rejected'], true);
@endphp

<div class="pb-6">
    {{-- box-shadow (not border-bottom) so the sticky column-header divider stays
         glued to the header under border-collapse — a real border is "owned" by
         the first body row and scrolls away. --}}
    <style>.ansi-list thead th { box-shadow: inset 0 -2px 0 #cbd5e1; }</style>
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- live KPIs — no status chips on this page, so the KPI band stays (not duplicated) --}}
        <div class="pt-3">@include('partials._kpi-band', ['tiles' => $kpi])</div>

        {{-- frozen header group: toolbar freezes; publish its bottom edge as
             --freeze-top so the table header sticks just under it --}}
        <div class="sticky top-16 z-30 bg-gray-100 pt-3 pb-2" x-data
             x-init="const root = document.documentElement;
                     const set = () => root.style.setProperty('--freeze-top', (64 + $el.offsetHeight) + 'px');
                     set(); new ResizeObserver(set).observe($el);">
            <div class="flex flex-col gap-2 py-3 sm:py-2 sm:min-h-[52px] sm:flex-row sm:items-center sm:gap-3">
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:flex-1 sm:min-w-0">
                    <div class="relative w-full sm:flex-1 sm:min-w-[9rem] sm:max-w-md">
                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔎</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search doc / originator / item…" class="w-full pl-8 rounded-lg border-gray-300 text-sm" />
                    </div>
                    <select wire:model.live="statusFilter" class="shrink-0 w-40 rounded-lg border-gray-300 text-sm">
                        <option value="">All statuses</option>
                        @foreach ($statusLabels as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach
                    </select>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <select wire:model.live="perPage" class="shrink-0 rounded-lg border-gray-300 text-sm min-h-[40px]" title="ຈຳນວນ ແຖວ ຕໍ່ ໜ້າ">
                        @foreach ([8, 10, 25, 50, 100] as $n)<option value="{{ $n }}">{{ $n }} {{ app()->getLocale() === 'en' ? 'rows' : 'ແຖວ' }}</option>@endforeach
                    </select>
                    @if ($canManageDeleted)<button wire:click="toggleDeleted" title="View deleted applications to restore" class="text-sm rounded-lg px-3 py-2 min-h-[40px] border transition whitespace-nowrap {{ $showDeleted ? 'bg-rose-600 text-white border-rose-600' : 'text-rose-700 bg-rose-50 border-rose-200 hover:bg-rose-100' }}">{{ $showDeleted ? '← Back to list' : '↩ Deleted items' }}</button>@endif
                    @can('ansi.create')<a href="{{ route('ansi.create') }}" wire:navigate class="text-sm font-medium text-white bg-sky-600 rounded-lg px-3.5 py-2 min-h-[40px] inline-flex items-center hover:bg-sky-700 transition shadow-sm whitespace-nowrap">+ New ANSI</a>@endcan
                </div>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-1.5 mb-3">{{ session('ok') }}</div>@endif

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <table class="wh-list ansi-list w-full text-sm table-fixed">
                <colgroup><col style="width:12%"><col style="width:16%"><col style="width:31%"><col style="width:8%"><col style="width:14%"><col style="width:19%"></colgroup>
                <thead class="sticky z-20 bg-slate-100 text-slate-600" style="top: var(--freeze-top, 14rem)">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-left">
                            <th class="px-3 py-1.5 font-semibold">Doc No.</th><th class="px-3 py-1.5 font-semibold">Originator</th>
                            <th class="px-3 py-1.5 font-semibold">Items</th><th class="px-3 py-1.5 font-semibold text-center">Qty</th>
                            <th class="px-3 py-1.5 font-semibold">Status</th><th class="px-3 py-1.5 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($records as $r)
                            <tr wire:key="ansi-{{ $r->id }}" class="transition {{ $dim($r->status) ? 'opacity-60 bg-gray-50/70' : 'hover:bg-sky-50/40' }}">
                                <td class="px-3 py-1.5 align-top"><a href="{{ route('ansi.show', $r) }}" wire:navigate class="font-mono text-xs font-medium text-indigo-600 hover:underline">{{ $r->request_number }}</a><div class="text-[10px] text-gray-400 mt-0.5">{{ $r->app_date?->format('d/m/Y') }}</div></td>
                                <td class="px-3 py-1.5 align-top"><div class="font-medium text-gray-800 break-words">{{ $r->originator_name }}</div><div class="text-xs text-gray-400">{{ $r->department?->name }}</div></td>
                                <td class="px-3 py-1.5 align-top text-gray-600 break-words">{{ \Illuminate\Support\Str::limit($r->summary_items, 90) ?: '—' }}</td>
                                <td class="px-3 py-1.5 align-top text-center"><span class="font-semibold tabular-nums">{{ $r->items->sum('qty_order') }}</span><div class="text-[10px] text-gray-400">{{ $r->items->count() }} line</div></td>
                                <td class="px-3 py-1.5 align-top"><span class="inline-flex text-xs font-semibold rounded-full px-2.5 py-1 {{ $badge($r->status) }}">{{ $statusLabels[$r->status] ?? $r->status }}</span></td>
                                <td class="px-3 py-1.5 align-top text-right">
                                    <div class="flex flex-wrap gap-1 justify-end">
                                        @if ($showDeleted)
                                            <button wire:click="restore({{ $r->id }})" class="text-xs font-medium text-emerald-700 border border-emerald-200 rounded-lg px-3 py-1.5 hover:bg-emerald-50">↩ Restore</button>
                                        @else
                                            <a href="{{ route('ansi.show', $r) }}" wire:navigate class="text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 inline-block">Open</a>
                                            @if ($canManageDeleted)<button wire:click="openDelete({{ $r->id }})" class="text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-3 py-1.5 hover:bg-rose-100">🗑 Delete</button>@endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-14 text-center text-gray-400"><div class="text-4xl mb-2">📝</div>{{ $showDeleted ? 'No deleted applications' : 'No applications yet — click “+ New ANSI”' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>

    @include('partials._delete-modal', ['title' => 'Delete this application?', 'subtitle' => $this->deletingRecord?->request_number])
</div>
