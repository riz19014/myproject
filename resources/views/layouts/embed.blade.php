<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Edit') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    @stack('head')
    <style>
        body {
            background: #f8fafc;
            margin: 0;
            min-height: 100vh;
        }
        .embed-shell {
            padding: 0.75rem 0.85rem 1.25rem;
        }
        .app-toast-container { z-index: 1080; pointer-events: none; }
        .app-toast-container .toast { pointer-events: auto; }
        .app-success-toast {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            color: #fff;
            border-radius: 12px;
            min-width: 280px;
            max-width: min(420px, calc(100vw - 2rem));
            box-shadow: 0 12px 40px rgba(22, 101, 52, 0.35);
        }
        .app-success-toast .toast-body { color: #fff; padding: 0.85rem 1rem; line-height: 1.45; }
    </style>
</head>
<body class="daybook-embed-body">
    <div class="embed-shell">
        @if(session('success'))
            <div class="alert alert-success py-2 px-3 mb-3" role="status">{{ session('success') }}</div>
        @endif
        @yield('content')
    </div>

    @stack('modals')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
