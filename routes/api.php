<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('user')->group(function() {
    Route::post('register', [UserController::class, 'register']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('login', [UserController::class, 'login']);
});

Route::prefix('course')->group(function() {
    Route::post('add', [CourseController::class, 'addCourse']);

    Route::post('join', [CourseController::class, 'joinCourse']);

    Route::get('list', [CourseController::class, 'getPagination']);

    Route::get('list/my', [CourseController::class, 'getMyPagination']);

    Route::get('total', [CourseController::class, 'getTotalCourseCount']);

    Route::get('details/{id}', [CourseController::class, 'getDetails']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
