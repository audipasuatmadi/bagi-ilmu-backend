<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePage;
use App\Models\Material;
use App\Models\QuizOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{

    private $customMessage = [
        'required' => ':attribute harus diisi',
    ];

    private $configRules = [
        'courseTitle' => 'required|string',
        'courseShortDescription' => 'required|string',
        'courseDescription' => 'required|string'
    ];

    private $customAttributes = [
        'courseTitle' => 'Judul Course',
        'courseShortDescription' => 'Deskripsi Singkat Course',
        'courseDescription' => 'Deskripsi Course'
    ];

    public function addCourse(Request $request) {

        $configData = [
            'courseTitle' => $request['courseConfig']['courseTitle'],
            'courseShortDescription' => $request['courseConfig']['courseShortDescription'],
            'courseDescription' => $request['courseConfig']['courseDescription']
        ];
        
        $validator = Validator::make($configData, $this->configRules, $this->customMessage, $this->customAttributes);
        $validator->validate();

        $courseConfig = $request['courseConfig'];        
        $pageData = $request['pageData'];
        $course = $this->insertNewCourse($request, $courseConfig);
        

        foreach ($pageData as $page) {
            $newCoursePage = new CoursePage();
            $newCoursePage->page_index = $page['pageNumber'];

            $course->coursePages()->save($newCoursePage);

            foreach ($page['contents'] as $content) {
                $material = new Material();
                $material->section_index = $content['sectionIndex'];
                $material->is_code = $content['isCode'];
                $material->content = $content['content'];
                
                $newCoursePage->materials()->save($material);
            }

            if ($page['isQuiz']) {
                foreach ($page['quizContents'] as $quizContent) {

                    $quizOption = new QuizOption();
                    $quizOption['contents'] = $quizContent['content'];
                    $newCoursePage->quizOptions()->save($quizOption);
                    
                }
            }

        }
    }

    private function insertNewCourse($request, $courseConfig) {
        $course = new Course();
        $course->title = $courseConfig['courseTitle'];
        $course->short_description = $courseConfig['courseShortDescription'];
        $course->long_description = $courseConfig['courseDescription'];
        
        $user = $request->user();
        $user->courses()->save($course);

        return $course;
    }
}
