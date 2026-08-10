<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Model tieto polia vracia v každej kontrole faktúry (DocumentUploadChecker::schema),
     * doteraz sa však zahadzovali. Damaro bez nich vie povedať len "nezhoda firmy",
     * nie AKÚ firmu a AKÚ sumu faktúra obsahovala.
     */
    public function up(): void
    {
        Schema::table('ai_document_checks', function (Blueprint $table) {
            $table->string('invoice_billed_to')->nullable()->after('invoice_currency');
            $table->string('invoice_billed_to_city')->nullable()->after('invoice_billed_to_ico');
            $table->text('company_evidence')->nullable()->after('invoice_billed_to_country');
            $table->text('amount_evidence')->nullable()->after('company_evidence');
            $table->text('vat_evidence')->nullable()->after('amount_evidence');
        });
    }

    public function down(): void
    {
        Schema::table('ai_document_checks', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_billed_to',
                'invoice_billed_to_city',
                'company_evidence',
                'amount_evidence',
                'vat_evidence',
            ]);
        });
    }
};
