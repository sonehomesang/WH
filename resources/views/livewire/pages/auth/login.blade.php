<?php

use App\Livewire\Forms\LoginForm;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /** Break-glass / admin: sign in with an email instead of name + department. */
    public bool $adminMode = false;

    /** Departments that actually have a selectable (named) person. */
    #[Computed]
    public function departments()
    {
        $ids = User::whereNotNull('department_id')->whereNotNull('username')->distinct()->pluck('department_id');

        return Department::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }

    /** People in the chosen department, for the name picker. */
    #[Computed]
    public function people()
    {
        if (! $this->form->department_id) {
            return collect();
        }

        return User::where('department_id', $this->form->department_id)
            ->whereNotNull('username')
            ->orderBy('display_name')
            ->get(['id', 'username', 'display_name']);
    }

    /** Changing department clears the previously picked name. */
    public function updatedFormDepartmentId(): void
    {
        $this->form->username = '';
    }

    public function toggleAdmin(): void
    {
        $this->adminMode = ! $this->adminMode;
        $this->resetErrorBag();
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->form->loginBy = $this->adminMode ? 'email' : 'name';

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-lg font-semibold text-gray-800">ເຂົ້າສູ່ລະບົບ</h1>
    <p class="text-sm text-gray-500 mt-1 mb-5">
        {{ $adminMode ? 'ຜູ້ດູແລ ລະບົບ — ເຂົ້າ ດ້ວຍ ອີເມວ' : 'ເລືອກ ພະແນກ ແລະ ຊື່ ຂອງ ທ່ານ ແລ້ວ ໃສ່ ລະຫັດຜ່ານ' }}
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (request('idle'))
        <div class="mb-4 text-sm rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-3 py-2">
            ⏳ ອອກ ຈາກ ລະບົບ ອັດຕະໂນມັດ ເນື່ອງ ຈາກ ບໍ່ ມີ ການ ໃຊ້ ງານ — ກະລຸນາ ເຂົ້າ ໃໝ່.
        </div>
    @endif

    <!-- General auth error (failed / throttled / locked) -->
    <x-input-error :messages="$errors->get('form.password')" class="mb-3" />

    <form wire:submit="login" class="space-y-4">
        @if (! $adminMode)
            <!-- Department -->
            <div>
                <x-input-label for="department_id" :value="'ພະແນກ'" />
                <select wire:model.live="form.department_id" id="department_id" name="department_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">— ເລືອກ ພະແນກ —</option>
                    @foreach ($this->departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.department_id')" class="mt-2" />
            </div>

            <!-- Name -->
            <div>
                <x-input-label for="username" :value="'ຊື່'" />
                <select wire:model="form.username" id="username" name="username" @disabled(! $this->form->department_id)
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:bg-gray-100 disabled:text-gray-400">
                    <option value="">{{ $this->form->department_id ? '— ເລືອກ ຊື່ —' : '— ເລືອກ ພະແນກ ກ່ອນ —' }}</option>
                    @foreach ($this->people as $p)
                        <option value="{{ $p->username }}">{{ $p->display_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('form.username')" class="mt-2" />
            </div>
        @else
            <!-- Admin email -->
            <div>
                <x-input-label for="email" :value="'ອີເມວ (admin)'" />
                <x-text-input wire:model="form.email" id="email" class="block w-full mt-1" type="text" name="email" required autofocus autocomplete="username" placeholder="you@example.com" />
                <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
            </div>
        @endif

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="'ລະຫັດຜ່ານ'" />
            <x-password-input wire:model="form.password" id="password" name="password" required autocomplete="current-password" class="mt-1" />
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

    <!-- Break-glass toggle -->
    <div class="mt-4 text-center">
        <button type="button" wire:click="toggleAdmin" class="text-xs text-gray-400 hover:text-gray-600 hover:underline">
            {{ $adminMode ? '← ກັບ ໄປ ເຂົ້າ ດ້ວຍ ຊື່ + ພະແນກ' : 'ຜູ້ດູແລ ລະບົບ: ເຂົ້າ ດ້ວຍ ອີເມວ' }}
        </button>
    </div>

    <div class="mt-6 pt-4 border-t border-gray-100 text-center text-xs text-gray-400 leading-relaxed">
        ຕ້ອງການ ບັນຊີ? ບັນຊີ ສ້າງ ໂດຍ ຜູ້ດູແລ ລະບົບ (admin / AD)<br>
        ກະລຸນາ ຕິດຕໍ່ ຝ່າຍ IT ຫຼື ຜູ້ດູແລ ລະບົບ
    </div>
</div>
