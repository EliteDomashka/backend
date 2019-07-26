<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    public $table = "lessons_id";
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = ['title'];

}
