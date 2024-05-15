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
        Schema::create('job_documents', function (Blueprint $table) {
            $table->id();
	    $table->integer('job_id');
	    $table->integer('userId');
	    $table->integer('doc_type_id');
	    $table->string('description');
	    $table->string('file_path');
	    $table->string('notes');
	    $table->integer('inactive');
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
        Schema::dropIfExists('job_documents');
    }
};
