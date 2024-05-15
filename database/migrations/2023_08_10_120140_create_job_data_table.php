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
        Schema::create('job_data', function (Blueprint $table) {
            $table->id();
	    $table->integer('job_id');
	    $table->string('type');
	    $table->string('phase');
	    $table->string('description');
	    $table->float('est_qty');
	    $table->string('unit_of_measure');
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
        Schema::dropIfExists('job_data');
    }
};
