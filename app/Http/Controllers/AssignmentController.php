<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Traits\DateFormatter;
use App\Course;
use App\Solution;
use App\Score;
use App\Extension;
use App\Submission;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreAssignment;
use Carbon\Carbon;

use \Illuminate\Http\Request;

use App\Exceptions\Handler;
use \Exception;

class AssignmentController extends Controller
{
    use DateFormatter;

    public function solutionsReleased(Request $request, Assignment $assignment, int $solutionsReleased)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('releaseSolutions', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['solutions_released' => !$solutionsReleased]);
            $solutions_released = !$solutionsReleased ? 'released' : 'hidden';
            $response['type'] = !$solutionsReleased ? 'success' : 'info';

            $response['message'] = "The solutions have been <strong>{$solutions_released}</strong>.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function showAssignment(Request $request, Assignment $assignment, int $shown)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showAssignment', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['shown' => !$shown]);
            $response['type'] = !$shown ? 'success' : 'info';
            $shown = !$shown ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$shown}</strong> see this assignment.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating whether your students can see <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function showScores(Request $request, Assignment $assignment, int $showScores)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showScores', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['show_scores' => !$showScores]);
            $response['type'] = !$showScores ? 'success' : 'info';
            $scores_released = !$showScores ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$scores_released}</strong> view their scores.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function showPointsPerQuestion(Request $request, Assignment $assignment, int $showPointsPerQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showPointsPerQuestion', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['show_points_per_question' => !$showPointsPerQuestion]);
            $response['type'] = !$showPointsPerQuestion ? 'success' : 'info';
            $points_per_question = !$showPointsPerQuestion ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$points_per_question}</strong> view the points per question.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function showAssignmentStatistics(Request $request, Assignment $assignment, int $showAssignmentStatistics)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showAssignmentStatistics', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['students_can_view_assignment_statistics' => !$showAssignmentStatistics]);
            $response['type'] = !$showAssignmentStatistics ? 'success' : 'info';
            $scores_released = !$showAssignmentStatistics ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$scores_released}</strong> view the assignment statistics.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error showing/hiding the assignment statistics for <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Display all assignments for the course
     * @param Course $course
     * @param Extension $extension
     * @param Score $Score
     * @param Submission $Submission
     * @param Solution $Solution
     * @return mixed
     * @throws Exception
     */
    public function index(Course $course, Extension $extension, Score $Score, Submission $Submission, Solution $Solution, AssignmentGroup $AssignmentGroup)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            if (Auth::user()->role === 3) {
                $solutions_by_assignment = $Solution->getSolutionsByAssignment($course);
                $extensions_by_assignment = $extension->getUserExtensionsByAssignment(Auth::user());
                $scores_by_assignment = $Score->getUserScoresByCourse($course, Auth::user());
                $number_of_submissions_by_assignment = $Submission->getNumberOfUserSubmissionsByCourse($course, Auth::user());

            } else {
                $assignment_groups_by_assignment = $AssignmentGroup->assignmentGroupsByCourse($course->id);
            }


            $assignments = $course->assignments;
            $assignments_info = [];
            foreach ($assignments as $key => $assignment) {
                $assignments_info[$key] = $assignment->attributesToArray();
                $assignments_info[$key]['shown'] = $assignment->shown;
                $available_from = $assignment['available_from'];
                if (Auth::user()->role === 3) {
                    $is_extension = isset($extensions_by_assignment[$assignment->id]);
                    $due = $is_extension ? $extensions_by_assignment[$assignment->id] : $assignment['due'];
                    $assignments[$key]['is_extension'] = isset($extensions_by_assignment[$assignment->id]);

                    $assignments_info[$key]['due'] = [
                        'due_date' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone), //for viewing
                        'is_extension' => $is_extension
                    ];//for viewing

                    //for comparing I just want the UTC version
                    $assignments_info[$key]['is_available'] = strtotime($available_from) < time();
                    $assignments_info[$key]['past_due'] = $due < time();
                    if (isset($scores_by_assignment[$assignment->id])) {
                        $assignments_info[$key]['score'] = $scores_by_assignment[$assignment->id];
                    } else {
                        $assignments_info[$key]['score'] = ($assignment->scoring_type === 'p') ? '0' : 'Incomplete';
                    }
                    $assignments_info[$key]['number_submitted'] = $number_of_submissions_by_assignment[$assignment->id];
                    $assignments_info[$key]['solution_key'] = $solutions_by_assignment[$assignment->id];
                } else {

                    $due = $assignment['due'];
                    $late_policy_deadline = $assignment['late_policy_deadline'];

                    $assignments_info[$key]['assignment_group'] = $assignment_groups_by_assignment[$assignment->id];
                    $assignments_info[$key]['due'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($due, Auth::user()->time_zone);
                    //for the editing form
                    $assignments_info[$key]['status'] = $this->getStatus($available_from, $due);
                    $assignments_info[$key]['available_from_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($available_from, Auth::user()->time_zone);
                    $assignments_info[$key]['available_from_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($available_from, Auth::user()->time_zone);
                    $assignments_info[$key]['late_policy_deadline_date'] = $late_policy_deadline ? $this->convertUTCMysqlFormattedDateToLocalDate($late_policy_deadline, Auth::user()->time_zone) : null;
                    $assignments_info[$key]['late_policy_deadline_time'] = $late_policy_deadline ? $this->convertUTCMysqlFormattedDateToLocalTime($late_policy_deadline, Auth::user()->time_zone) : null;
                    $assignments_info[$key]['due_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($due, Auth::user()->time_zone);
                    $assignments_info[$key]['due_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($due, Auth::user()->time_zone);
                    $assignments_info[$key]['has_submissions_or_file_submissions'] = $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty();//return as 0 or 1
                    $assignments_info[$key]['include_in_weighted_average'] = $assignment->include_in_weighted_average;
                }
//same regardless of whether you're a student
                $assignments_info[$key]['show_points_per_question'] = $assignment->show_points_per_question;
                $assignments_info[$key]['assessment_type'] = $assignment->assessment_type;
                $assignments_info[$key]['number_of_questions'] = count($assignment->questions);
                $assignments_info[$key]['available_from'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($available_from, Auth::user()->time_zone);
                if (Auth::user()->role === 3 && !$assignments_info[$key]['shown']) {
                    unset($assignments_info[$key]);
                }
            }
            $response['assignments'] = array_values($assignments_info);//fix the unset
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your assignments.  Please try again by refreshing the page or contact us for assistance.";

        }
        return $response;
    }

    function getDefaultPointsPerQuestion(array $data)
    {
        $default_points_per_question = null;
        if ($data['source'] === 'a') {
            $default_points_per_question = ($data['scoring_type'] === 'p') ? $data['default_points_per_question'] : 0;
        }
        return $default_points_per_question;
    }

    public function getStatus(string $available_from, string $due)
    {
        if (Carbon::now() < Carbon::parse($available_from)) {
            return 'Upcoming';
        }

        if (Carbon::now() < Carbon::parse($due)) {
            return 'Open';
        }
        return 'Closed';
    }

    public function addAssignmentGroupWeight(Assignment $assignment, int $assignment_group_id, AssignmentGroupWeight $assignmentGroupWeight)
    {
        $assignment_group_weight_exists = AssignmentGroupWeight::where('course_id', $assignment->course->id)
            ->where('assignment_group_id', $assignment->assignment_group_id)
            ->get()
            ->isNotEmpty();

        if (!$assignment_group_weight_exists) {
            $assignmentGroupWeight->assignment_group_id = $assignment_group_id;
            $assignmentGroupWeight->course_id = $assignment->course->id;
            $assignmentGroupWeight->assignment_group_weight = 0;
            $assignmentGroupWeight->save();
        }
    }


    /**
     * @param StoreAssignment $request
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @return array
     * @throws Exception
     */

    public function store(StoreAssignment $request, Assignment $assignment, AssignmentGroupWeight $assignmentGroupWeight)
    {
        //Log::info('can log');
        $response['type'] = 'error';
        $course = Course::find(['course_id' => $request->input('course_id')])->first();
        $authorized = Gate::inspect('createCourseAssignment', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

            $data = $request->validated();

            $learning_tree_assessment = $request->assessment_type === 'learning tree';
            DB::beginTransaction();
            $assignment = Assignment::create(
                ['name' => $data['name'],
                    'available_from' => $this->formatDateFromRequest($request->available_from_date, $request->available_from_time),
                    'due' => $this->formatDateFromRequest($request->due_date, $request->due_time),
                    'source' => $data['source'],
                    'assessment_type' => $data['source'] === 'a' ? $request->assessment_type : '',
                    'min_time_needed_in_learning_tree' => $learning_tree_assessment ? $data['min_time_needed_in_learning_tree'] : null,
                    'percent_earned_for_exploring_learning_tree' => $learning_tree_assessment ? $data['percent_earned_for_exploring_learning_tree'] : null,
                    'submission_count_percent_decrease' => $learning_tree_assessment ? $data['submission_count_percent_decrease'] : null,
                    'instructions' => $request->instructions ? $request->instructions : '',
                    'external_source_points' => $data['source'] === 'x' ? $data['external_source_points'] : null,
                    'assignment_group_id' => $data['assignment_group_id'],
                    'default_points_per_question' => $this->getDefaultPointsPerQuestion($data),
                    'scoring_type' => $data['scoring_type'],
                    'submission_files' => ($data['source'] === 'a' && $request->assessment_type === 'delayed') ? $data['submission_files'] : 0,
                    'late_policy' => $data['late_policy'],
                    'show_scores' => ($data['source'] === 'a' && $request->assessment_type === 'delayed') ? 0 : 1,
                    'solutions_released' => ($data['source'] === 'a' && $request->assessment_type === 'delayed') ? 0 : 1,
                    'show_points_per_question' => $request->assessment_type === 'delayed' ? 0 : 1,
                    'late_deduction_percent' => $data['late_deduction_percent'] ?? null,
                    'late_policy_deadline' => $this->getLatePolicyDeadeline($request),
                    'late_deduction_application_period' => $this->getLateDeductionApplicationPeriod($request, $data),
                    'include_in_weighted_average' => $data['include_in_weighted_average'],
                    'course_id' => $course->id
                ]
            );

            $this->addAssignmentGroupWeight($assignment, $data['assignment_group_id'], $assignmentGroupWeight);

            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>{$data['name']}</strong> has been created.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>{$data['name']}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getLateDeductionApplicationPeriod(StoreAssignment $request, array $data)
    {
        if ($request->late_deduction_applied_once) {
            return 'once';
        }
        return $data['late_deduction_application_period'] ?? null;
    }

    /**
     *
     * Display the specified resource
     *
     * @param Assignment $assignment
     * @return Assignment
     */
    public function viewQuestionsInfo(Assignment $assignment, Score $score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment = Assignment::find($assignment->id);
            $can_view_assignment_statistics = Auth::user()->role === 2 || (Auth::user()->role === 3 && $assignment->students_can_view_assignment_statistics);
            $response['assignment'] = [
                'name' => $assignment->name,
                'assessment_type' => $assignment->assessment_type,
                'has_submissions_or_file_submissions' => $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty(),
                'time_left' => $this->getTimeLeft($assignment),
                'late_policy' => $assignment->late_policy,
                'total_points' => $this->getTotalPoints($assignment),
                'source' => $assignment->source,
                'min_time_needed_in_learning_tree' => ($assignment->assessment_type === 'learning tree') ? $assignment->min_time_needed_in_learning_tree * 60000 : 0,//in milliseconds
                'percent_earned_for_exploring_learning_tree' => ($assignment->assessment_type === 'learning tree') ? $assignment->percent_earned_for_exploring_learning_tree : 0,
                'submission_files' => $assignment->submission_files,
                'show_points_per_question' => $assignment->show_points_per_question,
                'solutions_released' => $assignment->solutions_released,
                'show_scores' => $assignment->show_scores,
                'submission_count_percent_decrease' => $assignment->submission_count_percent_decrease,
                'scoring_type' => $assignment->scoring_type,
                'students_can_view_assignment_statistics' => $assignment->students_can_view_assignment_statistics,
                'scores' => $can_view_assignment_statistics
                    ? $score->where('assignment_id', $assignment->id)->get()->pluck('score')
                    : []
            ];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     *
     * Display the specified resource
     *
     * @param Assignment $assignment
     * @return Assignment
     */
    public function getQuestionsInfo(Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getQuestionsInfo', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment = Assignment::find($assignment->id);
            $response['assignment'] = [
                'name' => $assignment->name,
                'has_submissions' => $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty(),
                'submission_files' => $assignment->submission_files
            ];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;
    }


    /*
    * Display the specified resource
    *
    * @param Assignment $assignment
    * @return Assignment
    */
    public function scoresInfo(Assignment $assignment, Score $score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('scoresInfo', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment = Assignment::find($assignment->id);
            $response['scores'] = $score->where('assignment_id', $assignment->id)->get()->pluck('score');
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function getAssignmentNameAndLatePolicy(Assignment $assignment)
    {

        $response['type'] = 'error';
        try {
            $assignment = Assignment::find($assignment->id);
            $response['assignment'] = [
                'name' => $assignment->name,
                'late_policy' => $assignment->late_policy,
                'late_deduction_percent' => $assignment->late_deduction_percent,
                'late_deduction_application_period' => $assignment->late_deduction_application_period
            ];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;
    }


    /**
     *
     * Display the specified resource
     *
     * @param Assignment $assignment
     * @return Assignment
     */
    public function getAssignmentSummary(Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentSummary', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $role = Auth::user()->role;
        try {
            $assignment = Assignment::find($assignment->id);
            $can_view_assignment_statistics = in_array($role, [2, 4])
                || ($role === 3 && $assignment->students_can_view_assignment_statistics);


            $response['assignment'] = [
                'name' => $assignment->name,
                'instructions' => $assignment->instructions,
                'total_points' => $this->getTotalPoints($assignment),
                'can_view_assignment_statistics' => $can_view_assignment_statistics,
                'submission_count_percent_decrease' => $assignment->submission_count_percent_decrease,
                'assessment_type' => $assignment->assessment_type,
                'solutions_released' => $assignment->solutions_released,
                'scores_released' => $assignment->scores_released,
                'students_can_view_assignment_statistics' => $assignment->students_can_view_assignment_statistics,
                'include_in_weighted_average' => $assignment->include_in_weighted_average,
                'late_policy' => $this->formatLatePolicy($assignment),
                'due' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assignment->due, Auth::user()->time_zone),
                'available_on' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assignment->available_from, Auth::user()->time_zone),
            ];

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;
    }

    public function formatLatePolicy($assignment)
    {
        $late_policy ='';
        $late_policy_deadline = ($assignment->late_policy !== 'not accepted')
            ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assignment->late_policy_deadline, Auth::user()->time_zone)
            : '';
        switch ($assignment->late_policy) {
            case('not accepted'):
                $late_policy = "No late assignments are accepted.";
                break;
            case('marked late'):
                $late_policy = "Late assignments are marked late.  It is up to the instructor's discretion whether to apply a late penalty.";
                break;
            case('deduction'):
                if ($assignment->late_deduction_application_period === 'once') {
                    $late_policy = "A deduction of {$assignment->late_deduction_percent}% is applied once to any late assignment.";
                } else {
                    $late_policy = "A deduction of {$assignment->late_deduction_percent}% is applied every {$assignment->late_deduction_application_period} to any late assignment.";
                }
                break;
        }
        if ($assignment->late_policy !== 'not accepted') {
            $late_policy .= "  Students cannot submit assessments later than $late_policy_deadline.";
        }

        return $late_policy;
    }

    public function getTimeLeft(Assignment $assignment)
    {
        $Extension = new Extension();
        $extensions_by_user = $Extension->getUserExtensionsByAssignment(Auth::user());
        $due = $extensions_by_user[$assignment->id] ?? $assignment->due;
        $now = Carbon::now();
        return max($now->diffInMilliseconds(Carbon::parse($due), false), 0);

    }

    public function getTotalPoints(Assignment $assignment)
    {
        return DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->sum('points');

    }


    /**
     * @param StoreAssignment $request
     * @param Assignment $assignment
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @return array
     * @throws Exception
     */
    public function update(StoreAssignment $request, Assignment $assignment, AssignmentGroupWeight $assignmentGroupWeight)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

            $data = $request->validated();
            $data['assessment_type'] = ($request->assessment_type && $request->source === 'a') ? $request->assessment_type : '';
            $data['instructions'] = $request->instructions ? $request->instructions : '';
            $data['available_from'] = $this->formatDateFromRequest($request->available_from_date, $request->available_from_time);
            $data['submission_files'] = ($data['source'] === 'a' && $request->assessment_type === 'delayed') ? $data['submission_files'] : 0;
            $data['due'] = $this->formatDateFromRequest($request->due_date, $request->due_time);
            $data['late_policy_deadline'] = $this->getLatePolicyDeadeline($request);
            $data['late_deduction_application_period'] = $this->getLateDeductionApplicationPeriod($request, $data);
            unset($data['available_from_date']);
            unset($data['available_from_time']);

            //submissions exist so don't let them change the things below
            $data['default_points_per_question'] = $this->getDefaultPointsPerQuestion($data);
            if ($assignment->hasFileOrQuestionSubmissions()) {
                unset($data['scoring_type']);
                unset($data['default_points_per_question']);
                unset($data['submission_files']);
                unset($data['assessment_type']);
            }

            DB::beginTransaction();
            $assignment->update($data);

            $this->addAssignmentGroupWeight($assignment, $data['assignment_group_id'], $assignmentGroupWeight);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>{$data['name']}</strong> has been updated.";
        } catch (Exception $e) {
            dB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>{$data['name']}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Delete an assignment
     *
     * @param Course $course
     * @param Assignment $assignment
     * @param Score $score
     * @return mixed
     * @throws Exception
     */
    public function destroy(Assignment $assignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::transaction(function () use ($assignment) {
                DB::table('assignment_question')->where('assignment_id', $assignment->id)->delete();
                DB::table('extensions')->where('assignment_id', $assignment->id)->delete();
                DB::table('scores')->where('assignment_id', $assignment->id)->delete();
                DB::table('submission_files')->where('assignment_id', $assignment->id)->delete();
                DB::table('submissions')->where('assignment_id', $assignment->id)->delete();
                DB::table('seeds')->where('assignment_id', $assignment->id)->delete();
                DB::table('cutups')->where('assignment_id', $assignment->id)->delete();
                $assignment->delete();
            });
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>$assignment->name</strong> has been deleted.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$assignment->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getLatePolicyDeadeline($request)
    {
        return $request->late_policy !== 'not accepted' ? $this->convertLocalMysqlFormattedDateToUTC($request->late_policy_deadline_date . ' ' . $request->late_policy_deadline_time, Auth::user()->time_zone) : null;
    }

    public function formatDateFromRequest($date, $time)
    {
        return $this->convertLocalMysqlFormattedDateToUTC("$date $time", Auth::user()->time_zone);
    }
}
