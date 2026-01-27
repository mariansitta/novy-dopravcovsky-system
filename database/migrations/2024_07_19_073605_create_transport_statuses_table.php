<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransportStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transport_statuses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('transport_id')->unsigned()->index();
            $table->string('status', 64);
            $table->datetime('datetime')->nullable();
            $table->timestamps();

            $table->foreign('transport_id')->references('id')->on('transports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transport_statuses');
    }
}
