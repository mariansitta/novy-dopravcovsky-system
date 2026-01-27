@extends('layout.web')

@section('title', ' - ' . trans('texts.Drivers system'))

@section('body')

    <body data-layout="horizontal" data-topbar="dark">
    @endsection

    @section('content')
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <h4 class="card-title">{{ trans('texts.Transports') }}</h4>

                        @include('web._partials._alert')

                        <table id="datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                            <tr>
                                <th>{{ trans('texts.Order number') }}</th>
                                <th>{{ trans('texts.Loading date') }}</th>
                                <th>{{ trans('texts.Loading') }}</th>
                                <th>{{ trans('texts.Unloading') }}</th>
                                <th>{{ trans('texts.LDM') }}</th>
                                <th>{{ trans('texts.Driver plate number') }}</th>
                                <th>{{ trans('texts.Driver price') }}</th>
                                <th>{{ trans('texts.Status') }}</th>
                                <th>{{ trans('texts.Due date') }}</th>
                                <th>{{ trans('texts.Actions') }}</th>
                            </tr>
                            </thead>

                            <tbody>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

        @include('web._partials._modal')
    @endsection

    @section('js')
        <script>
            $(document).ready(function () {
                let t = $('#datatable').DataTable({
                    "ajax" : "{{ route('transports.ajax') }}",
                    "scrollX": true,
                    "order": false,
                    "responsive": false,
                    "columns" : [
                        { "data": "number" },
                        { "data": "loading_date",
                            render: function (data) {
                                return (data === null) ? "" : data.replace(/(\d{4})-(\d{2})-(\d{2})/gm, function(g1, g2, g3, g4) {
                                    return g4 + '. ' + g3 + '. ' + g2;
                                });
                            }
                        },
                        { "data": "loading" },
                        { "data": "unloading" },
                        { "data": "ldm" },
                        { "data": "driver_plate_number" },
                        { "data": "driver_price",
                            render: function (data) {
                                return (data === null) ? "<i>-</i>" : data.replace(/(?!^)(?=(?:\d{3})+(?:\.|$))/gm, ' ').replace('.', ',') + ' €';
                            }
                        },
                        { "data": null,
                            render: function (data) {
                                return "<span "+(data.status_slug === 'paid' ? "class='text-success'" : '')+">"+(data.status_name ?? '')+"</span>";
                            }
                        },
                        { "data": "due_date",
                            render: function (data) {
                                return (data === null) ? "" : data.replace(/(\d{4})-(\d{2})-(\d{2})/gm, function(g1, g2, g3, g4) {
                                    return g4 + '. ' + g3 + '. ' + g2;
                                });
                            }
                        },
                        { "data": null, render: function(data) {
                                let document_html = null;
                                let bill_html = null;
                                if (data.docs !== null) {
                                    let document_path = "{{ asset('/:path') }}".replace(":path", data.docs);
                                    document_html = "<a class=\"btn btn-light waves-effect waves-light action-button\" title=\"{{ trans('texts.Transport documents') }}\" href=\"" + document_path + "\" target=\"_blank\">\n" +
                                        "                    <i class=\"fas fa-car action-icon\"></i>\n" +
                                        "               </a>\n";
                                }

                                if (data.bill !== null) {
                                    let bill_path = "{{ asset('/:path') }}".replace(":path", data.bill);
                                    bill_html = "<a class=\"btn btn-light waves-effect waves-light action-button\" title=\"{{ trans('texts.Received invoice') }}\" href=\"" + bill_path + "\" target=\"_blank\">\n" +
                                        "             <i class=\"fas fa-file-alt action-icon\"></i>\n" +
                                        "       </a>\n";
                                }

                                let result = " <td>";
                                let upload_route = "{{ route('transports.edit', ':id') }}".replace(':id', data.trans_id);
                                let upload = " <a class=\"btn btn-warning waves-effect waves-light action-button\" title=\"{{ trans('texts.Upload documents') }}\" href=\"" + upload_route + "\">\n" +
                                    "                            <i class=\"fas fa-folder-open action-icon\"></i>\n" +
                                    "                        </a>\n";

                                result += (bill_html !== null) ? bill_html : "";
                                result += (document_html !== null) ? document_html : "";
                                result += ((bill_html !== null && document_html !== null) || data.status_id !== null) ? "</td>" : upload + "</td>";
                                return result;
                            }
                        },
                    ],
                });
            })
        </script>
@endsection
