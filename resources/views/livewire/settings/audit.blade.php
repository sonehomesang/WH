@php
    $modCls = [
        'borrow' => 'bg-indigo-100 text-indigo-700', 'request' => 'bg-sky-100 text-sky-700',
        'deposit' => 'bg-emerald-100 text-emerald-700', 'da' => 'bg-amber-100 text-amber-700', 'oga' => 'bg-teal-100 text-teal-700',
        'user' => 'bg-rose-100 text-rose-700',
    ];
@endphp
<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')

        <div class="flex flex-wrap items-center gap-2">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ໝາຍເລກ/ຜູ້ໃຊ້/action…" class="w-60 rounded-md border-gray-300 text-sm" />
            <select wire:model.live="moduleFilter" class="rounded-md border-gray-300 text-sm">
                <option value="">ທຸກ module</option>
                <option value="borrow">Borrow</option><option value="request">Request</option><option value="deposit">Deposit</option><option value="da">DA</option><option value="oga">OGA</option><option value="user">Users</option>
                <option value="inventory">Inventory</option><option value="supplier">Supplier</option>
                <option value="uom">UoM</option><option value="unit">Unit</option><option value="department">Department</option><option value="location">Location</option><option value="building">Building</option><option value="room">Room</option>
            </select>
            <label class="text-xs text-gray-500">ແຕ່ <input type="date" wire:model.live="from" class="rounded-md border-gray-300 text-sm" /></label>
            <label class="text-xs text-gray-500">ຫາ <input type="date" wire:model.live="to" class="rounded-md border-gray-300 text-sm" /></label>
            <button wire:click="export" class="ml-auto text-sm text-gray-700 border border-gray-200 rounded-md px-3 py-1.5 hover:bg-gray-50">⬇ Export CSV</button>
        </div>

        <div class="bg-white border border-gray-100 rounded-lg overflow-auto max-h-[calc(100vh-16rem)]">
            <table class="min-w-full text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-gray-700 border-b border-gray-200 shadow-sm text-xs">
                    <tr>
                        <th class="text-left px-3 py-2 font-semibold">Module</th>
                        <th class="text-left px-3 py-2 font-medium">ໝາຍເລກ</th>
                        <th class="text-left px-3 py-2 font-medium">Action</th>
                        <th class="text-left px-3 py-2 font-medium">Status</th>
                        <th class="text-left px-3 py-2 font-medium">ຜູ້ກະທຳ</th>
                        <th class="text-left px-3 py-2 font-medium">ໝາຍເຫດ</th>
                        <th class="text-left px-3 py-2 font-medium">ເວລາ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2"><span class="text-xs rounded px-1.5 py-0.5 {{ $modCls[$r->module] ?? 'bg-gray-100 text-gray-600' }}">{{ $r->module }}</span></td>
                            <td class="px-3 py-2 font-mono text-xs text-gray-700 whitespace-nowrap">{{ $r->number }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $r->action }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $r->status }}</td>
                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $r->user_name ?? '—' }}@if ($r->role)<span class="text-xs text-gray-400"> · {{ $r->role }}</span>@endif</td>
                            <td class="px-3 py-2 text-xs text-gray-400 max-w-xs truncate">{{ $r->comment }}</td>
                            <td class="px-3 py-2 text-xs text-gray-400 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">ບໍ່ມີ ບັນທຶກ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $rows->links() }}</div>
    </div>
</div>
