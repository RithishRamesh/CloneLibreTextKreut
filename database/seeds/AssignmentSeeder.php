<?php

use Illuminate\Database\Seeder;
use App\Assignment;
use App\User;
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
        $user = User::find(1);
        $current_date = Carbon::now();

        for($i=0; $i<=10; $i++):
            $current_date = $current_date->add('1 week');
            Assignment::create([
                'name' => $faker->text(15),
                'available_on' => $current_date->add(($i+2) . ' weeks')->format('Y-m-d H:i:s'),
                'due_date' => $current_date->add(($i+3) . ' weeks')->format('Y-m-d H:i:s'),
                'course_id' => 1
            ]);
        endfor;
    }
}
