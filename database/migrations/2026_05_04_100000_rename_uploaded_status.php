<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('statuses')->where('slug', 'uploaded')->update([
            'name_sk' => 'Čaká na spracovanie',
            'name_en' => 'Pending',
        ]);
    }

    public function down(): void
    {
        DB::table('statuses')->where('slug', 'uploaded')->update([
            'name_sk' => 'Nahraté',
            'name_en' => 'Uploaded',
        ]);
    }
};
