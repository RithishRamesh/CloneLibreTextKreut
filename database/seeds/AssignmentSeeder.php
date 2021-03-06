<?php

use Illuminate\Database\Seeder;
use App\Assignment;
use App\Course;
use Carbon\Carbon;

class AssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();
        $course = Course::find(1);
        $current_date = Carbon::now();

        for ($i = 0; $i <= 10; $i++):
            $current_date = $current_date->add('1 week');
            Assignment::create([
                'name' => $faker->text(15),
                'available_from' => $current_date->add(($i + 2) . ' weeks')->format('Y-m-d H:i:00'),
                'due' => $current_date->add(($i + 3) . ' weeks')->format('Y-m-d H:i:00'),
                'scoring_type' => 'p',
                'default_points_per_question' => 2,
                'course_id' => $course->id
            ]);
        endfor;
    }
}
