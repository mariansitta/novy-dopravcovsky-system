<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->date('paid_at')->nullable()->after('status_id');
        });
    }

    public function down()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
