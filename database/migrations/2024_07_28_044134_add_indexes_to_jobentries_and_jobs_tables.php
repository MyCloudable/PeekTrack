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
            $table->index('job_number');
            $table->index('approved');
            $table->index('submitted');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->index('job_number');
            $table->index('status');
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
            $table->dropIndex(['job_number']);
            $table->dropIndex(['approved']);
            $table->dropIndex(['submitted']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['job_number']);
            $table->dropIndex(['status']);
        });
    }
};
