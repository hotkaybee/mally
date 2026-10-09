<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mally Store — Премиальная оригинальная косметика и наборы из США</title>
        <meta name="description" content="Официальный магазин Mally Uzbekistan. 100% оригинальная люксовая косметика Rhode, Hourglass, Rituals, Rare Beauty, Sol de Janeiro, Dior. Быстрая доставка по Ташкенту и всему Узбекистану.">
        <meta name="theme-color" content="#1C1917">

        <!-- Google Fonts: Playfair Display (Serif) & Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- FontAwesome 6 Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FCECEC] text-[#1C1917] antialiased selection:bg-[#1C1917] selection:text-[#FCECEC]">
        <!-- React Storefront Root -->
        <div id="store-app"></div>
    </body>
</html>
