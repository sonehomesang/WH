<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        // ຖ້າ ບໍ່ ຕ້ອງ ປ່ຽນ ແລ້ວ → ໄປ dashboard
        if (! auth()->user()?->must_change_password) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function updatePassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required' => 'ກະລຸນາ ໃສ່ ລະຫັດຜ່ານ ໃໝ່.',
            'password.min' => 'ລະຫັດຜ່ານ ຕ້ອງ ຍາວ ຢ່າງ ໜ້ອຍ 10 ຕົວ.',
            'password.confirmed' => 'ລະຫັດຜ່ານ ຢືນຢັນ ບໍ່ ກົງ ກັນ.',
        ]);

        auth()->user()->forceFill([
            'password' => Hash::make($this->password),
            'must_change_password' => false,
            'local_password_set_at' => now(),   // a usable local password now exists
        ])->save();

        Session::flash('status', 'ຕັ້ງ ລະຫັດຜ່ານ ໃໝ່ ສຳເລັດ ✓');
        $this->redirectRoute('dashboard', navigate: true);
    }
}; ?>

<div>
    <h1 class="text-lg font-semibold text-gray-800">ຕັ້ງ ລະຫັດຜ່ານ ໃໝ່</h1>
    <p class="text-sm text-gray-500 mt-1 mb-5">ນີ້ ຄື ການ login ຄັ້ງ ທຳອິດ ຂອງ ທ່ານ — ກະລຸນາ ຕັ້ງ ລະຫັດຜ່ານ ໃໝ່ ກ່ອນ ຈຶ່ງ ໃຊ້ ງານ ໄດ້.</p>

    <form wire:submit="updatePassword" class="space-y-4">
        <div>
            <label for="password" class="block text-sm font-medium text-gray-600 mb-1">ລະຫັດຜ່ານ ໃໝ່</label>
            <x-password-input wire:model="password" id="password" required autofocus autocomplete="new-password" />
            <p class="text-xs text-gray-400 mt-1">ຢ່າງ ໜ້ອຍ 10 ຕົວ</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-600 mb-1">ຢືນຢັນ ລະຫັດຜ່ານ</label>
            <x-password-input wire:model="password_confirmation" id="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center gap-2 h-11 rounded-lg bg-sky-700 text-white text-sm font-semibold hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition disabled:opacity-60">
            ບັນທຶກ ແລະ ເຂົ້າ ໃຊ້ ງານ
        </button>
    </form>
</div>
