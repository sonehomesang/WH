@php
    $badge = fn ($s) => match ($s) {
        'active' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'pending' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        'locked' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        default => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200',
    };
@endphp

<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        {{-- nav + toolbar freeze together as one sticky unit; publish its bottom edge
             as --freeze-top so the table below can size + stick its header under it --}}
        <div class="sticky top-16 z-30" x-data
             x-init="const root = document.documentElement;
                     const set = () => root.style.setProperty('--freeze-top', (64 + $el.offsetHeight) + 'px');
                     set(); new ResizeObserver(set).observe($el);">
        @include('settings._tabs')

        {{-- toolbar (freezes with the tabs above): A-Z filter · search/create --}}
        <div class="bg-white flex flex-col gap-2 px-1 py-1.5 sm:flex-row sm:items-center sm:gap-3 border-b-2 border-gray-300 shadow-sm">
            {{-- A-Z group filter --}}
            <div class="flex-1 min-w-0 overflow-x-auto">
                <div class="flex items-center gap-0.5">
                    <button wire:click="setLetter('')" class="px-2 py-0.5 text-xs rounded whitespace-nowrap {{ $letter === '' ? 'bg-sky-600 text-white' : 'text-gray-500 hover:bg-gray-200' }}">ທັງໝົດ</button>
                    @foreach ($letters as $L)
                        <button wire:click="setLetter('{{ $L }}')" class="w-6 py-0.5 text-xs rounded shrink-0 {{ $letter === $L ? 'bg-sky-600 text-white' : 'text-gray-500 hover:bg-gray-200' }}">{{ $L }}</button>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="ຄົ້ນຫາ ຊື່/email…"
                       class="rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                @can('users.create')
                    <button wire:click="newUser" class="inline-flex items-center gap-1 text-sm text-white bg-sky-600 rounded-md px-3 py-2 min-h-[40px] hover:bg-sky-700 whitespace-nowrap">+ Create user</button>
                @endcan
            </div>
        </div>
        </div>{{-- /sticky nav+toolbar unit --}}

        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- ລິ້ງ ຕັ້ງ ລະຫັດ (ຫຼັງ ສ້າງ user / ກົດ ປຸ່ມ 🔑) — ກ໋ອບປີ້ ສົ່ງ ມື ໄດ້ ຖ້າ ອີເມລ ຍັງ ບໍ່ ພ້ອມ --}}
        @if ($setPasswordLink)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-3 text-sm" x-data="{ copied: false }" wire:key="setlink">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-medium text-amber-800">ລິ້ງ ຕັ້ງ ລະຫັດ — {{ $setLinkEmail }}</span>
                    <button wire:click="closeLink" class="text-amber-700 hover:text-amber-900 text-xs">ປິດ ✕</button>
                </div>
                <p class="text-xs text-amber-700 mb-2">ໝົດ ອາຍຸ 60 ນາທີ · ສົ່ງ ອີເມລ ແລ້ວ (ຖ້າ SMTP ພ້ອມ) · ຫຼື ກ໋ອບປີ້ ລິ້ງ ນີ້ ສົ່ງ ໃຫ້ ຜູ້ໃຊ້ ໂດຍ ກົງ:</p>
                <div class="flex gap-2">
                    <input type="text" readonly value="{{ $setPasswordLink }}" x-ref="lnk" class="flex-1 rounded border-amber-200 text-xs bg-white" onclick="this.select()" />
                    <button type="button" class="text-xs text-white bg-amber-600 rounded px-3 py-1.5 hover:bg-amber-700 whitespace-nowrap"
                            @click="navigator.clipboard.writeText($refs.lnk.value); copied=true; setTimeout(()=>copied=false,1500)">
                        <span x-show="!copied">ກ໋ອບປີ້</span><span x-show="copied" x-cloak>✓ ແລ້ວ</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- Desktop table — page scrolls; the column header sticks just under the frozen nav/toolbar (--freeze-top) --}}
        <div class="hidden md:block bg-white border border-gray-100 rounded-lg mt-3">
            <table class="w-full text-sm table-fixed">
                <thead class="sticky z-20 bg-gray-50 text-gray-700 border-b border-gray-200 shadow-sm" style="top: var(--freeze-top, 12rem)">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2">User</th>
                        <th class="text-left font-semibold px-4 py-2 w-32">Role</th>
                        <th class="text-left font-semibold px-4 py-2 w-44">Unit / Dept</th>
                        <th class="text-left font-semibold px-4 py-2 w-28">Status</th>
                        <th class="px-4 py-2 w-24"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="u-{{ $user->id }}" class="border-t border-gray-100">
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-800 truncate">{{ $user->display_name }}
                                    @if ($user->is_super_admin)<span class="text-[10px] font-medium text-amber-700 bg-amber-50 ring-1 ring-amber-200 rounded-full px-1.5 py-0.5 ml-1">super</span>@endif
                                </div>
                                <div class="text-xs text-gray-400 truncate">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap"><span class="text-xs font-medium bg-gray-100 text-gray-600 ring-1 ring-gray-200 rounded-full px-2 py-0.5">{{ $user->roles->first()?->name ?? '—' }}</span></td>
                            <td class="px-4 py-2 text-gray-600 truncate" title="{{ $user->unit?->name }}@if ($user->department) / {{ $user->department->name }}@endif">{{ $user->unit?->name ?? '—' }}@if ($user->department) / {{ $user->department->name }}@endif</td>
                            <td class="px-4 py-2 whitespace-nowrap"><span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $badge($user->status) }}">{{ $user->status }}</span></td>
                            <td class="px-4 py-2 text-right whitespace-nowrap text-gray-500">
                                @if ($user->status === 'pending') @can('users.activate')
                                    <button wire:click="approve({{ $user->id }})" class="hover:text-green-600 p-1" aria-label="Approve" title="Approve">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    </button>
                                @endcan @endif
                                @can('users.deactivate')
                                    <button wire:click="toggleLock({{ $user->id }})" class="hover:text-red-600 p-1" aria-label="Lock/unlock" title="Lock / unlock">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 0h10.5a2.25 2.25 0 0 1 2.25 2.25v6.75a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25v-6.75a2.25 2.25 0 0 1 2.25-2.25Z" /></svg>
                                    </button>
                                @endcan
                                @can('users.edit')
                                    @if (config('features.local_auth'))
                                        <button wire:click="linkFor({{ $user->id }})" class="hover:text-sky-600 p-1" aria-label="Set-password link" title="ລິ້ງ ຕັ້ງ ລະຫັດ">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                                        </button>
                                    @endif
                                    <button wire:click="editUser({{ $user->id }})" class="hover:text-gray-800 p-1" aria-label="Edit" title="Edit">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">ບໍ່ມີຜູ້ໃຊ້</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-2 mt-3">
            @forelse ($users as $user)
                <div wire:key="m-{{ $user->id }}" class="bg-white border border-gray-100 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <div class="font-medium text-gray-800">{{ $user->display_name }}</div>
                        <span class="text-xs font-medium rounded-full px-2 py-0.5 {{ $badge($user->status) }}">{{ $user->status }}</span>
                    </div>
                    <div class="text-xs text-gray-400">{{ $user->email }}</div>
                    <div class="text-xs text-gray-600 mt-1">{{ $user->roles->first()?->name ?? '—' }} · {{ $user->unit?->name ?? '—' }}@if ($user->department) / {{ $user->department->name }}@endif</div>
                    <div class="flex gap-2 mt-2">
                        @if ($user->status === 'pending') @can('users.activate')<button wire:click="approve({{ $user->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Approve</button>@endcan @endif
                        @can('users.deactivate')<button wire:click="toggleLock({{ $user->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">{{ $user->status === 'locked' ? 'Unlock' : 'Lock' }}</button>@endcan
                        @can('users.edit')<button wire:click="editUser({{ $user->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">Edit</button>@endcan
                        @can('users.edit')@if (config('features.local_auth'))<button wire:click="linkFor({{ $user->id }})" class="text-xs border rounded px-2 py-1 min-h-[36px]">🔑 ລິ້ງ</button>@endif@endcan
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-6">ບໍ່ມີຜູ້ໃຊ້</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/40 md:p-4" wire:key="user-modal">
            <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl border border-gray-300 shadow-lg overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-5 py-4 flex items-center gap-3 border-b border-gray-200 bg-gradient-to-b from-sky-200 to-sky-100 shrink-0">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white grid place-items-center text-lg shadow-sm shrink-0">👤</span>
                    <h3 class="text-base font-semibold text-gray-800">{{ $editingId ? 'ແກ້ໄຂຜູ້ໃຊ້' : 'ສ້າງຜູ້ໃຊ້ໃໝ່' }}</h3>
                    <button wire:click="$set('showModal', false)" class="ml-auto text-gray-400 hover:text-gray-700 p-1" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @include('partials._form-errors', ['wrapClass' => 'md:col-span-2'])
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">ຊື່ (display name) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="display_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('display_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Username <span class="text-gray-400 font-normal">(ໃຊ້ login · ຫຼື email ຢ່າງໜ້ອຍ 1)</span></label>
                        <input type="text" wire:model="username" autocomplete="off" placeholder="ຊື່ຜູ້ໃຊ້ ສຳລັບ login" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('username')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Email <span class="text-gray-400 font-normal">(ບໍ່ ບັງຄັບ)</span></label>
                        <input type="email" wire:model="email" placeholder="ວ່າງ ໄດ້ ຖ້າ ບໍ່ ມີ" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" />
                        @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">Password
                            @if ($editingId)<span class="text-gray-400 font-normal">(ວ່າງ = ບໍ່ ປ່ຽນ)</span>
                            @else<span class="text-gray-400 font-normal">(admin ຕັ້ງ — ຜູ້ໃຊ້ ຕ້ອງ ປ່ຽນ ຕອນ login ຄັ້ງ ທຳອິດ)</span>@endif
                        </label>
                        <div class="flex gap-2">
                            <input type="text" wire:model="password" autocomplete="off" placeholder="ພິມ password ໃຫ້ ຜູ້ໃຊ້ ຫຼື ກົດ 🎲" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm font-mono" />
                            <button type="button" wire:click="generatePassword" class="h-9 px-3 rounded-md bg-white border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 whitespace-nowrap">🎲 Generate</button>
                        </div>
                        @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2 text-xs text-gray-500 bg-sky-50 border border-sky-100 rounded-md px-3 py-2">
                        <b>ຕັ້ງ ລະຫັດ 2 ວິທີ:</b> ① admin ພິມ ຫຼື ກົດ 🎲 generate → ບອກ ຜູ້ໃຊ້ ເອງ, ຜູ້ໃຊ້ ປ່ຽນ ຕອນ login ຄັ້ງ ທຳອິດ · ② ຫຼື ໃສ່ email + ວ່າງ password → ລະບົບ ສ້າງ <b>ລິ້ງ ຕັ້ງ ລະຫັດ</b> ໃຫ້. <b>ບໍ່ ມີ password ໃດ ຖືກ ເກັບ ໄວ້</b> — ກາຍ ເປັນ hash ທາງດຽວ.
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Role <span class="text-red-500">*</span></label>
                        <select wire:model.live="role" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">— ເລືອກ —</option>
                            @foreach ($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                        </select>
                        @error('role')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Status</label>
                        <select wire:model="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="active">active</option>
                            <option value="pending">pending</option>
                            <option value="locked">locked</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ໜ່ວຍງານ (Unit)</label>
                        <select wire:model.live="unit_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                            <option value="">—</option>
                            @foreach ($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Department</label>
                        <select wire:model="department_id" @disabled(! $unit_id) class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm disabled:bg-gray-50">
                            <option value="">—</option>
                            @foreach ($formDepartments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 border-t border-gray-100 pt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ສິດ ເພີ່ມເຕີມ ສະເພາະ ບຸກຄົນ</label>
                        <p class="text-xs text-gray-400 mb-2">ເລືອກ ລະດັບ ສິດ ຕໍ່ ເມນູ (ນອກ ເໜືອ ຈາກ ບົດບາດ). ຕິກ "ນຳໃຊ້/ແກ້" ຈະ ລວມ "ເບິ່ງ" ໃຫ້ ອັດຕະໂນມັດ.</p>
                        <div class="border border-gray-100 rounded-md divide-y divide-gray-100 overflow-hidden">
                            <div class="flex items-center px-3 py-1.5 text-[11px] text-gray-400 bg-gray-50">
                                <span class="flex-1">ເມນູ</span>
                                @foreach ($grantableActions as $act => $actLabel)
                                    <span class="w-20 text-center leading-tight">{{ $actLabel }}</span>
                                @endforeach
                            </div>
                            @foreach ($grantableMenus as $key => $label)
                                <div class="flex items-center px-3 py-1.5 text-sm text-gray-700">
                                    <span class="flex-1">{{ $label }}</span>
                                    @foreach ($grantableActions as $act => $actLabel)
                                        <span class="w-20 flex justify-center">
                                            <input type="checkbox" wire:model="extraPerms.{{ $key }}.{{ $act }}" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                        </span>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($role === 'supplier')
                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-600 mb-1">Supplier <span class="text-red-500">*</span> <span class="text-xs text-gray-400">(ຜູກ user ກັບ supplier — portal scope)</span></label>
                            <select wire:model="supplier_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                                <option value="">— ເລືອກ supplier —</option>
                                @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                            @error('supplier_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-3 bg-gray-50/70 border-t border-gray-100 shrink-0">
                    <button wire:click="$set('showModal', false)" class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">ຍົກເລີກ</button>
                    <button wire:click="save" class="text-sm text-white bg-sky-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-sky-700 shadow-sm">ບັນທຶກ</button>
                </div>
            </div>
        </div>
    @endif
</div>
