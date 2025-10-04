<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dd(
            date('Y-m-d', strtotime(Carbon::now()->startOfWeek())),
            date('Y-m-d', strtotime(Carbon::now()->endOfWeek()))
        );
    }
}
