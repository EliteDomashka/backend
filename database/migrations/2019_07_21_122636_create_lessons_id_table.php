<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLessonsIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lessons_id', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 25);
            $table->boolean('verified')->default(true); //TODO: set false
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::table('lessons_id')->insert([
	        ['title' => "Алгебра"],
	        ['title' => "Геометрія"],
	        ['title' => "Укр. Мова"],
	        ['title' => "Укр. Літ"],
	        ['title' => "Англ. Мова"],
	        ['title' => "Фіз-ра"],
	        ['title' => "Зар. Літ"],
	        ['title' => "Труд. нвач"],
	        ['title' => "Інформатика"],
	        ['title' => "Географія"],
	        ['title' => "Історія"],
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lessons_id');
    }
}
