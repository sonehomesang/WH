<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @include('settings._tabs')

        @if (session('access_ok'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-2.5">{{ session('access_ok') }}</div>
        @endif

        {{-- ── Auth Mode ─────────────────────────────────────────── --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-4">
            <div>
                <h3 class="font-medium text-gray-800">🔐 Access &amp; Auth Modes</h3>
                <p class="text-xs text-gray-500">ຕັດສິນ ວ່າ WH ກວດ login ແນວໃດ. Local password ເປັນ ຮາກຖານ ທີ່ ໃຊ້ ໄດ້ ສະເໝີ · AD ເປັນ overlay ທີ່ ເປີດ/ປິດ ໄດ້ ບ່ອນ ນີ້. ມີ ຜົນ ທັນທີ.</p>
            </div>

            @php
                $modes = [
                    ['id' => 'local_only', 'title' => 'Local only', 'sub' => 'ທຸກ ຄົນ ໃຊ້ local password · ບໍ່ ຕິດຕໍ່ AD ເລີຍ', 'tone' => 'emerald', 'phaseB' => false],
                    ['id' => 'ad_strict', 'title' => 'AD only (strict)', 'sub' => 'domain user ຕ້ອງ bind AD · ລະຫັດ ຜິດ ຫຼື AD ລົ້ມ = ເຂົ້າ ບໍ່ ໄດ້', 'tone' => 'sky', 'phaseB' => false],
                    ['id' => 'ad_fallback', 'title' => 'AD + local fallback', 'sub' => 'bind AD · DC ລົ້ມ → local (Phase B — ຍັງ ບໍ່ ເປີດ)', 'tone' => 'amber', 'phaseB' => true],
                ];
            @endphp

            <div class="grid sm:grid-cols-3 gap-3">
                @foreach ($modes as $m)
                    @php $sel = $mode === $m['id']; @endphp
                    <button type="button"
                        @if (! $m['phaseB']) wire:click="setMode('{{ $m['id'] }}')" @endif
                        @disabled($m['phaseB'])
                        class="text-left rounded-xl border p-4 transition
                            {{ $sel ? 'border-sky-500 ring-2 ring-sky-100 bg-sky-50/40' : 'border-gray-200 hover:border-gray-300 bg-white' }}
                            {{ $m['phaseB'] ? 'opacity-55 cursor-not-allowed' : '' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold text-gray-800 text-sm">{{ $m['title'] }}</span>
                            @if ($sel)
                                <span class="text-[10px] font-bold uppercase tracking-wide text-sky-700 bg-sky-100 px-2 py-0.5 rounded-full">● active</span>
                            @elseif ($m['phaseB'])
                                <span class="text-[10px] font-bold uppercase tracking-wide text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">Phase B</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1.5">{{ $m['sub'] }}</p>
                    </button>
                @endforeach
            </div>
            @error('mode')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        {{-- ── Break-glass + AD reachability ─────────────────────── --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-white border border-gray-100 rounded-lg p-5">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h3 class="font-medium text-gray-800">🧯 Break-glass admins</h3>
                    @if ($breakGlass->isEmpty())
                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 ring-1 ring-rose-200">⚠ ບໍ່ ມີ</span>
                    @else
                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">✓ {{ $breakGlass->count() }} ບັນຊີ</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mb-2">local super admin — login ໄດ້ ທຸກ mode, ບໍ່ ຂຶ້ນ ກັບ AD.</p>
                @if ($breakGlass->isEmpty())
                    <p class="text-xs text-rose-600">ຄວນ ມີ ຢ່າງ ໜ້ອຍ 1 ບັນຊີ. ສ້າງ local super admin ຢູ່ ໜ້າ Users ກ່ອນ ເປີດ AD mode.</p>
                @else
                    <ul class="text-sm text-gray-700 space-y-1">
                        @foreach ($breakGlass as $bg)
                            <li class="font-mono text-[13px]">{{ $bg->username ?: $bg->email }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="bg-white border border-gray-100 rounded-lg p-5">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h3 class="font-medium text-gray-800">📡 AD reachability</h3>
                    <button type="button" wire:click="testConnection" wire:loading.attr="disabled"
                        class="h-8 px-3 rounded-lg bg-white border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                        <span wire:loading.remove wire:target="testConnection">Test connection</span>
                        <span wire:loading wire:target="testConnection">ກຳລັງ ທົດສອບ…</span>
                    </button>
                </div>
                <p class="text-xs text-gray-500">ປຸ່ມ ນີ້ ເປັນ ບ່ອນ ດຽວ ທີ່ ຕິດຕໍ່ DC — ກົດ ເອງ ເທົ່ານັ້ນ, ບໍ່ ມີ probe ອັດຕະໂນມັດ.</p>
                @if ($testResult)
                    <p class="mt-2 text-xs px-3 py-2 rounded-md {{ $testType === 'ok' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">{{ $testResult }}</p>
                @endif
                @if (! empty($lastSync['at']))
                    <p class="mt-2 text-[11px] text-gray-400">Sync ຫຼ້າສຸດ: {{ $lastSync['at'] }}</p>
                @endif
            </div>
        </div>

        {{-- ── Provisioning ──────────────────────────────────────── --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-4">
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-medium text-gray-800">🗝 Domain users · local access</h3>
                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full {{ $needingLocal ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' }}">
                    {{ $needingLocal }} / {{ $domainTotal }} ຍັງ ບໍ່ ມີ local password
                </span>
            </div>
            <p class="text-xs text-gray-500">ຕັ້ງ local password ໃຫ້ domain users ເພື່ອ ໃຫ້ login ໄດ້ ຕອນ ຢູ່ Local mode. ບັງຄັບ ປ່ຽນ ຕອນ login ຄັ້ງ ທຳອິດ. (ຕັ້ງ ເປັນ ຄົນ ໄດ້ ຢູ່ ໜ້າ Users ຜ່ານ set-password link.)</p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm font-semibold text-gray-800">① Unique temp passwords</p>
                    <p class="text-xs text-gray-500 mt-1 mb-3">ອອກ ລະຫັດ ຄົນ ລະ ອັນ (ປອດໄພ ສຸດ) · ສະ ແດງ ຄັ້ງ ດຽວ ໃຫ້ ກ໋ອບ ໄປ ແຈກ.</p>
                    <button type="button" wire:click="provisionUnique"
                        wire:confirm="ອອກ ລະຫັດ ຊົ່ວຄາວ ໃຫ້ {{ $needingLocal }} domain users? ຈະ ສະ ແດງ ຄັ້ງ ດຽວ."
                        @disabled($needingLocal === 0)
                        class="h-9 px-4 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 disabled:opacity-40">＋ ອອກ ລະຫັດ ({{ $needingLocal }})</button>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm font-semibold text-gray-800">② Shared temp password</p>
                    <p class="text-xs text-gray-500 mt-1 mb-3">ລະຫັດ ດຽວ ໃຫ້ ໝົດ (ແຈກ ງ່າຍ) · ບັງຄັບ ປ່ຽນ ຢູ່ ດີ.</p>
                    <div class="flex gap-2">
                        <input type="text" wire:model="sharedPassword" placeholder="≥ 8 ຕົວ" class="flex-1 rounded-lg border-gray-300 text-sm" />
                        <button type="button" wire:click="provisionShared"
                            wire:confirm="ຕັ້ງ ລະຫັດ ຊົ່ວຄາວ ຮ່ວມ ໃຫ້ {{ $needingLocal }} domain users?"
                            @disabled($needingLocal === 0)
                            class="h-9 px-3 rounded-lg bg-white border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40">ຕັ້ງ</button>
                    </div>
                    @error('sharedPassword')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            @if (! empty($provisioned))
                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-sm font-semibold text-amber-800">⚠ ລະຫັດ ຊົ່ວຄາວ — ສະ ແດງ ຄັ້ງ ດຽວ ({{ count($provisioned) }})</p>
                        <button type="button" wire:click="clearProvisioned" class="text-xs font-semibold text-gray-600 hover:text-gray-900">ປິດ / ແຈກ ແລ້ວ</button>
                    </div>
                    <div class="max-h-64 overflow-y-auto rounded-lg border border-amber-200 bg-white">
                        <table class="w-full text-[13px]">
                            <thead class="bg-amber-50 text-amber-700 text-xs sticky top-0">
                                <tr><th class="text-left px-3 py-1.5 font-semibold">ຊື່</th><th class="text-left px-3 py-1.5 font-semibold">Email</th><th class="text-left px-3 py-1.5 font-semibold">Temp password</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($provisioned as $p)
                                    <tr>
                                        <td class="px-3 py-1.5 text-gray-700">{{ $p['name'] }}</td>
                                        <td class="px-3 py-1.5 text-gray-500">{{ $p['email'] }}</td>
                                        <td class="px-3 py-1.5 font-mono font-semibold text-gray-900 select-all">{{ $p['password'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
