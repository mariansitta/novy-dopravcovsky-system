<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->index('bill_file');
            $table->index('docs_file');
            $table->index('number');
        });
    }

    public function down()
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropIndex(['bill_file']);
            $table->dropIndex(['docs_file']);
            $table->dropIndex(['number']);
        });
    }
};