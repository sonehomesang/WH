@php
    // Settings section tabs — shown on every settings sub-page for quick switching.
    // Built sections have a route; not-yet-built ones render greyed.
    $sections = [
        ['label' => 'Users', 'perm' => 'users.view', 'route' => 'settings.users'],
        ['label' => 'Roles & permissions', 'perm' => 'roles.view', 'route' => 'settings.roles'],
        ['label' => 'Organization', 'perm' => 'units.view', 'route' => 'settings.organization', 'match' => ['settings.organization', 'settings.facilities']],
        ['label' => 'Units of measure', 'perm' => 'units.view', 'route' => 'settings.uom'],
        ['label' => 'ສະຖານະພາບ ເຄື່ອງ', 'perm' => 'settings.view', 'route' => 'settings.condition-statuses'],
        ['label' => 'ປະເພດ ຂໍ ເຄື່ອງ', 'perm' => 'settings.view', 'route' => 'settings.request-types'],
        ['label' => 'Suppliers', 'perm' => 'supplier.view', 'route' => 'settings.suppliers'],
        ['label' => 'Audit log', 'perm' => 'audit.view', 'route' => 'settings.audit'],
        ['label' => 'Reports', 'perm' => 'reports.view', 'route' => 'settings.reports'],
        ['label' => 'Notifications', 'perm' => 'settings.view', 'route' => 'settings.notifications'],
        ['label' => 'Email (SMTP)', 'perm' => 'settings.view', 'route' => 'settings.email'],
        ['label' => 'Access & Auth', 'perm' => 'settings.view', 'route' => 'settings.access', 'super' => true],
        ['label' => 'Active Directory', 'perm' => 'settings.view', 'route' => 'settings.ldap'],
        ['label' => 'ແປ/ຄຳສັບ', 'perm' => 'settings.view', 'route' => 'settings.translations'],
        ['label' => 'Backup', 'perm' => 'settings.view', 'route' => 'settings.backup'],
        ['label' => 'System', 'perm' => 'settings.view', 'route' => 'settings.system'],
        ['label' => '🗑 ລ້າງ ຂໍ້ມູນ ທົດສອບ', 'perm' => 'settings.view', 'route' => 'settings.clear-test-data', 'super' => true],
    ];
@endphp

<div class="sticky top-16 z-20 bg-gray-100 flex flex-wrap items-center gap-1 border-b border-gray-200 py-2 sm:py-0 sm:h-[52px]">
    <a href="{{ route('settings') }}" wire:navigate class="px-2.5 py-1.5 rounded-md text-sm text-gray-500 hover:bg-gray-100" title="ໜ້າລວມ Settings">⌂</a>
    @foreach ($sections as $s)
        @if (($s['super'] ?? false) && ! auth()->user()->is_super_admin) @continue @endif
        @can($s['perm'])
            @if (isset($s['route']))
                @php $active = collect($s['match'] ?? [$s['route']])->contains(fn ($r) => request()->routeIs($r)); @endphp
                <a href="{{ route($s['route']) }}" wire:navigate
                   class="px-3 py-1.5 rounded-md text-sm {{ $active ? 'bg-sky-600 text-white font-medium' : 'text-gray-600 hover:bg-gray-100' }}">{{ $s['label'] }}</a>
            @else
                <span class="px-3 py-1.5 rounded-md text-sm text-gray-300 cursor-not-allowed" title="phase ຕໍ່ໄປ">{{ $s['label'] }}</span>
            @endif
        @endcan
    @endforeach
</div>
