@php
    // Top-level navigation (10). Display labels per user spec; menu id = permission key.
    // Routes are '#' until each module is built (Dashboard + Settings are live).
    // Order per user's sidebar layout (2026-09-14) — flat list, no group dividers.
    $nav = [
        ['menu' => 'dashboard', 'label' => 'Dashboard', 'route' => route('dashboard'), 'always' => true,
            'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.5a.75.75 0 00.75.75h4.5a.75.75 0 00.75-.75V15a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v5.25c0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75V9.75M8.25 21h8.25'],
        ['menu' => 'inventory', 'label' => 'WH Inventories', 'route' => route('inventory'),
            'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
        ['menu' => 'borrow', 'label' => 'Borrowing Material/Tools', 'route' => route('borrow'),
            'icon' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
        ['menu' => 'deposit', 'label' => 'Deposit Material/Equipment', 'route' => route('deposit'),
            'icon' => 'm20.25 7.5-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'],
        ['menu' => 'disposal', 'label' => 'Disposal · ຈຳໜ່າຍ', 'route' => route('disposal'),
            'icon' => 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'],
        ['menu' => 'catalog', 'label' => 'Shops Material', 'route' => route('catalog'),
            'icon' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3Z M6 6h.008v.008H6V6Z'],
        ['menu' => 'request', 'label' => 'Request Material', 'route' => route('request'),
            'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z'],
        ['menu' => 'ansi', 'label' => 'New Stock Item (ANSI)', 'route' => route('ansi'),
            'icon' => 'M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['menu' => 'da', 'label' => 'DA Claims', 'route' => route('da'),
            'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
        ['menu' => 'oga', 'label' => 'OGA', 'route' => route('oga'),
            'icon' => 'M6 12 3.269 3.125A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.875L5.999 12Zm0 0h7.5'],
        ['menu' => 'equipment', 'label' => 'Equipment & Tools', 'route' => route('equipment'),
            'icon' => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z'],
        ['menu' => 'area_inspection', 'match' => 'area-inspection', 'label' => 'ກວດ ສະຖານທີ່', 'route' => route('area-inspection'),
            'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z'],
        ['menu' => 'expo', 'label' => 'Expo Info', 'route' => route('expo'),
            'icon' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418'],
    ];
    $u = auth()->user();
    $settingsPerms = ['settings.view', 'users.view', 'roles.view', 'units.view', 'departments.view', 'locations.view', 'buildings.view', 'rooms.view', 'supplier.view', 'audit.view', 'reports.view'];
    $canSettings = $u->canAny($settingsPerms);

    $linkBase = 'flex items-center gap-3 min-h-[40px] px-3 py-1.5 rounded-md text-sm transition-colors';
    $linkIdle = 'text-gray-300 hover:bg-gray-800 hover:text-white';
    $linkActive = 'bg-sky-600 text-white font-medium';
@endphp

<div x-data="{ open: false }" @open-sidebar.window="open = true" @keydown.escape.window="open = false">
    {{-- Mobile overlay --}}
    <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 z-30 bg-black/40 md:hidden" style="display:none"></div>

    {{-- Sidebar panel --}}
    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 text-gray-100 overflow-y-auto transition-transform duration-200 ease-in-out md:translate-x-0"
    >
        {{-- Brand --}}
        <div class="flex items-center justify-between h-16 px-4 border-b border-gray-800">
            <a href="{{ route('dashboard') }}" wire:navigate class="leading-tight">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-semibold text-white">WH</span>
                    <span class="text-[10px] font-mono text-gray-500 whitespace-nowrap" title="ອັບເດດ ຫຼ້າສຸດ: {{ \App\Support\AppVersion::updatedAt() ?? '—' }}">{{ \App\Support\AppVersion::label() }}</span>
                </div>
                <div class="text-[11px] text-gray-400">Warehouse · Nam Theun 2</div>
            </a>
            <button @click="open = false" class="md:hidden p-2 min-h-[44px] min-w-[44px] text-gray-400 hover:text-white" aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Top-level menu --}}
        <nav class="px-2 py-3 space-y-0.5">
            @foreach ($nav as $item)
                @if ((($item['always'] ?? false) || $u->can($item['menu'].'.view')) && \App\Support\Modules::enabled($item['menu']))
                    @php $base = $item['match'] ?? $item['menu']; $active = $item['route'] !== '#' && (request()->routeIs($base) || request()->routeIs($base.'.*')); @endphp
                    <a href="{{ $item['route'] }}" @if($item['route'] !== '#') wire:navigate @endif
                       class="{{ $linkBase }} {{ $active ? $linkActive : $linkIdle }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach

            @canany(['survey.view', 'reports.view'])
                <a href="{{ route('survey.results') }}" wire:navigate
                   class="{{ $linkBase }} {{ request()->routeIs('survey.results') ? $linkActive : $linkIdle }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    <span>Satisfaction Survey</span>
                </a>
            @endcanany

            @if ($canSettings)
                <a href="{{ route('settings') }}" wire:navigate
                   class="{{ $linkBase }} {{ request()->routeIs('settings') || request()->routeIs('settings.*') ? $linkActive : $linkIdle }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.751-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281Z M15 12a3 3 0 11-6 0 3 3 0 016 0Z" /></svg>
                    <span>Settings</span>
                </a>
            @endif
        </nav>
    </aside>
</div>
