<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CustomTranslateMissing::class,
        \App\Console\Commands\CleanupDemoData::class,
        \App\Console\Commands\ConvertVideosToHLS::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    #[\Override]
    protected function schedule(Schedule $schedule)
    {
        // Demo cleanup disabled - data will not be automatically deleted after 8 hours
        // Uncomment below to enable automatic cleanup:
        // $schedule->command('demo:cleanup')
        //     ->hourly()
        //     ->when(function () {
        //         return config('app.demo_mode') == 1 || env('DEMO_MODE') == 1;
        //     });
        // $schedule->command('inspire')->hourly();

        // Release affiliate commissions (pending → available when available_date is reached)
        $schedule->command('affiliate:release-commissions')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    #[\Override]
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
