<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Users') - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    @stack('head')
    <style>
        .swal-light { background: #fff !important; border: 1px solid rgba(0,0,0,0.1); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); }
        .swal-title { color: #0f172a !important; }
        .swal-text { color: #64748b !important; }
        .swal-confirm { background: #f97316 !important; border: none !important; border-radius: 8px !important; padding: 10px 24px !important; color: #fff !important; }
        .swal-cancel { background: transparent !important; border: 1px solid rgba(15,23,42,0.2) !important; color: #0f172a !important; border-radius: 8px !important; }
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
<body>
    <div class="top-bar">
        <div class="container-fluid px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-link p-1 app-sidebar-toggle d-lg-none text-decoration-none" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation menu">
                        <span class="app-sidebar-toggle-icon" aria-hidden="true"></span>
                    </button>
                <span class="top-bar-meta">
                    @auth
                        @if(auth()->user()->is_active)
                            <span class="text-success fw-medium">Active</span>
                        @else
                            <span class="text-secondary fw-medium">Inactive</span>
                        @endif
                        <span class="text-muted">·</span>
                    @endauth
                    <time datetime="{{ now()->toIso8601String() }}">{{ now()->format('l, j M Y — g:i A') }}</time>
                </span>
                </div>
                <span class="top-bar-user">
                    @auth
                        {{ auth()->user()->name }}
                    @else
                        {{ config('app.name') }}
                    @endauth
                </span>
            </div>
        </div>
    </div>

    <div class="app-shell">
        <aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
            <div class="app-sidebar-inner">
                <a class="app-sidebar-brand" href="{{ route('users.index') }}">{{ config('app.name') }}</a>
                @auth
                <nav class="app-sidebar-nav">
                    <a class="app-sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people app-sidebar-link__icon" aria-hidden="true"></i><span>Users</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('sale.*') ? 'active' : '' }}" href="{{ route('sale.index') }}"><i class="bi bi-graph-up-arrow app-sidebar-link__icon" aria-hidden="true"></i><span>Sale</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('purchase.*') ? 'active' : '' }}" href="{{ route('purchase.index') }}"><i class="bi bi-bag-check app-sidebar-link__icon" aria-hidden="true"></i><span>Purchase</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><i class="bi bi-folder2 app-sidebar-link__icon" aria-hidden="true"></i><span>Projects</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('lands.*') ? 'active' : '' }}" href="{{ route('lands.index') }}"><i class="bi bi-geo-alt app-sidebar-link__icon" aria-hidden="true"></i><span>Land</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('factories.*') ? 'active' : '' }}" href="{{ route('factories.index') }}"><i class="bi bi-building app-sidebar-link__icon" aria-hidden="true"></i><span>Factory</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('daybook.*') ? 'active' : '' }}" href="{{ route('daybook.index') }}"><i class="bi bi-journal-text app-sidebar-link__icon" aria-hidden="true"></i><span>DayBook</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-person-vcard app-sidebar-link__icon" aria-hidden="true"></i><span>Customers</span></a>
                    {{--<a class="app-sidebar-link {{ request()->routeIs('jobs.*') ? 'active' : '' }}" href="{{ route('jobs.index') }}"><i class="bi bi-briefcase app-sidebar-link__icon" aria-hidden="true"></i><span>Jobs</span></a>--}}
                    <a class="app-sidebar-link {{ request()->routeIs('party-categories.*') ? 'active' : '' }}" href="{{ route('party-categories.index') }}"><i class="bi bi-tag app-sidebar-link__icon" aria-hidden="true"></i><span>Party Category</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('party-sub-categories.*') ? 'active' : '' }}" href="{{ route('party-sub-categories.index') }}"><i class="bi bi-tags app-sidebar-link__icon" aria-hidden="true"></i><span>Party Sub Category</span></a>
                    <a class="app-sidebar-link {{ request()->routeIs('land-types.*') ? 'active' : '' }}" href="{{ route('land-types.index') }}"><i class="bi bi-grid-3x2-gap app-sidebar-link__icon" aria-hidden="true"></i><span>Land types</span></a>
                </nav>
                @endauth
                <div class="app-sidebar-footer">
                    @auth
                        <form action="{{ route('logout') }}" method="POST" class="d-grid">
                            @csrf
                            <button type="submit" class="app-sidebar-logout w-100">
                                <i class="bi bi-box-arrow-right app-sidebar-logout__icon" aria-hidden="true"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="app-sidebar-logout app-sidebar-login w-100 text-center text-decoration-none"><i class="bi bi-box-arrow-in-right app-sidebar-logout__icon" aria-hidden="true"></i><span>Login</span></a>
                    @endauth
                </div>
            </div>
        </aside>
        <div class="app-sidebar-backdrop d-lg-none" id="appSidebarBackdrop" hidden aria-hidden="true"></div>

        <div class="app-main">
    <main class="@yield('main_class', 'container py-4')">
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
        </div>
    </div>

    @stack('modals')

    @if(session('success'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3 app-toast-container" aria-live="polite" aria-atomic="true">
        <div id="globalSuccessToast" class="toast app-success-toast border-0" role="alert" data-bs-delay="5000" data-bs-autohide="true">
            <div class="d-flex align-items-start">
                <div class="toast-body flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close btn-close-white flex-shrink-0 mt-2 me-2" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            var sidebar = document.getElementById('appSidebar');
            var backdrop = document.getElementById('appSidebarBackdrop');
            var toggle = document.querySelector('.app-sidebar-toggle');
            if (!sidebar || !toggle) return;
            function setOpen(open) {
                sidebar.classList.toggle('app-sidebar--open', open);
                if (backdrop) {
                    backdrop.hidden = !open;
                    backdrop.classList.toggle('is-visible', open);
                    backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
                }
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
                document.body.classList.toggle('app-sidebar-open', open);
            }
            toggle.addEventListener('click', function() {
                setOpen(!sidebar.classList.contains('app-sidebar--open'));
            });
            if (backdrop) {
                backdrop.addEventListener('click', function() { setOpen(false); });
            }
            window.addEventListener('resize', function() {
                if (window.matchMedia('(min-width: 992px)').matches) setOpen(false);
            });
            sidebar.querySelectorAll('a.app-sidebar-link').forEach(function(a) {
                a.addEventListener('click', function() {
                    if (window.matchMedia('(max-width: 991.98px)').matches) setOpen(false);
                });
            });
        })();
    </script>
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
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('globalSuccessToast');
            if (el && window.bootstrap && bootstrap.Toast) {
                bootstrap.Toast.getOrCreateInstance(el).show();
            }
        });
    </script>
    @endif
    @stack('scripts')
</body>
</html>
