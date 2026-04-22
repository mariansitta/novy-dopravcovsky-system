<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCargoFieldsToTransportsTable extends Migration
{
    public function up()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->string('cargo_vehicle_plate', 64)->nullable()->after('visibility');
            $table->string('cargo_vehicle_description', 255)->nullable()->after('cargo_vehicle_plate');
            $table->unsignedInteger('cargo_week')->nullable()->after('cargo_vehicle_description'); // YYYYWW
            $table->string('pdf_pin', 32)->nullable()->after('cargo_week');
            $table->text('loadings_json')->nullable()->after('pdf_pin');
            $table->text('unloadings_json')->nullable()->after('loadings_json');
            $table->unsignedInteger('distance')->nullable()->after('unloadings_json');
            $table->decimal('price_per_km', 8, 4)->nullable()->after('distance');
        });
    }

    public function down()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn([
                'cargo_vehicle_plate',
                'cargo_vehicle_description',
                'cargo_week',
                'pdf_pin',
                'loadings_json',
                'unloadings_json',
                'distance',
                'price_per_km',
            ]);
        });
    }
}
