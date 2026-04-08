<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->index('fileable_id');
            $table->index('fileable_type');
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['fileable_id']);
            $table->dropIndex(['fileable_type']);
            $table->dropIndex(['type']);
        });
    }
};