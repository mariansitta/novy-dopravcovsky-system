<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTransportsTableData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transports', function (Blueprint $table){
            $table->string('loading_date', 255)->nullable();
            $table->string('loading', 255)->nullable();
            $table->string('unloading', 255)->nullable();
            $table->string('ldm', 255)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('raal', 255)->nullable();
            $table->string('driver_plate_number', 255)->nullable();
            $table->decimal('driver_price', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transports', function (Blueprint $table){
            $table->dropColumn('driver_price');
            $table->dropColumn('driver_plate_number');
            $table->dropColumn('raal');
            $table->dropColumn('weight');
            $table->dropColumn('ldm');
            $table->dropColumn('unloading');
            $table->dropColumn('loading');
            $table->dropColumn('loading_date');
        });
    }
}
