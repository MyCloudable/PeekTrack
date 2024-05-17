<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('travel_times', function (Blueprint $table) {
            $table->id();
			$table->integer('crew');
			$table->string('type');
			$table->timestamp('depart');
			$table->timestamp('arrive');
			$table->integer('created_by');
			$table->integer('modified_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('travel_times');
    }
};
