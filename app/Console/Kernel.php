<?php

namespace App\Console;

use App\Jobs\InvestorMonthlySalaryJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('debt:notice')->everySecond();//dailyAt('11:00');
        // $schedule->command('inspire')->hourly();
        // $schedule->job(new InvestorMonthlySalaryJob)->monthlyOn(date('t'), '23:59');
        // $schedule->job(new InvestorMonthlySalaryJob)->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
