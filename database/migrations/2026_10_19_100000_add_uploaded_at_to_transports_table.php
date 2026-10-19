<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            // Skutočný čas nahratia dokladu dopravcom – damaro od neho počíta splatnosť
            // (nie od času, kedy si súbor stiahol).
            $table->timestamp('bill_uploaded_at')->nullable()->after('docs_return_reason');
            $table->timestamp('docs_uploaded_at')->nullable()->after('bill_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn(['bill_uploaded_at', 'docs_uploaded_at']);
        });
    }
};
