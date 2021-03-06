<?php

namespace App\Http\Controllers;

use App\AssignmentGroupWeight;
use App\Http\Requests\updateLetterGrade;
use App\FinalGrade;
use App\Course;
use Illuminate\Http\Request;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Gate;


class FinalGradeController extends Controller
{
    public function roundScores(Request $request, Course $course, int $roundScores, FinalGrade $FinalGrade)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('roundScores', [$FinalGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $FinalGrade->updateOrCreate(
                ['course_id' => $course->id],
                ['round_scores' => !$roundScores]
            );

            $round_scores_message = ((int)$roundScores === 0) ? "will" : "will not";
            $response['type'] = ((int)$roundScores === 0) ? 'success' : 'info';
            $response['message'] = "Scores <strong>$round_scores_message</strong> be rounded up to the nearest integer.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the round scores option.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function releaseLetterGrades(Request $request, Course $course, int $releaseLetterGrades, FinalGrade $FinalGrade, AssignmentGroupWeight $assignmentGroupWeight)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('releaseLetterGrades', [$FinalGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response = $assignmentGroupWeight->validateCourseWeights($course);
        if ($response['type'] === 'error'){
            return $response;
        }
        try {
            $FinalGrade->updateOrCreate(
                ['course_id' => $course->id],
                ['letter_grades_released' => !$releaseLetterGrades]
            );

            $response['type'] = 'success';
            $release_grades_message = ((int)$releaseLetterGrades === 0) ? "are" : "are not";
            $response['type'] = ((int)$releaseLetterGrades === 0) ? 'success' : 'info';
            $response['message'] = "The letter grades <strong>$release_grades_message</strong> released.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating whether the letter grades are released.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getDefaultLetterGrades(FinalGrade $finalGrade)
    {
        $response['default_letter_grades'] = $finalGrade->getLetterGradesAsArray($finalGrade->defaultLetterGrades());
        return $response;
    }

    public function letterGradesReleased(Request $request, Course $course)
    {

        $response['letter_grades_released'] = $course->finalGrades->letter_grades_released;
        return $response;
    }

    public function getCourseLetterGrades(Request $request, Course $course, FinalGrade $FinalGrade)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getCourseLetterGrades', [$FinalGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['letter_grades'] = $FinalGrade->getLetterGradesAsArray($course->finalGrades->letter_grades);
            $response['round_scores'] = $course->finalGrades->round_scores;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the course letter grades.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function update(updateLetterGrade $request, Course $course, FinalGrade $FinalGrade)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateLetterGrades', [$FinalGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        $letter_grades = $this->orderLetterGradesFromHighToLowCutoffs($data);
        $formatted_letter_grades = '';
        foreach ($letter_grades as $key => $value) {
            $formatted_letter_grades .= "$key,$value,";
        }
        $formatted_letter_grades = rtrim($formatted_letter_grades, ',');
        try {
            $FinalGrade->updateOrCreate(
                ['course_id' => $course->id],
                ['letter_grades' => $formatted_letter_grades]
            );
            $response['letter_grades'] = $FinalGrade->getLetterGradesAsArray($formatted_letter_grades);
            $response['type'] = 'success';
            $response['message'] = "Your letter grades have been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the letter grades.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function orderLetterGradesFromHighToLowCutoffs(array $data)
    {
        $letter_grades_array = explode(',', $data['letter_grades']);
        $letter_grades = [];
        for ($i = 0; $i < count($letter_grades_array) / 2; $i++) {
            $cutoff = $letter_grades_array[2 * $i];
            $letter_grade = $letter_grades_array[2 * $i + 1];
            $letter_grades[$cutoff] = $letter_grade;
        }
        krsort($letter_grades, SORT_NUMERIC);
        return $letter_grades;
    }
}
