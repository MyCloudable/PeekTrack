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
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('event_type'); // e.g. job_number_changed
    $table->string('link')->nullable(); // job card link
    $table->string('old_value')->nullable();
    $table->string('new_value')->nullable();
    $table->unsignedBigInteger('user_id');
    $table->string('ip_address')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
