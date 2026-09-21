<div class="pb-6">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
        @include('settings._tabs')
        <div x-data="{ show: false }" x-on:saved.window="show = true; setTimeout(() => show = false, 2000)" x-show="show" style="display:none"
             class="fixed bottom-4 right-4 z-50 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-2 shadow-lg">ບັນທຶກແລ້ວ ✓</div>

        {{-- Cards flow into 2 balanced columns on desktop, 1 on mobile --}}
        <div class="mt-4 columns-1 lg:columns-2 gap-4 [&>div]:mb-4 [&>div]:break-inside-avoid">
        {{-- General / App --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">⚙️ ທົ່ວໄປ (General)</h3>
                <p class="text-xs text-gray-500">ຄ່າພື້ນຖານ ຂອງແອັບ.</p>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">ຊື່ແອັບ</label>
                <input type="text" wire:model="appName" class="w-full rounded-md border-gray-300 text-sm" />
                @error('appName')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">ໄລຍະຢືມ ເລີ່ມຕົ້ນ (ມື້)</label>
                <input type="number" min="1" max="365" wire:model="defaultBorrowDays" class="w-32 rounded-md border-gray-300 text-sm" />
                @error('defaultBorrowDays')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            @can('settings.edit')<div class="pt-1"><button wire:click="saveGeneral" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save</button></div>@endcan
        </div>

        {{-- Security / Session --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-4">
            <div>
                <h3 class="font-medium text-gray-800">🔒 ຄວາມ ປອດໄພ (Security)</h3>
                <p class="text-xs text-gray-500">idle auto-logout.</p>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">ອອກ ຈາກ ລະບົບ ອັດຕະໂນມັດ ຫຼັງ idle (ນາທີ)</label>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" max="1440" wire:model="idleTimeoutMinutes" class="w-28 rounded-md border-gray-300 text-sm" />
                    <span class="text-xs text-gray-500">0 = ປິດ · ເຕືອນ ນັບ ຖອຍຫຼັງ 30 ວິ ກ່ອນ ອອກ</span>
                </div>
                @error('idleTimeoutMinutes')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            @can('settings.edit')<div class="pt-1"><button wire:click="saveSecurity" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save</button></div>@endcan
        </div>

        {{-- Borrow workflow — ທະຍອຍ ຮັບຄືນ --}}
        <div class="bg-white rounded-lg border border-gray-100 p-5 space-y-3">
            <div class="font-medium text-gray-800">ຂັ້ນຕອນ ການ ຢືມ (Borrow Workflow)</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຂັ້ນ ຮັບຊາບ (Acknowledge)</label>
                    <select wire:model="borrowAcknowledge" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="off">ປິດ — ບໍ່ ຕ້ອງ ຮັບຊາບ</option>
                        <option value="optional">ຕາມ ໃບ — ຂຶ້ນ ກັບ ຄຳ ຂໍ (optional)</option>
                        <option value="required">ບັງຄັບ ຮັບຊາບ ທຸກ ໃບ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ຂັ້ນ ອະນຸມັດ (Approve)</label>
                    <select wire:model="borrowApprove" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="required">ບັງຄັບ ອະນຸມັດ (ຄ່າ ເລີ່ມຕົ້ນ)</option>
                        <option value="off">ປິດ — ບໍ່ ຕ້ອງ ອະນຸມັດ</option>
                    </select>
                </div>
            </div>
            @error('borrowAcknowledge')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            @error('borrowApprove')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" wire:model="borrowPartialReturn" class="mt-1 rounded border-gray-300 text-sky-600" />
                <span class="text-sm text-gray-700">
                    <span class="font-medium">ເປີດ ໂໝດ "ທະຍອຍ ສົ່ງ ຍ່ອຍ / ທະຍອຍ ຮັບຄືນ"</span>
                    <span class="block text-xs text-gray-500 mt-0.5">ອະນຸຍາດ ຮັບຄືນ ເປັນ ລາຍການ/ຄັ້ງ ໄດ້ (ໃບ ຢືມ 1 ໃບ ຮັບ ຫຼາຍ ຄັ້ງ, ຄ້າງ ໄວ້ ຈົນ ຄືນ ຄົບ). ຖ້າ ປິດ = ຮັບຄືນ ຄັ້ງ ດຽວ ປິດ ໃບ (ຄ່າ ເລີ່ມຕົ້ນ).</span>
                </span>
            </label>
            @can('settings.edit')<div class="pt-1"><button wire:click="saveBorrowWorkflow" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save</button></div>@endcan
        </div>

        {{-- Modules on/off (feature flags) --}}
        <div class="bg-white rounded-lg border border-gray-100 p-5 space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">🧩 ໂມດູລ ທີ່ ເປີດ ໃຊ້ (Active Modules)</h3>
                <p class="text-xs text-gray-500">ຕິກ ໝາຍ = ເປີດ ໃຊ້. ປິດ ໂມດູລ ທີ່ ບໍ່ ໃຊ້ → ຫາຍ ຈາກ ເມນູ ແລະ ເຂົ້າ ໜ້າ ນັ້ນ ບໍ່ ໄດ້. ໂມດູລ ຫຼັກ (Dashboard · Inventory · Settings) ເປີດ ຕະຫຼອດ.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach (\App\Support\Modules::LABELS as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="modules.{{ $key }}" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> {{ $label }}
                    </label>
                @endforeach
            </div>
            @can('settings.edit')<div class="pt-1"><button wire:click="saveModules" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save modules</button></div>@endcan
        </div>

        {{-- Currency --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">💱 ສະກຸນເງິນ (Currency)</h3>
                <p class="text-xs text-gray-500">ສະກຸນຫຼັກ THB (ໄທ) + ສະກຸນຮອງ LAK (ກີບ) + ອັດຕາແລກປ່ຽນ.</p>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="block text-xs text-gray-500 mb-1">ສະກຸນຫຼັກ</label><input type="text" wire:model="curPrimary" maxlength="8" class="w-full rounded-md border-gray-300 text-sm uppercase" /></div>
                <div><label class="block text-xs text-gray-500 mb-1">ສະກຸນຮອງ</label><input type="text" wire:model="curSecondary" maxlength="8" class="w-full rounded-md border-gray-300 text-sm uppercase" /></div>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model="curSecondaryEnabled" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> ສະແດງ ສະກຸນຮອງ
            </label>
            <div>
                <label class="block text-sm text-gray-600 mb-1">ອັດຕາແລກປ່ຽນ (1 {{ strtoupper($curPrimary ?: 'THB') }} = ? {{ strtoupper($curSecondary ?: 'LAK') }})</label>
                <input type="number" step="0.01" min="0" wire:model="exchangeRate" class="w-40 rounded-md border-gray-300 text-sm" />
                @error('exchangeRate')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            @can('settings.edit')<div class="pt-1"><button wire:click="saveCurrency" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save</button></div>@endcan
        </div>

        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-4">
            <div>
                <h3 class="font-medium text-gray-800">VAT (global)</h3>
                <p class="text-xs text-gray-500">ໃຊ້ເປັນຄ່າເລີ່ມຕົ້ນ — supplier contract ທີ່ active override ໄດ້.</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.live="vat_enabled" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> ເປີດໃຊ້ VAT
            </label>
            <div>
                <label class="block text-sm text-gray-600 mb-1">ອັດຕາ VAT (%)</label>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.01" min="0" max="100" wire:model="vat_rate" @disabled(! $vat_enabled) class="w-32 rounded-md border-gray-300 text-sm disabled:bg-gray-100" />
                    <span class="text-sm text-gray-500">%</span>
                </div>
                @error('vat_rate')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            @can('settings.edit')
                <div class="pt-1"><button wire:click="save" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save</button></div>
            @endcan
        </div>

        {{-- Request form fields — admin ເປີດ/ປິດ --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">Request form fields</h3>
                <p class="text-xs text-gray-500">ເລືອກ field ໃດ ໃຫ້ສະແດງ ໃນຟອມ ໃບເບີກວັດສະດຸ (ປິດ = ເຊື່ອງ + ບໍ່ບັງຄັບ).</p>
            </div>
            @php $reqLabels = ['supplier' => 'Supplier', 'currency' => 'ສະກຸນເງິນ (Currency)', 'rooms' => 'ຫ້ອງ (Rooms)', 'functions' => 'Functions', 'approver' => 'Approver', 'request_type' => 'ປະເພດ (Type)', 'wo_e_form' => 'WO / eForm']; @endphp
            <div class="grid grid-cols-2 gap-2">
                @foreach ($reqLabels as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="reqFields.{{ $key }}" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> {{ $label }}
                    </label>
                @endforeach
            </div>
            @can('settings.edit')
                <div class="pt-1"><button wire:click="saveRequestFields" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save fields</button></div>
            @endcan
        </div>

        {{-- Deposit form optional fields — admin ເປີດ/ປິດ (ໜ້າ ຝາກ ຂັ້ນ 1) --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">ຟອມ ຝາກ ເຄື່ອງ — ຫ້ອງ ເສີມ</h3>
                <p class="text-xs text-gray-500">ຫ້ອງ ເສີມ ໃນ ຟອມ ຝາກ (ຂັ້ນ 1 · ໜ້າງານ). ປິດ = ເຊື່ອງ. ຊື່ · ລະຫັດ · ຮູບ · ຈຳນວນ · ບ່ອນຈັດເກັບ · ສະຖານະ = ສະແດງ ຕະຫຼອດ.</p>
            </div>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Livewire\Deposit\Create::OPTIONAL_ITEM_FIELDS as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="depFields.{{ $key }}" class="rounded border-gray-300 text-sky-600 focus:ring-sky-500" /> {{ $label }}
                    </label>
                @endforeach
            </div>
            @can('settings.edit')
                <div class="pt-1"><button wire:click="saveDepositFields" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700">Save fields</button></div>
            @endcan
        </div>

        {{-- Letterhead (PDF exports) --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5 space-y-3">
            <div>
                <h3 class="font-medium text-gray-800">Letterhead (PDF)</h3>
                <p class="text-xs text-gray-500">logo + ຂໍ້ມູນບໍລິສັດ ສຳລັບ header/footer ຂອງ PDF (Borrow/Deposit/Request/DA/OGA).</p>
            </div>

            <div class="flex items-center gap-3">
                @if ($lhLogoPath)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($lhLogoPath) }}" alt="logo" class="w-16 h-16 object-contain border border-gray-200 rounded" />
                    @can('settings.edit')<button wire:click="removeLogo" wire:confirm="ລຶບ logo?" class="text-xs text-red-600 hover:underline">ລຶບ logo</button>@endcan
                @else
                    <div class="w-16 h-16 border border-dashed border-gray-300 rounded flex items-center justify-center text-gray-300 text-xs">no logo</div>
                @endif
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">ອັບໂຫລດ logo (PNG/JPG ≤2MB)</label>
                    <input type="file" wire:model="lhLogo" accept="image/png,image/jpeg" class="block w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-2 file:rounded file:border-0 file:bg-sky-50 file:text-sky-700" />
                    <div wire:loading wire:target="lhLogo" class="text-xs text-gray-400">ກຳລັງອັບ…</div>
                    @error('lhLogo')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    @if ($lhLogo)<div class="mt-1"><img src="{{ $lhLogo->temporaryUrl() }}" class="w-12 h-12 object-contain border border-sky-200 rounded" /></div>@endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <div><label class="block text-xs text-gray-500 mb-1">ຊື່ບໍລິສັດ (ລາວ)</label><input type="text" wire:model="lhCompanyLo" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-xs text-gray-500 mb-1">Company name (EN)</label><input type="text" wire:model="lhCompanyEn" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div class="md:col-span-2"><label class="block text-xs text-gray-500 mb-1">ທີ່ຢູ່ ແຖວ 1</label><input type="text" wire:model="lhAddress1" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div class="md:col-span-2"><label class="block text-xs text-gray-500 mb-1">ທີ່ຢູ່ ແຖວ 2</label><input type="text" wire:model="lhAddress2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div class="md:col-span-2"><label class="block text-xs text-gray-500 mb-1">ທີ່ຢູ່ ແຖວ 3</label><input type="text" wire:model="lhAddress3" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-xs text-gray-500 mb-1">ເບີໂທ (Tel)</label><input type="text" wire:model="lhPhone" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-xs text-gray-500 mb-1">Fax</label><input type="text" wire:model="lhFax" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-xs text-gray-500 mb-1">Email</label><input type="text" wire:model="lhEmail" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div><label class="block text-xs text-gray-500 mb-1">Web</label><input type="text" wire:model="lhWebsite" class="w-full rounded-md border-gray-300 text-sm" /></div>
                <div class="md:col-span-2"><label class="block text-xs text-gray-500 mb-1">Footer note</label><input type="text" wire:model="lhFooter" class="w-full rounded-md border-gray-300 text-sm" /></div>
            </div>

            @can('settings.edit')
                <div class="pt-1"><button wire:click="saveLetterhead" wire:loading.attr="disabled" wire:target="saveLetterhead,lhLogo" class="text-sm text-white bg-sky-600 rounded-md px-5 py-2 min-h-[40px] hover:bg-sky-700 disabled:opacity-50">Save letterhead</button></div>
            @endcan
        </div>
        </div>
    </div>
</div>
