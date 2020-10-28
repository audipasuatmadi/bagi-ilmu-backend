<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_index', 'is_code', 'content'
    ];

    public function coursePage() {
        return $this->belongsTo(CoursePage::class);
    }
}
