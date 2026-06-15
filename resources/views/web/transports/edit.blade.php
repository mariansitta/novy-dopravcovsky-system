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
                    "ajax" : {
                        "url": "{{ route('transports.ajax') }}",
                        "cache": false
                    },
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
                                // Ikonka pre nahraný doklad (klik = otvoriť). Ak doklad nie je, nič.
                                function docCell(path, icon, title) {
                                    if (path === null) {
                                        return "";
                                    }
                                    let url = "{{ asset('/:path') }}".replace(":path", path);
                                    return "<a class=\"btn btn-light waves-effect waves-light action-button\" title=\"" + title + "\" href=\"" + url + "\" target=\"_blank\">\n" +
                                        "    <i class=\"fas " + icon + " action-icon\"></i>\n" +
                                        "</a>\n";
                                }

                                let bill_html = docCell(data.bill, "fa-file-alt", "{{ trans('texts.Received invoice') }}");
                                let document_html = docCell(data.docs, "fa-car", "{{ trans('texts.Transport documents') }}");

                                let upload_route = "{{ route('transports.edit', ':id') }}".replace(':id', data.trans_id);
                                let upload = " <a class=\"btn btn-warning waves-effect waves-light action-button\" title=\"{{ trans('texts.Upload documents') }}\" href=\"" + upload_route + "\">\n" +
                                    "                            <i class=\"fas fa-folder-open action-icon\"></i>\n" +
                                    "                        </a>\n";

                                let result = " <td>";
                                result += bill_html;
                                result += document_html;
                                // Tlačidlo na nahranie ostáva, kým chýba aspoň jeden doklad.
                                result += (data.bill !== null && data.docs !== null) ? "</td>" : upload + "</td>";
                                return result;
                            }
                        },
                    ],
                });

                // --- AI kontrola dokladov (per-slot, hneď pri výbere súboru) ---
                const $form = $('#documents-form');

                // Bez data-check-url (dopravca mimo whitelistu) sa AI kontrola
                // vôbec neaktivuje a formulár sa odošle natívne.
                if ($form.length && $form.data('check-url')) {
                    const checkUrl = $form.data('check-url');
                    const $submitBtn = $form.find('button[type=submit]').last();

                    // Stav každého slotu: 'idle' | 'checking' | 'ok' | 'warn'
                    const slotStatus = { bill: 'idle', docs: 'idle' };

                    // Počas prebiehajúcej kontroly je Uložiť zablokované.
                    // Upozornenia sú len informatívne – ukladanie neblokujú.
                    function refreshActions() {
                        const anyChecking = slotStatus.bill === 'checking' || slotStatus.docs === 'checking';
                        $submitBtn.prop('disabled', anyChecking);
                    }

                    // Spustí AI kontrolu pre jeden slot
                    function checkSlot(slot, file) {
                        slotStatus[slot] = 'checking';
                        $('.ai-check-box[data-slot="' + slot + '"]').hide();
                        $('.ai-check-ok[data-slot="' + slot + '"]').hide();
                        $('.ai-check-loading[data-slot="' + slot + '"]').show();
                        refreshActions();

                        const fd = new FormData();
                        fd.append('_token', '{{ csrf_token() }}');
                        fd.append(slot, file);

                        $.ajax({
                            url: checkUrl,
                            method: 'POST',
                            data: fd,
                            processData: false,
                            contentType: false,
                            success: function (res) {
                                $('.ai-check-loading[data-slot="' + slot + '"]').hide();

                                const result = (res.results && res.results[slot]) ? res.results[slot] : null;
                                const checked = result ? !!result.checked : false;

                                // Zobrazujeme len reálne problémy (severity 'warning').
                                const warnings = ((result ? result.warnings : res.warnings) || []).filter(function (w) {
                                    return w.slot === slot && w.severity === 'warning';
                                });
                                const hasWarning = warnings.length > 0;

                                const $box = $('.ai-check-box[data-slot="' + slot + '"]');
                                const $ok = $('.ai-check-ok[data-slot="' + slot + '"]');
                                const $list = $box.find('.ai-check-list').empty();

                                if (hasWarning) {
                                    // Žltý box – sú reálne problémy.
                                    // Riadky oddelené '\n' zobrazíme cez <br> (bezpečne, text per riadok).
                                    warnings.forEach(function (w) {
                                        const $li = $('<li>');
                                        String(w.message).split('\n').forEach(function (line, i) {
                                            if (i > 0) { $li.append($('<br>')); }
                                            $li.append(document.createTextNode(line));
                                        });
                                        $list.append($li);
                                    });
                                    $ok.hide();
                                    $box.show();
                                } else if (checked) {
                                    // Zelený box – kontrola prebehla a je všetko v poriadku
                                    $box.hide();
                                    $ok.show();
                                } else {
                                    // Kontrola sa nevykonala (skip) – nezobrazuj nič
                                    $box.hide();
                                    $ok.hide();
                                }

                                slotStatus[slot] = hasWarning ? 'warn' : 'ok';
                                refreshActions();
                            },
                            error: function () {
                                // Pri zlyhaní kontroly nič neblokujeme (fail-safe)
                                $('.ai-check-loading[data-slot="' + slot + '"]').hide();
                                $('.ai-check-box[data-slot="' + slot + '"]').hide();
                                $('.ai-check-ok[data-slot="' + slot + '"]').hide();
                                slotStatus[slot] = 'ok';
                                refreshActions();
                            }
                        });
                    }

                    // Pri výbere/zmene súboru spusti kontrolu daného slotu
                    $form.find('input[type=file]').on('change', function () {
                        const slot = this.name; // 'bill' alebo 'docs'
                        if (this.files && this.files.length > 0) {
                            checkSlot(slot, this.files[0]);
                        } else {
                            slotStatus[slot] = 'idle';
                            $('.ai-check-loading[data-slot="' + slot + '"]').hide();
                            $('.ai-check-box[data-slot="' + slot + '"]').hide();
                            $('.ai-check-ok[data-slot="' + slot + '"]').hide();
                            refreshActions();
                        }
                    });

                    $form.on('submit', function (e) {
                        // Počas prebiehajúcej kontroly neodosielaj (tlačidlo je aj tak disabled).
                        // Upozornenia ukladanie neblokujú – sú len informatívne.
                        if (slotStatus.bill === 'checking' || slotStatus.docs === 'checking') {
                            e.preventDefault();
                            return false;
                        }

                        return true;
                    });
                }
            })
        </script>
@endsection
