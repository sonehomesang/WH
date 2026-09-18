<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-lg font-semibold text-gray-800">ເຂົ້າສູ່ລະບົບ</h1>
    <p class="text-sm text-gray-500 mt-1 mb-5">ປ້ອນ ອີເມວ ແລະ ລະຫັດຜ່ານ ຂອງ ທ່ານ</p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (request('idle'))
        <div class="mb-4 text-sm rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-3 py-2">
            ⏳ ອອກ ຈາກ ລະບົບ ອັດຕະໂນມັດ ເນື່ອງ ຈາກ ບໍ່ ມີ ການ ໃຊ້ ງານ — ກະລຸນາ ເຂົ້າ ໃໝ່.
        </div>
    @endif

    <form wire:submit="login" class="space-y-4">
        <!-- Username or email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-600 mb-1">{{ app()->getLocale() === 'en' ? 'Username or email' : 'ຊື່ຜູ້ໃຊ້ ຫຼື ອີເມວ' }}</label>
            <x-text-input wire:model="form.email" id="email" class="block w-full" type="text" name="email" required autofocus autocomplete="username" placeholder="{{ app()->getLocale() === 'en' ? 'username or you@example.com' : 'username ຫຼື you@example.com' }}" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-600 mb-1">ລະຫັດຜ່ານ</label>
            <x-password-input wire:model="form.password" id="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember + Forgot -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-sky-600 shadow-sm focus:ring-sky-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">ຈື່ ການ login</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm text-sky-700 hover:text-sky-900 hover:underline" href="{{ route('password.request') }}" wire:navigate>
                    ລືມ ລະຫັດຜ່ານ?
                </a>
            @endif
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center gap-2 h-11 rounded-lg bg-sky-700 text-white text-sm font-semibold hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition disabled:opacity-60">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                <path d="M20 12h-13l3 -3m0 6l-3 -3" />
            </svg>
            <span>ເຂົ້າສູ່ລະບົບ</span>
        </button>
    </form>

    <div class="mt-6 pt-4 border-t border-gray-100 text-center text-xs text-gray-400 leading-relaxed">
        ຕ້ອງການ ບັນຊີ? ບັນຊີ ສ້າງ ໂດຍ ຜູ້ດູແລ ລະບົບ (admin / AD)<br>
        ກະລຸນາ ຕິດຕໍ່ ຝ່າຍ IT ຫຼື ຜູ້ດູແລ ລະບົບ
    </div>
</div>
