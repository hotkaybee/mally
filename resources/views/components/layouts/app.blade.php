<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Mally Store — Премиальная оригинальная косметика и наборы из США' }}</title>
        <meta name="description" content="Официальный магазин Mally Uzbekistan. 100% оригинальная люксовая косметика Rhode, Hourglass, Rituals, Rare Beauty, Sol de Janeiro, Dior. Быстрая доставка по Ташкенту и всему Узбекистану.">
        <meta name="theme-color" content="#1C1917">

        <!-- Google Fonts: Playfair Display (Serif) & Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- FontAwesome 6 Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FCECEC] text-[#1C1917] antialiased selection:bg-[#1C1917] selection:text-[#FCECEC]">
        {{ $slot }}

        <!-- Глобальные всплывающие уведомления (только ошибки, поверх всех окон) -->
        <div
            x-data="{
                toasts: [],
                add(detail) {
                    const msg = typeof detail === 'string' ? detail : (detail?.message || (Array.isArray(detail) && detail[0]?.message) || '');
                    const type = (typeof detail === 'object' && !Array.isArray(detail) ? detail?.type : (Array.isArray(detail) ? detail[0]?.type : 'info')) || 'info';
                    if (!msg) return;
                    if (type !== 'error' && type !== 'warning') return;
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, message: msg, type: 'error' });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 3500);
                }
            }"
            @toast.window="add($event.detail)"
            style="z-index: 99999;"
            class="fixed top-5 right-5 z-[99999] flex flex-col gap-2 max-w-sm w-full pointer-events-none px-4 sm:px-0"
        >
            <template x-for="t in toasts" :key="t.id">
                <div
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-auto px-4 py-3.5 rounded-2xl shadow-2xl border border-rose-500 bg-rose-600 text-white flex items-center gap-3 text-xs font-semibold"
                >
                    <i class="fa-solid fa-circle-exclamation text-white text-sm flex-shrink-0"></i>
                    <span x-text="t.message" class="flex-1 leading-snug"></span>
                </div>
            </template>
        </div>

        @livewireScripts
    </body>
</html>
