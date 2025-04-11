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
        Schema::create('overflow_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');

            $table->foreignId('crew_type_id')->nullable()->constrained('crew_types')->onDelete('set null');
            
            // $table->unsignedBigInteger('branch_id')->nullable(); // Store branch.department as plain integer

            // $table->foreignId('branch_id')->nullable()->constrained('branch')->onDelete('set null');
            // $table->foreignId('branch_id')->nullable()->constrained('branch', 'id')->onDelete('set null');

            $table->unsignedBigInteger('branch_id')->nullable();




            $table->text('notes')->nullable();

            $table->boolean('traffic_shift')->default(false);
            $table->date('timeout_date');
            $table->foreignId('superintendent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('completion_date')->nullable();
            $table->foreignId('complete_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
			$table->boolean('approved')->default(false);
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
        Schema::dropIfExists('overflow_items');
    }
};
