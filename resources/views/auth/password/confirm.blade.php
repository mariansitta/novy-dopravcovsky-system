@extends('layout.auth')
@section('title', ' - ' . trans('texts.confirm'))

@section('content')
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <a href="javascript:void(0)" class="mb-5 d-block auth-logo">
                            <img src="{{ asset('images/logo.png') }}" alt="" height="100" class="logo logo-dark">
                        </a>
                    </div>
                </div>
            </div>
            <div class="row align-items-center justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card">

                        <div class="card-body p-4">
                            <div class="text-center mt-2">
                                <h5 class="text-primary">Damaro — {{ trans('texts.Drivers system') }}</h5>
                                <p class="text-muted">{{ trans('texts.Reset') }}</p>
                            </div>
                            <div class="p-2 mt-4">
                                <h2>{{ trans('texts.email-text-1') }}</h2>
                                <form method="POST" action="{{ route('login') }}" method="post" id="login-form">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label" for="password">{{ trans('texts.Password') }}</label>
                                        <input type="password" class="form-control {{ $errors->has("password") ? 'parsley-error' : '' }} login"
                                               value="{{ old('username') ? '' : request('password') }}" name="password" id="password" placeholder="{{ trans('texts.Password') }}">
                                        @include('web._partials._errors', ['column' => "password"])
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-6">
                                            <button class="btn btn-primary w-sm waves-effect waves-light" type="submit" id="login-button">{{ trans('texts.Confirm) }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>

                    <div class="mt-5 text-center created-by">
                        © {{ \Carbon\Carbon::now()->year }} Damaro — {{ trans('texts.Drivers system') }} |
                        <span class="d-none d-sm-inline-block">
                            {{ trans('texts.Developed by') }}
                            <a href="https://www.demi.studio/" target="_blank" class="color-demi link-demi">
                                <b>DeMi Studio</b>
                            </a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

