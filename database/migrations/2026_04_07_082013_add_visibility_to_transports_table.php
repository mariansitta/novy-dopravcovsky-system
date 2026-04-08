<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->string('visibility', 8)->nullable()->after('status_id'); // null | 'hidden' | 'visible'
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropIndex(['visibility']);
            $table->dropColumn('visibility');
        });
    }
};