<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call([
            UserSeeder::class,
            RoleSeeder::class,
            CourseSeeder::class,
            CourseAccessCodeSeeder::class,
            EnrollmentSeeder::class,
            AssignmentSeeder::class,
            GradeSeeder::class
        ]);
        //get the h5p questions
        Artisan::call('store:h5p');
    }
}
