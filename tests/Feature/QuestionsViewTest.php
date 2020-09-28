<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Extension;
use App\User;
use App\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionsViewTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->user_2 = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);
        $this->question = factory(Question::class)->create(['page_id' => 1]);
        $this->question_2 = factory(Question::class)->create(['page_id' => 2]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'points' => 10
        ]);
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'points' => 10
        ]);;

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        $this->student_user_2 = factory(User::class)->create();
        $this->student_user_2->role = 3;

        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);
        $this->h5pSubmission = [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'submission' =>   '{"actor":{"account":{"name":"5038b12a-1181-4546-8735-58aa9caef971","homePage":"https://h5p.libretexts.org"},"objectType":"Agent"},"verb":{"id":"http://adlnet.gov/expapi/verbs/answered","display":{"en-US":"answered"}},"object":{"id":"https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_embed&id=97","objectType":"Activity","definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":97},"name":{"en-US":"1.3 Actividad # 5: comparativos y superlativos"},"interactionType":"fill-in","type":"http://adlnet.gov/expapi/activities/cmi.interaction","description":{"en-US":"<p><strong>Instrucciones: Ponga las palabras en orden. Empiece con el sujeto de la oración.</strong></p>\n<br/>1. de todas las universidades californianas / la / antigua / es / La Universidad del Pacífico / más <br/>__________ __________ __________ __________ __________ __________.<br/><br/>2. el / UC Merced / número de estudiantes / tiene / menor<br/>__________ __________ __________ __________ __________."},"correctResponsesPattern":["La Universidad del Pacífico[,]es[,]la[,]más[,]antigua[,]de todas las universidades californianas[,]UC Merced[,]tiene[,]el[,]menor[,]número de estudiantes"]}},"context":{"contextActivities":{"category":[{"id":"http://h5p.org/libraries/H5P.DragText-1.8","objectType":"Activity"}]}},"result":{"response":"[,][,][,][,][,][,][,]antigua[,][,][,]","score":{"min":0,"raw":11,"max":11,"scaled":0},"duration":"PT3.66S","completion":true}}'
       ];

    }

    /** @test */

    public function must_submit_a_question_with_a_valid_technology()
    {
        $this->assignment->submission_files = '0';
        $this->assignment->save();
        $this->h5pSubmission['technology'] = 'bogus technology';
        $this->actingAs($this->student_user)->postJson("/api/submissions",     $this->h5pSubmission)->assertStatus(422);

    }
    /** @test */
    public function must_submit_a_question_with_a_valid_assignment_number()
    {
        $this->assignment->submission_files = '0';
        $this->h5pSubmission['assignment_id'] = false;
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions",
            $this->h5pSubmission)  ->assertStatus(422);

    }
/** @test */
    public function must_submit_a_question_with_a_valid_question_number()
    {
        $this->assignment->submission_files = '0';
        $this->assignment->save();
        $this->h5pSubmission['question_id'] = false;
        $this->actingAs($this->student_user)->postJson("/api/submissions",
            $this->h5pSubmission)  ->assertStatus(422);

    }


        /** @test */

    public function assignments_of_scoring_type_p_and_no_question_files_will_compute_the_score_based_on_the_question_points()
    {
        $this->assignment->submission_files = '0';
        $this->assignment->save();
        $this->actingAs($this->student_user)->postJson("/api/submissions",
            $this->h5pSubmission);


        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');
        $points_1 = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->get()
            ->pluck('points');


        $this->assertEquals(number_format($points_1[0],2), number_format($score[0],2), 'Score saved when student submits.');

        //do it again and it should update

        $this->actingAs($this->student_user)->postJson("/api/submissions", [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'submission' =>  $this->h5pSubmission['submission']]
        );

        $points_2 = DB::table('assignment_question')
            ->where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question_2->id)
            ->get()
            ->pluck('points');

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');

        $this->assertEquals(number_format($points_1[0] + $points_2[0],2), number_format($score[0],2), 'Score saved when student submits.');


    }

    /**@test* */

    public function score_is_computed_correctly_for_h5p()
    {

    }

    /** @test */

    public function score_is_computed_correctly_for_imathas()
    {

    }

    /**@test* */

    public function score_is_computed_correctly_for_webwork()
    {

    }

    /**@test* */

    public function the_associated_technology_is_valid()
    {


    }

    /**@test* */

    public function the_assignment_id_is_an_integer()
    {


    }

    /**@test* */

    public function the_question_id_is_an_integer()
    {


    }

    /**@test* */
    public function can_not_update_question_points_if_students_have_already_made_a_submission()
    {

//not sure if this is even a real thing: I have an update in the controller but nothing in the questions.get.vue?
    }


    /**@test* */

    public function the_submission_is_a_string()
    {


    }

    /** @test */

    public function assignments_of_scoring_type_c_will_count_the_number_of_submissions_and_compare_to_the_number_of_questions()
    {
        $this->assignment->scoring_type = 'c';
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions",   $this->h5pSubmission);

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->first();
        $this->assertEquals(null, $score, 'No assignment score saved in not completed assignment.');


        $this->actingAs($this->student_user)->postJson("/api/submissions", [
            'technology' => 'h5p',
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question_2->id,
            'submission' =>  $this->h5pSubmission['submission']])
            ->assertJson(['type' => 'success']);

        $score = DB::table('scores')->where('user_id', $this->student_user->id)
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->pluck('score');
        $this->assertEquals('C', $score[0], 'Assignment marked as completed when all questions are answered.');

    }


    /** @test */
    public function cannot_store_a_file_if_the_number_of_uploads_exceeds_the_max_number_of_uploads()
    {

    }

    /** @test */
    public function cannot_store_a_file_if_the_size_of_the_file_exceeds_the_max_size_permitted()
    {

    }

    /** @test */

    public function cannot_store_a_question_file_if_it_is_not_in_the_assignment()
    {


    }

    /** @test */

    public function cannot_store_a_question_file_if_it_has_the_wrong_type()
    {
//testing for question/assignment

    }

    /** @test */

    public function cannot_store_a_question_file()
    {


    }

    /** @test */

    public function can_toggle_question_files_if_you_are_the_owner()
    {


    }

    /** @test */

    public function cannot_toggle_question_files_if_you_are_not_the_owner()
    {


    }

    /** @test */

    public function will_mark_assignment_as_completed_if_number_of_questions_is_equal_to_number_of_questions()
    {


    }

    /** @test */
    public function can_submit_response()
    {

        $this->actingAs($this->student_user)->postJson("/api/submissions",  $this->h5pSubmission)
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function can_update_response()
    {

        ///to do ---- change the second one to see if the database actually updated!
        $this->actingAs($this->student_user)->postJson("/api/submissions",   $this->h5pSubmission);


        $this->actingAs($this->student_user)->postJson("/api/submissions",   $this->h5pSubmission)
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_submit_response_if_question_not_in_assignment()
    {
        $this->actingAs($this->student_user)->postJson("/api/submissions", [
            'assignment_id' => $this->assignment->id,
            'question_id' => 0,
            'submission' => 'some submission'])
            ->assertJson(['type' => 'error',
                'message' => 'That question is not part of the assignment.']);
    }

    /** @test */
    public function cannot_submit_response_if_user_not_enrolled_in_course()
    {
        $this->actingAs($this->student_user_2)->postJson("/api/submissions",   $this->h5pSubmission)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since the assignment is not part of your course.']);

    }

    /** @test */
    public function can_submit_response_if_assignment_past_due_has_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'extension' => '2027-01-01 09:00:00']);

        $this->actingAs($this->student_user)->postJson("/api/submissions", $this->h5pSubmission)
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_submit_response_if_assignment_past_due_and_no_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        $this->actingAs($this->student_user)->postJson("/api/submissions",  $this->h5pSubmission)
            ->assertJson(['type' => 'error', 'message' => 'No responses will be saved since the due date for this assignment has passed.']);

    }

    /** @test */
    public function cannot_submit_response_if_assignment_past_due_and_past_extension()
    {
        $this->assignment->due = "2001-03-05 09:00:00";
        $this->assignment->save();

        Extension::create(['user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'extension' => '2020-01-01 09:00:00']);

        $this->actingAs($this->student_user)->postJson("/api/submissions",  $this->h5pSubmission)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since your extension for this assignment has passed.']);

    }

    /** @test */
    public function cannot_submit_response_if_assignment_not_yet_available()
    {
        $this->assignment->available_from = "2035-03-05 09:00:00";
        $this->assignment->save();


        $this->actingAs($this->student_user)->postJson("/api/submissions",  $this->h5pSubmission)
            ->assertJson(['type' => 'error',
                'message' => 'No responses will be saved since this assignment is not yet available.']);

    }

    /** @test */
    public function can_get_titles_of_learning_tree()
    {
        $this->actingAs($this->user)->getJson("/api/libreverse/library/chem/page/21691/title")
            ->assertSeeText('Studying Chemistry');


    }

    /** @test */
    public function can_get_assignment_title_if_owner_course()
    {
        $this->actingAs($this->user)->getJson("/api/assignments/{$this->assignment->id}")
            ->assertJson(['name' => $this->assignment->name]);
    }

    /** @test */
    public function can_get_assignment_title_if_student_in_course()
    {
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}")
            ->assertJson(['name' => $this->assignment->name]);
    }

    /** @test */
    public function cannot_get_assignment_title_if_not_student_in_course()
    {
        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to access this assignment.']);
    }

    /** @test */
    public function can_get_assignment_questions_if_student_in_course()
    {
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/questions/view")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_get_assignment_questions_if_not_student_in_course()
    {
        $this->actingAs($this->student_user_2)->getJson("/api/assignments/{$this->assignment->id}/questions/view")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to access this assignment.']);

    }

    /** @test */
    public function can_remove_question_from_assignment_if_owner()
    {
        $this->actingAs($this->user)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'success']);

    }

    /** @test */
    public function cannot_remove_question_from_assignment_if_not_owner()
    {
        $this->actingAs($this->user_2)->deleteJson("/api/assignments/{$this->assignment->id}/questions/{$this->question->id}")
            ->assertJson(['type' => 'error',
                'message' => 'You are not allowed to remove a question from this assignment.']);
    }

    /** @test */
    public function can_view_page_if_grader_in_course()
    {


    }

}
