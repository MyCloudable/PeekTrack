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
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('crew_type_id');
            $table->integer('user_id');
            $table->timestamp('clockin_time');
            $table->timestamp('clockout_time')->nullable();

            $table->foreignId('job_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('time_type_id')->nullable();
            
            $table->integer('created_by');
            $table->integer('modified_by');
            $table->string('per_diem')->nullable();
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
        Schema::dropIfExists('timesheets');
    }
};
