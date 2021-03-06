<?php


namespace App\Traits;

use App\Assignment;
use App\AssignmentGroupWeight;
use App\AssignToGroup;
use App\AssignToTiming;
use App\AssignToUser;
use App\Question;
use App\Score;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait Test
{

    public function headers(){
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->student_user);
        return [
            'Accept' => 'application/json',
            'AUTHORIZATION' => 'Bearer ' . $token
        ];
    }
    public function createAssignTosFromGroups($assignment_info, $groups){
        $assign_tos= [
            [
                'groups' =>  $groups,
                'available_from' => '2020-06-10 09:00:00',
                'available_from_date' => '2020-06-10',
                'available_from_time' => '09:00:00',
                'due' => '2020-06-12 09:00:00',
                'due_date' => '2020-06-12',
                'due_time' => '09:00:00',
                'final_submission_deadline' => '2021-06-12 09:00:00',
                'final_submission_deadline_date' => '2021-06-12',
                'final_submission_deadline_time' => '09:00:00'
            ]
        ];
        $assignment_info['assign_tos'] = $assign_tos;
        foreach ( $assignment_info['assign_tos'][0]['groups'] as $key => $group) {
            $group_info = ["groups_$key" => [$groups],
                "due_$key" => '2020-06-12 09:00:00',
                "due_date_$key" => '2020-06-12',
                "due_time_$key" => '09:00:00',
                "available_from_$key" => '2020-06-10',
                "available_from_date_$key" => '2020-06-12',
                "available_from_time_$key" => '09:00:00',
                "final_submission_deadline_date_$key" => '2021-06-12',
                "final_submission_deadline_time_$key" => '09:00:00'];
            foreach ($group_info as $info_key => $info_value) {
                $assignment_info[$info_key] = $info_value;
            }
        }
        return $assignment_info;
    }

    public function assignUserToAssignment(int $assignment_id, string $group, int $group_id, int $student_user_id = 0)
    {
        $assignToTiming = new AssignToTiming();
        $assignToTiming->assignment_id = $assignment_id;
        $assignToTiming->available_from = Carbon::now();
        $assignToTiming->due = Carbon::now()->addHours(1);
        $assignToTiming->save();

        $assignToGroup = new AssignToGroup();
        $assignToGroup->group = $group;
        $assignToGroup->group_id = $group_id;
        $assignToGroup->assign_to_timing_id = $assignToTiming->id;
        $assignToGroup->save();

        if ($student_user_id) {
            $assignToUser = new AssignToUser();
            $assignToUser->user_id = $student_user_id;
            $assignToUser->assign_to_timing_id = $assignToTiming->id;
            $assignToUser->save();
        }
    }

    function question()
    {
        return $question = factory(Question::class)->create(['page_id' => rand(1, 1000000000)]);
    }


    function createAssignmentGroupWeightsAndAssignments()
    {


        //2 groups of assignments
        AssignmentGroupWeight::create([
            'course_id' => $this->course->id,
            'assignment_group_id' => 1,
            'assignment_group_weight' => 10
        ]);

        AssignmentGroupWeight::create([
            'course_id' => $this->course->id,
            'assignment_group_id' => 2,
            'assignment_group_weight' => 90
        ]);

        //GROUP 1
//assignment has 1 question
        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question()->id,
            'order' => 1,
            'open_ended_submission_type' => 'none',
            'points' => 2
        ]);


        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment->id,
            'open_ended_submission_type' => 'none',
            'score' => 2
        ]);


        //assignment 1 has 3 questions
        $this->assignment_1 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_1',
            'show_scores' => 1,
            'assignment_group_id' => 1
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_1->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 10
        ]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_1->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 20
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_1->id,
            'score' => 5
        ]);
        //Assignment 1: 5/30

        //GROUP 1
        //assignment 2 has 2 questions
        $this->assignment_2 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_2',
            'show_scores' => 1,
            'assignment_group_id' => 1
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_2->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 1
        ]);

        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_2->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 2
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_2->id,
            'score' => 2
        ]);

        //Assignment 2: 2/3

        //GROUP 2
        //assignment 3 has 2 questions
        $this->assignment_3 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_3',
            'show_scores' => 1,
            'assignment_group_id' => 2
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_3->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 50
        ]);


        DB::table('assignment_question')->insert([
            'assignment_id' => $this->assignment_3->id,
            'question_id' => $this->question()->id,
            'open_ended_submission_type' => 'none',
            'order' => 1,
            'points' => 50
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_3->id,
            'score' => 25
        ]);

        //Assignment 3: 25/100
        $this->assignment_4 = factory(Assignment::class)->create([
            'course_id' => $this->course->id,
            'name' => 'Assignment_4',
            'assignment_group_id' => 2,
            'source' => 'x',
            'show_scores' => 1,
            'external_source_points' => 100
        ]);

        Score::create([
            'user_id' => $this->student_user->id,
            'assignment_id' => $this->assignment_4->id,
            'score' => 75
        ]);

        //GROUP 1 scores: 5/30 and 2/3 weight of 10
        //GROUP 2 scores: 25/30 weight of 90
        //10*((2/2 + 5/30 + 2/3)/3)+90*(.5*(25/100 + 75/100))=51.11%

        //Leaving out $this->assignment
        ////10*(( 5/30 + 2/3)/2)+90*(.5*(25/100 + 75/100))=49.17%
    }


}
