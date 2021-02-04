<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Course;
use Faker\Generator as Faker;

$factory->define(Course::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => 'First Course',
        'start_date' => '2020-06-10',
        'shown' => 1,
        'end_date' => '2021-06-10',
        'public' => 1
    ];

});
