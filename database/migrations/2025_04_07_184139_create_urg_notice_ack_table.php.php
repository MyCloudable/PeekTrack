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
    Schema::create('urg_notice_ack', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('notification_id');

        $table->timestamp('acknowledged_at')->nullable();
        $table->timestamps();

        $table->foreign('user_id', 'fk_ack_user')
              ->references('id')->on('users')
              ->onDelete('cascade');

        $table->foreign('notification_id', 'fk_ack_notification')
              ->references('id')->on('urg_notice')
              ->onDelete('cascade');
    });
}



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('urgent_notification_user_acknowledgements');
    }
};
