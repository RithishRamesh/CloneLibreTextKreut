<?php

namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;

class CookieController extends Controller
{
    /**
     * @param string $questionView
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws Exception
     */
    public function setQuestionView(string $questionView)
    {
        $response['type'] = 'error';
        $cookie = cookie()->forever('question_view', 'basic');
        try {
            $question_view = ($questionView === 'basic') ? 'advanced' : 'basic';
            $cookie = cookie()->forever('question_view', $question_view);
            $response['type'] = 'info';
            $response['message'] = "You have successfully switched the question view to <strong>$question_view.</strong>";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error switching views. Please try again or contact us for assistance.";
        }
        return response($response)->withCookie($cookie);
    }

    public function setAssignmentGroupFilter(Request $request, Course $course, string $chosenAssignmentGroup)
    {
        try {
            $response['type'] = 'error';
            $cookie = $request->cookie('assignment_group_filter');
            $assignment_group_filters = json_decode($cookie, true);
            $assignment_group_filters[$course->id] = $chosenAssignmentGroup === "null" ? null : (int)$chosenAssignmentGroup;
            $cookie = cookie()->forever('assignment_group_filter', json_encode($assignment_group_filters));
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
        return response($response)->withCookie($cookie);
    }
}
