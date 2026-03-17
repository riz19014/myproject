<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Users') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <style>
        .swal-light { background: #fff !important; border: 1px solid rgba(0,0,0,0.1); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); }
        .swal-title { color: #0f172a !important; }
        .swal-text { color: #64748b !important; }
        .swal-confirm { background: #f97316 !important; border: none !important; border-radius: 8px !important; padding: 10px 24px !important; color: #fff !important; }
        .swal-cancel { background: transparent !important; border: 1px solid rgba(15,23,42,0.2) !important; color: #0f172a !important; border-radius: 8px !important; }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <span>We provide premium hosting services!</span>
                <span>{{ config('app.name') }}</span>
            </div>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-theme">
        <div class="container">
            <a class="navbar-brand" href="{{ route('users.index') }}">{{ config('app.name') }}</a>
            <button class="navbar-toggler border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                @auth
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('users.index') }}">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('projects.index') }}">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('lands.index') }}">Land</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('factories.index') }}">Factory</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('daybook.index') }}">DayBook</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('customers.index') }}">Customers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('jobs.index') }}">Jobs</a>
                    </li>
                </ul>
                @else
                <div class="me-auto"></div>
                @endauth
                <div class="d-flex align-items-center gap-2">
                    @auth
                        <a href="{{ route('users.create') }}" class="btn btn-pink btn-sm">Add User</a>
                        <a href="{{ route('projects.create') }}" class="btn btn-pink btn-sm">Add Project</a>
                        <a href="{{ route('daybook.create') }}" class="btn btn-pink btn-sm">DayBook Entry</a>
                        <a href="{{ route('customers.create') }}" class="btn btn-pink btn-sm">Add Customer</a>
                        <a href="{{ route('jobs.create') }}" class="btn btn-pink btn-sm">Add Job</a>
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-theme btn-sm">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-pink btn-sm">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        @if(session('success'))
            <div class="alert alert-theme-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-theme-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.btn-delete-confirm').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var form = this.closest('form');
                var title = this.dataset.title || 'Are you sure?';
                var text = this.dataset.text || 'You won\'t be able to revert this!';
                var confirmText = this.dataset.confirm || 'Yes, delete it!';
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f97316',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Cancel',
                    background: '#fff',
                    color: '#0f172a',
                    customClass: {
                        popup: 'swal-light',
                        title: 'swal-title',
                        htmlContainer: 'swal-text',
                        confirmButton: 'swal-confirm',
                        cancelButton: 'swal-cancel'
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
