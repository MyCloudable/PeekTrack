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
        Schema::create('jobentries', function (Blueprint $table) {
            $table->id();
            $table->uuid('link');
			$table->string('job_number');
			$table->date('workdate');
			$table->integer('submitted');
			$table->date('submitted_on');
			$table->integer('userId');
			$table->string('name');
			$table->timestamps();
			$table->integer('approved');
			$table->string('approvedBy');
			$table->date('approved_date');
			
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobentries');
    }
};
