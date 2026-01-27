<!doctype html>
<html lang="sk">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Condensed&family=IBM+Plex+Sans:wght@400;500&display=swap" rel="stylesheet">

    <link href="{{ asset('css/login.min.css') }}" rel="stylesheet" type="text/css">
    @yield('css')

    <title>
        {{ env('APP_NAME') }}
        @yield('title')
    </title>
</head>

<body class="authentication-bg">

    @yield('content')
    <script src="{{ asset('js/login.min.js') }}" type="text/javascript"></script>
    @yield('js')

</body>
</html>
