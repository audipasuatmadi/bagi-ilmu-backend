<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CoursePage;
use App\Models\Material;
use App\Models\QuizOption;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::factory(10)
            ->has(
                Course::factory()
                ->count(3)
                ->has(CoursePage::factory()
                    ->count(5)
                    ->has(Material::factory()
                        ->count(10), 'materials')
                    ->has(QuizOption::factory()
                        ->count(4), 'quizOptions')
                    , 'coursePages')
                , 'courses')

            ->create();
    }
}
