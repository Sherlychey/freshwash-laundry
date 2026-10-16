<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FreshWash — Laundry lebih mudah')</title>
    <link rel="stylesheet" href="{{ asset('css/freshwash.css') }}">
    <link rel="stylesheet" href="{{ asset('css/readability.css') }}">
</head>
<body class="public-body">
    @yield('content')
    <script src="{{ asset('js/freshwash.js') }}" defer></script>
</body>
</html>
