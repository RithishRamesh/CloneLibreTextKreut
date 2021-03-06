<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Assignment;
use App\Course;
use App\Exceptions\Handler;

use \Exception;

class BreadcrumbController extends Controller
{
    public function index(Request $request)
    {
        $name = $request->name;
        $params = $request->params;
        $course_id = $params['courseId'] ?? 0;
        $assignment_id = $params['assignmentId'] ?? 0;
        $course = $assignment = null;
        if ($course_id) {
            $course = Course::find($course_id);
        }
        if ($assignment_id) {
            $assignment = Assignment::find($assignment_id);
        }
        $users = (Auth::user()->role === 3) ? 'students' : 'instructors';
        $response['type'] = 'error';
        $breadcrumbs = [];

        try {
            if (!$request->session()->has('lti_user_id')) {
                if (Auth::check()) {
                    $breadcrumbs[0] = ['text' => 'My Courses', 'href' => "/$users/courses"];
                    switch ($name) {
                        case('questions.get'):
                        case('learning_trees.get'):
                            $breadcrumbs[] = ['text' => $assignment->course->name,
                                'href' => "/$users/courses/{$assignment->course->id}/assignments"];
                            $breadcrumbs[] = ['text' => "$assignment->name Information",
                                'href' => "/instructors/assignments/{$assignment->id}/information",
                            ];
                            $breadcrumbs[] = ['text' => 'Add Assessments',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('course_properties.general_info'):
                        case('course_properties.sections'):
                        case('course_properties.letter_grades'):
                        case('course_properties.tethered_courses'):
                        case('course_properties.graders'):
                        case('course_properties.grader_notifications'):
                        case('course_properties.ungraded_submissions'):
                        case('course_properties.students'):
                        case('course_properties.access_codes'):
                        case('course_properties.assignment_group_weights'):
                        case('course_properties.iframe_properties'):
                            $breadcrumbs[] = ['text' => $course->name,
                                'href' => "/instructors/courses/{$course->id}/assignments"
                            ];
                            $breadcrumbs[] = ['text' => 'Properties',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('instructors.learning_trees.index'):
                        case('instructors.courses.index'):
                            $breadcrumbs[0] = ['text' => '', 'href' => ""];
                            break;
                        case('login.as'):
                        case('refresh.question.requests'):
                            $breadcrumbs[0] = ['text' => 'My Courses', 'href' => "/$users/courses"];
                            break;
                        case('instructors.learning_trees.editor'):
                            $breadcrumbs[0] = ['text' => 'My Learning Trees', 'href' => "/instructors/learning-trees"];
                            $breadcrumbs[1] = ['text' => 'Editor', 'href' => "#", 'active' => true];
                            break;
                        case('settings.profile'):
                        case('settings.password'):
                        case('settings.notifications'):
                            $breadcrumbs[] = ['text' => 'Settings',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('instructors.assignments.index'):
                            //My courses / the assignment's course
                            $breadcrumbs[] = ['text' => $course->name,
                                'href' => "/instructors/courses/{$course->id}/assignments",
                                'active' => true];
                            break;
                        case('students.assignments.index'):
                            $breadcrumbs[] = ['text' => $course->name,
                                'href' => "/students/courses/{$course->id}/assignments",
                                'active' => true];
                            break;
                        case('students.assignments.summary'):
                        case('instructors.assignments.summary'):
                        case('instructors.assignments.properties'):
                        case('instructors.assignments.control_panel'):
                        case('instructors.assignments.statistics'):
                        case('instructors.assignments.grader_access'):
                        case('instructors.assignments.questions'):
                        case('instructors.assignments.submissions'):
                        case('instructors.assignments.gradebook'):

                            //My courses / The assignment's course / that assignment;
                            $breadcrumbs[] = ['text' => $assignment->course->name,
                                'href' => "/$users/courses/{$assignment->course->id}/assignments"];
                            $breadcrumbs[] = ['text' => $assignment->name,
                                'href' => "#",
                                'active' => true];
                            break;
                        case('questions.view'):
                            //My courses / The assignment's course / that assignment summary / the assignment questions
                            $breadcrumbs[] = ['text' => $assignment->course->name,
                                'href' => "/$users/courses/{$assignment->course->id}/assignments"];

                            if (Auth::user()->role === 3) {
                                $breadcrumbs[] = ['text' => "{$assignment->name}",
                                    'href' => "/students/assignments/{$assignment_id}/summary"];
                            } else {
                                $breadcrumbs[] = ['text' => "{$assignment->name}",
                                    'href' => "/instructors/assignments/{$assignment_id}/information"];
                            }
                            $breadcrumbs[] = ['text' => "View Assessments",
                                'href' => "#",
                                'active' => true];
                            break;
//My courses / The assignment's course / that assignment / questions get
                        case('gradebook.index'):
                            //My courses / that course
                            $breadcrumbs[] = ['text' => $course->name,
                                'href' => "/instructors/courses/{$course->id}/assignments"];
                            $breadcrumbs[] = ['text' => 'Gradebook',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('assignment.grading.index'):
                            $breadcrumbs[] = ['text' => $assignment->course->name,
                                'href' => "/instructors/courses/{$assignment->course->id}/assignments"];
                            $breadcrumbs[] = ['text' => 'Grading',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('question.view'):
                            $breadcrumbs[] = ['text' => $assignment->course->name,
                                'href' => "/instructors/courses/{$assignment->course->id}/assignments"];
                            if (in_array($assignment->submission_files, ['q', 'a'])) {
                                $type = $assignment->submission_files === 'q' ? 'question' : 'assignment';
                                $breadcrumbs[] = ['text' => 'Grading',
                                    'href' => "/assignments/{$assignment->id}/$type-files"];
                            }
                            $breadcrumbs[] = ['text' => 'View Assessments',
                                'href' => "#",
                                'active' => true];
                    }
                }
            }
            $response['type'] = 'success';
            $response['breadcrumbs'] = $breadcrumbs;
        } catch (Exception $e) {
            //no message for the user: just for me

        }
        return $response;

    }
}
