<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | CSMS</title>
    <link rel="stylesheet" href="{{ asset('css/residents.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="header-content">
            <a class="brand" href="{{ url('/') }}"><span class="brand-mark" aria-hidden="true">C</span> CSMS</a>
            <span class="site-name">Community Services Management System</span>
            <nav aria-label="Main navigation"><a href="{{ route('residents.index') }}">Resident Management</a></nav>
        </div>
    </header>
    <main class="container" id="main-content">
        @if (session('success'))
            <div class="notice success" role="status">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer">Community Services Management System &middot; Resident services</footer>
</body>
</html>
