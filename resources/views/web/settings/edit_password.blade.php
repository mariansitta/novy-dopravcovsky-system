@extends('layout.web')

@section('title', ' - ' . trans('texts.Drivers system'))

@section('body')

    <body data-layout="horizontal" data-topbar="dark">
    @endsection

    @section('content')


        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-sm-12">
                                @include('web._partials._alert')
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-5">
                                <h4 class="card-title">Zmena Hesla</h4>
                            </div>
                        </div>

                        <form action="{{ route('settings.update_password') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group row">
                                <div class="col-lg-6">
                                    <label for="example-password-input" class="col-sm-2 col-form-label">Password</label>
                                    <input class="form-control" name="password" type="password" id="example-password-input">
                                    @include('web._partials._errors', ['column' => "password"])
                                </div>
                                <div class="col-lg-6">
                                    <label for="example-password-input" class="col-sm-4 col-form-label">Password Confirmation</label>
                                    <input class="form-control" name="password_confirmation" type="password" id="example-password-input">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="text-center mt-4 form-group">
                                        <button type="submit" class="btn btn-primary waves-effect waves-light" style="padding-right: 30px; padding-left: 30px">{{ trans('texts.Save') }}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
@endsection
