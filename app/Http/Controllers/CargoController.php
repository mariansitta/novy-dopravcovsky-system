<?php

namespace App\Http\Controllers;

use App\Models\Transport;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CargoController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // Všetky vozidlá (unikátne) pre prepravy tohto dopravcu kde je pridelené vozidlo
        $vehicles = Transport::where('user_id', $userId)
            ->whereNotNull('cargo_vehicle_plate')
            ->select('cargo_vehicle_plate', 'cargo_vehicle_description')
            ->distinct()
            ->orderBy('cargo_vehicle_plate')
            ->get();

        // Generovanie týždňov: minulý týždeň + 8 týždňov dopredu
        $current  = Carbon::now();
        $weeks    = [];
        $cursor   = $current->copy()->subWeek()->startOfWeek(Carbon::MONDAY);
        $end      = $current->copy()->addWeeks(8);

        while ($cursor <= $end) {
            $week  = $cursor->isoWeek();
            $year  = $cursor->isoWeekYear();
            $weeks[] = [
                'week'      => $week,
                'year'      => $year,
                'week_code' => (int) ($year . str_pad($week, 2, '0', STR_PAD_LEFT)),
                'start'     => $cursor->copy()->startOfWeek(Carbon::MONDAY)->format('d.m.'),
                'end'       => $cursor->copy()->endOfWeek(Carbon::SUNDAY)->format('d.m.'),
                'date_from' => $cursor->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
                'date_to'   => $cursor->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d'),
            ];
            $cursor->addWeek();
        }

        $currentWeek = (int) Carbon::now()->isoWeek();
        $currentYear = (int) Carbon::now()->isoWeekYear();

        return view('web.cargo.index', compact('vehicles', 'weeks', 'currentWeek', 'currentYear'));
    }

    public function weeks_data(Request $request)
    {
        $centerWeek = (int) $request->input('week', Carbon::now()->isoWeek());
        $centerYear = (int) $request->input('year', Carbon::now()->isoWeekYear());

        $center = Carbon::now()->setISODate($centerYear, $centerWeek)->startOfWeek(Carbon::MONDAY);

        $weeks = [];
        $cursor = $center->copy()->subWeeks(1);
        $end    = $center->copy()->addWeeks(8);

        while ($cursor <= $end) {
            $week  = $cursor->isoWeek();
            $year  = $cursor->isoWeekYear();
            $weeks[] = [
                'week'      => $week,
                'year'      => $year,
                'week_code' => (int) ($year . str_pad($week, 2, '0', STR_PAD_LEFT)),
                'start'     => $cursor->copy()->startOfWeek(Carbon::MONDAY)->format('d.m.'),
                'end'       => $cursor->copy()->endOfWeek(Carbon::SUNDAY)->format('d.m.'),
                'date_from' => $cursor->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
                'date_to'   => $cursor->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d'),
            ];
            $cursor->addWeek();
        }

        return response()->json([
            'weeks'       => $weeks,
            'active_week' => $centerWeek,
            'active_year' => $centerYear,
        ]);
    }

    public function ajax_datatable(Request $request)
    {
        $userId       = auth()->id();
        $vehiclePlate = $request->input('vehicle_plate');
        $cargoWeek    = $request->input('cargo_week'); // YYYYWW integer

        if (!$vehiclePlate || !$cargoWeek) {
            return response()->json(['data' => []]);
        }

        // Vypočítaj dátumový rozsah týždňa z YYYYWW kódu
        $weekYear   = (int) substr((string) $cargoWeek, 0, 4);
        $weekNum    = (int) substr((string) $cargoWeek, 4, 2);
        $weekStart  = Carbon::now()->setISODate($weekYear, $weekNum)->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $weekEnd    = Carbon::now()->setISODate($weekYear, $weekNum)->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        $transports = Transport::withTrashed()
            ->where('user_id', $userId)
            ->where('cargo_vehicle_plate', $vehiclePlate)
            ->where(function ($q) use ($cargoWeek, $weekStart, $weekEnd) {
                // 1) explicitne nastavený cargo_week → podľa YYYYWW kódu
                $q->where('cargo_week', (int) $cargoWeek);
                // 2) bez cargo_week → podľa loading_date v rozsahu týždňa
                $q->orWhere(function ($x) use ($weekStart, $weekEnd) {
                    $x->whereNull('cargo_week')
                      ->whereBetween('loading_date', [$weekStart, $weekEnd]);
                });
            })
            ->get();

        $data = $transports->map(function ($t) {
            $loadings   = $t->loadings_json  ?? [];
            $unloadings = $t->unloadings_json ?? [];

            $firstLoading   = $loadings[0]  ?? null;
            $lastUnloading  = $unloadings[count($unloadings) - 1] ?? null;

            // Formátovaný dátum naloženia
            $deadlineDate = '';
            $deadlineSort = '';
            if ($firstLoading && !empty($firstLoading['deadline'])) {
                $dt = Carbon::parse($firstLoading['deadline']);
                $deadlineDate = $dt->format('d.m.Y');
                $deadlineSort = $dt->format('Ymd');
            }

            // Formátovaný dátum vyloženia
            $unloadingDeadlineDate = '';
            $unloadingDeadlineSort = '';
            if ($lastUnloading && !empty($lastUnloading['deadline'])) {
                $dt = Carbon::parse($lastUnloading['deadline']);
                $unloadingDeadlineDate = $dt->format('d.m.Y');
                $unloadingDeadlineSort = $dt->format('Ymd');
            }

            return [
                'id'                    => $t->id,
                'order_number'          => $t->number,
                'deadlineDate'          => $deadlineDate,
                'deadlineSort'          => $deadlineSort,
                'unloadingDeadlineDate' => $unloadingDeadlineDate,
                'unloadingDeadlineSort' => $unloadingDeadlineSort,
                'loadingState'          => $firstLoading['country'] ?? '',
                'loadingPostalCode'     => $firstLoading['postal_code'] ?? '',
                'loadingCity'           => $firstLoading['city'] ?? '',
                'unloadingState'        => $lastUnloading['country'] ?? '',
                'unloadingPostalCode'   => $lastUnloading['postal_code'] ?? '',
                'unloadingCity'         => $lastUnloading['city'] ?? '',
                'loadings'              => $loadings,
                'unloadings'            => $unloadings,
                'ldm'                   => $t->ldm,
                'weight'                => $t->weight,
                'driver_price'          => $t->driver_price,
                'formatted_driver_price'=> $t->driver_price !== null
                                            ? number_format((float) $t->driver_price, 2, ',', ' ')
                                            : '-',
                'distance'              => $t->distance ? $t->distance . ' km' : '',
                'price_per_km'          => $t->price_per_km ? number_format((float) $t->price_per_km, 2, ',', ' ') . ' €' : '',
                'pdf_pin'               => $t->pdf_pin,
                'invoice_type'          => $t->invoice_type,
                'driver_plate_number'   => $t->driver_plate_number,
                'bill_sent'             => $t->bill_sent,
                'docs_sent'             => $t->docs_sent,
                'edit_url'              => !($t->bill_sent && $t->docs_sent) && !$t->trashed()
                                            ? route('transports.edit', $t->id)
                                            : null,
            ];
        });

        $totalDriverPrice = round((float) $transports->sum('driver_price'), 2);
        $totalDistance    = round((float) $transports->sum('distance'), 2);
        $pricePerKm       = $totalDistance > 0
            ? round($totalDriverPrice / $totalDistance, 4)
            : 0;

        return response()->json([
            'data' => $data,
            'summary' => [
                'count'             => $transports->count(),
                'total_driver_price' => $totalDriverPrice,
                'diff_km'           => $totalDistance,
                'price_per_km'      => $pricePerKm,
            ],
        ]);
    }
}
