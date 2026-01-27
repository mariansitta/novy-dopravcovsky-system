<form id="bill-delete-form" action="{{ route('transports.document_delete', $transport->id) }}" method="post">
    @csrf
    <input name="column" type="hidden" value="bill">
</form>

<form id="docs-delete-form" action="{{ route('transports.document_delete', $transport->id) }}" method="post">
    @csrf
    <input name="column" type="hidden" value="docs">
</form>

<div class="row">
    <div class="col-sm-6 col-md-3 mt-4">
        <div class="modal fade bs-example-modal-center" id="edit-form" data-route="{{ route('index') }}" tabindex="-1" style="overflow-y: hidden" aria-labelledby="mySmallModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0">{{ trans('texts.Documents') }}</h5>
                        <button type="button" class="close-modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-5">
                                <h4 class="card-title">{{ trans('texts.Received invoice') }}</h4>
                            </div>

                            <div class="col-2"></div>

                            <div class="col-5">
                                <h4 class="card-title">{{ trans('texts.Transport documents') }}</h4>
                            </div>

                        </div>

                        <form action="{{ route('transports.documents', $transport->id) }}" method="post" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-5">
                                    @if(!$transport->bill_file)
                                    <div class="form-group">
                                        <input name="bill" type="file" class="form-control filestyle" data-buttonname="btn-secondary" data-buttonText="{{ trans('texts.Upload file') }}">
                                        @include('web._partials._errors', ['column' => "bill"])
                                    </div>
                                    @else
                                        <div>{{ trans('texts.docs-uploaded') }}</div>
                                    @endif

                                    @if($path = $transport->bill)
                                        <div class="form-group">
                                            <a href="/{{ $transport->bill->path.$transport->bill->filename }}" target="_blank" class="btn btn-warning waves-effect waves-light">
                                                <i class="fas fa-file-alt"></i>
                                            </a>

                                            <button type="submit" form="bill-delete-form" class="btn btn-danger waves-effect waves-light">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    @endif

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
                                    @if(!$transport->docs_file)
                                    <div class="form-group">
                                        <input name="docs" type="file" class="form-control filestyle" data-buttonname="btn-secondary" data-buttonText="{{ trans('texts.Upload file') }}">
                                        @include('web._partials._errors', ['column' => "docs"])
                                    </div>
                                    @else
                                        <div>{{ trans('texts.docs-uploaded') }}</div>
                                    @endif

                                    @if($path = $transport->docs)
                                        <div class="form-group">
                                            <a href="/{{ $transport->docs->path.$transport->docs->filename }}" target="_blank" class="btn btn-warning waves-effect waves-light">
                                                <i class="fas fa-file-alt"></i>
                                            </a>

                                            <button type="submit" form="docs-delete-form" class="btn btn-danger waves-effect waves-light">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-2">
                                    <h4 class="card-title">{{ trans('texts.notify-email') }}</h4>
                                </div>
                                <div class="col-3">
                                    <div class="form-group">
                                        <input id="input-email" name="email" type="email" class="form-control" value="{{ $transport->user->notify_email}}" required />
                                        @include('web._partials._errors', ['column' => "email"])
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
    </div>
</div>