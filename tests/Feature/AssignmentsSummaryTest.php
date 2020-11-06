<?php

namespace Tests\Feature;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Grader;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssignmentsSummaryTest extends TestCase
{

    public function setup(): void
    {

        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->course = factory(Course::class)->create(['user_id' => $this->user->id]);
        $this->assignment = factory(Assignment::class)->create(['course_id' => $this->course->id]);

        $this->student_user = factory(User::class)->create();
        $this->student_user->role = 3;
        factory(Enrollment::class)->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id
        ]);

    }

    /** @test **/
    public function student_cannot_get_scores_info_if_students_can_view_assignment_statistics_is_false()
    {

        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/scores-info")
            ->assertJson(['type' => 'error',
                'message' => "You are not allowed to get these scores."]);

    }

    /** @test **/
    public function student_can_get_scores_info_if_students_can_view_assignment_statistics_is_true()
    {
$this->assignment->students_can_view_assignment_statistics = 1;
$this->assignment->save();
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/scores-info")
            ->assertJson(['type' => 'success']);

    }

    /** @test **/
    public function student_cannot_get_total_points_info_if_students_can_view_assignment_statistics_is_false()
    {

        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/total-points-info")
            ->assertJson(['type' => 'error',
                'message' => "You are not allowed to retrieve this summary."]);

    }

    /** @test **/
    public function student_can_get_total_points_info_if_students_can_view_assignment_statistics_is_true()
    {
        $this->assignment->students_can_view_assignment_statistics = 1;
        $this->assignment->save();
        $this->actingAs($this->student_user)->getJson("/api/assignments/{$this->assignment->id}/total-points-info")
            ->assertJson(['type' => 'success']);

    }

}
