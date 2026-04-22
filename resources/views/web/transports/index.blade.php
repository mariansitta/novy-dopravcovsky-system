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

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">{{ trans('texts.Transports') }}</h4>
                        @if($hasCargo)
                            <a href="{{ route('cargo.index') }}" class="btn btn-primary btn-sm">Prepravy podľa vozidiel</a>
                        @endif
                    </div>

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
                                            @if($item->invoice_type === 'mail')
                                                <span class="badge text-dark" style="background-color:#ffc107" title="Faktúra poštou">Poštou</span>
                                            @elseif(!($item->bill_file && $item->docs_file) && !$item->is_deleted)
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

            {{-- Panel správ (dočasne iba pre spedicia@damaro-slovakia.eu) --}}
            @if(auth()->check() && auth()->user()->email === 'spedicia@damaro-slovakia.eu')
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-0" id="messages-toggle" style="cursor: pointer; user-select: none;">
                        <i class="fas fa-comments mr-2"></i>
                        Správy
                        @if($unreadCount > 0)
                            <span class="badge ml-2" id="unread-badge" style="background-color:#dc3545; color:#fff;">{{ $unreadCount }}</span>
                        @else
                            <span class="badge ml-2" id="unread-badge" style="background-color:#dc3545; color:#fff; display:none;">0</span>
                        @endif
                        <i class="fas fa-chevron-down ml-2 float-right" id="messages-chevron"></i>
                    </h5>

                    <div id="messages-panel" style="display: none; margin-top: 20px;">

                        {{-- História správ --}}
                        <div id="messages-history" style="max-height: 400px; overflow-y: auto; padding: 10px; background: #f8f9fa; border-radius: 6px; margin-bottom: 15px;">
                            <p class="text-muted text-center" id="messages-loading">Načítavam správy...</p>
                        </div>

                        {{-- Formulár na odoslanie správy --}}
                        <div>
                            <textarea id="message-body" class="form-control" rows="3" placeholder="Napíšte správu..." maxlength="2000" style="resize: vertical;"></textarea>
                            <div class="text-right mt-2">
                                <button id="message-send-btn" class="btn btn-primary waves-effect waves-light">
                                    <i class="fas fa-paper-plane mr-1"></i> Odoslať
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            @endif

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

        // ---- Správy ----

        let messagesLoaded = false;

        // Rozbalenie/zbalenie panelu
        $('#messages-toggle').on('click', function () {
            $('#messages-panel').slideToggle(200);
            $('#messages-chevron').toggleClass('fa-chevron-down fa-chevron-up');

            if (!messagesLoaded) {
                loadMessages();
            }
        });

        // Načítanie histórie správ + označenie ako prečítané
        function loadMessages() {
            $.ajax({
                url: '{{ route("transports.messages.load") }}',
                method: 'GET',
                success: function (response) {
                    messagesLoaded = true;
                    $('#unread-badge').hide();

                    let html = '';
                    if (response.messages.length === 0) {
                        html = '<p class="text-muted text-center">Žiadne správy.</p>';
                    } else {
                        response.messages.forEach(function (msg) {
                            if (msg.direction === 'driver') {
                                html += `
                                    <div style="text-align: right; margin-bottom: 10px;">
                                        <div style="display: inline-block; background: #007bff; color: #fff; padding: 8px 12px; border-radius: 12px 12px 0 12px; max-width: 75%; word-break: break-word;">
                                            ${escapeHtml(msg.body)}
                                        </div>
                                        <div style="font-size: 11px; color: #999; margin-top: 2px;">${msg.created_at}</div>
                                    </div>`;
                            } else {
                                html += `
                                    <div style="text-align: left; margin-bottom: 10px;">
                                        <div style="display: inline-block; background: #e9ecef; color: #333; padding: 8px 12px; border-radius: 12px 12px 12px 0; max-width: 75%; word-break: break-word;">
                                            ${escapeHtml(msg.body)}
                                        </div>
                                        <div style="font-size: 11px; color: #999; margin-top: 2px;">${msg.created_at}</div>
                                    </div>`;
                            }
                        });
                    }

                    $('#messages-history').html(html);
                    $('#messages-history').scrollTop($('#messages-history')[0].scrollHeight);
                },
                error: function () {
                    $('#messages-history').html('<p class="text-danger text-center">Chyba pri načítaní správ.</p>');
                }
            });
        }

        // Odoslanie správy
        $('#message-send-btn').on('click', function () {
            let body = $('#message-body').val().trim();
            if (!body) return;

            $('#message-send-btn').prop('disabled', true);

            $.ajax({
                url: '{{ route("transports.messages.send") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    body: body,
                },
                success: function () {
                    $('#message-body').val('');
                    messagesLoaded = false;
                    loadMessages();
                },
                error: function () {
                    alert('Chyba pri odosielaní správy.');
                },
                complete: function () {
                    $('#message-send-btn').prop('disabled', false);
                }
            });
        });

        // Escape HTML pre bezpečné zobrazenie textu
        function escapeHtml(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;')
                .replace(/\n/g, '<br>');
        }
    });
</script>
@endsection