<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            // Per-slot stav "vrátené na opravu" – nastavuje sa v momente,
            // keď damaro vráti doklad (bill_delete/docs_delete/sync).
            $table->timestamp('bill_returned_at')->nullable()->after('driver_notice');
            $table->text('bill_return_reason')->nullable()->after('bill_returned_at');
            $table->timestamp('docs_returned_at')->nullable()->after('bill_return_reason');
            $table->text('docs_return_reason')->nullable()->after('docs_returned_at');
        });
    }

    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn(['bill_returned_at', 'bill_return_reason', 'docs_returned_at', 'docs_return_reason']);
        });
    }
};
