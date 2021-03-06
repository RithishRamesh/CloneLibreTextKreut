<?php

namespace App\Http\Controllers;

use App\AssignmentSyncQuestion;
use App\AssignToGroup;
use App\AssignToTiming;
use App\BetaAssignment;
use App\BetaCourse;
use App\BetaCourseApproval;
use App\Course;
use App\FinalGrade;
use App\Http\Requests\UpdateCourse;
use App\School;
use App\Section;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use App\Enrollment;
use App\Http\Requests\StoreCourse;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatter;

use \Illuminate\Http\Request;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{

    use DateFormatter;


    public function updateIFrameProperties(Request $request, Course $course)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateIFrameProperties', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $item = $request->item;
            if (!in_array($item, ['attribution', 'assignment', 'submission'])) {
                $response['message'] = "$item is not a valid iframe property.";
                return $response;
            }
            $action = $request->action;
            if (!in_array($action, ['show', 'hide'])) {
                $response['message'] = "$action isn't a valid action.";
                return $response;
            }
            $value = ($action === 'show') ? 1 : 0;
            $assignments = DB::table('assignments')->where('course_id', $course->id)->get('id');
            $message = "This course has no assignments.";
            $type = "info";
            if ($assignments) {
                $assignment_ids = $assignments->pluck('id');
                DB::table('assignment_question')
                    ->whereIn('assignment_id', $assignment_ids)
                    ->update(["{$item}_information_shown_in_iframe" => $value]);
                $type = ($action === 'show') ? 'success' : 'info';
                $action_message = ($action === 'show') ? 'shown' : 'hidden';
                $message = "The $item information will now be $action_message when embedded in an iframe.";
            }
            $response['message'] = $message;
            $response['type'] = $type;
            return $response;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the iframe properties for your course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCommonsCourses()
    {
        $response['type'] = 'error';
        try {
            $commons_user = User::where('email', 'commons@libretexts.org')->first();
            $commons_courses = DB::table('courses')
                ->where('courses.user_id', $commons_user->id)
                ->where('shown', 1)
                ->select('id',
                    'courses.name AS name',
                    'courses.public_description AS description',
                    'alpha')
                ->get();
            $response['commons_courses'] = $commons_courses;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see get the courses from the Commons.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function updateBetaApprovalNotifications(Course $course)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('updateBetaApprovalNotifications', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $course->beta_approval_notifications = !$course->beta_approval_notifications;
            $course->save();
            $message_text = $course->beta_approval_notifications ? "now" : "no longer";
            $response['type'] = 'info';
            $response['message'] = "You will $message_text receive daily email notifications of pending approvals.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see whether this course is an Alpha course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getBetaApprovalNotifications(Course $course)
    {

        $response['type'] = 'error';
        try {
            $response['beta_approval_notifications'] = $course->beta_approval_notifications;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see whether this course is an Alpha course.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function isAlpha(Course $course)
    {
        $response['type'] = 'error';
        try {
            $response['alpha'] = $course->alpha;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see whether this course is an Alpha course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getLastSchool(Request $request, School $school)
    {
        $response['type'] = 'error';
        try {
            $school_name = '';
            $school_id = 1;
            if ($request->user()->role === 2) {
                $school = DB::table('courses')
                    ->join('schools', 'courses.school_id', '=', 'schools.id')
                    ->where('user_id', $request->user()->id)
                    ->orderBy('courses.created_at', 'desc')
                    ->first();
                if ($school && ($school->school_id !== 1)) {
                    $school_name = $school->name;
                    $school_id = $school->school_id;
                }
            }
            $response['last_school_name'] = $school_name;
            $response['last_school_id'] = $school_id;
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get your last school.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function getPublicCourses(Course $course, User $instructor = null)
    {

        $response['type'] = 'error';
        try {
            $response['public_courses'] = $instructor
                ? $course->where('public', 1)
                    ->where('user_id', $instructor->id)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get()
                : $course->where('public', 1)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get();
            $response['type'] = 'success';

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the public courses.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getCoursesAndAssignments(Request $request)
    {

        $response['type'] = 'error';
        $courses = [];
        $assignments = [];
        try {
            $results = DB::table('courses')
                ->join('assignments', 'courses.id', '=', 'assignments.course_id')
                ->where('courses.user_id', $request->user()->id)
                ->select(DB::raw('courses.id AS course_id'),
                    DB::raw('courses.name AS course_name'),
                    'lms',
                    DB::raw('assignments.id AS assignment_id'),
                    DB::raw('assignments.name AS assignment_name'))
                ->orderBy('courses.start_date', 'desc')
                ->get();
            $course_ids = [];
            foreach ($results as $value) {
                $course_id = $value->course_id;
                if (!in_array($course_id, $course_ids)) {
                    $courses[] = ['value' => $course_id,
                                'text' => $value->course_name,
                            'lms' => $value->lms];
                    $course_ids[] = $course_id;
                }
                $assignments[$course_id][] = ['value' => $value->assignment_id, 'text' => $value->assignment_name];
            }

            $response['type'] = 'success';
            $response['courses'] = $courses;
            $response['assignments'] = $assignments;
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get your courses and assignments.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Course $course
     * @param AssignmentGroup $assignmentGroup
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Enrollment $enrollment
     * @param FinalGrade $finalGrade
     * @param Section $section
     * @param School $school
     * @param BetaCourse $betaCourse
     * @return array
     * @throws Exception '
     */
    public function import(Request                $request,
                           Course                 $course,
                           AssignmentGroup        $assignmentGroup,
                           AssignmentGroupWeight  $assignmentGroupWeight,
                           AssignmentSyncQuestion $assignmentSyncQuestion,
                           Enrollment             $enrollment,
                           FinalGrade             $finalGrade,
                           Section                $section,
                           School                 $school,
                           BetaCourse             $betaCourse): array
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('import', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $import_as_beta = (int)$request->import_as_beta;
        if ($import_as_beta && !$course->alpha) {
            $response['message'] = "You cannot import this course as a Beta course since the original course is not an Alpha course.";
            return $response;
        }
        $school = $this->getLastSchool($request, $school);
        try {
            DB::beginTransaction();
            $imported_course = $course->replicate();
            $imported_course->name = "$imported_course->name Import";
            $imported_course->start_date = Carbon::now()->startOfDay();
            $imported_course->end_date = Carbon::now()->startOfDay()->addMonths(3);
            $imported_course->shown = 0;
            $imported_course->alpha = 0;
            $imported_course->lms = 0;
            $imported_course->school_id = $school['last_school_id'];
            $imported_course->show_z_scores = 0;
            $imported_course->students_can_view_weighted_average = 0;
            $imported_course->user_id = $request->user()->id;
            $imported_course->save();
            if ($import_as_beta) {
                $betaCourse->id = $imported_course->id;
                $betaCourse->alpha_course_id = $course->id;
                $betaCourse->save();
            }
            foreach ($course->assignments as $assignment) {
                $imported_assignment_group_id = $assignmentGroup->importAssignmentGroupToCourse($imported_course, $assignment);
                $assignmentGroupWeight->importAssignmentGroupWeightToCourse($course, $imported_course, $imported_assignment_group_id, false);
                $imported_assignment = $assignment->replicate();
                $imported_assignment->course_id = $imported_course->id;
                $imported_assignment->shown = 0;
                if ($imported_assignment->assessment_type !== 'real time') {
                    $imported_assignment->solutions_released = 0;
                }
                if ($imported_assignment->assessment_type === 'delayed') {
                    $imported_assignment->show_scores = 0;
                }
                $imported_assignment->students_can_view_assignment_statistics = 0;
                $imported_assignment->assignment_group_id = $imported_assignment_group_id;
                $imported_assignment->save();
                if ($import_as_beta) {
                    BetaAssignment::create([
                        'id' => $imported_assignment->id,
                        'alpha_assignment_id' => $assignment->id
                    ]);
                }
                $assignment->saveAssignmentTimingAndGroup($imported_assignment);
                $assignmentSyncQuestion->importAssignmentQuestionsAndLearningTrees($assignment->id, $imported_assignment->id);
            }

            $section->name = 'Main';
            $section->course_id = $imported_course->id;
            $section->crn = "To be determined";
            $section->save();
            $course->enrollFakeStudent($imported_course->id, $section->id, $enrollment);

            $finalGrade->setDefaultLetterGrades($imported_course->id);

            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "<strong>$imported_course->name</strong> has been imported.  </br></br>Don't forget to change the dates associated with this course and all of its assignments.";

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error importing the course.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function getImportable(Request $request, Course $course)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('getImportable', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $importable_courses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('public', 1)
                ->orWhere('user_id', $request->user()->id)
                ->select('name', 'first_name', 'last_name', 'courses.id')
                ->get();
            $formatted_importable_courses = [];
            foreach ($importable_courses as $course) {
                $course_info = "$course->name --- $course->first_name $course->last_name";
                if (!in_array($course_info, $formatted_importable_courses)) {
                    $formatted_importable_courses[] = [
                        'course_id' => $course->id,
                        'formatted_course' => "$course->name --- $course->first_name $course->last_name"
                    ];
                }
            }
            $response['type'] = 'success';
            $response['importable_courses'] = $formatted_importable_courses;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the importable courses.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function index(Request $request, Course $course)
    {

        $response['type'] = 'error';


        if ($request->session()->get('completed_sso_registration')) {
            \Log::info('Just finished registration.');
        }
        $authorized = Gate::inspect('viewAny', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['courses'] = $this->getCourses(auth()->user());
            $response['show_beta_course_dates_warning'] = !$request->hasCookie('show_beta_course_dates_warning');

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your courses.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function updateShowZScores(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateShowZScores', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response = $assignmentGroupWeight->validateCourseWeights($course);
        if ($response['type'] === 'error') {
            return $response;
        }
        try {

            $course->show_z_scores = !$request->show_z_scores;
            $course->save();

            $verb = $course->show_z_scores ? "can" : "cannot";
            $response['type'] = $course->show_z_scores ? 'success' : 'info';
            $response['message'] = "Students <strong>$verb</strong> view their z-scores.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the ability for students to view their z-scores.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public
    function updateStudentsCanViewWeightedAverage(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateStudentsCanViewWeightedAverage', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response = $assignmentGroupWeight->validateCourseWeights($course);
            if ($response['type'] === 'error') {
                return $response;
            }
            $course->students_can_view_weighted_average = !$request->students_can_view_weighted_average;
            $course->save();

            $verb = $course->students_can_view_weighted_average ? "can" : "cannot";
            $response['type'] = $course->students_can_view_weighted_average ? 'success' : 'info';
            $response['message'] = "Students <strong>$verb</strong> view their weighted averages.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the ability for students to view their weighted averages.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public
    function show(Course $course)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $course);
        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $response['course'] = [
                'school' => $course->school->name,
                'name' => $course->name,
                'public_description' => $course->public_description,
                'private_description' => $course->private_description,
                'term' => $course->term,
                'students_can_view_weighted_average' => $course->students_can_view_weighted_average,
                'letter_grades_released' => $course->finalGrades->letter_grades_released,
                'sections' => $course->sections,
                'show_z_scores' => $course->show_z_scores,
                'graders' => $course->graderInfo(),
                'start_date' => $course->start_date,
                'end_date' => $course->end_date,
                'public' => $course->public,
                'lms' => $course->lms,
                'alpha' => $course->alpha,
                'is_beta_course' => $course->isBetaCourse(),
                'beta_courses_info' => $course->betaCoursesInfo()];
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Course $course
     * @param int $shown
     * @return array
     * @throws Exception
     */
    public
    function showCourse(Course $course, int $shown): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showCourse', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $course->sections()->update(['access_code' => null]);
            $course->shown = !$shown;
            $course->save();

            $response['type'] = !$shown ? 'success' : 'info';
            $shown_message = !$shown ? 'can' : 'cannot';
            $is_commons_user = Auth::user()->email === 'commons@libretexts.org';
            $access_code_message = !$shown || $is_commons_user ? '' : '  In addition, all course access codes have been revoked.';
            $people = $is_commons_user ? "Visitors to the Commons " : "Your students";
            $response['message'] = "$people <strong>{$shown_message}</strong> view this course.{$access_code_message}";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error showing <strong>{$course->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param $user
     * @return array|\Illuminate\Support\Collection
     */
    public
    function getCourses($user)
    {

        switch ($user->role) {
            case(2):
                return DB::table('courses')
                    ->select('courses.*', DB::raw("beta_courses.id IS NOT NULL AS is_beta_course"))
                    ->leftJoin('beta_courses', 'courses.id', '=', 'beta_courses.id')
                    ->where('user_id', $user->id)->orderBy('start_date', 'desc')
                    ->get();
            case(4):
                $sections = DB::table('graders')
                    ->join('sections', 'section_id', '=', 'sections.id')
                    ->where('user_id', $user->id)
                    ->get()
                    ->pluck('section_id');

                $course_section_info = DB::table('courses')
                    ->join('sections', 'courses.id', '=', 'sections.course_id')
                    ->select('courses.id AS id',
                        DB::raw('courses.id AS course_id'),
                        'start_date',
                        'end_date',
                        DB::raw('courses.name AS course_name'),
                        DB::raw('sections.name AS section_name')
                    )
                    ->whereIn('sections.id', $sections)->orderBy('start_date', 'desc')
                    ->get();

                $course_sections = [];
                foreach ($course_section_info as $course_section) {
                    if (!isset($course_sections[$course_section->course_id])) {
                        $course_sections[$course_section->course_id]['id'] = $course_section->course_id;
                        $course_sections[$course_section->course_id]['name'] = $course_section->course_name;
                        $course_sections[$course_section->course_id]['start_date'] = $course_section->start_date;
                        $course_sections[$course_section->course_id]['end_date'] = $course_section->end_date;
                        $course_sections[$course_section->course_id]['sections'] = [];
                    }
                    $course_sections[$course_section->course_id]['sections'][] = $course_section->section_name;
                }

                foreach ($course_sections as $key => $course_section) {
                    $course_sections[$key]['sections'] = implode(', ', $course_section['sections']);
                }
                $course_sections = array_values($course_sections);
                return collect($course_sections);

        }
    }

    /**
     * @param StoreCourse $request
     * @param Course $course
     * @param Enrollment $enrollment
     * @param FinalGrade $finalGrade
     * @param Section $section
     * @param School $school
     * @return array
     * @throws Exception
     */

    public
    function store(StoreCourse $request,
                   Course      $course,
                   Enrollment  $enrollment,
                   FinalGrade  $finalGrade,
                   Section     $section,
                   School      $school)
    {
        //todo: check the validation rules
        $response['type'] = 'error';
        $authorized = Gate::inspect('create', $course);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $data = $request->validated();
            DB::beginTransaction();
            $data['user_id'] = auth()->user()->id;
            $data['school_id'] = $this->getSchoolIdFromRequest($request, $school);
            $data['start_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['start_date'] . '00:00:00', auth()->user()->time_zone);
            $data['end_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['end_date'] . '00:00:00', auth()->user()->time_zone);
            $data['shown'] = 0;
            $data['public_description'] = $request->public_description;
            $data['private_description'] = $request->private_description;
            //create the main section
            $section->name = $data['section'];
            $section->crn = $data['crn'];
            unset($data['section']);
            unset($data['crn']);
            unset($data['school']);
            //create the course
            $new_course = $course->create($data);

            $section->course_id = $new_course->id;
            $section->save();
            $course->enrollFakeStudent($new_course->id, $section->id, $enrollment);
            $finalGrade->setDefaultLetterGrades($new_course->id);

            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$request->name</strong> has been created.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>$request->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getSchoolIdFromRequest(Request $request, School $school)
    {

        return $request->school
            ? $school->where('name', $request->school)->first()->id
            : $school->first()->id;
    }

    /**
     *
     * Update the specified resource in storage.
     *
     *
     * @param UpdateCourse $request
     * @param Course $course
     * @param School $school
     * @return mixed
     * @throws Exception
     */
    public
    function update(UpdateCourse $request,
                    Course       $course,
                    School       $school,
                    BetaCourse   $betaCourse)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('update', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $is_beta_course = $betaCourse->where('id', $course->id)->first();
        if ($message = $this->failsTetherCourseValidation($request, $course, $is_beta_course)) {
            $response['message'] = $message;
            return $response;
        }
        try {
            $data = $request->validated();
            DB::beginTransaction();
            $data['school_id'] = $this->getSchoolIdFromRequest($request, $school);
            $data['start_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['start_date'], auth()->user()->time_zone);
            $data['end_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['end_date'], auth()->user()->time_zone);
            $data['public_description'] = $request->public_description;
            $data['private_description'] = $request->private_description;
            if ($is_beta_course && $request->untether_beta_course) {
                $betaCourse->untether($course);
            }
            $course->update($data);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been updated.";
            $response['is_beta_course'] = $betaCourse->where('id', $course->id)->first();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param $request
     * @param $course
     * @param $is_beta_course
     * @return string
     */
    public function failsTetherCourseValidation($request, $course, $is_beta_course): string
    {
        $message = '';
        $at_least_one_beta_course_exists = BetaCourse::where('alpha_course_id', $course->id)->first();
        if ($course->alpha && (int)$request->alpha === 0 && $at_least_one_beta_course_exists) {
            $message = "You are trying to change an Alpha course into a non-Alpha course but Beta courses are currently tethered to this course.";
        }
        if ((int)$request->alpha === 1 && $is_beta_course) {
            $message = "You can't change a Beta course into an Alpha course.";
        }
        return $message;
    }

    /**
     *
     * Delete a course
     *
     * @param Course $course
     * @param AssignToTiming $assignToTiming
     * @param BetaAssignment $betaAssignment
     * @param BetaCourse $betaCourse
     * @param BetaCourseApproval $betaCourseApproval
     * @return array
     * @throws Exception
     */

    public
    function destroy(Course             $course,
                     AssignToTiming     $assignToTiming,
                     BetaAssignment     $betaAssignment,
                     BetaCourse         $betaCourse,
                     BetaCourseApproval $betaCourseApproval)

    {


        $response['type'] = 'error';

        $authorized = Gate::inspect('delete', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if (BetaCourse::where('alpha_course_id', $course->id)->first()) {
            $response['message'] = "You cannot delete an Alpha course with tethered Beta courses.";
            return $response;
        }

        try {
            DB::beginTransaction();
            foreach ($course->assignments as $assignment) {
                $assignment_question_ids = DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->get()
                    ->pluck('id');

                DB::table('assignment_question_learning_tree')
                    ->whereIn('assignment_question_id', $assignment_question_ids)
                    ->delete();
                $assignToTiming->deleteTimingsGroupsUsers($assignment);
                $assignment->questions()->detach();
                $assignment->submissions()->delete();
                $assignment->fileSubmissions()->delete();
                $assignment->scores()->delete();
                $assignment->cutups()->delete();
                $assignment->seeds()->delete();
                $assignment->graders()->detach();
                $betaAssignment->where('id', $assignment->id)->delete();
                $betaCourseApproval->where('beta_assignment_id', $assignment->id)->delete();
            }
            $course->extensions()->delete();
            $course->assignments()->delete();


            AssignmentGroupWeight::where('course_id', $course->id)->delete();
            AssignmentGroup::where('course_id', $course->id)->where('user_id', Auth::user()->id)->delete();//get rid of the custom assignment groups
            $course->enrollments()->delete();
            foreach ($course->sections as $section) {
                $section->graders()->delete();
                $section->delete();
            }

            $course->finalGrades()->delete();
            $betaCourse->where('id', $course->id)->delete();
            $course->delete();
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been deleted.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
