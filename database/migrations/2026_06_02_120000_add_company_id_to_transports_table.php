<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdToTransportsTable extends Migration
{
    /**
     * Odkaz na fakturačnú firmu (zrkadlená tabuľka companies), ktorý posiela
     * Damaro. Používa sa pri AI kontrole nahrávaných faktúr (firma + DPH logika).
     * Bez tvrdého FK: sync firiem môže meškať za transportmi a chýbajúca firma
     * má len preskočiť kontrolu, nie zlyhať.
     */
    public function up()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('invoice_type')->index();
        });
    }

    public function down()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
}
