@extends('layouts.master-without-nav')

@section('title')
@lang('translation.Login')
@endsection

@section('css')
<style>
    .auth-body-bg {
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fb 0%, #eef1f8 100%);
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .auth-card {
        width: 100%;
        max-width: 440px;
        border: none;
        border-radius: 1rem;
        box-shadow: 0 1.5rem 4rem rgba(18, 38, 63, 0.12);
        overflow: hidden;
    }

    .auth-card .card-body {
        padding: 2.5rem 2.5rem 2rem;
    }

    .auth-card .form-control,
    .auth-card .btn {
        border-radius: 0.6rem;
    }

    .auth-card .form-control {
        padding: 0.65rem 0.9rem;
    }

    .auth-card .btn-primary {
        padding: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
</style>
@endsection

@section('body')

<body class="auth-body-bg">
@endsection

@section('content')

<div class="auth-wrapper">
    <div class="card auth-card">
        <div class="card-body">

            <!-- LOGO: mb-0 untuk menghilangkan jarak bawah -->
            <div class="text-center mb-0">
                <a href="index" class="d-inline-block auth-logo">
                    <img src="{{ URL::asset('build/images/logos44.png') }}" alt="Resq" height="200">
                </a>
            </div>

            <!-- TULISAN: Menggunakan style margin-top negatif untuk ditarik ke atas -->
            <div class="text-center mb-4" style="margin-top: -60px; position: relative; z-index: 10;">
                {{-- <h5 class="text-primary mb-1">Welcome Back !</h5> --}}
                <p class="text-muted mb-0">Sign in to continue to Sentinal Platform</p>
            </div>

            <form class="form-horizontal" method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="username" class="form-label">Email <span class="text-danger">*</span></label>
                    <input name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="" id="username" placeholder="Enter Email" autocomplete="email" autofocus>
                    @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="float-end">
                        {{-- @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-muted">Forgot password?</a>
                        @endif --}}
                    </div>
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group auth-pass-inputgroup @error('password') is-invalid @enderror">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="userpassword" value="" placeholder="Enter password" aria-label="Password" aria-describedby="password-addon">
                        <button class="btn btn-light" type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                {{-- <div class="form-check"> --}}
                    {{-- <input class="form-check-input" type="checkbox" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label> --}}
                {{-- </div> --}}

                <div class="mt-4 d-grid">
                    <button class="btn btn-primary waves-effect waves-light" type="submit">Log in</button>
                </div>
            </form>

            <div class="mt-4 text-center">
                <p class="text-muted mb-0">© <script>
                        document.write(new Date().getFullYear())
                    </script> sentinalplatform</p>
            </div>

        </div>
    </div>
</div>

@endsection

@section('script')
@endsection