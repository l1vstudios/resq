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

<!-- App js -->
<script src="{{ asset('build/js/plugin.js') }}?v={{ filemtime(public_path('build/js/plugin.js')) }}"></script>
