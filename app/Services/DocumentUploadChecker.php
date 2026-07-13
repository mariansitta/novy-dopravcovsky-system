<?php

namespace App\Services;

use App\Models\Transport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AI kontrola dokladov nahrávaných dopravcom pred uložením.
 *
 * Mirror riešenia z damaro-system (TransportDocumentAiAnalyzer): OpenAI Files
 * API + Responses API so structured json_schema outputom. Na rozdiel od neho
 * beží nad ešte neuloženým UploadedFile a vracia normalizovaný výsledok
 * s poradnými varovaniami (issues[]). AI nikdy nezablokuje upload – pri
 * akejkoľvek chybe / chýbajúcom kľúči / vypnutej feature vráti skipnutý
 * výsledok s ok = true.
 */
class DocumentUploadChecker
{
    private Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?: new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 180,
        ]);
    }

    /**
     * @param  string  $slot  'bill' (faktúra) alebo 'docs' (dopravné dokumenty)
     * @return array{ok: bool, skipped: bool, warnings: array<int, array{slot: string, severity: string, message: string}>, raw?: array, meta?: array}
     */
    public function check(UploadedFile $file, string $slot, Transport $transport): array
    {
        $config = config('ai.doc_check');

        if (empty($config['api_key'])) {
            return $this->skipped($slot);
        }

        $path = $file->getRealPath();
        $size = $path ? @filesize($path) : false;

        if (!$path || $size === false || $size <= 0) {
            return $this->skipped($slot);
        }

        if ($size > (int) $config['max_file_bytes']) {
            // Príliš veľký súbor – nedávame falošné varovanie, len preskočíme.
            return $this->skipped($slot);
        }

        // Metadáta analýzy (čas, použitý model, eskalácia) na uloženie/prenos.
        $meta = [
            'model_used' => null,
            'escalated' => false,
            'duration_ms' => 0,
            'original_filename' => $file->getClientOriginalName() ?: null,
        ];

        $startedAt = hrtime(true);
        try {
            $data = $this->analyzeWithFallback($file, $slot, $transport, $config, $meta);
        } catch (\Throwable $e) {
            Log::warning('AI doc check failed, skipping', [
                'transport_id' => $transport->id,
                'slot' => $slot,
                'error' => $e->getMessage(),
            ]);

            return $this->skipped($slot);
        }
        $meta['duration_ms'] = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        $warnings = $this->buildWarnings($data, $slot, $transport);

        return [
            'ok' => collect($warnings)->where('severity', 'warning')->isEmpty(),
            'skipped' => false,
            'warnings' => $warnings,
            'raw' => $data,
            'meta' => $meta,
        ];
    }

    private function skipped(string $slot): array
    {
        return ['ok' => true, 'skipped' => true, 'warnings' => []];
    }

    /**
     * @param  array  $meta  out-param: doplní 'model_used' a 'escalated' podľa toho,
     *                        ktorá vetva (primary/secondary) dala finálny výsledok.
     */
    private function analyzeWithFallback(UploadedFile $file, string $slot, Transport $transport, array $config, array &$meta = []): array
    {
        $openAiFileId = $this->uploadFile($file, $config);

        $primary = $this->analyze($openAiFileId, $slot, $transport, $config['primary_model'], $config);
        $meta['model_used'] = $config['primary_model'];

        $confidence = $primary['confidence'] ?? 0;
        if ($config['primary_model'] !== $config['secondary_model']
            && $confidence < (float) $config['escalation_confidence']) {
            $meta['model_used'] = $config['secondary_model'];
            $meta['escalated'] = true;
            return $this->analyze($openAiFileId, $slot, $transport, $config['secondary_model'], $config);
        }

        return $primary;
    }

    private function analyze(string $openAiFileId, string $slot, Transport $transport, string $model, array $config): array
    {
        try {
            $response = $this->client->post('responses', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'input' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'input_file', 'file_id' => $openAiFileId],
                                ['type' => 'input_text', 'text' => $this->buildPrompt($slot, $transport)],
                            ],
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'document_upload_check',
                            'strict' => true,
                            'schema' => $this->schema(),
                        ],
                    ],
                ],
            ]);
        } catch (RequestException $e) {
            throw new RuntimeException($this->formatRequestException($e), 0, $e);
        }

        $body = json_decode((string) $response->getBody(), true);
        if (!is_array($body)) {
            throw new RuntimeException('OpenAI returned an invalid response body.');
        }

        $data = json_decode($this->extractOutputText($body), true);
        if (!is_array($data)) {
            throw new RuntimeException('OpenAI returned invalid JSON analysis.');
        }

        return $data;
    }

    private function uploadFile(UploadedFile $file, array $config): string
    {
        $response = $this->client->post('files', [
            'headers' => [
                'Authorization' => 'Bearer ' . $config['api_key'],
            ],
            'multipart' => [
                ['name' => 'purpose', 'contents' => 'user_data'],
                [
                    'name' => 'file',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName() ?: 'document.pdf',
                ],
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true);
        $fileId = $body['id'] ?? null;

        if (!$fileId) {
            throw new RuntimeException('OpenAI file upload did not return a file id.');
        }

        return $fileId;
    }

    private function buildPrompt(string $slot, Transport $transport): string
    {
        $expectedType = $slot === 'bill'
            ? 'invoice (faktúra)'
            : 'transport document (CMR, delivery note, loading list – dopravný doklad)';

        $lines = [
            'You are validating a PDF that a freight carrier uploaded to a slot in a portal.',
            'Slot meaning: the carrier intended this file to be a ' . $expectedType . '.',
            'Read the PDF (it may be scanned or photographed). Do not invent values.',
            'Decide whether the document actually matches the intended slot.',
            'Assess readability of the PDF. Set readable = false ONLY when the content genuinely cannot be read'
                . ' (heavily blurred, cut off, blank, or corrupted so key fields are illegible). If you were able to'
                . ' read the document type and the relevant fields (e.g. amounts, company, IČO), then readable = true'
                . ' – minor scan noise, slight skew, watermarks or "photographed" look are NOT reasons to set readable = false.',
            'IMPORTANT: Do NOT report positive findings, confirmations, or "unknown"/uncertain results as issues.'
                . ' The issues[] array must contain ONLY clear, actionable problems the carrier should fix.'
                . ' If something is correct or cannot be determined, leave it out of issues[].',
        ];

        if ($slot === 'bill') {
            $company = $transport->company;
            $expectedVat = $this->expectedVatState($transport);
            $expectedAmount = $transport->driver_price;

            $lines[] = 'This slot is an INVOICE, so additionally validate:';

            if ($company) {
                $lines[] = '- The invoice must be issued TO (recipient/odberateľ) the expected billing company below.';
                $lines[] = '  Expected billing company name: ' . ($company->name ?? '');
                $lines[] = '  Expected billing company IČO: ' . ($company->ico ?? '');
                $lines[] = '  Expected billing company DIČ: ' . ($company->dic ?? '');
                $lines[] = '  Expected billing company IČ DPH / VAT id: ' . ($company->icdph ?? '');
                $lines[] = '  Expected billing company city: ' . ($company->city ?? '');
                $lines[] = '  Expected billing company country: ' . ($company->country ?? '');
                $lines[] = '  CRITICAL matching rule – the IČO (company registration number) is the DECISIVE identifier:';
                $lines[] = '    * If the recipient IČO on the invoice EQUALS the expected IČO above, it is the SAME company -> company_matches_expected = "match",'
                    . ' regardless of how the name or country is written (e.g. "DAMARO SLOVAKIA s.r.o." with country printed as "CESKO"/"Česko"/"CZ" is still a match if the IČO matches).';
                $lines[] = '    * Only if the recipient IČO is DIFFERENT from the expected IČO (or no IČO is present and the IČ DPH/VAT id clearly belongs to a different entity) is it a "mismatch".';
                $lines[] = '    * Do NOT base the match on the company name or the word "SLOVAKIA"/"CZ" in the name. Related group companies share the same name and differ only by IČO / country.';
                $lines[] = '    * Treat country names and codes as equivalent: CESKO = Česko = CZ; SLOVENSKO = Slovensko = SK. A different wording of the same country is NOT a mismatch.';
                $lines[] = '  Always fill invoice_billed_to_ico, invoice_billed_to_city and invoice_billed_to_country with what is actually on the invoice (recipient), so the difference can be shown.';
                $lines[] = '  MANDATORY: read the recipient IČO from the invoice and put the digits into invoice_billed_to_ico. It is almost always printed near the recipient (labelled "IČO"). Never leave invoice_billed_to_ico empty if any IČO is visible for the recipient; ignore spaces when reading it.';
            } else {
                $lines[] = '- The expected billing company is unknown; set company_matches_expected = "unknown".';
            }

            if ($expectedAmount !== null) {
                $lines[] = '- Expected invoice amount (price for the carrier): ' . $expectedAmount
                    . '. The amount must match EXACTLY to the cent (compare against the invoice net total, or the gross total when VAT applies). Any difference, even a few cents, is a mismatch.';
            } else {
                $lines[] = '- Expected amount is unknown; read the amount but set amount_matches_expected = "unknown".';
            }

            if ($expectedVat === 'with_vat') {
                $lines[] = '- VAT rule: carrier and billing company are in the SAME country, so the invoice MUST include VAT (s DPH). If VAT is missing or reverse charge is stated, that is a mismatch.';
            } elseif ($expectedVat === 'without_vat') {
                $lines[] = '- VAT rule: carrier and billing company are in DIFFERENT EU countries, so the invoice MUST be WITHOUT VAT / reverse charge (bez DPH). If VAT is charged, that is a mismatch.';
            } else {
                $lines[] = '- VAT rule cannot be determined; set vat_matches_expected = "unknown".';
            }

            $lines[] = '- The invoice should reference the order/transport number: ' . ($transport->number ?? '') . '.';
            $lines[] = '- This is an invoice, not a transport document; set carrier_name_found = true (it is not evaluated here).';
        } else {
            $lines[] = 'This slot is for TRANSPORT DOCUMENTS (CMR, delivery note, loading list). An invoice in this slot is a mismatch.';
            $lines[] = 'For invoice-only checks (company/amount/vat/order number) set them to "unknown" / found = true and DO NOT raise issues about them.';
            $lines[] = 'Transport documents normally do NOT contain an order/transport number – never report a missing order number here.';

            $carrierName = trim((string) ($transport->user?->name ?? ''));
            if ($carrierName !== '') {
                $lines[] = 'Validate that the carrier company appears in the document (as the freight carrier / dopravca, e.g. in CMR box 16/17 or as a stamp).';
                $lines[] = '  Expected carrier company name: ' . $carrierName;
                $lines[] = '  Set carrier_name_found = true if it is present (allow minor spelling/legal-form differences), false only if it is clearly absent.';
            } else {
                $lines[] = 'The expected carrier name is unknown; set carrier_name_found = true and do not raise an issue about it.';
            }
        }

        $lines[] = 'Return only JSON matching the schema. Write message_sk in Slovak and message_en in English, short and clear.';
        $lines[] = 'Only report issues that are clear problems. Use severity "warning" for likely mistakes the carrier should fix, "info" for minor notes.';

        return implode("\n", $lines);
    }

    private function schema(): array
    {
        $matchEnum = ['type' => 'string', 'enum' => ['match', 'mismatch', 'unknown']];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'detected_document_type',
                'type_matches_slot',
                'type_evidence',
                'invoice_billed_to',
                'invoice_billed_to_ico',
                'invoice_billed_to_city',
                'invoice_billed_to_country',
                'company_matches_expected',
                'company_evidence',
                'order_number_found',
                'order_number_evidence',
                'carrier_name_found',
                'carrier_name_evidence',
                'invoice_amount',
                'invoice_currency',
                'amount_matches_expected',
                'amount_evidence',
                'invoice_has_vat',
                'vat_matches_expected',
                'vat_evidence',
                'readable',
                'readability_note',
                'confidence',
                'issues',
            ],
            'properties' => [
                'detected_document_type' => [
                    'type' => 'string',
                    'enum' => ['invoice', 'cmr', 'delivery_note', 'loading_list', 'transport_document', 'other', 'unreadable'],
                ],
                'type_matches_slot' => ['type' => 'boolean'],
                'type_evidence' => ['type' => 'string'],
                'invoice_billed_to' => ['type' => 'string'],
                'invoice_billed_to_ico' => ['type' => 'string'],
                'invoice_billed_to_city' => ['type' => 'string'],
                'invoice_billed_to_country' => ['type' => 'string'],
                'company_matches_expected' => $matchEnum,
                'company_evidence' => ['type' => 'string'],
                'order_number_found' => ['type' => 'boolean'],
                'order_number_evidence' => ['type' => 'string'],
                'carrier_name_found' => ['type' => 'boolean'],
                'carrier_name_evidence' => ['type' => 'string'],
                'invoice_amount' => ['type' => 'number'],
                'invoice_currency' => ['type' => 'string'],
                'amount_matches_expected' => $matchEnum,
                'amount_evidence' => ['type' => 'string'],
                'invoice_has_vat' => ['type' => 'string', 'enum' => ['yes', 'no', 'unknown']],
                'vat_matches_expected' => $matchEnum,
                'vat_evidence' => ['type' => 'string'],
                'readable' => ['type' => 'boolean'],
                'readability_note' => ['type' => 'string'],
                'confidence' => ['type' => 'number'],
                'issues' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['code', 'severity', 'message_sk', 'message_en'],
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'severity' => ['type' => 'string', 'enum' => ['warning', 'info']],
                            'message_sk' => ['type' => 'string'],
                            'message_en' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Premapuje AI výstup na zoznam varovaní v jazyku používateľa.
     *
     * @return array<int, array{slot: string, severity: string, message: string}>
     */
    private function buildWarnings(array $data, string $slot, Transport $transport): array
    {
        $sk = in_array(strtolower(app()->getLocale()), ['sk', 'cz'], true);
        $warnings = [];

        $add = function (string $severity, string $messageSk, string $messageEn) use (&$warnings, $slot, $sk) {
            $warnings[] = [
                'slot' => $slot,
                'severity' => $severity,
                'message' => $sk ? $messageSk : $messageEn,
            ];
        };

        // 1) Typ dokumentu v správnom slote
        if (($data['type_matches_slot'] ?? true) === false) {
            $expectedSk = $slot === 'bill' ? 'faktúru' : 'dopravné dokumenty';
            $expectedEn = $slot === 'bill' ? 'an invoice' : 'transport documents';
            $add(
                'warning',
                "Do tohto poľa patrí {$expectedSk}, ale nahraný dokument tomu nezodpovedá.",
                "This slot expects {$expectedEn}, but the uploaded document does not match."
            );
        }

        // 2) Čitateľnosť
        // Deterministická poistka proti falošnému readable=false: ak AI z dokumentu
        // reálne vyčítala kľúčové údaje (typ dokumentu, sumu, IČO odberateľa), doklad
        // je preukázateľne čitateľný a hlášku o nečitateľnosti nedávame – aj keby AI
        // omylom vrátila readable=false kvôli kozmetickej kvalite skenu.
        $readableFalse = ($data['readable'] ?? true) === false;
        $readData = $this->readableEvidence($data);
        if ($readableFalse && !$readData) {
            $note = trim((string) ($data['readability_note'] ?? ''));
            $add(
                'warning',
                'Dokument je nečitateľný alebo nekvalitný.' . ($note !== '' ? " ($note)" : ''),
                'The document is unreadable or low quality.' . ($note !== '' ? " ($note)" : '')
            );
        }

        // Firemné kontroly len pre faktúru
        if ($slot === 'bill') {
            // 3) Faktúra na správnu firmu.
            // Nesprávny DPH režim v praxi takmer vždy znamená, že dopravca fakturuje
            // na nesprávnu DAMARO spoločnosť (napr. slovenskú namiesto českej). Preto
            // za signál nesprávnej firmy berieme aj DPH mismatch – a hlášku o firme
            // dávame VŽDY ako prvú (pred DPH).
            $companyMismatch = ($data['company_matches_expected'] ?? 'unknown') === 'mismatch';
            $vatMismatch = ($data['vat_matches_expected'] ?? 'unknown') === 'mismatch';

            // Deterministická poistka: IČO je rozhodujúci identifikátor. Ak IČO na
            // faktúre sedí s očakávaným IČO firmy, je to preukázateľne tá istá firma –
            // aj keby AI omylom vrátilo mismatch (napr. kvôli "SLOVAKIA" v názve /
            // "CESKO" vs "CZ" / "BRNO" vs "BRNO - ŠTÝŘICE").
            $expectedIcoDigits = preg_replace('/\D+/', '', (string) ($transport->company->ico ?? ''));
            $foundIcoDigits = preg_replace('/\D+/', '', (string) ($data['invoice_billed_to_ico'] ?? ''));
            $icoConfirmedMatch = $expectedIcoDigits !== '' && $expectedIcoDigits === $foundIcoDigits;
            if ($companyMismatch && $icoConfirmedMatch) {
                $companyMismatch = false;
            }

            // Hlášku o nesprávnej firme dávame aj pri DPH mismatchi (zlý DPH režim
            // väčšinou znamená fakturáciu na zlú DAMARO spoločnosť). To ale NEPLATÍ,
            // keď IČO na faktúre preukázateľne sedí – vtedy je firma správna a hlásime
            // len samotný DPH problém (bod 5) bez falošnej hlášky o firme.
            $showCompanyWarning = $companyMismatch || ($vatMismatch && !$icoConfirmedMatch);

            if ($showCompanyWarning) {
                $company = $transport->company;

                // Očakávaná firma s rozlišujúcimi údajmi (názvy bývajú rovnaké,
                // líšia sa krajinou / IČO / IČ DPH).
                $expectedName = trim((string) ($company->name ?? ''));
                $expectedParts = array_filter([
                    trim((string) ($company->city ?? '')),
                    strtoupper(trim((string) ($company->country ?? ''))),
                ]);
                $expectedIco = trim((string) ($company->ico ?? ''));
                $expected = $expectedName;
                if ($expectedParts) {
                    $expected .= ' (' . implode(', ', $expectedParts) . ')';
                }
                if ($expectedIco !== '') {
                    $expected .= ", IČO {$expectedIco}";
                }
                $expected = trim($expected);

                // Čo je reálne na faktúre (odberateľ).
                $foundName = trim((string) ($data['invoice_billed_to'] ?? ''));
                $foundIco = trim((string) ($data['invoice_billed_to_ico'] ?? ''));
                $foundParts = array_filter([
                    trim((string) ($data['invoice_billed_to_city'] ?? '')),
                    strtoupper(trim((string) ($data['invoice_billed_to_country'] ?? ''))),
                ]);
                $found = $foundName;
                if ($foundParts) {
                    $found .= ' (' . implode(', ', $foundParts) . ')';
                }
                if ($foundIco !== '') {
                    $found .= ", IČO {$foundIco}";
                }
                $found = trim($found);

                $add(
                    'warning',
                    trim('Faktúra je zrejme vystavená na nesprávnu spoločnosť.'
                        . ($expected !== '' ? " Pre túto prepravu fakturujte na {$expected}." : '')
                        . ($found !== '' ? "\nNájdené na faktúre: {$found}." : '')),
                    trim('The invoice appears to be issued to the wrong company.'
                        . ($expected !== '' ? " For this transport, please bill {$expected}." : '')
                        . ($found !== '' ? "\nFound on the invoice: {$found}." : ''))
                );
            }

            // 4) Suma
            if (($data['amount_matches_expected'] ?? 'unknown') === 'mismatch') {
                $found = $data['invoice_amount'] ?? null;
                $currency = trim((string) ($data['invoice_currency'] ?? ''));
                $foundText = $found !== null ? rtrim(number_format((float) $found, 2, ',', ' ') . ' ' . $currency) : '';
                $expectedText = $transport->driver_price !== null
                    ? number_format((float) $transport->driver_price, 2, ',', ' ') . ' €'
                    : '';
                $add(
                    'warning',
                    trim("Suma na faktúre nezodpovedá očakávanej cene" . ($expectedText !== '' ? " ({$expectedText})" : '') . '.' . ($foundText !== '' ? " Nájdené: {$foundText}." : '')),
                    trim("The invoice amount does not match the expected price" . ($expectedText !== '' ? " ({$expectedText})" : '') . '.' . ($foundText !== '' ? " Found: {$foundText}." : ''))
                );
            }

            // 5) DPH / reverse charge – krátka hláška za hláškou o firme.
            if ($vatMismatch) {
                $expected = $this->expectedVatState($transport);
                if ($expected === 'with_vat') {
                    $add('warning', 'Faktúra má byť s DPH, ale DPH na nej chýba.', 'The invoice should include VAT, but VAT is missing.');
                } elseif ($expected === 'without_vat') {
                    $add('warning', 'Faktúra má byť bez DPH (prenesenie daňovej povinnosti), ale je na nej účtovaná DPH.', 'The invoice should be without VAT (reverse charge), but VAT is charged.');
                }
            }

        }

        // Kontrola názvu firmy dopravcu – len pre prepravné dokumenty.
        // Číslo objednávky sa na prepravných dokladoch NEkontroluje (často tam nie je).
        if ($slot === 'docs') {
            $carrierName = trim((string) ($transport->user?->name ?? ''));
            $readable = ($data['readable'] ?? true) !== false;
            $typeOk = ($data['type_matches_slot'] ?? true) !== false;

            // Hlásime len ak je názov dodaný, doklad je čitateľný a správneho typu,
            // a firma sa preukázateľne nenašla (konzervatívne – pri pochybnosti ticho).
            if ($carrierName !== '' && $readable && $typeOk
                && ($data['carrier_name_found'] ?? true) === false) {
                $add(
                    'warning',
                    "V prepravnom doklade sa nenašiel názov dopravcu ({$carrierName}). Skontrolujte, či ste nahrali správny doklad.",
                    "The carrier name ({$carrierName}) was not found in the transport document. Please check you uploaded the correct document."
                );
            }
        }

        return $warnings;
    }

    /**
     * Dôkaz, že dokument bol reálne čitateľný: AI z neho vyčítala aspoň jeden
     * nepochybný kľúčový údaj. Ak áno, prípadné readable=false je falošné a hlášku
     * o nečitateľnosti nedávame.
     */
    private function readableEvidence(array $data): bool
    {
        // Rozpoznaný typ dokumentu (čokoľvek okrem 'unreadable').
        $type = (string) ($data['detected_document_type'] ?? '');
        if ($type !== '' && $type !== 'unreadable') {
            return true;
        }

        // Vyčítaná suma faktúry.
        if ((float) ($data['invoice_amount'] ?? 0) > 0) {
            return true;
        }

        // Vyčítané IČO odberateľa.
        if (preg_replace('/\D+/', '', (string) ($data['invoice_billed_to_ico'] ?? '')) !== '') {
            return true;
        }

        // Vyčítaný názov odberateľa.
        if (trim((string) ($data['invoice_billed_to'] ?? '')) !== '') {
            return true;
        }

        return false;
    }

    /**
     * Očakávaný DPH stav: rovnaká krajina dopravcu a fakturačnej firmy => s DPH,
     * rôzna => bez DPH (reverse charge). Inak 'unknown'.
     */
    private function expectedVatState(Transport $transport): string
    {
        $companyCountry = $transport->company?->country;
        $carrierCountry = $transport->user?->country;

        if (!$companyCountry || !$carrierCountry) {
            return 'unknown';
        }

        return strtoupper($carrierCountry) === strtoupper($companyCountry) ? 'with_vat' : 'without_vat';
    }

    private function extractOutputText(array $body): string
    {
        if (!empty($body['output_text'])) {
            return $body['output_text'];
        }

        foreach ($body['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    return $content['text'];
                }
            }
        }

        $message = Arr::get($body, 'error.message');
        throw new RuntimeException($message ?: 'OpenAI response did not contain output text.');
    }

    private function formatRequestException(RequestException $e): string
    {
        if ($e->hasResponse()) {
            $body = (string) $e->getResponse()->getBody();
            $apiMessage = Arr::get(json_decode($body, true) ?: [], 'error.message');
            if ($apiMessage) {
                return $apiMessage;
            }
            if ($body !== '') {
                return $body;
            }
        }

        return $e->getMessage();
    }
}
