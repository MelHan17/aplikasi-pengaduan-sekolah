<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Aspirasi') · {{ config('app.name', 'Sekolah') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
</head>
<body class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">r.</span><span>ruang<span class="brand-light">aspirasi</span><small>PORTAL SEKOLAH</small></span></a>
        <div class="side-label">MENU UTAMA</div>
        <a class="nav-link active" href="{{ route('dashboard') }}"><span class="nav-symbol">◫</span>{{ auth()->user()->role === 'admin' ? 'Semua aspirasi' : 'Aspirasi saya' }}</a>
        @if (auth()->user()->role === 'student')<a class="nav-link" href="#form-aspirasi"><span class="nav-symbol">＋</span>Kirim aspirasi</a>@endif
        <div class="sidebar-note"><span class="note-dot"></span><strong>{{ auth()->user()->role === 'admin' ? 'Panel pengelola' : 'Suaramu berarti' }}</strong><p>{{ auth()->user()->role === 'admin' ? 'Setiap laporan adalah kesempatan untuk membuat sekolah lebih baik.' : 'Setiap aspirasi akan ditinjau dan progresnya bisa kamu pantau.' }}</p></div>
        <div class="sidebar-bottom"><div class="user-chip"><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Siswa' }}</small></span></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button" type="submit" aria-label="Keluar" title="Keluar">↗</button></form></div>
    </aside>
    <main class="main-area">
        <header class="topbar"><span class="topbar-caption">{{ now()->translatedFormat('l, d F Y') }}</span><span class="topbar-school"><span class="school-dot"></span>Portal Pengaduan Sekolah</span></header>
        @if (session('success'))<div class="flash-success" role="status">{{ session('success') }}</div>@endif
        @yield('content')
    </main>
</body>
</html>