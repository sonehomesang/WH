<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            ລຶບ ບັນຊີ
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            ເມື່ອ ລຶບ ບັນຊີ ແລ້ວ, ຂໍ້ມູນ ແລະ ຊັບພະຍາກອນ ທັງ ໝົດ ຈະ ຖືກ ລຶບ ຖາວອນ. ກ່ອນ ລຶບ, ກະລຸນາ ດາວໂຫຼດ ຂໍ້ມູນ ທີ່ ຕ້ອງການ ເກັບ ໄວ້.
        </p>
    </header>

    <button type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
        <span>ລຶບ ບັນຊີ</span>
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" maxWidth="sm" focusable>
        <form wire:submit="deleteUser" class="p-5 space-y-4">

            <div class="flex items-start gap-3">
                <div class="shrink-0 w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-gray-900">ທ່ານ ແນ່ໃຈ ບໍ ວ່າ ຕ້ອງການ ລຶບ ບັນຊີ?</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        ເມື່ອ ລຶບ ບັນຊີ ແລ້ວ, ຂໍ້ມູນ ທັງ ໝົດ ຈະ ຖືກ ລຶບ ຖາວອນ. ກະລຸນາ ໃສ່ ລະຫັດຜ່ານ ເພື່ອ ຢືນຢັນ.
                    </p>
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-600 mb-1">ລະຫັດຜ່ານ</label>
                <x-password-input
                    wire:model="password"
                    id="password"
                    name="password"
                    placeholder="ລະຫັດຜ່ານ"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t">
                <button type="button" x-on:click="$dispatch('close')"
                        class="text-sm text-gray-700 bg-white border border-gray-300 rounded-lg px-4 py-2 min-h-[40px] hover:bg-gray-50">
                    ຍົກເລີກ
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="deleteUser"
                        class="inline-flex items-center gap-1.5 text-sm text-white bg-red-600 rounded-lg px-4 py-2 min-h-[40px] hover:bg-red-700 disabled:opacity-50 shadow-sm">
                    🗑 ລຶບ ບັນຊີ
                </button>
            </div>
        </form>
    </x-modal>
</section>
