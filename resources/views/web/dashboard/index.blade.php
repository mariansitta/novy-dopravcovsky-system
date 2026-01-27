@extends('layout.web')

@section('title', ' - ' . trans('texts.Drivers system'))

@section('body')

    <body data-layout="horizontal" data-topbar="dark">
@endsection

@section('content')
    <div class="row">
        <div class="col main-info">
            <h4 class="text-center card-title-desc">
                {{ trans('texts.to-upload') }}
            </h4>

            <h4 class="text-center card-title-desc text-danger">
                {{ trans('texts.make-sure-1') }}
                <span>{{ trans('texts.make-sure-2') }} {{ auth()->user()->transport ? auth()->user()->transport->number : '-' }}</span>.
                {{ trans('texts.make-sure-3') }}
            </h4>
        </div>
    </div>

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
                            <h4 class="card-title">{{ trans('texts.Received invoice') }}</h4>
                        </div>

                        <div class="col-2"></div>

                        <div class="col-5">
                            <h4 class="card-title">{{ trans('texts.Transport documents') }}</h4>
                        </div>

                    </div>

                    <form action="{{ route('documents') }}" method="post" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-5">
                                <div class="form-group">
                                    <input name="bill" type="file" class="form-control filestyle" data-buttonname="btn-secondary" data-buttonText="{{ trans('texts.Upload file') }}">
                                    @include('web._partials._errors', ['column' => "bill"])
                                </div>

                                <div class="mt-3">
                                    {{ trans('texts.due-date-1') }}
                                    <b>{{ $transport ? $transport->due_days : '-' }}</b>
                                    {{ trans('texts.due-date-2') }}
                                    {{ trans('texts.due-date-3') }}
                                    <b>{{ $transport ? now()->addDays($transport->due_days)->format('d. m. Y') : '-' }}</b>
                                </div>
                            </div>
                            <div class="col-2"></div>
                            <div class="col-5">
                                <div class="form-group">
                                    <input name="docs" type="file" class="form-control filestyle" data-buttonname="btn-secondary" data-buttonText="{{ trans('texts.Upload file') }}">
                                    @include('web._partials._errors', ['column' => "docs"])
                                </div>
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
