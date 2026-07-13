<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillRequest;
use App\Http\Requests\DocsRequest;
use App\Http\Requests\BillDocumentRequest;
use App\Http\Requests\DocsDocumentRequest;
use App\Http\Requests\DocumentsRequest;
use App\Http\Requests\DeleteDocumentRequest;
use App\Models\AiDocumentCheck;
use App\Models\DriverMessage;
use App\Models\Status;
use App\Models\Transport;
use App\Models\TransportStatus;
use App\Services\DocumentUploadChecker;
use App\Traits\UploadTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransportsController extends Admin\AdminController
{
    use UploadTrait;

    // AI kontrola sa spúšťa len pre tieto sloty. Momentálne len faktúry ('bill').
    // Pre zapnutie kontroly prepravných dokumentov pridaj sem 'docs':
    //   private const AI_CHECK_SLOTS = ['bill', 'docs'];
    private const AI_CHECK_SLOTS = ['bill'];

    /** Sloty, pre ktoré je aktívna AI kontrola (pre backend aj frontend). */
    public static function aiCheckSlots(): array
    {
        return self::AI_CHECK_SLOTS;
    }

    public function index()
    {
        $table = Transport::with([
                'status',
                'transport_status',
                'files' => fn($q) => $q->withTrashed()->orderByDesc('id'),
            ])
            ->where('user_id', auth()->user()->id)
            ->visible()
            ->orderBy('number', 'desc')
            ->get();

        /*
        $table = Transport::with([
                'status',
                'transport_status',
                'files' => fn($q) => $q->whereNull('deleted_at')
            ])
            ->where('user_id', auth()->user()->id)
            ->whereDate('created_at', '>', now()->subMonths(3)->startOfDay())
            ->where(function ($query) {
                $query->whereDate('created_at', '>', now()->subDays(60)->startOfDay())
                    ->orWhere('bill_sent', 0)
                    ->orWhere('docs_sent', 0);
            })
            ->where(function ($query) {
                $query->whereDate('deleted_at', '>', now()->subDays(10)->startOfDay())
                    ->orWhereNull('deleted_at');
            })
            ->withTrashed()
            ->orderBy('number', 'desc')
            ->get();
        */

        // Počet neprečítaných správ od Damaro pre tohto dopravcu
        $unreadCount = DriverMessage::where('driver_id', auth()->user()->driver_id)
            ->where('direction', 'damaro')
            ->whereNull('read_at')
            ->count();

        $hasCargo = $table->whereNotNull('cargo_vehicle_plate')->isNotEmpty();

        return view('web.transports.index', compact('table', 'unreadCount', 'hasCargo'));
    }

    // Vráti históriu správ + označí správy od Damaro ako prečítané
    public function messages_load()
    {
        $driverId = auth()->user()->driver_id;

        // Označ správy od Damaro ako prečítané
        DriverMessage::where('driver_id', $driverId)
            ->where('direction', 'damaro')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Načítaj celú históriu
        $messages = DriverMessage::where('driver_id', $driverId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($msg) => [
                'direction'  => $msg->direction,
                'body'       => $msg->body,
                'created_at' => $msg->created_at->format('d.m.Y H:i'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    // Uloží novú správu od dopravcu
    public function messages_send(Request $request)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $driverId = auth()->user()->driver_id;

        if (!$driverId) {
            return response()->json(['ok' => false, 'error' => 'Driver not assigned.'], 422);
        }

        DriverMessage::create([
            'driver_id'  => $driverId,
            'direction'  => 'driver',
            'body'       => $request->body,
            'read_at'    => null,
            'synced_at'  => null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function index_old(){
        $transport = auth()->user()->transport;

        return view('web.transports.index', compact( 'transport'));
    }

    public function bill_document(BillDocumentRequest $request, $id) {
        $transport = Transport::where('id', $id)->first();

        if (isset($transport->docs) && isset($transport->bill)) {
            return redirect()->route('index');
        }

        $filename = $this->upload_file($request, 'bill', 'transports', $transport, 'bill');
        $transport->bill_file = $filename;
        $transport->bill_sent = 0;
        $transport->status_id = $this->resolveUploadStatusId($transport);

        $transport->due_date = now()->addDays($transport->due_days);
        $transport->save();

        $this->_setFlashMessage($request, 'success', trans('texts.upload-success-alert'));

        return redirect()->route('index');
    }

    public function doc_document(DocsDocumentRequest $request, $id) {
        $transport = Transport::where('id', $id)->first();

        if (isset($transport->docs) && isset($transport->bill)) {
            return redirect()->route('index');
        }

        $filename = $this->upload_file($request, 'docs', 'transports', $transport, 'docs');
        $transport->docs_file = $filename;
        $transport->docs_sent = 0;
        $transport->status_id = $this->resolveUploadStatusId($transport);

        $transport->due_date = now()->addDays($transport->due_days);
        $transport->save();

        $this->_setFlashMessage($request, 'success', trans('texts.upload-success-alert'));

        return redirect()->route('index');
    }

    public function documents(DocumentsRequest $request, $id) {
        $transport = Transport::where('id', $id)->first();

        if (isset($transport->docs) && isset($transport->bill)) {
            return redirect()->route('index');
        }

        $this->upload_file($request, 'bill', 'transports', $transport, 'bill');
        $this->upload_file($request, 'docs', 'transports', $transport, 'docs');
        $transport->status_id = $this->resolveUploadStatusId($transport);

        $transport->due_date = now()->addDays($transport->due_days);
        $transport->save();

        $transport->user->notify_email = $request->email;
        $transport->user->save();

        $this->_setFlashMessage($request, 'success', trans('texts.upload-success-alert'));

        return redirect()->route('index');
    }

    public function edit($id)
    {
        $transport = Transport::findOrFail($id);
        return view('web.transports.edit', compact('transport'));
    }

    public function transport_status_form(Request $request, $id) {
        $transport = Transport::where('id', $id)->first();

        $datetime = null;

        if ($transport && $request->transport_status) {
            $TransportStatus = TransportStatus::create([
                'transport_id' => $transport->id,
                'status' => $request->transport_status,
                'datetime' => $datetime,
            ]);
            $this->_setFlashMessage($request, 'success', trans('texts.transport-status-success-alert'));
        }

        return redirect()->route('index');
    }

    public function transport_status($id)
    {
        $transport = Transport::findOrFail($id);
        return view('web.transports.transport_status', compact('transport'));
    }

    public function bill($id)
    {
        $transport = Transport::findOrFail($id);
        return view('web.transports.bill', compact('transport'));
    }

    public function docs($id)
    {
        $transport = Transport::findOrFail($id);
        return view('web.transports.docs', compact('transport'));
    }

    // Login user with token
    public function enter($token) {
        Auth::logout();

        $user = User::where('token', $token)->first();

        if ($user) {
            Auth::login($user);

            return redirect()->route('index');
        } else {
            return redirect()->route('login');
        }
    }
    
    // AJAX kontrola nahrávaných dokladov cez AI pred uložením.
    // Súbory sa NEukladajú – kontroluje sa priamo dočasný upload z requestu.
    public function check_document(Request $request, $id, DocumentUploadChecker $checker) {
        $request->validate([
            'bill' => 'nullable|file|mimes:pdf',
            'docs' => 'nullable|file|mimes:pdf',
        ]);

        $transport = Transport::with(['user', 'company'])->find($id);

        if (!$transport) {
            return response()->json(['ok' => true, 'warnings' => []]);
        }

        $warnings = [];
        $results = [];

        foreach (self::AI_CHECK_SLOTS as $slot) {
            if (!$request->hasFile($slot)) {
                continue;
            }

            $result = $checker->check($request->file($slot), $slot, $transport);
            $warnings = array_merge($warnings, $result['warnings']);

            // Ulož metriky každej reálne prebehnutej analýzy (pre neskoršiu analýzu
            // a prenos do Damaro). Skipnuté (bez API kľúča / veľký súbor) neukladáme.
            if (!($result['skipped'] ?? false)) {
                $this->storeAiCheckMetrics($transport, $slot, $result);
            }

            // checked = AI kontrola reálne prebehla (nebola preskočená).
            // Frontend podľa toho rozlíši zelené OK (checked, 0 varovaní) od skipnutia.
            $results[$slot] = [
                'checked' => !($result['skipped'] ?? false),
                'warnings' => $result['warnings'],
            ];
        }

        $ok = collect($warnings)->where('severity', 'warning')->isEmpty();

        return response()->json([
            'ok' => $ok,
            'warnings' => $warnings, // spätná kompatibilita
            'results' => $results,
        ]);
    }

    /**
     * Uloží kompaktné metriky jednej AI analýzy dokladu (na neskoršiu analýzu
     * a prenos do Damaro). Nikdy nesmie zhodiť upload – všetko v try/catch.
     */
    private function storeAiCheckMetrics(Transport $transport, string $slot, array $result): void
    {
        try {
            $raw = $result['raw'] ?? [];
            $meta = $result['meta'] ?? [];

            $warningCount = collect($result['warnings'] ?? [])
                ->where('severity', 'warning')
                ->count();

            AiDocumentCheck::create([
                'transport_id'              => $transport->transport_id,
                'slot'                      => $slot,
                'original_filename'         => $meta['original_filename'] ?? null,

                'model_used'                => $meta['model_used'] ?? null,
                'escalated'                 => (bool) ($meta['escalated'] ?? false),
                'confidence'                => isset($raw['confidence']) ? (float) $raw['confidence'] : null,
                'duration_ms'               => (int) ($meta['duration_ms'] ?? 0),

                'detected_document_type'    => $raw['detected_document_type'] ?? null,
                'type_matches_slot'         => $raw['type_matches_slot'] ?? null,
                'company_matches_expected'  => $raw['company_matches_expected'] ?? null,
                'amount_matches_expected'   => $raw['amount_matches_expected'] ?? null,
                'vat_matches_expected'      => $raw['vat_matches_expected'] ?? null,
                'invoice_has_vat'           => $raw['invoice_has_vat'] ?? null,
                'readable'                  => $raw['readable'] ?? null,
                'carrier_name_found'        => $raw['carrier_name_found'] ?? null,

                'invoice_amount'            => isset($raw['invoice_amount']) ? (float) $raw['invoice_amount'] : null,
                'invoice_currency'          => $raw['invoice_currency'] ?? null,
                'invoice_billed_to_ico'     => $raw['invoice_billed_to_ico'] ?? null,
                'invoice_billed_to_country' => $raw['invoice_billed_to_country'] ?? null,

                'warnings_count'            => $warningCount,
                'warning_codes'            => $this->aiWarningCodes($raw, $slot),

                'synced_at'                 => null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI doc check metrics store failed', [
                'transport_id' => $transport->id,
                'slot' => $slot,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deterministické kódy problémov odvodené z AI výstupu (nie z voľných hlášok),
     * aby sa dali v Damaro agregovať. Zodpovedajú kontrolám v buildWarnings().
     *
     * @return array<int, string>
     */
    private function aiWarningCodes(array $raw, string $slot): array
    {
        $codes = [];

        if (($raw['type_matches_slot'] ?? true) === false) {
            $codes[] = 'type_mismatch';
        }
        if (($raw['readable'] ?? true) === false) {
            $codes[] = 'unreadable';
        }
        if ($slot === 'bill') {
            if (($raw['company_matches_expected'] ?? 'unknown') === 'mismatch') {
                $codes[] = 'company_mismatch';
            }
            if (($raw['amount_matches_expected'] ?? 'unknown') === 'mismatch') {
                $codes[] = 'amount_mismatch';
            }
            if (($raw['vat_matches_expected'] ?? 'unknown') === 'mismatch') {
                $codes[] = 'vat_mismatch';
            }
        }
        if ($slot === 'docs' && ($raw['carrier_name_found'] ?? true) === false) {
            $codes[] = 'carrier_name_missing';
        }

        return $codes;
    }

    public function document_delete(DeleteDocumentRequest $request, $id){
        $transport = Transport::findOrFail($id);

        $transport->files()->where('type', $request->column)->delete();

        // Vyčisti príslušný *_file/*_sent stĺpec na transporte.
        if ($request->column === 'bill') {
            $transport->bill_file = null;
            $transport->bill_sent = 0;
        } else {
            $transport->docs_file = null;
            $transport->docs_sent = 0;
        }

        // Status "Čaká na spracovanie" platí len keď sú nahraté OBA doklady.
        // Po vymazaní jedného sa transport vráti do pôvodného stavu (bez statusu).
        $transport->status_id = $this->resolveUploadStatusId($transport);

        $transport->save();

        $this->_setFlashMessage($request, 'success', "Dokument bol úspešne vymazaný.");

        return redirect()->route('index');
    }

    /**
     * Status "Čaká na spracovanie" (uploaded) platí len ak má transport nahraté
     * OBA doklady (faktúru aj prepravné dokumenty). Inak sa vráti na null
     * (pôvodný stav – čaká sa na dodanie oboch dokladov).
     */
    private function resolveUploadStatusId(Transport $transport): ?int
    {
        $hasBill = $transport->files()->where('type', 'bill')->exists();
        $hasDocs = $transport->files()->where('type', 'docs')->exists();

        if ($hasBill && $hasDocs) {
            return Status::where('slug', 'uploaded')->value('id');
        }

        return null;
    }

    public function _table() {
        $locale = in_array(app()->getLocale(), ['SK', 'CZ']) ? 'sk' : 'en';

        $table = DB::table('transports')
            //->whereDate('transports.created_at', '>', now()->addDays(-90)->startOfDay()->format('Y-m-d'))
            ->where(function($query) {
                $query->whereDate('transports.created_at', '>', now()->addDays(-60)->startOfDay()->format('Y-m-d'))
                      ->orWhere('bill_sent', 0)
                      ->orWhere('docs_sent', 0);
            })
            ->select('transports.id AS trans_id',
                'number',
                'loading_date',
                'due_date',
                'loading',
                'unloading',
                'ldm',
                'weight',
//                'timocom_id',
//                'raal_id',
                'driver_notice',
                'bill_file',
                'docs_file',
                'driver_plate_number',
                'driver_price',
                "statuses.name_$locale AS status_name",
                "statuses.slug AS status_slug",
                'transports.status_id AS status_id',
                //DB::raw("(SELECT CONCAT(path, '', filename) AS full_path FROM files WHERE type = 'bill' AND fileable_id = transports.id AND deleted_at IS NULL) AS bill"),
                //DB::raw("(SELECT CONCAT(path, '', filename) AS full_path FROM files WHERE type = 'docs' AND fileable_id = transports.id AND deleted_at IS NULL) AS docs"),
                DB::raw("(SELECT CONCAT(path, '', filename) FROM files WHERE type = 'bill' AND fileable_id = transports.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) AS bill"),
                DB::raw("(SELECT CONCAT(path, '', filename) FROM files WHERE type = 'docs' AND fileable_id = transports.id AND deleted_at IS NULL ORDER BY id DESC LIMIT 1) AS docs"),
                DB::raw("CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END AS is_deleted"),
                DB::raw("(SELECT status FROM transport_statuses WHERE transport_statuses.transport_id = transports.id ORDER BY created_at DESC LIMIT 1) AS transport_status")
                )
            ->leftJoin('statuses', 'transports.status_id', '=', 'statuses.id')
            ->where('user_id', '=', auth()->user()->id)
            //->whereNUll('deleted_at')
            ->where(function ($query) {
                // Include soft deleted records not older than 10 days
                $query->whereDate('deleted_at', '>', now()->subDays(10)->startOfDay()->format('Y-m-d'))
                      ->orWhereNull('deleted_at');
            })
            ->orderBy('transports.number', 'desc')->get();
        return response()->json([
            'data' => $table
        ]);
    }

}
