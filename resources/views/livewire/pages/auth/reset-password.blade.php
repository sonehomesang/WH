<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required' => 'ກະລຸນາ ໃສ່ ລະຫັດຜ່ານ ໃໝ່.',
            'password.min' => 'ລະຫັດຜ່ານ ຕ້ອງ ຍາວ ຢ່າງ ໜ້ອຍ 10 ຕົວ.',
            'password.confirmed' => 'ລະຫັດຜ່ານ ຢືນຢັນ ບໍ່ ກົງ ກັນ.',
            'email.required' => 'ກະລຸນາ ໃສ່ ອີເມວ.',
            'email.email' => 'ຮູບ ແບບ ອີເມວ ບໍ່ ຖືກ ຕ້ອງ.',
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                    // ຜູ້ໃຊ້ ຕັ້ງ ລະຫັດ ເອງ ຜ່ານ ລິ້ງ ແລ້ວ → ບໍ່ ຕ້ອງ ບັງຄັບ ປ່ຽນ ອີກ (ກັນ ປ່ຽນ ຊ້ຳ 2 ຮອບ
                    // ສຳລັບ ບັນຊີ ທີ່ ເຄີຍ ໄດ້ ລະຫັດ ຊົ່ວຄາວ ຈາກ users:temp-password).
                    'must_change_password' => false,
                    'local_password_set_at' => now(),   // a usable local password now exists
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <h1 class="text-lg font-semibold text-gray-800">ຕັ້ງ ລະຫັດຜ່ານ ໃໝ່</h1>
    <p class="text-sm text-gray-500 mt-1 mb-5">ປ້ອນ ລະຫັດຜ່ານ ໃໝ່ ສຳລັບ ບັນຊີ ຂອງ ທ່ານ.</p>

    <form wire:submit="resetPassword" class="space-y-4">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-600 mb-1">ອີເມວ</label>
            <x-text-input wire:model="email" id="email" class="block w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-600 mb-1">ລະຫັດຜ່ານ ໃໝ່</label>
            <x-password-input wire:model="password" id="password" name="password" required autocomplete="new-password" />
            <p class="text-xs text-gray-400 mt-1">ຢ່າງ ໜ້ອຍ 10 ຕົວ</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-600 mb-1">ຢືນຢັນ ລະຫັດຜ່ານ</label>
            <x-password-input wire:model="password_confirmation" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center gap-2 h-11 rounded-lg bg-sky-700 text-white text-sm font-semibold hover:bg-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition disabled:opacity-60">
            ບັນທຶກ ລະຫັດຜ່ານ ໃໝ່
        </button>
    </form>
</div>
