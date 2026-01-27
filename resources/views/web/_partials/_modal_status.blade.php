<div class="row">
    <div class="col-sm-6 col-md-3 mt-4">
        <div class="modal fade bs-example-modal-center" id="edit-form" data-route="{{ route('index') }}" tabindex="-1" style="overflow-y: hidden" aria-labelledby="mySmallModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0">{{ trans('texts.Transport status') }}</h5>
                        <button type="button" class="close-modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">

                        <form action="{{ route('transports.transport_status_form', $transport->id) }}" method="post" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                                <div class="col-4">
                                    {{ \Carbon\Carbon::parse($transport->loading_date)->format('d.m.Y') }} {{ $transport->loading }} ---> {{ $transport->unloading }}
                                </div>
                                <div class="col-4">
                                    <select class="form-control" name="transport_status">
                                        <option value="">Žiadny</option>
                                        <option value="loading-arrival" {{ $transport->transport_status && $transport->transport_status->status == 'loading-arrival' ? 'selected' : '' }}>{{ trans('texts.transport-status.loading-arrival') }}</option>
                                        <option value="loaded" {{ $transport->transport_status && $transport->transport_status->status == 'loaded' ? 'selected' : '' }}>{{ trans('texts.transport-status.loaded') }}</option>
                                        <option value="unloading-arrival" {{ $transport->transport_status && $transport->transport_status->status == 'unloading-arrival' ? 'selected' : '' }}>{{ trans('texts.transport-status.unloading-arrival') }}</option>
                                        <option value="unloaded" {{ $transport->transport_status && $transport->transport_status->status == 'unloaded' ? 'selected' : '' }}>{{ trans('texts.transport-status.unloaded') }}</option>
                                    </select>
                                </div>
                                <div class="col-4">
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