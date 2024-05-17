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
        Schema::create('production', function (Blueprint $table) {
            $table->id();
	    $table->uuid('link');
		$table->string('job_number')
	    $table->integer('userId');
	    $table->string('phase');
	    $table->string('description');
	    $table->double('qty');
	    $table->string('unit_of_measure');
	    $table->string('mark_mill');
	    $table->string('road_name');
	    $table->string('phase_item_complete');
	    $table->string('surface_type');
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
        Schema::dropIfExists('production');
    }
};
