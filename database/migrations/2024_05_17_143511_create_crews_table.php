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
        Schema::create('crews', function (Blueprint $table) {
            $table->id();
			$table->integer('crew_type_id');
			$table->integer('superintendentId');
			$table->longText('crew_members');
			$table->timestamp('last_verified_date')->nullable();
			$table->integer('created_by');
			$table->integer('modified_by');
            $table->boolean('is_ready_for_verification')->default(0);
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
        Schema::dropIfExists('crews');
    }
};
