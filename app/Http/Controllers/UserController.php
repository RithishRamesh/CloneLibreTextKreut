<?php

namespace App\Http\Controllers;

use App\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

use App\Exceptions\Handler;
use \Exception;

class UserController extends Controller
{

    public function getGraders(Request $request, Course $course)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getGraders', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['graders']  = [];
            foreach ($course->graders as $grader){
                $response['graders'][] = [
                    'name' => $grader->first_name . ' ' . $grader->last_name,
                    'id' => $grader->user_id
                ];
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your graders.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;

    }
}
