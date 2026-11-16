<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PT Sentinal ResQ Tech menyediakan solusi kebencanaan, monitoring lingkungan, geospasial, engineering, dan sistem digital.">
    <title>@yield('title') | PT Sentinal ResQ Tech</title>
    <link rel="shortcut icon" href="{{ asset('build/images/favicon.ico') }}">
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; color: #17241f; background: #f8faf8; }
        a { color: inherit; }
        button, input, textarea { font: inherit; }
        .public-shell { min-height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        .public-header {
            position: relative;
            z-index: 2;
            width: min(1160px, calc(100% - 48px));
            margin: 0 auto;
            padding: 24px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .public-brand { display: inline-flex; align-items: center; text-decoration: none; flex: 0 0 auto; }
        .public-brand img { display: block; width: 190px; height: 64px; object-fit: cover; object-position: center 45%; }
        .public-nav { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 10px 28px; }
        .public-nav a { text-decoration: none; color: #52645b; font-size: 14px; font-weight: 600; }
        .public-nav a:hover, .public-nav a[aria-current="page"] { color: #176d54; }
        .public-nav .public-login { padding: 11px 19px; border: 1px solid #cbd9d0; border-radius: 999px; color: #214937; }
        .public-nav .public-login:hover { border-color: #176d54; background: #e8f4ed; }
        .public-main { position: relative; z-index: 1; flex: 1; }
        .public-main::before {
            position: absolute;
            z-index: -1;
            top: -220px;
            right: -300px;
            width: 780px;
            height: 780px;
            border: 1px solid rgba(23, 109, 84, .1);
            border-radius: 50%;
            box-shadow: 0 0 0 80px rgba(23, 109, 84, .025), 0 0 0 180px rgba(23, 109, 84, .018);
            content: "";
            pointer-events: none;
        }
        .public-container { width: min(1060px, calc(100% - 48px)); margin: 0 auto; }
        .page-eyebrow { margin: 0 0 14px; color: #176d54; font-size: 12px; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
        .page-title { margin: 0; color: #111b16; font-size: clamp(48px, 8vw, 92px); line-height: 1.02; letter-spacing: -.055em; font-weight: 600; }
        .page-intro { max-width: 760px; margin: 22px 0 0; color: #52645b; font-size: clamp(16px, 2vw, 20px); line-height: 1.7; }
        .public-card { background: #fff; border: 1px solid #e2eae4; border-radius: 22px; box-shadow: 0 20px 60px rgba(29, 61, 44, .07); }
        .public-footer { width: min(1160px, calc(100% - 48px)); margin: 0 auto; padding: 28px 0 34px; display: flex; justify-content: space-between; gap: 18px; border-top: 1px solid #e1e9e2; color: #68786f; font-size: 12px; }
        .public-footer a { color: #176d54; text-decoration: none; }
        .public-footer a:hover { text-decoration: underline; }
        @media (max-width: 640px) {
            .public-header, .public-container, .public-footer { width: min(1060px, calc(100% - 32px)); }
            .public-header { align-items: flex-start; padding: 20px 0; }
            .public-brand img { width: 144px; height: 48px; }
            .public-nav { gap: 12px; }
            .public-nav a { font-size: 12px; }
            .public-nav .public-login { padding: 7px 12px; }
            .public-footer { flex-direction: column; }
        }
        @media (max-width: 390px) {
            .public-header { flex-wrap: wrap; }
            .public-nav { justify-content: flex-start; }
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
            <a href="{{ route('contact-us') }}" @if(request()->routeIs('contact-us')) aria-current="page" @endif>Contact Us</a>
            <a class="public-login" href="{{ route('login') }}">Masuk</a>
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
