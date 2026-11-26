<!doctype html>
<html lang="id">
<head>
    @include('admin.layouts.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Timore Soccer School</title>
    @if(is_file(public_path('assets/admin/js/enhancements/interactions.js')))
        <script type="module" src="{{ asset('assets/admin/js/enhancements/interactions.js') }}"></script>
    @endif
    @stack('page-scripts')
</head>
<body>
<a class="skip-link" href="#main-content">Lewati ke konten</a>
<div class="shell">
    <aside class="sidebar">@include('admin.layouts.navigation')</aside>
    <dialog class="navigation-drawer" id="navigation-drawer" aria-label="Menu admin">
        <div class="drawer-inner">
            <form method="dialog" class="drawer-close"><button class="btn" autofocus>Tutup menu</button></form>
            @include('admin.layouts.navigation')
        </div>
    </dialog>
    <main class="main" id="main-content" tabindex="-1">
        <header class="topbar">
            <div class="topbar-start">
                <button class="btn nav-launcher" type="button" id="navigation-launcher" aria-label="Buka menu" aria-expanded="false" aria-controls="navigation-drawer"><x-admin.icon name="menu" /></button>
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Admin</a><x-admin.icon name="chevron" />
                    <span aria-current="page">@yield('breadcrumb', trim($__env->yieldContent('title', 'Dashboard')))</span>
                </nav>
            </div>
            <div class="topbar-end">
                @if(app()->environment('local'))<span class="demo-badge" title="Lingkungan presentasi lokal">Demo</span>@endif
                <x-admin.theme-control />
                <details class="account">
                    <summary class="avatar" aria-label="Menu akun">{{ strtoupper(substr(auth('web')->user()->name, 0, 1)) }}</summary>
                    <div class="account-menu">
                        <strong>{{ auth('web')->user()->name }}</strong>
                        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="btn" type="submit">Keluar</button></form>
                    </div>
                </details>
            </div>
        </header>
        <div class="content">
            <div class="page-heading">
                <div>
                    <h1>@yield('title', 'Dashboard')</h1>
                    <p>@yield('description', 'Kelola kegiatan operasional Timore Soccer School.')</p>
                </div>
                @yield('header-action')
            </div>
            @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
            @include('admin.student-access')
            @if($errors->any())
                <div class="alert error" role="alert"><strong>Periksa kembali isian formulir.</strong>
                    <ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
