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
            $newCoursePage = $this->insertNewPage($page, $course);

            foreach ($page['contents'] as $content) {
                $this->insertNewMaterial($content, $newCoursePage);
            }

            if ($page['isQuiz']) {
                foreach ($page['quizContents'] as $quizContent) {
                    $this->insertNewQuiz($quizContent, $newCoursePage);
                }
            }

        }
    }

    public function getPagination() {
        $courses = Course::query()->orderByDesc('id')->paginate(5);
        
        $formattedCourse = [];
        foreach($courses as $course) {
            $courseObj = [
                'id' => $course->id,
                'title' => $course->title,
                'shortDescription' => $course->short_description,
                'creatorName' => $course->user->name
            ];
            
            array_push($formattedCourse, $courseObj);
        }

        $course['data'] = $formattedCourse;

        return $course;
    }

    public function getDetails(Request $request, $id) {
        $courseModel = Course::find($id);

        $course = [
            'id' => $courseModel->id,
            'title' => $courseModel->title,
            'shortDescription' => $courseModel->short_description,
            'description' => $courseModel->long_description,
            'creatorName' => $courseModel->user->name
        ];

        return response()->json($course);
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

    private function insertNewPage($pageData, $course) {
        $newCoursePage = new CoursePage();
        $newCoursePage->page_index = $pageData['pageNumber'];
        $newCoursePage->is_quiz = $pageData['isQuiz'];

        $course->coursePages()->save($newCoursePage);

        return $newCoursePage;
    }

    private function insertNewMaterial ($content, $newCoursePage) {
        $material = new Material();
        $material->section_index = $content['sectionIndex'];
        $material->is_code = $content['isCode'];
        $material->content = $content['content'];
        
        $newCoursePage->materials()->save($material);
    }

    private function insertNewQuiz($quizContent, $newCoursePage) {
        $quizOption = new QuizOption();
        $quizOption['contents'] = $quizContent['content'];
        $newCoursePage->quizOptions()->save($quizOption);
    }
}
