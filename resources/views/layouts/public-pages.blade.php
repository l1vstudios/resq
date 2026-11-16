<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PT Sentinal ResQ Tech menyediakan solusi kebencanaan, monitoring lingkungan, geospasial, engineering, dan sistem digital.">
    <title>@yield('title') | PT Sentinal ResQ Tech</title>
    <link rel="shortcut icon" href="{{ asset('build/images/favicon.ico') }}">
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body { margin: 0; color: #34483e; background: #fff; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        button, input, textarea { font: inherit; }
        .public-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #fff url("{{ asset('images/topolecek.png') }}") center / cover no-repeat;
        }
        .public-header {
            width: min(1440px, 100%);
            margin: 0 auto;
            padding: 12px 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .public-brand { display: inline-flex; align-items: center; text-decoration: none; flex: 0 0 auto; }
        .public-brand img { display: block; width: 170px; height: 56px; object-fit: cover; object-position: center 45%; }
        .public-nav { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 12px 18px; }
        .public-nav a { text-decoration: none; color: #40594c; font-size: 17px; font-weight: 400; line-height: 1; white-space: nowrap; }
        .public-nav a:hover, .public-nav a[aria-current="page"] { color: #176d54; }
        .public-nav-divider { color: #c9d5cd; font-size: 17px; user-select: none; }
        .public-main { flex: 1; }
        .public-container { width: min(1060px, calc(100% - 48px)); margin: 0 auto; }
        .public-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 5px; box-shadow: 0 4px 20px rgba(0, 0, 0, .06); }
        .public-footer { width: min(1320px, calc(100% - 120px)); margin: 0 auto; padding: 16px 0 20px; display: flex; justify-content: space-between; gap: 18px; color: #60776b; font-size: 13px; }
        .public-footer a { color: #176d54; text-decoration: none; }
        .public-footer a:hover { text-decoration: underline; }
        @media (max-width: 640px) {
            .public-header { padding: 14px 20px; flex-direction: column; align-items: flex-start; gap: 12px; }
            .public-container, .public-footer { width: calc(100% - 40px); }
            .public-brand img { width: 150px; height: 50px; }
            .public-nav { justify-content: flex-start; gap: 10px 12px; }
            .public-nav a, .public-nav-divider { font-size: 14px; }
            .public-footer { flex-direction: column; }
        }
    </style>
    @yield('page-css')
</head>
<body>
<div class="public-shell">
    <header class="public-header">
        <a class="public-brand" href="{{ url('/') }}" aria-label="Beranda Sentinel EMP">
            <img src="{{ asset('images/logofixeds.png') }}" alt="Sentinel EMP">
        </a>
        <nav class="public-nav" aria-label="Navigasi utama">
            <a href="{{ route('about-us') }}" @if(request()->routeIs('about-us')) aria-current="page" @endif>About Us</a>
            <span class="public-nav-divider" aria-hidden="true">|</span>
            <a href="{{ route('contact-us') }}" @if(request()->routeIs('contact-us')) aria-current="page" @endif>Contact Us</a>
            <span class="public-nav-divider" aria-hidden="true">|</span>
            <a href="{{ route('login') }}">Masuk</a>
        </nav>
    </header>

    <main class="public-main" id="main-content">@yield('content')</main>

    <footer class="public-footer">
        <span>&copy; {{ date('Y') }} PT Sentinal ResQ Tech</span>
        <span><a href="{{ route('about-us') }}">About Us</a> &nbsp;·&nbsp; <a href="{{ route('contact-us') }}">Contact Us</a></span>
    </footer>
</div>
@yield('page-script')
</body>
</html>
