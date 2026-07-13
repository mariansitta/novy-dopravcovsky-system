<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transport_id')->index();
            $table->text('body');
            $table->string('slot', 8)->nullable();   // 'bill' | 'docs' | null
            $table->string('source', 16);            // 'notice' | 'return'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('transport_notices');
    }
};
