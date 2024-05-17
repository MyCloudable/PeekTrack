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
        Schema::create('Timesheets', function (Blueprint $table) {
        $table->id();
		$table->timestamp('timeentry');
	    $table->integer('timeentry_detail');
	    $table->integer('userid');
	    $table->string('job_number');
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
        Schema::dropIfExists('Timesheets');
    }
};
