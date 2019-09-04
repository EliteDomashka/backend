<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTasksTable extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('tasks', function (Blueprint $table) {
            $table->bigInteger('author_id')->nullable(); //без нула если добавлять - никак
            $table->bigInteger('chat_user_msg_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
