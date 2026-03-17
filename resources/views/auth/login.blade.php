<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1920') center center / cover no-repeat;
            z-index: 0;
        }
        .login-page::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(248,250,252,0.85) 0%, rgba(255,255,255,0.95) 100%);
            z-index: 1;
        }
        .login-box {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 420px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        .login-card .card-body {
            padding: 2.5rem;
        }
        .login-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }
        .login-subtitle {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
        }
        .login-page .form-control-theme,
        .login-page .form-select-theme {
            padding: 0.65rem 1rem;
            border-radius: 8px;
        }
        .login-page .btn-pink {
            padding: 0.65rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
        }
        .login-page .btn-outline-theme {
            border-radius: 8px;
        }
        .login-logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            letter-spacing: -0.5px;
            color: #0f172a;
        }
        .login-page .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
            vertical-align: middle;
            border-color: rgba(15,23,42,0.2);
            border-right-color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <div class="login-card card">
                <div class="card-body">
                    <div class="login-logo">{{ config('app.name') }}</div>
                    <h2 class="login-title">Welcome back</h2>
                    <p class="login-subtitle">Sign in to your account</p>

                    @if($errors->any())
                        <div class="alert alert-theme-danger alert-dismissible fade show mb-3" role="alert">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST" id="loginForm">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control form-control-theme @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control form-control-theme @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" class="btn btn-pink w-100 mb-2" id="loginBtn">
                            <span class="btn-text">Sign In</span>
                            <span class="btn-spinner d-none">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Signing in...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            var btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.querySelector('.btn-text').classList.add('d-none');
            btn.querySelector('.btn-spinner').classList.remove('d-none');
        });
    </script>
</body>
</html>
