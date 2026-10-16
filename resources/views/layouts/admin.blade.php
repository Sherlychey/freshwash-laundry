<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - FreshWash</title>
    <link rel="stylesheet" href="{{ asset('css/freshwash.css') }}"><link rel="stylesheet" href="{{ asset('css/readability.css') }}">
</head>
<body class="admin-body">
<aside class="sidebar" id="admin-sidebar">
    <a class="brand sidebar-brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark">F</span><span>fresh<span class="brand-light">wash</span><small>LAUNDRY CARE</small></span></a>
    <div class="sidebar-label">WORKSPACE</div>
    <nav class="side-nav" aria-label="Navigasi admin">
        <a class="side-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="side-symbol"><svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></svg></span>Dashboard</a>
        <div class="sidebar-label side-label-spaced">OPERASIONAL</div>
        <a class="side-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}"><span class="side-symbol"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M17 4a4 4 0 0 1 0 8m2 3a5 5 0 0 1 3 4v2"/></svg></span>Pelanggan</a>
        <a class="side-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}" href="{{ route('admin.services.index') }}"><span class="side-symbol"><svg viewBox="0 0 24 24"><path d="M4 4h16v16H4zM8 8h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/></svg></span>Layanan</a>
        <a class="side-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}"><span class="side-symbol"><svg viewBox="0 0 24 24"><path d="M5 3h14v18H5zM8 7h8M8 11h8M8 15h5"/></svg></span>Semua pesanan</a>
        <a class="side-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><span class="side-symbol"><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20m-15 5h4"/></svg></span>Pembayaran</a>
        <a class="side-link {{ request()->routeIs('admin.pickups.*') ? 'active' : '' }}" href="{{ route('admin.pickups.index') }}"><span class="side-symbol"><svg viewBox="0 0 24 24"><path d="M3 7h11v11H3zM14 11h4l3 3v4h-7z"/><circle cx="7.5" cy="19" r="1.5"/><circle cx="18" cy="19" r="1.5"/></svg></span>Antar-jemput</a>
        <a class="side-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}"><span class="side-symbol"><svg viewBox="0 0 24 24"><path d="M4 20V10m8 10V4m8 16v-7M2 20h20"/></svg></span>Laporan</a>
    </nav>
    <div class="sidebar-bottom"><div class="sidebar-help"><span class="help-mark">i</span><div><strong>Butuh bantuan?</strong><small>Hubungi administrator</small></div></div><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="logout-button" type="submit"><span aria-hidden="true">↪</span> Keluar</button></form></div>
</aside>
<div class="admin-main">
    <header class="admin-topbar"><button class="sidebar-toggle" type="button" aria-label="Buka menu" aria-expanded="false"><span></span><span></span><span></span></button><div class="breadcrumbs"><span>FreshWash</span><b>/</b><strong>@yield('page', 'Dashboard')</strong></div><div class="topbar-user"><span class="topbar-date">{{ now()->translatedFormat('l, d F Y') }}</span><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Pegawai' }}</small></span></div></header>
    <main class="admin-content">@yield('content')</main>
    <footer class="admin-footer">FreshWash Management System <span>•</span> Lingkungan pengembangan</footer>
</div>
<script src="{{ asset('js/freshwash.js') }}" defer></script>
</body>
</html>
