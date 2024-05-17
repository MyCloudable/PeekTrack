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
        Schema::create('job_po', function (Blueprint $table) {
            $table->id();
	    $table->uuid('link');
	    $table->string('phase');
	    $table->integer('userID');
	    $table->string('po_number');
	    $table->string('signer_name');
	    $table->string('signature');
	    $table->string('notes');
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
        Schema::dropIfExists('job_po');
    }
};
