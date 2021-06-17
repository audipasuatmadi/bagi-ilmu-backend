<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_index', 'created_at', 'updated_at', 'is_quiz'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function materials()
    {
        return $this->hasMany(Material::class);
    }

    public function quizOptions()
    {
        return $this->hasMany(QuizOption::class);
    }

    public function correctAnswer()
    {
        return $this->belongsTo(QuizOption::class);
    }
}
