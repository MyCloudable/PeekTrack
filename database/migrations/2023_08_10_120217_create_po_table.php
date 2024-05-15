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
        Schema::create('po', function (Blueprint $table) {
            $table->id();
	    $table->integer('job_id');
	    $table->integer('userId');
	    $table->string('notes');
	    $table->string('non_est_task');
	    $table->float('qty');
	    $table->string('unit_of_measure');
	    $table->string('mark_mill');
	    $table->string('road_name');
	    $table->string('phase_item_complete');
	    $table->string('surface_type');
	    $table->string('po_number');
	    $table->string('mob');
	    $table->string('signature');
	    $table->string('sig_name');
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
        Schema::dropIfExists('po');
    }
};
