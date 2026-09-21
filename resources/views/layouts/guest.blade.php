<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>WH Warehouse Info System · ເຂົ້າສູ່ລະບົບ</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak]{display:none!important}</style>

        @include('partials._pwa-head')
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
            <div class="w-full sm:max-w-md">
                {{-- Brand: WH / Nam Theun 2 --}}
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-sky-700 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9" viewBox="0 0 24 24" fill="none"
                             stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21v-13l9 -4l9 4v13" />
                            <path d="M13 13h4v8h-10v-6h6" />
                            <path d="M13 21v-9a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3" />
                        </svg>
                    </div>
                    <div class="mt-3 text-2xl font-semibold tracking-wide text-gray-800">WH</div>
                    <div class="text-sm text-gray-500">ລະບົບ ຄຸ້ມຄອງ ສາງ · Warehouse</div>
                    <div class="text-xs text-gray-400">Nam Theun 2 Power Company</div>
                </div>

                {{-- Auth card --}}
                <div class="bg-white shadow-md rounded-2xl px-6 py-6 sm:px-8">
                    {{ $slot }}
                </div>

                <div class="mt-6 text-center text-xs text-gray-400">© {{ date('Y') }} Nam Theun 2 Power Company</div>
            </div>
        </div>
    </body>
</html>
