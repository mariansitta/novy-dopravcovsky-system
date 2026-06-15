<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Zrkadlo tabuľky `companies` z damaro-system. Sem ju synchronizuje Damaro
     * cez /api/companies/sync (upsert podľa id). Na rozdiel od originálu sa
     * krajina ukladá ako 2-písmenový kód (string), nie cez countries FK –
     * stačí to na DPH logiku (zhoda s users.country).
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('ico')->nullable();
            $table->string('dic')->nullable();
            $table->string('icdph')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('swift')->nullable();
            $table->string('iban')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
