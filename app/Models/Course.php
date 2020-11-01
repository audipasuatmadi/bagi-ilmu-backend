<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'short_description', 'long_description',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function coursePages() {
        return $this->hasMany(CoursePage::class);
    }

    public function joinedUsers() {
        return $this->belongsToMany(User::class)
            ->using(CourseUser::class)->withPivot('progress')->as('details');
    }

}
