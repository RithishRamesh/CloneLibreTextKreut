<?php

namespace App\Http\Controllers;

use App\Course;
use App\User;
use App\CourseAccessCode;
use App\Enrollment;
use App\Assignment;
use App\Http\Requests\StoreCourse;
use Illuminate\Support\Facades\DB;
use App\Exceptions\Handler;
use \Exception;

class CourseController extends Controller
{
    /**
     *
     *  Get the authenticated user's courses
     *
     * @param Course $course
     * @return \Illuminate\Support\Collection
     */
    public function index(Course $course)
    {
        return DB::table('courses')
            ->join('course_access_codes', 'courses.id', '=', 'course_access_codes.course_id')
            ->select('courses.*', 'course_access_codes.access_code')
            ->where('user_id', auth()->user()->id)->orderBy('start_date', 'desc')
            ->get();

    }

    /**
     *
     * Store a newly created resource in storage.
     *
     * @param StoreCourse $request
     * @param Course $course
     * @param CourseAccessCode $course_access_code
     * @return mixed
     * @throws Exception
     */

    public function store(StoreCourse $request, Course $course, CourseAccessCode $course_access_code, User $user, Enrollment $enrollment)
    {
        //todo: check the validation rules
        $response['type'] = 'error';
        try {
            DB::transaction(function () use ($request, $course, $course_access_code, $user, $enrollment) {
                $data = $request->validated();
                $data['user_id'] = auth()->user()->id;
                //create the course
                $new_course = $course->create($data);
                //create the access code
                $course_access_code->create(['course_id' => $new_course->id,
                    'access_code' => $course_access_code->createCourseAccessCode()]);
                //create a test student
                $fake_student = $user->create(['last_name' => 'Student',
                    'first_name' => 'Fake'
                ]);
                //enroll the fake student
                $enrollment->create(['user_id' => $fake_student->id,
                    'course_id' => $new_course->id]);
            });

            $response['type'] = 'success';
            $response['message'] = "The course <strong>$request->name</strong> has been created.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>$request->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Update the specified resource in storage.
     *
     *
     * @param StoreCourse $request
     * @param Course $course
     * @return mixed
     * @throws Exception
     */
    public function update(StoreCourse $request, Course $course)
    {
        $response['type'] = 'error';
        try {
            $request->validated();
            $course->update($request->except('user_id'));
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Delete a course
     *
     * @param Course $course
     * @param CourseAccessCode $course_access_code
     * @param Enrollment $enrollment
     * @return mixed
     * @throws Exception
     */
    public function destroy(Course $course)
    {

        $response['type'] = 'error';
        try {
            DB::transaction(function () use ($course) {
                $course->accessCodes()->delete();
                foreach ($course->assignments as $assignment){
                    $assignment->questions()->detach();
                    $assignment->grades()->delete();
                }
                $course->assignments()->delete();
                $course->enrollments()->delete();
                $course->delete();
            });
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been deleted.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
