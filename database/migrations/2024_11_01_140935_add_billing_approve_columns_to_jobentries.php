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
        Schema::table('jobentries', function (Blueprint $table) {
            $table->boolean('billing_approval')->nullable();
            $table->integer('billing_approval_by')->nullable();
            $table->timestamp('billing_approval_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobentries', function (Blueprint $table) {
            //
        });
    }
};
