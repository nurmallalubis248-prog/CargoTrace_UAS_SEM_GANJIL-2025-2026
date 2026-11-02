{{-- Shared design tokens: matcha (primary) + sakura pink (accent). Used by app, guest and error layouts. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Manrope', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                colors: {
                    matcha: { 50: '#F3F7EE', 100: '#E4EDD8', 200: '#CADBB3', 300: '#A9C486', 400: '#86A85D', 500: '#678B41', 600: '#506E33', 700: '#3F5729', 800: '#324422', 900: '#26331A' },
                    sakura: { 50: '#FDF2F5', 100: '#FBE4EB', 200: '#F7CAD8', 300: '#F0A5BC', 400: '#E67B9C', 500: '#D85A82', 600: '#BF3F69', 700: '#9E3256' },
                    ink:    { 50: '#F7F8F5', 100: '#EEF0EA', 200: '#DEE2D8', 300: '#C5CBBB', 400: '#9AA290', 500: '#737B69', 600: '#565E4E', 700: '#3F463A', 800: '#2B3027', 900: '#1B1F18' }
                }
            }
        }
    };
</script>
<style type="text/tailwindcss">
    @layer base {
        body { @apply font-sans antialiased; font-feature-settings: 'tnum' 1; }
        a:focus-visible, button:focus-visible, select:focus-visible, input:focus-visible { @apply outline-none ring-2 ring-sakura-400 ring-offset-1; }
    }
    @layer components {
        .card { @apply bg-white rounded-2xl border border-matcha-100 shadow-[0_1px_2px_rgba(38,51,26,0.05),0_6px_16px_-8px_rgba(38,51,26,0.10)] p-5; }
        .input { @apply w-full rounded-lg border border-ink-300 bg-white px-3 py-2 text-sm text-ink-800 placeholder:text-ink-400 focus:border-matcha-500 focus:outline-none focus:ring-2 focus:ring-matcha-300; }
        .label { @apply block text-xs font-semibold text-ink-600 mb-1; }
        .btn { @apply inline-flex items-center justify-center rounded-lg bg-matcha-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-matcha-800; }
        .btn-accent { @apply inline-flex items-center justify-center rounded-lg bg-sakura-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sakura-600; }
        .btn-light { @apply inline-flex items-center justify-center rounded-lg border border-matcha-200 bg-white px-4 py-2 text-sm font-semibold text-matcha-800 transition-colors hover:bg-matcha-50; }
        .btn-danger { @apply inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700; }
        .th { @apply px-3 py-2.5 text-left text-xs font-semibold text-ink-600 bg-matcha-50; }
        .td { @apply px-3 py-2.5 text-sm; }
        .nav-link { @apply rounded-full px-3 py-1.5 text-sm font-medium text-matcha-100 transition-colors hover:bg-matcha-800 hover:text-white; }
        .nav-link-active { @apply bg-sakura-400 text-white hover:bg-sakura-500; }
    }
</style>
