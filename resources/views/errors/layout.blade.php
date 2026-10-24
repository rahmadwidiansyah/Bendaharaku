@php
    /*
     * Error pages are rendered outside Inertia, so the layout must resolve the
     * same appearance preferences as app.blade.php. Guard the user lookup: a
     * 500 caused by the database/session must not break the error page itself.
     */
    try {
        $errorUser = auth()->user();
    } catch (\Throwable) {
        $errorUser = null;
    }

    $errorUiTheme = $errorUser?->ui_theme === 'm3' ? 'm3' : 'default';
    $errorTheme = in_array($errorUser?->theme, ['light', 'dark', 'system'], true)
        ? $errorUser->theme
        : 'system';
    $errorScheme = \App\Support\M3ColorScheme::normalize($errorUser?->ui_m3_color_scheme);
    $errorIsM3 = $errorUiTheme === 'm3';

    $errorManifestPath = public_path('build/manifest.json');
    $errorManifest = is_file($errorManifestPath)
        ? json_decode((string) file_get_contents($errorManifestPath), true)
        : [];
    $errorCssBuilt = is_array($errorManifest)
        && isset($errorManifest['resources/css/errors.css']);
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="{{ $errorIsM3 ? 'ui-m3' : '' }}"
    @if ($errorIsM3) data-m3-scheme="{{ $errorScheme }}" @endif
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="color-scheme" content="dark light">

        <title>@yield('title', 'Error')</title>

        <link rel="icon" type="image/png" href="/icons/favicon-32.png">
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

        <script>
            // Anti-FOUC: mirror useTheme.js resolution before first paint.
            (function () {
                var preference = @json($errorTheme);
                var prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
                if (preference === 'light' || (preference === 'system' && prefersLight)) {
                    document.documentElement.classList.add('light');
                }
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @if ($errorCssBuilt || is_file(public_path('hot')))
            @vite('resources/css/errors.css')
        @endif
    </head>
    <body class="err-page">
        <div class="err-page__glow err-page__glow--one" aria-hidden="true"></div>
        <div class="err-page__glow err-page__glow--two" aria-hidden="true"></div>

        <div class="err-shell">
            <a href="{{ url('/') }}" class="err-brand" aria-label="Bendaharaku">
                <img src="/icons/logo.png" alt="" class="err-brand__logo" width="32" height="32">
                <span class="err-brand__name">Bendaharaku</span>
            </a>

            <main class="err-card" role="main">
                <span class="err-badge">
                    <span class="err-badge__icon" aria-hidden="true">
                        @include('errors.icon', ['name' => trim($__env->yieldContent('icon')) ?: 'alert'])
                    </span>
                    Error @yield('code', '')
                </span>

                <p class="err-code" aria-hidden="true">@yield('code', '')</p>

                <h1 class="err-title">@yield('heading', 'Terjadi kesalahan')</h1>
                <p class="err-message">@yield('message')</p>

                <div class="err-actions">
                    <button
                        type="button"
                        class="err-btn err-btn--outlined"
                        onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ url('/') }}'; }"
                    >
                        Kembali
                    </button>
                    <a href="{{ url('/') }}" class="err-btn err-btn--filled">Halaman Utama</a>
                </div>
            </main>

            <p class="err-foot">Bendaharaku · Error @yield('code', '')</p>
        </div>
    </body>
</html>
