@extends('layout.web')

@section('title', ' - Prepravy podľa vozidiel')

@section('body')
    <body data-layout="horizontal" data-topbar="dark">
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="{{ route('index') }}" class="btn btn-warning btn-sm">←&nbsp;&nbsp;Späť</a>
                    <h4 class="card-title mb-0">Prepravy podľa vozidiel</h4>
                    <div style="width: 70px;"></div>
                </div>

                @include('web._partials._alert')

                @if($vehicles->isEmpty())
                    <p class="text-muted">Žiadne vozidlá s priradenými prepravami.</p>
                @else

                {{-- Záložky vozidiel --}}
                <div class="mb-3" id="cargo-vehicles-list">
                    @foreach($vehicles as $vehicle)
                        <button
                            type="button"
                            class="btn btn-outline-primary mr-1 mb-1 orders-cargo-vehicle"
                            data-plate="{{ $vehicle->cargo_vehicle_plate }}"
                        >
                            {{ $vehicle->cargo_vehicle_plate }}
                            @if($vehicle->cargo_vehicle_description)
                                <small class="ml-1">{{ $vehicle->cargo_vehicle_description }}</small>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Navigácia týždňov --}}
                <div class="d-flex align-items-center mb-3" id="cargo-weeks-list"
                    data-current-week="{{ $currentWeek }}"
                    data-current-year="{{ $currentYear }}">
                    <button type="button" class="btn btn-light btn-sm mr-2" id="cargo-week-prev"><i class="fas fa-chevron-left"></i></button>

                    <div id="cargo-weeks-buttons" class="d-flex flex-wrap">
                        @foreach($weeks as $w)
                            <button
                                type="button"
                                class="btn btn-sm mr-1 mb-1 cargo-week-btn {{ ($w['week'] == $currentWeek && $w['year'] == $currentYear) ? 'btn-primary' : 'btn-outline-secondary' }}"
                                data-week="{{ $w['week'] }}"
                                data-year="{{ $w['year'] }}"
                                data-week-code="{{ $w['week_code'] }}"
                                data-date-from="{{ $w['date_from'] }}"
                                data-date-to="{{ $w['date_to'] }}"
                            >
                                <span class="font-weight-bold">{{ $w['week'] }}/{{ $w['year'] }}</span>
                                <br>
                                <small>{{ $w['start'] }}&nbsp;–&nbsp;{{ $w['end'] }}</small>
                            </button>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-light btn-sm ml-2" id="cargo-week-next"><i class="fas fa-chevron-right"></i></button>
                </div>

                {{-- Tabuľka prepráv --}}
                <div class="table-responsive">
                    <table id="cargo-datatable" class="table table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%; font-size: 11px;">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Súhrn --}}
                <div id="cargo-summary">
                    <div class="alert alert-info mb-2 p-2">
                        <table>
                            <tbody>
                                <tr>
                                    <td class="font-weight-light pr-2">Počet objednávok: </td>
                                    <td><span class="pocet-objednavok font-weight-bold">0</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-light pr-2">Suma dopravca: </td>
                                    <td><span class="suma-dopravca font-weight-bold">0</span> &euro;</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-light pr-2">Celkom km: </td>
                                    <td><span class="rozdiel-km font-weight-bold">0</span> km</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-light pr-2">Cena za km: </td>
                                    <td><span class="cena-za-km font-weight-bold">0</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function () {

    var cargoTable = null;
    var activeVehicle = null;
    var activeWeekCode = null;

    // === TÝŽDNE ===
    var weeksRoot = $('#cargo-weeks-list');
    var weeksBtns = $('#cargo-weeks-buttons');
    var currentWeek = parseInt(weeksRoot.data('current-week'), 10);
    var currentYear = parseInt(weeksRoot.data('current-year'), 10);

    function loadWeeks(week, year) {
        $.get('{{ route("cargo.weeks.data") }}', { week: week, year: year })
            .done(function (res) {
                renderWeeks(res.weeks, res.active_week, res.active_year);
                currentWeek = res.active_week;
                currentYear = res.active_year;
            });
    }

    function renderWeeks(weeks, activeWeek, activeYear) {
        weeksBtns.empty();
        weeks.forEach(function (w) {
            var isActive = (w.week === activeWeek && w.year === activeYear);
            var btn = $('<button>')
                .attr('type', 'button')
                .addClass('btn btn-sm mr-1 mb-1 cargo-week-btn')
                .toggleClass('btn-primary', isActive)
                .toggleClass('btn-outline-secondary', !isActive)
                .attr('data-week', w.week)
                .attr('data-year', w.year)
                .attr('data-week-code', w.week_code)
                .attr('data-date-from', w.date_from)
                .attr('data-date-to', w.date_to)
                .append($('<span>').addClass('font-weight-bold').text(w.week + '/' + w.year))
                .append($('<br>'))
                .append($('<small>').html(w.start + '&nbsp;–&nbsp;' + w.end));
            weeksBtns.append(btn);
        });
        // ak bol aktívny týždeň, nastav activeWeekCode
        var $active = weeksBtns.find('.cargo-week-btn.btn-primary').first();
        if ($active.length) {
            activeWeekCode = $active.data('week-code');
            loadData();
        }
    }

    $('#cargo-week-prev').on('click', function () {
        currentWeek -= 1;
        if (currentWeek < 1) { currentYear -= 1; currentWeek = 52; }
        loadWeeks(currentWeek, currentYear);
    });

    $('#cargo-week-next').on('click', function () {
        currentWeek += 1;
        if (currentWeek > 52) { currentYear += 1; currentWeek = 1; }
        loadWeeks(currentWeek, currentYear);
    });

    // Inicializácia DataTable
    function initTable() {
        if (cargoTable) {
            cargoTable.destroy();
            $('#cargo-datatable thead').empty();
        }

        cargoTable = $('#cargo-datatable').DataTable({
            language: {
                search: "Hľadať:",
                emptyTable: "Žiadne prepravy pre vybraté vozidlo a týždeň",
                lengthMenu: "",
                info: "",
                infoEmpty: "",
                paginate: { first: "", last: "", next: "", previous: "" }
            },
            paging: false,
            ordering: false,
            responsive: false,
            columns: [
                { title: 'Č. obj.',       data: 'order_number' },
                { title: 'Dát. naloženia', data: null, render: function(d, type) {
                    return type === 'sort' ? d.deadlineSort : d.deadlineDate;
                }},
                { title: 'Štát Nal.',     data: 'loadingState' },
                { title: 'PSČ Nal.',      data: 'loadingPostalCode' },
                { title: 'Naloženie',     data: null, render: function(d) {
                    if (!d.loadings || d.loadings.length === 0) return '';
                    return d.loadings.map(function(l) {
                        return (l.country ? l.country + ': ' : '') + (l.postal_code || '') + ' ' + (l.city || '');
                    }).join('<br>');
                }},
                { title: 'Štát Vyl.',     data: 'unloadingState' },
                { title: 'PSČ Vyl.',      data: 'unloadingPostalCode' },
                { title: 'Vyloženie',     data: null, render: function(d) {
                    if (!d.unloadings || d.unloadings.length === 0) return '';
                    return d.unloadings.map(function(u) {
                        return (u.country ? u.country + ': ' : '') + (u.postal_code || '') + ' ' + (u.city || '');
                    }).join('<br>');
                }},
                { title: 'Dát. vyloženia', data: null, render: function(d, type) {
                    return type === 'sort' ? d.unloadingDeadlineSort : d.unloadingDeadlineDate;
                }},
                { title: 'LDM',           data: 'ldm' },
                { title: 'Váha',          data: 'weight' },
                { title: 'Vodič €',       data: null, render: function(d) {
                    return d.formatted_driver_price + ' €';
                }},
                //{ title: 'Vzdialenosť',   data: 'distance' },
                //{ title: 'Cena/km',       data: 'price_per_km' },
                { title: 'PIN',           data: 'pdf_pin' },
                { title: 'FA',            data: null, render: function(d) {
                    if (!d.invoice_type || d.invoice_type === 'electronic') return '';
                    return '<span class="badge text-dark" style="background-color:#ffc107; padding:4px;" title="Faktúra poštou">Poštou</span>';
                }},
            ],
            ajax: {
                url: '{{ route("cargo.ajax.datatable") }}',
                type: 'GET',
                dataSrc: function(json) {
                    if (json.summary) {
                        $('#cargo-summary .pocet-objednavok').text(json.summary.count);
                        $('#cargo-summary .suma-dopravca').text(Number(json.summary.total_driver_price).toLocaleString('sk-SK'));
                        $('#cargo-summary .rozdiel-km').text(Number(json.summary.diff_km).toLocaleString('sk-SK'));
                        $('#cargo-summary .cena-za-km').text(Number(json.summary.price_per_km).toLocaleString('sk-SK'));
                    }
                    return json.data;
                },
                data: function(d) {
                    d.vehicle_plate = activeVehicle;
                    d.cargo_week    = activeWeekCode;
                }
            }
        });
    }

    function loadData() {
        if (!activeVehicle || !activeWeekCode) return;
        if (cargoTable) {
            cargoTable.ajax.reload(null, false);
        } else {
            initTable();
        }
    }

    // Klik na vozidlo
    $(document).on('click', '.orders-cargo-vehicle', function() {
        $('.orders-cargo-vehicle').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        activeVehicle = $(this).data('plate');
        loadData();
    });

    // Klik na týždeň
    $(document).on('click', '.cargo-week-btn', function() {
        $('.cargo-week-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
        activeWeekCode = $(this).data('week-code');
        loadData();
    });

    // Predvolene vybrať aktuálny týždeň
    var $activeWeek = $('.cargo-week-btn.btn-primary').first();
    if ($activeWeek.length) {
        activeWeekCode = $activeWeek.data('week-code');
    }

    // Predvolene vybrať prvé vozidlo
    var $firstVehicle = $('.orders-cargo-vehicle').first();
    if ($firstVehicle.length) {
        $firstVehicle.trigger('click');
    }

    // Inicializovať tabuľku
    initTable();
});
</script>
@endsection
