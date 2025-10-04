<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Cost;
use App\Models\Investor;
use App\Models\InvestorMonthlySalary;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;

class InvestorMonthlySalaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = Carbon::now();
        $firstDayOfMonth = $now->copy()->startOfMonth();
        $lastDayOfMonth = $now->copy()->endOfMonth();

        $payment = Payment::whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])->sum('amount') + Order::whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])->sum('initial_payment');

        $cost = Cost::whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])->sum('amount');

        $benefit = $payment - $cost ;

        $investors = Investor::all() ;

        foreach ($investors as $investor){
            $fraction = ($investor->percentage * $benefit) / 100 ;

            InvestorMonthlySalary::create([
                'investor_id' => $investor->id,
                'amount' => $fraction ,
                'month' => date('Y-m'),
                'status' =>  0
            ]);
        }
    }
}
