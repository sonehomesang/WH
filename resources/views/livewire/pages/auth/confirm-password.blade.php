<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-lg font-semibold text-gray-800">ຢືນຢັນ ລະຫັດຜ່ານ</h1>
    <p class="text-sm text-gray-500 mt-1 mb-5">ນີ້ ຄື ພື້ນທີ່ ປອດໄພ ຂອງ ລະບົບ — ກະລຸນາ ຢືນຢັນ ລະຫັດຜ່ານ ຂອງ ທ່ານ ກ່ອນ ດຳເນີນ ການ ຕໍ່.</p>

    <form wire:submit="confirmPassword" class="space-y-4">
        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-600 mb-1">ລະຫັດຜ່ານ</label>
            <x-password-input wire:model="password" id="password" name="password" required autofocus autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center gap-2 h-11 rounded-lg bg-sky-700 text-white text-sm font-semibold hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition disabled:opacity-60">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                <path d="M9 12l2 2l4 -4" />
            </svg>
            <span>ຢືນຢັນ</span>
        </button>
    </form>
</div>
