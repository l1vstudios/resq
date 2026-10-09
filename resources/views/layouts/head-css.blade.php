<!-- Bootstrap Css -->
<link href="{{ asset('build/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="{{ asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="{{ asset('build/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

@if(app()->environment('local'))
    @php
        $inlineCss = collect([
            public_path('build/css/bootstrap.min.css'),
            public_path('build/css/app.min.css'),
        ])->filter(fn ($path) => is_file($path))
            ->map(fn ($path) => file_get_contents($path))
            ->implode("\n");

        $inlineCss = str_replace([
            '@charset "UTF-8";',
            '@import"https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap";',
            '</style',
        ], [
            '',
            '',
            '<\/style',
        ], $inlineCss);
    @endphp
    <style id="local-css-fallback">{!! $inlineCss !!}</style>
@endif

@yield('css')

<style id="sentinel-card-surface">
    .card,
    .card.border,
    .card[class*="border"] {
        border: 0 !important;
        box-shadow: 0 6px 18px rgba(18, 38, 63, 0.07) !important;
    }

    .tab-content:focus,
    .tab-content:focus-visible,
    .tab-content > .tab-pane:focus,
    .tab-content > .tab-pane:focus-visible,
    .collapse:focus,
    .collapse:focus-visible {
        outline: 0 !important;
    }
</style>

<!-- App js -->
<script src="{{ asset('build/js/plugin.js') }}?v={{ filemtime(public_path('build/js/plugin.js')) }}"></script>
