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

    <title>
        {{ env('APP_NAME') }}
        - 404
    </title>
</head>

<body class="authentication-bg">

    <div class="my-5 pt-sm-5">
    <div class="container">

        <div class="row">
            <div class="col-md-12">
                <div class="text-center">
                    <div>
                        <div class="row justify-content-center">
                            <div class="col-sm-4">
                                <div class="error-img">
                                    <img src="{{ asset('images/404-error.png') }}" alt=""
                                         class="img-fluid mx-auto d-block">
                                </div>
                            </div>
                        </div>
                    </div>
                    <h4 class="text-uppercase mt-4">Stránka nebola nájdená</h4>
                    <div class="mt-5">
                        <a class="btn btn-primary waves-effect waves-light" href="{{ route('index') }}">Späť domov</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
    <script src="{{ asset('js/login.min.js') }}" type="text/javascript"></script>

</body>
</html>
