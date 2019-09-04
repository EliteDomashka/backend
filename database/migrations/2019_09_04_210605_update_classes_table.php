<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateClassesTable extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('classes', function (Blueprint $table) {
            $table->bigInteger('notify_chat_id')->nullable();
            $table->smallInteger('notifyTime')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        //
    }
}
