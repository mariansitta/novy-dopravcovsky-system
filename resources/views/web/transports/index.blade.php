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

                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap " style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th>{{ trans('texts.Order number') }}</th>
                                    <th>{{ trans('texts.Loading date') }}</th>
                                    <th>{{ trans('texts.Loading') }}</th>
                                    <th>{{ trans('texts.Unloading') }}</th>
                                    <th>{{ trans('texts.LDM') }}</th>
                                    <th>{{ trans('texts.Weight') }}</th>
                                    <th>{{ trans('texts.Driver price') }}</th>
                                    <th>{{ trans('texts.Status') }}</th>
                                    {{--<th>{{ trans('texts.Paid at') }}</th>--}}
                                    <th>{{ trans('texts.Notice') }}</th>
                                    <th>{{ trans('texts.Bill') }}</th>
                                    <th>{{ trans('texts.Docs') }}</th>
                                    <th>{{ trans('texts.Actions') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($table as $item)
                                    <tr>
                                        <td>{{ $item->number }}</td>

                                        <td>
                                            {{ $item->loading_date ? \Carbon\Carbon::parse($item->loading_date)->format('d. m. Y') : '' }}
                                        </td>

                                        <td>{{ $item->loading }}</td>
                                        <td>{{ $item->unloading }}</td>
                                        <td>{{ $item->ldm }}</td>
                                        <td>{{ $item->weight }}</td>

                                        <td>
                                            @if($item->driver_price === null)
                                                <i>-</i>
                                            @else
                                                {{ number_format((float) $item->driver_price, 2, ',', ' ') }} €
                                            @endif
                                        </td>

                                        <td>
                                            <span {!! $item->status_slug === 'paid' ? "class='text-success'" : '' !!}>
                                                {{ $item->status_name ?? '' }}
                                            </span>
                                        </td>

                                        {{--
                                        <td>
                                            @if($item->paid_at)
                                                {{ \Carbon\Carbon::parse($item->paid_at)->format('d. m. Y') }}
                                            @endif
                                        </td>
                                        --}}

                                        <td>
                                            @if($item->driver_notice !== null)
                                                <div style="max-width: 350px; white-space: normal;">{{ $item->driver_notice }}</div>
                                            @endif
                                        </td>

                                        <td>
                                            @if($item->bill_file)
                                                <i class="fas fa-2x fa-check text-success"></i>
                                            @else
                                                @if($item->bill !== null)
                                                    <a
                                                        class="btn btn-light waves-effect waves-light action-button"
                                                        title="{{ trans('texts.Received invoice') }}"
                                                        href="{{ asset($item->bill->path . $item->bill->filename) }}"
                                                        target="_blank"
                                                    >
                                                        <i class="fas fa-file-alt action-icon"></i>
                                                    </a>
                                                @else
                                                    <i class="fas fa-2x fa-check fa-times text-danger"></i>
                                                @endif
                                            @endif
                                        </td>

                                        <td>
                                            @if($item->docs_file)
                                                <i class="fas fa-2x fa-check text-success"></i>
                                            @else
                                                @if($item->docs !== null)
                                                    <a
                                                        class="btn btn-light waves-effect waves-light action-button"
                                                        title="{{ trans('texts.Transport documents') }}"
                                                        href="{{ asset($item->docs->path . $item->docs->filename) }}"
                                                        target="_blank"
                                                    >
                                                        <i class="fas fa-car action-icon"></i>
                                                    </a>
                                                @else
                                                    <i class="fas fa-2x fa-check fa-times text-danger"></i>
                                                @endif
                                            @endif
                                        </td>

                                        <td>
                                            @if(!($item->bill_file && $item->docs_file) && !$item->is_deleted)
                                                <a
                                                    class="btn btn-warning waves-effect waves-light action-button"
                                                    title="{{ trans('texts.Upload documents') }}"
                                                    href="{{ route('transports.edit', $item->trans_id) }}"
                                                >
                                                    <i class="fas fa-folder-open action-icon"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <p>{!! trans('texts.manual-notice') !!}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $(document).ready(function () {
        $('#datatable').DataTable({
            "order": false,
            "ordering": false,
            "responsive": true,
            "pageLength": 25,
            "language": {
                "search": "Hľadať:",
                "emptyTable": "Žiadne dáta",
                "lengthMenu": "Zobraziť _MENU_ záznamov",
                "info": "Zobrazené _START_ - _END_ z _TOTAL_",
                "paginate": {
                    "first": "Prvá",
                    "last": "Posledná",
                    "next": "Nasledujúca",
                    "previous": "Predchádzajúca"
                }
            }
        });
    });
</script>
@endsection