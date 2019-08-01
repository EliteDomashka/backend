<?php

use Illuminate\Database\Seeder;

class LessonTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	    \App\Lesson::insert([
		    ['title' => "Алгебра"],
		    ['title' => "Геометрія"],
		    ['title' => "Укр. Мова"],
		    ['title' => "Укр. Літ"],
		    ['title' => "Англ. Мова"],
		    ['title' => "Фіз-ра"],
		    ['title' => "Зар. Літ"],
		    ['title' => "Труд. навч"],
		    ['title' => "Інформатика"],
		    ['title' => "Географія"],
		    ['title' => "Історія"],
		    ['title' => "Фізика"]
	    ]);
    }
}
