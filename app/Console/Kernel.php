<?php

namespace App\Console;

use App\Jobs\LogFromCRONJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

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

        $schedule->command('notify:LatestErrors')->everyFiveMinutes();

        if (env('APP_ENV') === 'production') {
            if (!env('APP_VAPOR')) {
                $schedule->command('db:backup')->twiceDaily();
            }

            $schedule->command('notification:sendAssignmentDueReminderEmails')->everyMinute();

            $schedule->command('dataShop:toS3')->twiceDaily();

            $schedule->command('notify:BetaCourseApprovals')->daily();
            /* grader notifications */
            $schedule->command('notify:gradersForDueAssignments')->hourly();
            $schedule->command('notify:gradersForLateSubmissions')->Daily();
            $schedule->command('notify:gradersReminders')->Daily();
            /* end grader notifications */
            $schedule->command('check:AssignTos')->twiceDaily();

        }

        if (env('APP_ENV') === 'dev') {
            $schedule->command('s3:backup')->hourly();
        }

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
