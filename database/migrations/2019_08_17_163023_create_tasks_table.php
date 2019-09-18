<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('class_id');
            $table->integer('week');
            $table->integer('num');
            $table->integer('day'); //dayOfWeek
            $table->string('task');
            $table->text('desc');
    
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE tasks ADD attachments bigint[]');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
