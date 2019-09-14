<?php

namespace Tests\Unit;

use App\Telegram\Helpers\TaskCropper;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskCropperTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testCrop() {
        $cropped = TaskCropper::crop("Висловіть свою точку зору, щодо причин поразки Німеччини та її союзників.".PHP_EOL."+Індивідуальні завдання");

        $this->assertTrue($cropped[0] == "Висловіть свою точку зору");

        $cropped = TaskCropper::crop("прочитати повністю  \"Кайдашева сім'я\", с тексту підручника -цитати виписати. ст 14 виписати  тему, основну думку.");
    
        $this->assertTrue($cropped[0] == "прочитати повністю ");
    }
    public function testLongText(){
        $text = "Розробка веб сайту з засобами HTML. структурою: головна стр містить Заголовок, Інформація про автора, та перелік (посилання). Кожна сторінка з переліку повинна містити фото, опис та посилання повернення назад. Весь сайт повинен бути від форматований з використанням кольорів, шрифтів, картинок, вирівнювань. Всього сторінок: Головна + 5 стр";
        $cropped = TaskCropper::crop($text);

        $this->assertTrue($cropped[0] == "Розробка веб сайту з");
    
        $text = "У чому полягали. причини появи радикальної течії у визвольному русі? Яку роль у радикалізації визвольного руху відіграв Драгоманов?";
        $cropped = TaskCropper::crop($text);

        $this->assertTrue($cropped[0] == "У чому полягали");
    }
}
