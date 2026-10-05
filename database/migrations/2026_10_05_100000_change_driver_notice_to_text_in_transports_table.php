<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            // Damaro posiela upozornenia dlhšie ako 255 znakov.
            $table->text('driver_notice')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->string('driver_notice', 255)->nullable()->change();
        });
    }
};
