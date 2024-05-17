<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateFilesTable extends Migration
{
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
			$table->string('job_number')->nullable();
            $table->string('name')->nullable();
			$table->string('description');
			$table->string('type');
			$table->integer('doctype');
            $table->string('file_path')->nullable();
			$table->uuid('link');
			$table->integer('active');
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('files');
    }
}