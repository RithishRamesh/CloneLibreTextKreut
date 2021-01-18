<?php


namespace App\Traits;

use App\User;
use Illuminate\Support\Facades\DB;

trait GeneralSubmissionPolicy
{
    public function canSubmitBasedOnGeneralSubmissionPolicy(User $user, $assignment, int $assignment_id, int $question_id)
    {
        $response['type'] = 'error';
        $response['message'] = '';

        if ($assignment->assessment_type === 'clicker'){
            $assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->select()
                ->first(['can_view','can_submit']);
            if (!($assignment_question->can_view && $assignment_question->can_submit)){
                $response['message'] = "This question is currently not open for submission.";
                return $response;
            }

        }
        if (!$assignment->questions->contains($question_id)) {
            $response['message'] = 'No responses will be saved since that question is not in the assignment.';
            return $response;
        }

        if (!$assignment->course->enrollments->contains('user_id', $user->id)) {
            $response['message'] = 'No responses will be saved since the assignment is not part of your course.';
            return $response;
        }

        if (strtotime($assignment->available_from) > time()) {
            $response['message'] = 'No responses will be saved since this assignment is not yet available.';
            return $response;
        }

        $file_submission = DB::table('submission_files')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->where('user_id', $user->id)
            ->select('date_graded')
            ->first();

        if ($file_submission && $file_submission->date_graded){
            $response['message'] = 'Your submission has already been graded and may not be re-submitted.';
            return $response;
        }

        //first let's see if there's an extension
        $extension = DB::table('extensions')
            ->select('extension')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $user->id)
            ->first('extension');
        $past_due = time() > strtotime($assignment->due);
        //check to see if the instructor accidentally released scores (which will have comments) or released solutions
        switch ($past_due) {
            case(false):
                if ($assignment->assessment_type === 'delayed') {
                    if ($assignment->show_scores) {
                        $response['message'] = 'No responses will be saved since the scores to this assignment have been released.';
                        return $response;
                    }
                }
                if (in_array($assignment->assessment_type, ['delayed', 'learning tree'])) {
                    if ($assignment->solutions_released) {
                        $response['message'] = 'No responses will be saved since the solutions to this assignment have been released.';
                        return $response;
                    }
                }
                break;
            case(true):
                if ($extension) {
                    if (strtotime($extension->extension) < time()) {
                        $response['message'] = 'No responses will be saved since your extension for this assignment has passed.';
                    } else {
                        $response['type'] = 'success';
                    }
                    return $response;
                }

                if ($assignment->late_policy === 'not accepted') {
                    $response['message'] = 'No responses will be saved since the due date for this assignment has passed.';
                    return $response;
                }
                if (in_array($assignment->late_policy, ['deduction', 'marked late'])) {
                    //now let's check the late policy deadline
                    //if past policy deadline
                    if (strtotime($assignment->late_policy_deadline) < time()) {
                        $response['message'] = 'No more late responses are being accepted.';
                        return $response;
                    }
                }
                break;
        }
        $response['type'] = 'success';
        return $response;
    }


}
