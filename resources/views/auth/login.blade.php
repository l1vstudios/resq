@extends('layouts.master-without-nav')

@section('title')
@lang('translation.Login')
@endsection

@section('css')
<style>
    *, *::before, *::after {
        box-sizing: border-box;
    }

    html, body {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        background-color: #FFFFFF;
        font-family: Arial, sans-serif;
        color: #34483E;
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden;
    }

    .login-layout {
        height: 100vh;
        width: 100%;
        position: relative;
        background-color: #FFFFFF;
        background-image: url("{{ asset('images/topolecek.png') }}");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        overflow: hidden;
        display: flex;
        justify-content: center;
    }

    .spec-canvas {
        position: relative;
        width: 100%;
        max-width: 1440px;
        height: 100vh;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 28px 60px 28px 60px;
    }

    .top-nav-wrapper {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 18px;
        z-index: 20;
    }

    .top-nav-link {
        color: #40594C;
        font-family: Arial, sans-serif;
        font-size: 17px;
        font-weight: 400;
        line-height: 1;
        text-decoration: none;
        transition: color 0.2s ease;
        white-space: nowrap;
    }

    .top-nav-link:hover {
        color: #176D54;
    }

    .top-nav-divider {
        color: #C9D5CD;
        font-size: 17px;
        user-select: none;
    }

    .main-area {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 70%;
        max-width: 1120px;
        margin: 0 auto;
        padding-bottom: 6vh;
        z-index: 10;
    }

    .platform-brand {
        width: 290px;
        margin-top: -35px;
        flex-shrink: 0;
    }

    .platform-brand a {
        display: block;
        width: 100%;
    }

    .platform-brand img {
        width: 100%;
        max-width: 490px;
        height: auto;
        object-fit: contain;
        object-position: left center;
        display: block;
        transform: scale(1.12);
        transform-origin: left center;
    }

    .login-card {
        width: 350px;
        background-color: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 5px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        padding: 28px 24px;
        margin-top: -15px;
        flex-shrink: 0;
    }

    .login-form {
        width: 100%;
        margin: 0;
        padding: 0;
    }

    .form-group-email {
        margin-bottom: 22px;
    }

    .form-group-password {
        margin-bottom: 14px;
    }

    .field-label {
        display: block;
        font-family: Arial, sans-serif;
        font-size: 15px;
        font-weight: 600;
        color: #34483E;
        line-height: 1;
        margin-bottom: 12px;
    }

    .input-wrapper {
        position: relative;
        width: 100%;
        height: 54px;
    }

    .custom-input {
        width: 100%;
        height: 54px;
        padding: 0 42px 0 16px;
        font-family: Arial, sans-serif;
        font-size: 14px;
        color: #34483E;
        background-color: #FFFFFF;
        border: 1.5px solid #C9D5CD;
        border-radius: 5px;
        outline: none;
        box-shadow: none;
        transition: border-color 0.2s ease;
    }

    .custom-input::placeholder {
        font-family: Arial, sans-serif;
        font-size: 13.5px;
        color: #9CA9A0;
    }

    .custom-input:focus {
        border-color: #176D54;
        box-shadow: none;
    }

    .custom-input.is-invalid {
        border-color: #e53e3e;
    }

    .btn-input-action {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #7A8B82;
        font-size: 20px;
        padding: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .btn-input-action:hover {
        color: #34483E;
    }

    .btn-reset {
        display: none;
    }

    .btn-reset.is-visible {
        display: flex;
    }

    .error-msg {
        font-family: Arial, sans-serif;
        font-size: 11.5px;
        color: #e53e3e;
        margin-top: 4px;
        line-height: 1.2;
    }

    .forgot-wrapper {
        width: 100%;
        text-align: right;
        margin-bottom: 22px;
    }

    .forgot-link {
        font-family: Arial, sans-serif;
        font-size: 13.5px;
        font-weight: 600;
        color: #176D54;
        text-decoration: none;
        transition: opacity 0.2s ease;
    }

    .forgot-link:hover {
        opacity: 0.85;
        text-decoration: underline;
    }

    .btn-masuk {
        width: 100%;
        height: 54px;
        background-color: #176D54;
        color: #FFFFFF;
        font-family: Arial, sans-serif;
        font-size: 17px;
        font-weight: 600;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        box-shadow: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background-color 0.2s ease;
    }

    .btn-masuk i {
        font-size: 20px;
        line-height: 1;
    }

    .btn-masuk:hover {
        background-color: #125843;
        color: #FFFFFF;
        box-shadow: none;
    }

    .attribution {
        font-family: Arial, sans-serif;
        font-size: 15px;
        color: #60776B;
        line-height: 1;
        display: flex;
        align-items: center;
        gap: 5px;
        z-index: 10;
        margin-left: -22px;
    }

    .attribution .attr-title {
        font-weight: 400;
    }

    .attribution .attr-brand {
        font-weight: 600;
    }

    @media (max-width: 991px) {
        .login-layout {
            height: auto;
            min-height: 100vh;
            overflow-y: auto;
        }

        .spec-canvas {
            height: auto;
            min-height: 100vh;
            padding: 24px 32px;
        }

        .main-area {
            flex-direction: column;
            justify-content: center;
            gap: 36px;
            padding-bottom: 20px;
        }

        .platform-brand {
            width: 360px;
            max-width: 90%;
            margin-top: 0;
            text-align: center;
        }

        .platform-brand img {
            transform: none;
            object-position: center;
            margin: 0 auto;
        }

        .login-card {
            width: 100%;
            max-width: 350px;
            margin-top: 0;
        }

        .login-form {
            margin-top: 0;
        }

        .attribution {
            margin-left: 0;
            justify-content: center;
            padding-top: 16px;
        }
    }
</style>
@endsection

@section('body')
<body>
@endsection

@section('content')

<div class="login-layout">
    <div class="spec-canvas">

        <nav class="top-nav-wrapper">
            <a href="#about-us" class="top-nav-link">About Us</a>
            <span class="top-nav-divider">|</span>
            <a href="#contact-us" class="top-nav-link">Contact Us</a>
        </nav>

        <div class="main-area">
            <div class="platform-brand">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/logofixeds.png') }}" alt="Sentinel EMP">
                </a>
            </div>

            <div class="login-card">
                <form method="POST" action="{{ route('login') }}" class="login-form" novalidate>
                    @csrf

                    <div class="form-group-email">
                        <label for="username" class="field-label">Email / Username</label>
                        <div class="input-wrapper">
                            <input
                                name="email"
                                type="text"
                                class="custom-input @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                id="username"
                                placeholder="Email atau username"
                                autocomplete="username"
                                autofocus
                                required
                            >
                            <button type="button" class="btn-input-action btn-reset" id="btn-email-reset" title="Reset" tabindex="-1">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>
                        @error('email')
                        <div class="error-msg" role="alert">
                            <strong>{{ $message }}</strong>
                        </div>
                        @enderror
                    </div>

                    <div class="form-group-password">
                        <label for="userpassword" class="field-label">Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                name="password"
                                class="custom-input @error('password') is-invalid @enderror"
                                id="userpassword"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="btn-input-action" id="password-addon" title="Show/Hide" tabindex="-1">
                                <i class="mdi mdi-eye-off-outline" id="password-toggle-icon"></i>
                            </button>
                        </div>
                        @error('password')
                        <div class="error-msg" role="alert">
                            <strong>{{ $message }}</strong>
                        </div>
                        @enderror
                    </div>

                    <div class="forgot-wrapper">
                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">Lupa Kata Sandi?</a>
                        @else
                        <a href="#" class="forgot-link">Lupa Kata Sandi?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-masuk">
                        <i class="mdi mdi-login-variant"></i>
                        <span>Masuk</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="attribution">
            <span class="attr-title">Powered by</span>
            <span class="attr-brand">Sentinal Resq Tech</span>
        </div>

    </div>
</div>

@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggleBtn = document.getElementById('password-addon');
        var passwordInput = document.getElementById('userpassword');
        var toggleIcon = document.getElementById('password-toggle-icon');

        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    if (toggleIcon) {
                        toggleIcon.classList.remove('mdi-eye-off-outline');
                        toggleIcon.classList.add('mdi-eye-outline');
                    }
                } else {
                    passwordInput.type = 'password';
                    if (toggleIcon) {
                        toggleIcon.classList.remove('mdi-eye-outline');
                        toggleIcon.classList.add('mdi-eye-off-outline');
                    }
                }
            });
        }

        var emailInput = document.getElementById('username');
        var resetBtn = document.getElementById('btn-email-reset');

        if (emailInput && resetBtn) {
            function updateResetVisibility() {
                if (emailInput.value && emailInput.value.trim().length > 0) {
                    resetBtn.classList.add('is-visible');
                } else {
                    resetBtn.classList.remove('is-visible');
                }
            }

            emailInput.addEventListener('input', updateResetVisibility);
            emailInput.addEventListener('change', updateResetVisibility);
            updateResetVisibility();

            resetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                emailInput.value = '';
                updateResetVisibility();
                emailInput.focus();
            });
        }
    });
</script>
@endsection
