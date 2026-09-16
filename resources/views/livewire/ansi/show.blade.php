@php
    $labels = \App\Models\AnsiApplication::STATUS_LABELS;
    $badge = match ($record->status) {
        'draft' => 'bg-gray-100 text-gray-600',
        'pending_hos', 'pending_manager', 'pending_warehouse' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        default => 'bg-gray-100 text-gray-400',
    };
    $idx = ['draft' => 1, 'pending_hos' => 2, 'pending_manager' => 3, 'pending_warehouse' => 4, 'completed' => 5][$record->status] ?? 0;
    $steps = [[1, 'Originator', 'Complete & submit'], [2, 'HoS / TL', 'Review & endorse'], [3, 'Manager', 'Review & approve'], [4, 'Warehouse', 'Item no. · PR'], [5, 'Closeout', 'Notify originator']];
    $kv = fn ($l, $v) => '<div><div class="text-xs text-gray-400">'.e($l).'</div><div class="text-gray-800 mt-0.5">'.e($v ?: '—').'</div></div>';
@endphp

<div class="pb-10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col gap-4">
        {{-- ══ ONE frozen identity row (back · icon · record# · status · facts) ══ --}}
        <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-2.5 bg-gradient-to-b from-sky-100 to-white border-b border-sky-200/70 backdrop-blur flex items-center gap-3 flex-wrap">
            <a href="{{ route('ansi') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                <span class="hidden sm:inline">ANSI list</span>
            </a>
            <span class="w-px h-5 bg-sky-200 shrink-0"></span>
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-sky-400 text-white grid place-items-center text-lg shadow-sm shrink-0">🆕</span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-base font-bold text-gray-900">{{ $record->request_number }}</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $badge }}">{{ $labels[$record->status] ?? $record->status }}</span>
                </div>
                <div class="hidden md:flex items-center gap-x-3 gap-y-0.5 flex-wrap text-[11px] text-gray-500 mt-0.5">
                    <span class="inline-flex items-center gap-1">👤 {{ $record->originator_name }}</span>
                    <span class="inline-flex items-center gap-1">🏢 {{ $record->department?->name }}</span>
                </div>
            </div>
        </div>

        @if (session('ok'))<div class="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5">{{ session('ok') }}</div>@endif
        @error('act')<div class="text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-4 py-2.5">{{ $message }}</div>@enderror

        {{-- workflow stepper --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="h-1.5 bg-gradient-to-r from-sky-500 to-cyan-500"></div>
            @if ($record->status === 'rejected')
                <div class="mx-5 mb-4 text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-lg px-4 py-2.5">✕ Rejected at <b>{{ $record->reject_stage }}</b>: {{ $record->reject_reason }}</div>
            @elseif ($record->status === 'cancelled')
                <div class="mx-5 mb-4 text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5">Cancelled by originator.</div>
            @endif
            <div class="flex gap-1.5 px-5 py-4 bg-gray-50 border-t border-gray-100 flex-wrap">
                @foreach ($steps as [$n, $title, $desc])
                    @php $st = $record->status === 'completed' || $n < $idx ? 'done' : ($n === $idx && ! in_array($record->status, ['rejected', 'cancelled']) ? 'curr' : 'todo');
                        $c = ['done' => ['bg-emerald-50', 'bg-emerald-500', 'text-emerald-700'], 'curr' => ['bg-amber-50 ring-2 ring-amber-300', 'bg-amber-500', 'text-amber-700'], 'todo' => ['', 'bg-gray-300', 'text-gray-400']][$st]; @endphp
                    <div class="flex-1 min-w-[110px] rounded-lg px-3 py-2 {{ $c[0] }}">
                        <div class="flex items-center gap-1.5 text-xs font-bold {{ $c[2] }}"><span class="w-5 h-5 rounded-full text-white text-[11px] flex items-center justify-center {{ $c[1] }}">{{ $st === 'done' ? '✓' : $n }}</span>{{ $title }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $desc }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ACTION BAR --}}
        @php $hasAction = ($this->isOriginator() && $record->status === 'draft') || $this->canEndorse() || $this->canApprove() || $this->canWarehouse() || ($this->isOriginator() && in_array($record->status, ['pending_hos','pending_manager','pending_warehouse'])); @endphp
        @if ($hasAction)
        <div class="bg-white border border-indigo-100 rounded-xl shadow-sm px-5 py-3.5 flex items-center gap-2 flex-wrap">
            <span class="text-sm font-semibold text-gray-700">Your action:</span>
            <div class="ml-auto flex gap-2 flex-wrap">
                @if ($this->isOriginator() && $record->status === 'draft')
                    <button wire:click="openEdit" class="text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2 hover:bg-amber-100">✏️ Edit</button>
                    <button wire:click="cancel" wire:confirm="Cancel this application?" class="text-sm font-medium text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">Cancel</button>
                    <button wire:click="submit" class="text-sm font-semibold text-white bg-sky-600 rounded-lg px-5 py-2 hover:bg-sky-700">Submit → HoS/TL</button>
                @elseif ($this->canEndorse())
                    <button wire:click="openReject('hos')" class="text-sm font-medium text-rose-700 bg-white border border-rose-200 rounded-lg px-4 py-2 hover:bg-rose-50">✕ Reject</button>
                    <button wire:click="endorse" class="text-sm font-semibold text-white bg-emerald-600 rounded-lg px-5 py-2 hover:bg-emerald-700">✓ Endorse → Manager</button>
                @elseif ($this->canApprove())
                    <button wire:click="openReject('manager')" class="text-sm font-medium text-rose-700 bg-white border border-rose-200 rounded-lg px-4 py-2 hover:bg-rose-50">✕ Reject</button>
                    <button wire:click="approve" class="text-sm font-semibold text-white bg-emerald-600 rounded-lg px-5 py-2 hover:bg-emerald-700">✓ Approve → Warehouse</button>
                @elseif ($this->canWarehouse())
                    <button wire:click="openReject('warehouse')" class="text-sm font-medium text-rose-700 bg-white border border-rose-200 rounded-lg px-4 py-2 hover:bg-rose-50">✕ Reject</button>
                    <button wire:click="openWarehouse" class="text-sm font-semibold text-white bg-sky-600 rounded-lg px-5 py-2 hover:bg-sky-700">🏷️ Complete (item no. + PR)</button>
                @elseif ($this->isOriginator())
                    <button wire:click="cancel" wire:confirm="Cancel this application?" class="text-sm font-medium text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">Cancel application</button>
                @endif
            </div>
        </div>
        @endif

        {{-- GENERAL INFO --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">General Information</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                {!! $kv('Originator', $record->originator_name) !!}
                {!! $kv('HoS / TL', $record->hos_name) !!}
                {!! $kv('Manager', $record->manager_name) !!}
                {!! $kv('Branch / Unit', $record->unit?->name) !!}
                {!! $kv('Department', $record->department?->name) !!}
                {!! $kv('Section / Team', $record->section_team) !!}
                {!! $kv('Phone', $record->phone) !!}
                {!! $kv('Date', $record->app_date?->format('d/m/Y')) !!}
                {!! $kv('Sub Assembly', $record->sub_assembly) !!}
                {!! $kv('Functional System', $record->functional_system) !!}
                <div class="col-span-2 md:col-span-3">{!! $kv('Purpose', $record->purpose) !!}</div>
            </div>
        </div>

        {{-- ITEMS --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50/70 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-700">Detail of New Stock Items ({{ $record->items->count() }})</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs min-w-[900px]">
                    <thead class="bg-slate-50 text-slate-500"><tr class="text-[10.5px] uppercase tracking-wide text-left">
                        <th class="px-2 py-2 font-semibold">#</th><th class="px-2 py-2 font-semibold">Stock</th><th class="px-2 py-2 font-semibold">Description</th>
                        <th class="px-2 py-2 font-semibold">Price</th><th class="px-2 py-2 font-semibold">Qty</th><th class="px-2 py-2 font-semibold">Unit</th>
                        <th class="px-2 py-2 font-semibold">Min/Max</th><th class="px-2 py-2 font-semibold">Supplier</th><th class="px-2 py-2 font-semibold">Haz</th><th class="px-2 py-2 font-semibold">Crit</th>
                        <th class="px-2 py-2 font-semibold">Storage</th>@if ($record->status === 'completed')<th class="px-2 py-2 font-semibold text-emerald-700">Item No.</th>@endif
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($record->items as $it)
                            <tr>
                                <td class="px-2 py-2 text-center text-gray-400 font-bold">{{ $loop->iteration }}</td>
                                <td class="px-2 py-2">{{ $it->stock ? 'Yes' : 'No' }}</td>
                                <td class="px-2 py-2 text-gray-800">{{ $it->description }}</td>
                                <td class="px-2 py-2">{{ $it->price_usd ? number_format($it->price_usd, 2) : '—' }}</td>
                                <td class="px-2 py-2 font-semibold">{{ $it->qty_order }}</td>
                                <td class="px-2 py-2">{{ $it->unit ?: '—' }}</td>
                                <td class="px-2 py-2 text-gray-500">{{ $it->min_qty ?? '—' }} / {{ $it->max_qty ?? '—' }}</td>
                                <td class="px-2 py-2 text-gray-500">{{ $it->suggested_supplier ?: '—' }}</td>
                                <td class="px-2 py-2">{{ $it->hazardous ? '⚠️ Yes' : 'No' }}</td>
                                <td class="px-2 py-2">{{ $it->criticality ? 'Yes' : 'No' }}</td>
                                <td class="px-2 py-2">{{ $it->special_storage }}</td>
                                @if ($record->status === 'completed')
                                    <td class="px-2 py-2 font-mono text-emerald-700 whitespace-nowrap">
                                        @if ($it->created_inventory_id && $it->item_number)
                                            <a href="{{ route('inventory', ['search' => $it->item_number]) }}" wire:navigate class="inline-flex items-center gap-1 text-sky-700 hover:text-sky-900 hover:underline" title="ເປີດ ໃນ Inventory">{{ $it->item_number }} <span class="text-emerald-600">↗</span></a>
                                        @else
                                            {{ $it->item_number ?: '—' }}
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- WAREHOUSE RESULT --}}
        @if ($record->status === 'completed')
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-sm text-emerald-800">
                <div class="font-semibold mb-1">✓ Completed by Warehouse</div>
                <div class="flex flex-wrap gap-x-6 gap-y-1 text-emerald-700">
                    <span>PR No.: <b>{{ $record->pr_number ?: '—' }}</b></span>
                    <span>By: {{ optional(\App\Models\User::find($record->warehoused_by))->display_name ?? '—' }}</span>
                    <span>{{ $record->warehoused_at?->format('d/m/Y H:i') }}</span>
                </div>
                @if ($record->warehouse_note)<div class="mt-1 text-emerald-700">Note: {{ $record->warehouse_note }}</div>@endif
            </div>
        @endif

        {{-- ATTACHMENTS --}}
        @if ($record->attachments->count())
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">📎 Attachments</h3>
                <ul class="flex flex-col gap-1.5">
                    @foreach ($record->attachments as $a)
                        <li><a href="{{ $a->url }}" target="_blank" class="text-sm text-sky-700 hover:underline">📄 {{ $a->original_name ?: 'file' }}</a> <span class="text-xs text-gray-400">{{ $a->size ? round($a->size / 1024).' KB' : '' }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- HISTORY --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">History</h3>
            <ol class="flex flex-col gap-2">
                @foreach ($record->history as $h)
                    <li class="flex items-start gap-2 text-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 mt-1.5 shrink-0"></span>
                        <div><span class="font-semibold text-gray-700">{{ ucfirst(str_replace('_', ' ', $h->action)) }}</span> · {{ $h->user_name }} · <span class="text-gray-400">{{ $h->created_at?->format('d/m/Y H:i') }}</span>@if ($h->comment)<div class="text-gray-500">{{ $h->comment }}</div>@endif</div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- REJECT MODAL --}}
    @if ($showReject)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
            <div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl p-5 space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">Reject application</h3>
                <p class="text-sm text-gray-500">This returns the application to the originator with your comment.</p>
                <textarea wire:model="rejectReason" rows="3" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Reason for rejecting…"></textarea>
                @error('rejectReason')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showReject', false)" class="text-sm border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">Cancel</button>
                    <button wire:click="reject" class="text-sm text-white bg-rose-600 rounded-lg px-4 py-2 hover:bg-rose-700">✕ Confirm reject</button>
                </div>
            </div>
        </div>
    @endif

    {{-- WAREHOUSE MODAL --}}
    @if ($showWarehouse)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-800">Warehouse — complete</h3>
                <p class="text-sm text-gray-500">After checking for duplicates, assign an item number to each line and record the PR.</p>
                <div class="space-y-2">
                    @foreach ($record->items as $it)
                        <div wire:key="wn-{{ $it->id }}" class="flex items-center gap-2">
                            <div class="text-xs text-gray-600 flex-1 truncate">{{ $it->description }}</div>
                            <input type="text" wire:model="itemNumbers.{{ $it->id }}" placeholder="item no." class="w-40 rounded-lg border-gray-300 text-sm font-mono" />
                        </div>
                    @endforeach
                </div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">PR Number</label><input type="text" wire:model="prNumber" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                <div><label class="block text-xs font-medium text-gray-500 mb-1">Note (optional)</label><textarea wire:model="warehouseNote" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showWarehouse', false)" class="text-sm border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">Cancel</button>
                    <button wire:click="warehouseComplete" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 hover:bg-sky-700">✓ Complete & notify originator</button>
                </div>
            </div>
        </div>
    @endif

    {{-- EDIT DRAFT MODAL --}}
    @if ($showEdit)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4">
            <div class="bg-white w-full md:max-w-3xl rounded-t-2xl md:rounded-2xl p-5 space-y-3 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-800">Edit draft — {{ $record->request_number }}</h3>
                @include('partials._form-errors')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div><label class="block text-xs text-gray-500 mb-1">HoS / TL</label><select wire:model="ef.hos_user_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($people as $p)<option value="{{ $p->id }}">{{ $p->display_name ?: $p->email }}</option>@endforeach</select></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Manager</label><select wire:model="ef.manager_user_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">—</option>@foreach ($people as $p)<option value="{{ $p->id }}">{{ $p->display_name ?: $p->email }}</option>@endforeach</select></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Section / Team</label><input type="text" wire:model="ef.section_team" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Phone</label><input type="text" wire:model="ef.phone" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Date</label><input type="date" wire:model="ef.app_date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Sub Assembly</label><input type="text" wire:model="ef.sub_assembly" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Functional System</label><input type="text" wire:model="ef.functional_system" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500 mb-1">Purpose</label><textarea wire:model="ef.purpose" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                </div>
                <div class="flex items-center justify-between"><div class="text-sm font-semibold text-gray-700">Items</div><button wire:click="addEditItem" class="text-xs text-sky-700 bg-sky-50 border border-sky-200 rounded-lg px-2.5 py-1">+ Add</button></div>
                <div class="overflow-x-auto"><table class="w-full text-xs min-w-[820px]"><thead class="bg-slate-50 text-slate-500"><tr class="text-left">
                    <th class="px-2 py-1.5">Stock</th><th class="px-2 py-1.5">Description</th><th class="px-2 py-1.5">Price</th><th class="px-2 py-1.5">Qty</th><th class="px-2 py-1.5">Unit</th><th class="px-2 py-1.5">Min</th><th class="px-2 py-1.5">Max</th><th class="px-2 py-1.5">Supplier</th><th class="px-2 py-1.5">Haz</th><th class="px-2 py-1.5">Crit</th><th class="px-2 py-1.5">Storage</th><th></th></tr></thead>
                    <tbody>@foreach ($ei as $i => $row)<tr wire:key="ei-{{ $i }}">
                        <td class="px-1 py-1"><select wire:model="ei.{{ $i }}.stock" class="rounded border-gray-300 text-xs"><option value="0">No</option><option value="1">Yes</option></select></td>
                        <td class="px-1 py-1"><input wire:model="ei.{{ $i }}.description" class="w-full rounded border-gray-300 text-xs" /></td>
                        <td class="px-1 py-1"><input type="number" step="0.01" wire:model="ei.{{ $i }}.price_usd" class="w-16 rounded border-gray-300 text-xs" /></td>
                        <td class="px-1 py-1"><input type="number" min="1" wire:model="ei.{{ $i }}.qty_order" class="w-14 rounded border-gray-300 text-xs" /></td>
                        <td class="px-1 py-1"><select wire:model="ei.{{ $i }}.unit" class="w-16 rounded border-gray-300 text-xs"><option value="">—</option>@foreach ($uoms as $uom)<option value="{{ $uom->name }}">{{ $uom->name }}</option>@endforeach</select></td>
                        <td class="px-1 py-1"><input type="number" wire:model="ei.{{ $i }}.min_qty" class="w-14 rounded border-gray-300 text-xs" /></td>
                        <td class="px-1 py-1"><input type="number" wire:model="ei.{{ $i }}.max_qty" class="w-14 rounded border-gray-300 text-xs" /></td>
                        <td class="px-1 py-1"><input wire:model="ei.{{ $i }}.suggested_supplier" class="w-full rounded border-gray-300 text-xs" /></td>
                        <td class="px-1 py-1"><select wire:model="ei.{{ $i }}.hazardous" class="w-14 rounded border-gray-300 text-xs"><option value="0">No</option><option value="1">Yes</option></select></td>
                        <td class="px-1 py-1"><select wire:model="ei.{{ $i }}.criticality" class="w-14 rounded border-gray-300 text-xs"><option value="0">No</option><option value="1">Yes</option></select></td>
                        <td class="px-1 py-1"><select wire:model="ei.{{ $i }}.special_storage" class="w-24 rounded border-gray-300 text-xs"><option value="Normal">Normal</option><option value="Air Cond room">Air Cond room</option></select></td>
                        <td class="px-1 py-1 text-center">@if (count($ei) > 1)<button wire:click="removeEditItem({{ $i }})" class="text-gray-400 hover:text-rose-600">✕</button>@endif</td>
                    </tr>@endforeach</tbody></table></div>
                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('showEdit', false)" class="text-sm border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">Cancel</button>
                    <button wire:click="saveEdit" class="text-sm text-white bg-amber-600 rounded-lg px-4 py-2 hover:bg-amber-700">Save draft</button>
                </div>
            </div>
        </div>
    @endif
</div>
