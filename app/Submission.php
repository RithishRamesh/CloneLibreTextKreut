<?php

namespace App;

use App\Exceptions\Handler;
use App\Http\Requests\StoreSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

use App\Traits\DateFormatter;

class Submission extends Model
{

    use DateFormatter;

    protected $fillable = ['user_id', 'submission', 'assignment_id', 'question_id', 'score'];


    public function store(StoreSubmission $request, Submission $submission, Assignment $Assignment, Score $score)
    {

        $response['type'] = 'error';//using an alert instead of a noty because it wasn't working with post message

        // $data = $request->validated();//TODO: validate here!!!!!
        // $data = $request->all(); ///maybe request->all() flag in the model or let it equal request???
        // Log::info(print_r($request->all(), true));


        $data = $request;

        $data['user_id'] = Auth::user()->id;
        $assignment = $Assignment->find($data['assignment_id']);

        $assignment_question = DB::table('assignment_question')->where('assignment_id', $assignment->id)
            ->where('question_id', $data['question_id'])
            ->select('points')
            ->first();

        if (!$assignment_question) {
            $response['message'] = 'That question is not part of the assignment.';
            return $response;
        }

        if ($assignment->solutions_released) {
            $response['message'] = 'You submission will not be saved since the solutions have been released.';
            return $response;
        }

        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment->id, $data['question_id']]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        switch ($data['technology']) {
            case('h5p'):
                $submission = json_decode($data['submission']);
                $data['score'] = floatval($assignment_question->points) * (floatval($submission->result->score->raw) / floatval($submission->result->score->max));
                break;
            case('imathas'):
                $submission = $data['submission'];
                $data['score'] = floatval($assignment_question->points) * floatval($submission->score);
                $data['submission'] = json_encode($data['submission'], JSON_UNESCAPED_SLASHES);
                break;
            case('webwork'):
                // Log::info('case webwork');
                $submission = $data['submission'];
                $data['score'] =  floatval($assignment_question->points) * floatval($submission->score->score);
                Log::info('Score: ' .  $data['score'] );
                $data['submission'] = json_encode($data['submission']);
                break;
            default:
                $response['message'] = 'That is not a valid technology.';
                return $response;
        }

        try {
            DB::beginTransaction();
            //do the extension stuff also
            $submission = Submission::where('user_id', $data['user_id'])
                ->where('assignment_id', $data['assignment_id'])
                ->where('question_id', $data['question_id'])
                ->first();

            if ($submission) {

                $submission->submission = $data['submission'];
                $submission->score = $data['score'];
                $submission->save();

            } else {
                Submission::create(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'score' => $data['score']]);

            }

            //update the score if it's supposed to be updated
            switch ($assignment->scoring_type) {
                case 'c':
                    $num_submissions_by_assignment = DB::table('submissions')
                        ->where('user_id', $data['user_id'])
                        ->where('assignment_id', $assignment->id)
                        ->count();
                    if ((int)$num_submissions_by_assignment === count($assignment->questions)) {
                        Score::updateOrCreate(['user_id' => $data['user_id'],
                            'assignment_id' => $assignment->id],
                            ['score' => 'c']);
                    }
                    break;
                case 'p':
                    $score->updateAssignmentScore($data['user_id'], $assignment->id, $assignment->submission_files);
                    break;
            }
            $response['type'] = 'success';
            $response['message'] = 'Question submission saved.';
            $log = new \App\Log();
            $request->action = 'submit-question-response';
            $request->data =  ['assignment_id' => $data['assignment_id'],
                    'question_id' => $data['question_id']];
            $log->store($request);
            Submission::create(['user_id' => $data['user_id'],
                'assignment_id' => $data['assignment_id'],
                'question_id' => $data['question_id'],
                'submission' => $data['submission'],
                'score' => $data['score']]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your response.  Please try again or contact us for assistance.";
        }

        return $response;

    }

    public function getSubmissionDatesByAssignmentIdAndUser($assignment_id, User $user)
    {
        $last_submitted_by_user = [];
        $submissions = DB::table('submissions')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $user->id)
            ->select('updated_at', 'question_id')
            ->get();

        foreach ($submissions as $key => $value) {
            $last_submitted_by_user[$value->question_id] = $value->updated_at;
        }

        return $last_submitted_by_user;
    }

    public function getSubmissionsCountByAssignmentIdsAndUser(Collection $assignments, Collection $assignment_ids, User $user)
    {

        $assignment_question_submissions = [];
        $assignment_file_submissions = [];
        $results = DB::table('submissions')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->select('question_id', 'assignment_id')
            ->get();
        foreach ($results as $key => $value) {
            $assignment_question_submissions[$value->assignment_id][] = $value->question_id;
        }

        $results = DB::table('submission_files')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->where('type', 'q')
            ->select('question_id', 'assignment_id')
            ->get();
        foreach ($results as $key => $value) {
            $assignment_file_submissions[$value->assignment_id][] = $value->question_id;
        }


        $submissions_count_by_assignment_id = [];
        foreach ($assignments as $assignment) {
            $question_submissions= [];
            $file_submissions = [];
            if (isset($assignment_question_submissions[$assignment->id])) {
                foreach ($assignment_question_submissions[$assignment->id] as $question_id) {
                    $question_submissions[] = $question_id;
                }
            }
            if (isset($assignment_file_submissions[$assignment->id])) {
                foreach ($assignment_file_submissions[$assignment->id] as $question_id) {
                    $file_submissions[] = $question_id;
                }
            }
            $total_submissions_for_assignment = 0;
            foreach ($assignment->questions as $question) {
                if (in_array($question->id, $question_submissions) || in_array($question->id, $file_submissions)) {
                    $total_submissions_for_assignment++;
                }

                $submissions_count_by_assignment_id[$assignment->id] = $total_submissions_for_assignment;
            }

        }

        return $submissions_count_by_assignment_id;
    }


    public function getNumberOfUserSubmissionsByCourse($course, $user)
    {
        $AssignmentSyncQuestion = new AssignmentSyncQuestion();
        $num_sumbissions_per_assignment = [];
        $assignment_source = [];
        $assignment_ids = collect([]);
        $assignments = $course->assignments;
        if ($assignments->isNotEmpty()) {

            foreach ($course->assignments as $assignment) {
                $assignment_ids[] = $assignment->id;
                $assignment_source[$assignment->id] = $assignment->source;

            }

            $questions_count_by_assignment_id = $AssignmentSyncQuestion->getQuestionCountByAssignmentIds($assignment_ids);

            $submissions_count_by_assignment_id = $this->getSubmissionsCountByAssignmentIdsAndUser($course->assignments, $assignment_ids, $user);
            //set to 0 if there are no questions
            foreach ($assignment_ids as $assignment_id) {
                $num_questions = $questions_count_by_assignment_id[$assignment_id] ?? 0;
                $num_submissions = $submissions_count_by_assignment_id[$assignment_id] ?? 0;
                switch ($assignment_source[$assignment_id]) {
                    case('a'):
                        $num_sumbissions_per_assignment[$assignment_id] = ($num_questions === 0) ? "No questions" : "$num_submissions/$num_questions";
                        break;
                    case('x'):
                        $num_sumbissions_per_assignment[$assignment_id] = 'N/A';
                }
            }
        }
        return $num_sumbissions_per_assignment;


    }
}
