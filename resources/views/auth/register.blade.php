@extends('layout.auth')
@section('title', ' - ' . trans('texts.Registration'))

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
                                <p class="text-muted">{{ trans('texts.Registration') }}</p>
                            </div>
                            <div class="p-2 mt-4">
                                <form method="POST" action="{{ route('register') }}" method="post" id="registration-form">
                                    @csrf
                                    @include('web._partials._errors', ['column' => "hash"])
                                    <input type="hidden" name="hash" value="{{ request('hash') != null ? request('hash') : ""  }}">
                                    <div class="mb-3">
                                        <label class="form-label" for="username">{{ trans('texts.Email') }}</label>
                                        <input type="text" class="form-control {{ $errors->has("email") ? 'parsley-error' : '' }} login"
                                               name="email" value="{{ old('email', request('email')) }}" id="email"
                                               placeholder="{{ trans('texts.Email') }}">
                                        @include('web._partials._errors', ['column' => "email"])
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="userpassword">{{ trans('texts.Password') }}</label>
                                        <input type="password" class="form-control {{ $errors->has("password") ? 'parsley-error' : '' }} login"
                                               value="{{ old('username') ? '' : request('password') }}" name="password" id="password" placeholder="{{ trans('texts.Password') }}">
                                        @include('web._partials._errors', ['column' => "password"])
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="userpassword">{{ trans('texts.Password Confirmation') }}</label>
                                        <input type="password" class="form-control {{ $errors->has("password_confirmation") ? 'parsley-error' : '' }} login"
                                               name="password_confirmation" id="password" placeholder="{{ trans('texts.Password Confirmation') }}">
                                        @include('web._partials._errors', ['column' => "password_confirmation"])
                                    </div>

                                    <div class="mt-3 text-end">
                                        <a class="redirect_link" href="{{ route('login') }}">{{ trans('texts.Already have account') }}</a>
                                        <button class="btn btn-primary w-sm waves-effect waves-light" type="submit" id="login-button">{{ trans('texts.Register') }}</button>
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
