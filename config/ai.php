<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI kontrola nahrávaných dokladov (faktúra / dopravné dokumenty)
    |--------------------------------------------------------------------------
    |
    | Konfigurácia pre DocumentUploadChecker, ktorý cez OpenAI overuje
    | nahrávané doklady. Ak nie je nastavený OPENAI_API_KEY, kontrola sa
    | "skipne" a upload prebehne ako doteraz (AI nikdy nezablokuje legitímny
    | upload). Prítomnosť kľúča je teda zároveň vypínačom funkcie.
    |
    | Referenčné údaje fakturačnej firmy (názov, IČO, DIČ, IČ DPH, krajina)
    | NIE sú v .env – berú sa z tabuľky `companies`, ktorú sem synchronizuje
    | Damaro, cez transport->company.
    |
    */

    'doc_check' => [

        // Bez API kľúča sa kontrola preskočí (kľúč = vypínač funkcie).
        'api_key' => env('OPENAI_API_KEY'),

        // Primárny (lacnejší/rýchlejší) a sekundárny (silnejší) model.
        'primary_model'   => env('OPENAI_DOCUMENT_ANALYSIS_PRIMARY_MODEL', 'gpt-5.4-mini'),
        'secondary_model' => env('OPENAI_DOCUMENT_ANALYSIS_SECONDARY_MODEL', 'gpt-5.4'),

        // Ak je confidence z primárneho modelu nižšia, prepne sa na sekundárny.
        'escalation_confidence' => (float) env('OPENAI_DOCUMENT_ANALYSIS_ESCALATION_CONFIDENCE', 0.75),

        // Maximálna veľkosť PDF pre inline analýzu (OpenAI limit ~ 45 MB).
        'max_file_bytes' => 45 * 1024 * 1024,
    ],
];
