<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>WH Warehouse Info System</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak]{display:none!important}</style>

        @include('partials._pwa-head')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <!-- Left sidebar (responsive: fixed on desktop, slide-in on mobile) -->
            <x-layout.sidebar />

            <!-- Main column (shifted right of the sidebar on desktop) -->
            <div class="md:pl-64">
                @php
                    // Page title ໄດນາມິກ ຕາມ route ປັດຈຸບັນ (ແທນ logo). Fallback = headline ຂອງ route name.
                    $routeName = request()->route()?->getName();
                    $titleMap = [
                        'dashboard' => 'Dashboard',
                        'inventory' => 'WH Inventories',
                        'borrow' => 'Borrowing & Tracking',
                        'borrow.create' => 'New Borrow Request',
                        'borrow.show' => 'Borrowing Record Details',
                        'deposit' => 'Deposit Material/Equipment',
                        'deposit.create' => 'New Deposit Request',
                        'deposit.show' => 'Deposit Record Details',
                        'disposal' => 'Disposal · ຈຳໜ່າຍ',
                        'disposal.create' => 'New disposal',
                        'disposal.show' => 'Disposal record details',
                        'disposal.summary' => 'Disposal summary',
                        'catalog' => 'Shops Material',
                        'equipment' => 'Equipment and Tools',
                        'equipment.templates' => 'ແມ່ແບບ ການ ກວດກາ',
                        'equipment.maintenance-templates' => 'ແມ່ແບບ ບຳລຸງຮັກສາ',
                        'equipment.categories' => 'ປະເພດ ເຄື່ອງ',
                        'request' => 'Request Material',
                        'request.create' => 'New Material Request',
                        'request.show' => 'Material Request Details',
                        'da' => 'DA Claims',
                        'da.create' => 'New Discrepancy Advice',
                        'da.show' => 'Discrepancy Advice Details',
                        'oga' => 'OGA',
                        'oga.create' => 'New Outwards Goods Advice',
                        'oga.show' => 'Outwards Goods Advice Details',
                        'expo' => 'Expo Info',
                        'expo.create' => 'New Expo Event',
                        'expo.show' => 'Expo Event Details',
                        'area-inspection' => 'Area Inspection',
                        'ansi' => 'Stock Item Applications · ANSI',
                        'ansi.create' => 'New Stock Item Application',
                        'ansi.show' => 'ANSI Details',
                        'settings' => 'Settings',
                        'settings.users' => 'Users',
                        'settings.roles' => 'Roles & permissions',
                        'settings.organization' => 'Organization',
                        'settings.facilities' => 'Organization',
                        'settings.uom' => 'Units of measure',
                        'settings.condition-statuses' => 'ສະຖານະພາບ ເຄື່ອງ · Condition statuses',
                        'settings.request-types' => 'ປະເພດ ການ ຂໍ ເຄື່ອງ · Request types',
                        'settings.clear-test-data' => 'ລ້າງ ຂໍ້ມູນ ທົດສອບ · Clear test data',
                        'settings.suppliers' => 'Suppliers',
                        'settings.suppliers.show' => 'Supplier detail',
                        'settings.notifications' => 'Notifications',
                        'settings.email' => 'Email (SMTP)',
                        'settings.notification-log' => 'Notification log',
                        'settings.audit' => 'Audit log',
                        'settings.reports' => 'Reports',
                        'settings.translations' => 'ແປ / ຄຳສັບ',
                        'settings.backup' => 'Backup / Restore',
                        'settings.system' => 'System',
                        'profile' => 'Profile',
                    ];
                    $pageTitle = $titleMap[$routeName]
                        ?? \Illuminate\Support\Str::of($routeName ?? config('app.name'))->afterLast('.')->headline();

                    // ຄຳອະທິບາຍສັ້ນຂອງແຕ່ລະໜ້າ — ສະແດງຕໍ່ກັບ title (ແທນທີ່ຈະຢູ່ໃນ toolbar).
                    $subtitleMap = [
                        'inventory' => 'ສາງເຄື່ອງ & ວັດສະດຸ · ຄົ້ນຫາ / ນຳເຂົ້າ',
                        'borrow' => 'Monitor active loans and return schedules',
                        'borrow.create' => 'ເລືອກ ປະເພດ → ລາຍການ ເຄື່ອງ → ຈຸດ ປະສົງ → ສາຍ ອະນຸມັດ',
                        'deposit.create' => 'ບັນທຶກ ຂໍ້ມູນ ເຈົ້າ ຂອງ, ລາຍການ ເຄື່ອງ ແລະ ຮູບ ຫຼັກຖານ → ຮັບ ເຂົ້າ ເກັບ',
                        'request.create' => 'ໃສ່ ຈຸດ ປະສົງ + ຂໍ້ມູນ → ຄົ້ນ catalog / free-text → ສົ່ງ ຂໍ ອະນຸມັດ',
                        'disposal.create' => 'ດຶງ ເຄື່ອງ ຊຳລຸດ ຈາກ ທະບຽນ ຕົ້ນທາງ ຫຼື ພິມ ໃໝ່ → ຮັບຮອງ → ຈຳໜ່າຍ',
                        'ansi.create' => 'Apply to add a new material to WH Inventories · HoS/TL → Manager → Warehouse',
                        'deposit' => 'ຮັບຝາກ-ເກັບຮັກສາ-ສົ່ງຄືນ ເຄື່ອງຂອງ',
                        'catalog' => 'ບັນຊີສິນຄ້າ supplier · ລາຄາ net (ບໍ່ລວມ VAT)',
                        'equipment' => 'ທະບຽນ ເຄື່ອງມື/ເຄື່ອງຈັກ · ກວດກາ · ບຳລຸງຮັກສາ',
                        'area-inspection' => 'ກວດ ສະຖານທີ່ ເຮັດ ວຽກ · ວັນ/ອາທິດ/ເດືອນ · C/NC/NA',
                        'ansi' => 'ຂໍ ເປີດ ລະຫັດ ເຄື່ອງ ໃໝ່ · originator → HoS → manager → warehouse',
                        'request' => 'ເບີກວັດສະດຸ · approve → validate → dispatch → ຮັບ',
                        'da' => 'ແຈ້ງເຄື່ອງ supplier ສົ່ງຜິດ/ເສຍ · 3 ພາກ',
                        'oga' => 'ໃບສົ່ງເຄື່ອງອອກ · dispatch → delivered',
                        'expo' => 'ບັນທຶກການໄປ expo · ບໍລິສັດ/ຜູ້ຕິດຕໍ່ · report',
                        'settings.users' => 'ຈັດການຜູ້ໃຊ້ · role + ໜ່ວຍງານ · approve / lock',
                        'settings.roles' => '25 menus × 6 actions + scope · ແກ້ໄດ້ໂດຍ super_admin',
                        'settings.uom' => 'ໜ່ວຍວັດ (pcs · kg · m …) · ໃຊ້ໃນ Inventory / Materials',
                        'settings.suppliers' => 'ຮ້ານຄ້າ supplier · ໃຊ້ໃນ Materials / supplier users',
                        'settings.notifications' => 'feature flags + ແມ່ແບບ ຂໍ້ຄວາມ',
                        'settings.notification-log' => 'log ການແຈ້ງເຕືອນ ທັງໝົດ + export',
                        'settings.audit' => 'ບັນທຶກການກະທຳ ທຸກ module + export',
                        'settings.reports' => 'ສະຫຼຸບ ຕາມ module + ຊ່ວງວັນທີ + export',
                        'settings.translations' => 'ແກ້ ຄຳສັບ ໃນແອັບ ເອງ (replace + term key)',
                        'settings.backup' => 'backup/restore ຖານຂໍ້ມູນ (admin)',
                        'settings.system' => 'General · currency · VAT · letterhead',
                    ];
                    $pageSubtitle = $subtitleMap[$routeName] ?? null;

                    // Module icon for the merged header (keyed by route base). Emoji is self-coloured
                    // so no dynamic colour classes are needed.
                    $hdrBase = \Illuminate\Support\Str::of($routeName ?? '')->before('.')->value();
                    $moduleIcon = [
                        'dashboard' => '🏠', 'inventory' => '📦', 'borrow' => '🔄', 'deposit' => '📥',
                        'request' => '📝', 'ansi' => '🆕', 'catalog' => '🏷️', 'equipment' => '🛠️',
                        'area-inspection' => '📋', 'disposal' => '🗑️', 'da' => '🧾', 'oga' => '📤',
                        'expo' => '🎪', 'settings' => '⚙️', 'profile' => '👤',
                    ][$hdrBase] ?? null;
                @endphp

                <!-- Global app header: title (left) + user menu (right) — ທຸກໜ້າ -->
                <header class="sticky top-0 z-40 border-b border-gray-200 shadow-sm" style="background:linear-gradient(to right,#38bdf8 0%,#7dd3fc 25%,#bae6fd 55%,#ffffff 95%)">
                    <div class="px-4 sm:px-6 lg:px-8">
                        <div class="h-16 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <button x-data @click="$dispatch('open-sidebar')" class="md:hidden p-2 -ml-2 min-h-[44px] min-w-[44px] text-gray-600 hover:text-gray-900" aria-label="Open menu">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                </button>
                                @if ($moduleIcon)
                                    <span class="w-10 h-10 rounded-xl bg-white border border-gray-200 shadow-sm grid place-items-center text-xl shrink-0">{{ $moduleIcon }}</span>
                                @endif
                                <h1 class="text-[26px] font-bold text-gray-800 truncate shrink-0 leading-tight">{{ $pageTitle }}</h1>
                                @if ($pageSubtitle)
                                    <span class="text-gray-300 hidden lg:inline">·</span>
                                    <span class="text-sm text-gray-500 truncate hidden lg:inline">{{ $pageSubtitle }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                {{-- Language toggle (Lao / English) — top-right --}}
                                <div class="flex items-center rounded-lg border border-white/70 bg-white/70 backdrop-blur text-xs font-semibold overflow-hidden shrink-0 mr-1" title="ປ່ຽນ ພາສາ / Language">
                                    <a href="{{ route('locale.switch', 'lo') }}"
                                       class="px-2 py-1 min-h-[30px] flex items-center {{ app()->getLocale() === 'lo' ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-white' }}"
                                       aria-label="ພາສາລາວ" @if (app()->getLocale() === 'lo') aria-current="true" @endif>ລາວ</a>
                                    <a href="{{ route('locale.switch', 'en') }}"
                                       class="px-2 py-1 min-h-[30px] flex items-center {{ app()->getLocale() === 'en' ? 'bg-sky-600 text-white' : 'text-gray-600 hover:bg-white' }}"
                                       aria-label="English" @if (app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
                                </div>
                                @auth
                                    <button type="button" x-data="{ busy: false }" @click="busy = true; window.updateApp()" :disabled="busy"
                                            class="p-2 text-gray-500 hover:text-sky-600 rounded-md hover:bg-gray-50 disabled:opacity-50"
                                            title="ອັບເດດ ແອັບ ໃຫ້ເປັນລຸ້ນລ່າສຸດ (ລ້າງ cache + ໂຫຼດໃໝ່)" aria-label="ອັບເດດ ແອັບ">
                                        <svg class="w-5 h-5" :class="busy && 'animate-spin'" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                    </button>
                                    <livewire:notifications.bell />
                                @endauth
                                <livewire:layout.navigation />
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @include('partials._lightbox')
        @auth
            @include('partials._idle-timeout')
            <livewire:survey.prompt />
        @endauth
    </body>
</html>
