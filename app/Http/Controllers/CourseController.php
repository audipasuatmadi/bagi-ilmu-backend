<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePage;
use App\Models\Material;
use App\Models\QuizOption;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function addCourse(Request $request)
    {



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

    public function getPagination()
    {
        $courses = Course::query()->orderByDesc('id')->paginate(5);

        $formattedCourse = [];
        foreach ($courses as $course) {
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

    public function getMyPagination(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $joinedUnformattedCourse = $user->joinedCourses()->orderBy('course_user.created_at', 'desc')->get();
            $courses = [];

            foreach ($joinedUnformattedCourse as $courseModel) {
                $course = [
                    'id' => $courseModel->id,
                    'title' => $courseModel->title,
                    'shortDescription' => $courseModel->short_description,
                    'description' => $courseModel->long_description,
                    'creatorName' => $courseModel->user->name
                ];
                array_push($courses, $course);
            }

            return response()->json($courses);
        }
        return response(null, '403');
    }

    public function getDetails(Request $request, $id)
    {
        $courseModel = Course::find($id);



        $isJoined = false;

        if ($request->user()) {
            if ($request->user()->joinedCourses()->where('course_id', $courseModel['id'])->first()) {
                $isJoined = true;
            }
        }

        error_log($isJoined);

        $course = [
            'id' => $courseModel->id,
            'title' => $courseModel->title,
            'shortDescription' => $courseModel->short_description,
            'description' => $courseModel->long_description,
            'creatorName' => $courseModel->user->name,
            'isJoined' => $isJoined
        ];

        return response()->json($course);
    }

    public function joinCourse(Request $request)
    {
        $user = $request->user();
        $courseId = $request->input('courseId');

        $hasJoined = $user->joinedCourses()->where('course_id', $courseId)->first();
        if (!$hasJoined) {
            $user->joinedCourses()->attach($courseId);
            $pivot = $user->joinedCourses()->where('course_id', $courseId)->first()->details;
            $pivot->progress = $this->findLatestQuizIndex(Course::where('id', $courseId)->first(), 1);
            $pivot->save();
            return response(null, 200);
        }
        return response('user already joined the course', 409);
    }

    public function getTotalCourseCount()
    {
        $count = Course::count();
        return $count;
    }

    public function getOngoingCourse(Request $request, $courseId, $pageIndex)
    {
        $user = $request->user();
        if (!$user) {
            return response(null, 401);
        }


        $courseData = $user->joinedCourses()->where('course_id', $courseId)->first();
        $count = $courseData->details->progress;

        if ($count < $pageIndex) {
            error_log("thrill");
            return response("anda belum mencapai tahap ini", 401);
        }

        if (!$courseData) {
            return response(null, 401);
        }
        $pageData = $courseData->coursePages()->where('page_index', $pageIndex)->first();

        if (!$pageData) {
            return response("halaman tidak ditemukan", 404);
        }

        $formattedContents = [];
        foreach ($pageData->materials as $content) {
            array_push($formattedContents, [
                "id" => $content['id'],
                "sectionIndex" => $content['section_index'],
                "isCode" => $content['is_code'],
                "content" => $content['content']
            ]);
        }

        if ($pageData['is_quiz']) {
            $formattedQuiz = [];
            foreach ($pageData->quizOptions as $options) {
                array_push($formattedQuiz, [
                    "id" => $options['id'],
                    "content" => $options['contents']
                ]);
            }

            $shuffeledFormatedQuiz = collect($formattedQuiz);
            $formattedQuiz = $shuffeledFormatedQuiz->shuffle();

            $formattedPageData = [
                "pageId" => $pageData['id'],
                "courseId" => $pageData['course_id'],
                "pageNumber" => $pageData['page_index'],
                "isQuiz" => $pageData['is_quiz'],
                "contents" => $formattedContents,
                "quizContents" => $formattedQuiz
            ];
        } else {
            $formattedPageData = [
                "pageId" => $pageData['id'],
                "courseId" => $pageData['course_id'],
                "pageNumber" => $pageData['page_index'],
                "isQuiz" => $pageData['is_quiz'],
                "contents" => $formattedContents
            ];
        }



        return response()->json(["pageData" => $formattedPageData, "totalPages" => $count]);
    }

    public function validateOption(Request $request, $courseId, $pageIndex, $optionId)
    {
        $course = Course::firstWhere('id', $courseId);
        $page = $course->coursePages()->where('page_index', $pageIndex)->first();
        $correctOption = $page->quizOptions[0];

        $result = $optionId == $correctOption->id;

        $user = $request->user();
        $pivotData = $user->joinedCourses()->where('course_id', $courseId)->first()->details;

        if ($result) {
            if ($pivotData->progress <= $pageIndex) {
                $pivotData->progress = $this->findLatestQuizIndex($course, $pivotData->progress);
                $pivotData->save();
            }
        }

        return response()->json(["result" => $result, "newProgress" => $pivotData->progress]);
    }

    public function getCreatedCourses(Request $request)
    {
        $user = $request->user();
        $courses = $user->courses()->select('id', 'title', 'is_published as isPublished')->get();

        return response()->json($courses);
    }

    public function toggleIsPublished(Request $request, $courseId)
    {
        $user = $request->user();
        $selectedCourse = $user->courses()->find($courseId);
        $selectedCourse->is_published = !$selectedCourse->is_published;

        $selectedCourse->save();

        return $this->getCreatedCourses($request);
    }

    private function findLatestQuizIndex($course, $currentProgress)
    {
        $allPages = $course->coursePages;
        $newProgress = $currentProgress;
        foreach ($allPages as $page) {
            if ($page['is_quiz']) {
                if ($page['page_index'] > $currentProgress) {
                    $newProgress = $page['page_index'];
                    break;
                }
            }
        }

        return $newProgress;
    }

    private function insertNewCourse($request, $courseConfig)
    {
        $course = new Course();
        $course->title = $courseConfig['courseTitle'];
        $course->short_description = $courseConfig['courseShortDescription'];
        $course->long_description = $courseConfig['courseDescription'];

        $user = $request->user();
        $user->courses()->save($course);

        return $course;
    }

    private function insertNewPage($pageData, $course)
    {
        $newCoursePage = new CoursePage();
        $newCoursePage->page_index = $pageData['pageNumber'];
        $newCoursePage->is_quiz = $pageData['isQuiz'];

        $course->coursePages()->save($newCoursePage);

        return $newCoursePage;
    }

    private function insertNewMaterial($content, $newCoursePage)
    {
        $material = new Material();
        $material->section_index = $content['sectionIndex'];
        $material->is_code = $content['isCode'];
        $material->content = $content['content'];

        $newCoursePage->materials()->save($material);
    }

    private function insertNewQuiz($quizContent, $newCoursePage)
    {
        $quizOption = new QuizOption();
        $quizOption['contents'] = $quizContent['content'];
        $newCoursePage->quizOptions()->save($quizOption);
    }
}
