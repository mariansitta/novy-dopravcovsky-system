<!doctype html>
<html lang="sk">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Condensed&family=IBM+Plex+Sans:wght@400;500&display=swap" rel="stylesheet">

    <link href="{{ asset('css/admin.min.css') }}" rel="stylesheet" type="text/css">
    @yield('css')

    <title>
        {{ env('APP_NAME') }}
        @yield('title')
    </title>

</head>

@section('body')

    <body>
    @show

    <div id="layout-wrapper">

    @include('web._partials._topbar')
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
            @include('web._partials._footer')
        </div>
    </div>

    <script src="{{ asset('js/admin.min.js') }}" type="text/javascript"></script>
    @yield('js')
    </body>
</html>
