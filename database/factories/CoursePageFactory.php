<?php

namespace Database\Factories;

use App\Models\CoursePage;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CoursePageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CoursePage::class;

    private $defaultNum = 0;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $this->defaultNum++;
        return [
            'page_index' => $this->defaultNum,
            'is_quiz' => $this->faker->boolean
        ];
    }
}
