<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\storeQuestions::class,
        Commands\storeH5P::class,
        Commands\storeWebwork::class,
        Commands\DbBackup::class,
        Commands\sendAssignmentDueReminderEmails::class,
        Commands\dataShopToS3::class

    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {


        if (env('APP_ENV') === 'production') {
            $schedule->command('db:backup')->twiceDaily()
                ->emailOutputOnFailure('kreut@hotmail.com');

            $schedule->command('notification:sendAssignmentDueReminderEmails')->everyMinute()
                ->emailOutputOnFailure('kreut@hotmail.com');

            $schedule->command('dataShop:toS3')->twiceDaily()
                ->emailOutputOnFailure('kreut@hotmail.com');

           $schedule->command('notify:gradersForLateSubmissions')->Daily()
                ->emailOutputOnFailure('kreut@hotmail.com');

          $schedule->command('notify:gradersForDueAssignments',[3])->days([0,3,5])
                ->emailOutputOnFailure('kreut@hotmail.com');

            $schedule->command('notify:gradersForDueAssignments',[2])->days([0,4])
                ->emailOutputOnFailure('kreut@hotmail.com');
            $schedule->command('notify:gradersForDueAssignments',[7])->Daily()
                ->emailOutputOnFailure('kreut@hotmail.com');

            $schedule->command('notify:gradersForDueAssignments',[1])->days([0])
                ->emailOutputOnFailure('kreut@hotmail.com');


        }

        if (env('APP_ENV') === 'staging') {
            $schedule->command('s3:backup')->hourly()
                ->emailOutputOnFailure('kreut@hotmail.com');
        }

        $schedule->command('check:AssignTos')->twiceDaily()
            ->emailOutputOnFailure('kreut@hotmail.com');


        $schedule->command('notify:LatestErrors')->everyFiveMinutes()
            ->emailOutputOnFailure('kreut@hotmail.com');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
