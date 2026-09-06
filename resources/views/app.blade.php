<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#f59e0b">
    <link rel="icon" type="image/png" href="/icons/favicon-32.png">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    <!-- Google AdSense Account Verification -->
    <meta name="google-adsense-account" content="ca-pub-1031540480419431">
    <!-- SEO Meta Tags -->
    <meta name="description" content="Bendaharaku - Solusi cerdas pencatatan keuangan pribadi, manajemen aset, dan pelacakan hutang piutang secara real-time.">
    <meta name="keywords" content="bendaharaku, catatan keuangan, manajemen aset, tracker hutang, aplikasi keuangan indonesia">
    <meta name="author" content="Bendaharaku Team">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:title" content="Bendaharaku - Catat Keuangan Jadi Lebih Mudah">
    <meta property="og:description" content="Kelola aset, pemasukan, dan pengeluaran Anda dalam satu dashboard premium yang intuitif.">
    <meta property="og:image" content="{{ asset('logo.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <script>
        // ── Anti-FOUC: Theme ──────────────────────────────────────────
        // Mirrors useTheme.js resolve + AuthenticatedLayout init logic.
        // Server-rendered preference (DB) | default 'system'.
        // Applies `.light` synchronously BEFORE first paint — no flash.
        (function () {
            var t = @json(auth()->user()?->theme ?? 'system');
            t = ['dark', 'light', 'system'].includes(t) ? t : 'system';
            var light = t === 'light' || (t === 'system' && window.matchMedia('(prefers-color-scheme: light)').matches);
            if (light) document.documentElement.classList.add('light');
        })();
    </script>

    @routes
    @vite(['resources/js/app.js'])
    @inertiaHead
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1031540480419431" crossorigin="anonymous"></script>
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
