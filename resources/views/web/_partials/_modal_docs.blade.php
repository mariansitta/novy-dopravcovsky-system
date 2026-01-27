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
                            <div class="col-12">
                                <h4 class="card-title">{{ trans('texts.Transport documents') }}</h4>
                            </div>
                        </div>

                        <form action="{{ route('transports.doc_document', $transport->id) }}" method="post" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-12">
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
    </div>
</div>