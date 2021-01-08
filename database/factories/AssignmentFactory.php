<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Assignment;
use Faker\Generator as Faker;

$factory->define(Assignment::class, function (Faker $faker) {
    return ['course_id' => 1,
        'name' => 'First Assignment',
        'available_from' => '2020-06-10 09:00:00',
        'due' => '2027-06-12 09:00:00',
        'scoring_type' => 'p',
        'default_points_per_question' => 2,
        'default_open_ended_submission_type' => 'file',
        'assignment_group_id' => 1,
        'assessment_type' => 'delayed',
        'late_policy' => 'not accepted',
        'include_in_weighted_average' => 1
        ];
});
