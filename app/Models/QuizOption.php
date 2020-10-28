<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'contents'
    ];

    public function coursePage() {
        return $this->belongsTo(CoursePage::class);
    }
}
