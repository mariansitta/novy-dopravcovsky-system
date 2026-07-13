<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_document_checks', function (Blueprint $table) {
            $table->id();

            // Väzba na transport v Damaro (transport_id = Transport.id v Damaro).
            $table->unsignedBigInteger('transport_id')->nullable()->index();
            $table->string('slot', 16);                     // 'bill' | 'docs'
            $table->string('original_filename')->nullable(); // pôvodný názov analyzovaného súboru

            // Výkon / model.
            $table->string('model_used')->nullable();
            $table->boolean('escalated')->default(false);   // eskalované na silnejší model
            $table->decimal('confidence', 4, 3)->nullable(); // 0.000 - 1.000
            $table->unsignedInteger('duration_ms')->default(0);

            // Výsledok analýzy (kompaktné polia z AI výstupu).
            $table->string('detected_document_type')->nullable();
            $table->boolean('type_matches_slot')->nullable();
            $table->string('company_matches_expected', 16)->nullable(); // match|mismatch|unknown
            $table->string('amount_matches_expected', 16)->nullable();
            $table->string('vat_matches_expected', 16)->nullable();
            $table->string('invoice_has_vat', 16)->nullable();          // yes|no|unknown
            $table->boolean('readable')->nullable();
            $table->boolean('carrier_name_found')->nullable();

            // Vyčítané hodnoty (krátke).
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->string('invoice_currency', 8)->nullable();
            $table->string('invoice_billed_to_ico')->nullable();
            $table->string('invoice_billed_to_country')->nullable();

            // Nájdené chyby / varovania.
            $table->unsignedSmallInteger('warnings_count')->default(0);
            $table->json('warning_codes')->nullable();      // zoznam kódov varovaní

            // null = ešte neprenesené do Damaro.
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_document_checks');
    }
};
