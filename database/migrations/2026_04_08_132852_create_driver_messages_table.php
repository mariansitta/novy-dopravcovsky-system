<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');        // = User.driver_id = Driver.id v Damaro
            $table->enum('direction', ['driver', 'damaro']); // kto napísal správu
            $table->text('body');                           // text správy
            $table->timestamp('read_at')->nullable();       // null = neprečítaná
            $table->timestamp('synced_at')->nullable();     // null = ešte neprenesená do druhého systému
            $table->timestamps();

            $table->index('driver_id');
            $table->index('synced_at');
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_messages');
    }
};