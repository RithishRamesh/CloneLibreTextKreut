<?php

namespace App;

use App\Assignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Score extends Model
{
    protected $fillable = ['user_id', 'assignment_id', 'score'];

    public function updateAssignmentScore(int $student_user_id, int $assignment_id, string $submission_files_type)
    {

        //files are for extra credit
        //remediations are for extra credit
        //loop through all of the submitted questions
        //loop through all of the submitted files
        //for each question add the submitted question score + submitted file score and max out at the score for the question

        $assignment_questions = DB::table('assignment_question')->where('assignment_id', $assignment_id)->get();

        $assignment_score = 0;
        //initialize
        $assignment_question_scores_info = [];
        foreach ($assignment_questions as $question) {
            $assignment_question_scores_info[$question->question_id] = [];
            $assignment_question_scores_info[$question->question_id]['points'] = $question->points;
            $assignment_question_scores_info[$question->question_id]['question'] = 0;
            $assignment_question_scores_info[$question->question_id]['file'] = 0;//need for file uploads
        }

        $submissions = DB::table('submissions')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $student_user_id)->get();
        if ($submissions->isNotEmpty()) {
            foreach ($submissions as $submission) {
                $assignment_question_scores_info[$submission->question_id]['question'] = $submission->score;
            }
        }
        switch ($submission_files_type) {
            case('q'):

                $submission_files = DB::table('submission_files')
                    ->where('assignment_id', $assignment_id)
                    ->where('type', 'q') //'q', 'a', or 0
                    ->where('user_id', $student_user_id)->get();

                if ($submission_files->isNotEmpty()) {
                    foreach ($submission_files as $submission_file) {
                        $assignment_question_scores_info[$submission_file->question_id]['file'] = $submission_file->score
                            ? $submission_file->score
                            : 0;
                    }
                }

                foreach ($assignment_question_scores_info as $score) {
                    $question_points = $score['question'];
                    $file_points = $score['file'];
                    $assignment_score = $assignment_score + min($score['points'], $question_points + $file_points);
                }
                break;
            case('a'):
                $assignment_score_from_questions = $assignment_question_scores_info ?
                    $this->getAssignmentScoreFromQuestions($assignment_question_scores_info)
                    : 0;
                //get the points from the submission
                $submission_file = DB::table('submission_files')
                    ->where('assignment_id', $assignment_id)
                    ->where('type', 'a') //'q', 'a', or 0
                    ->where('user_id', $student_user_id)->first();

                $points_from_submissions = $assignment_score_from_questions + ($submission_file->score ?? 0);

                //get the total assignment points
                $total_assignment_points = 0;
                foreach ($assignment_questions as $question) {
                    $total_assignment_points = $total_assignment_points + $question->points;

                }

                $assignment_score = min($total_assignment_points, $points_from_submissions);
                break;

            case('0'):
                $assignment_score = $assignment_question_scores_info ?
                    $this->getAssignmentScoreFromQuestions($assignment_question_scores_info)
                    : 0;
                break;

        }
        DB::table('scores')
            ->updateOrInsert(
                ['user_id' => $student_user_id, 'assignment_id' => $assignment_id],
                ['score' => $assignment_score]);

    }

    public function getUserScoresByCourse(Course $course, User $user)
    {

        $assignments = $course->assignments;
        $assignment_ids = [];
        $solutions_released = [];
        $scoring_types = [];
        $scores_by_assignment = [];


//initialize
        foreach ($assignments as $assignment) {
            $assignment_ids[] = $assignment->id;
            $solutions_released[$assignment->id] = $assignment->solutions_released;
            $scoring_types[$assignment->id] = $assignment->scoring_type;
            if ($assignment->scoring_type === 'p') {
                $scores_by_assignment[$assignment->id] = ($assignment->solutions_released)
                    ? 0 : 'Not yet released';

            } else {
                $scores_by_assignment[$assignment->id] = 'Incomplete';

            }

        }
        $scores = DB::table('scores')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->get();

//show the score for points only if the solutions have been released
//otherwise show the score
        foreach ($scores as $key => $value) {
            if ( $scoring_types[$value->assignment_id] === 'p') {
                if ($solutions_released[$value->assignment_id]) {
                    $scores_by_assignment[$value->assignment_id] = $value->score;
                }
            } else {
                $scores_by_assignment[$value->assignment_id] = ($value->score === 'c') ? 'Complete' : 'Incomplete';
            }
        }

        return $scores_by_assignment;

    }


    public function getAssignmentScoreFromQuestions(array $assignment_question_scores_info)
    {

        $assignment_score_from_questions = 0;
        //get the assignment points for the questions
        foreach ($assignment_question_scores_info as $score) {
            $question_points = $score['question'] ?? 0;
            $assignment_score_from_questions = $assignment_score_from_questions + $question_points;
        }

        return $assignment_score_from_questions;
    }
}
