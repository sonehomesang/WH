import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Condition-status badge palette (App\Support\ConditionStatus::COLORS) is applied
    // from DB values at runtime, so Tailwind can't see the classes — safelist them.
    safelist: [
        'bg-emerald-50', 'text-emerald-700', 'bg-lime-50', 'text-lime-700',
        'bg-teal-50', 'text-teal-700', 'bg-sky-50', 'text-sky-700',
        'bg-amber-50', 'text-amber-700', 'bg-orange-50', 'text-orange-700',
        'bg-yellow-50', 'text-yellow-800', 'bg-red-50', 'text-red-700',
        'bg-rose-50', 'text-rose-700', 'bg-purple-50', 'text-purple-700',
        'bg-indigo-50', 'text-indigo-700', 'bg-pink-50', 'text-pink-700',
        'bg-slate-100', 'text-slate-600', 'bg-gray-100', 'text-gray-600',
        // Area-inspection C/NC/NA radio pills build `peer-checked:{color}` from a
        // PHP loop, so JIT can't see them — safelist the checked fills.
        'peer-checked:bg-green-600', 'peer-checked:bg-red-600', 'peer-checked:bg-gray-400',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Latin stays Figtree; Lao glyphs (absent in Figtree) fall through
                // to the self-hosted Phetsarath OT before the system stack.
                sans: ['Figtree', 'Phetsarath OT', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
