<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cost;
use App\Models\Investor;
use App\Models\InvestorMonthlySalary;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InvestorMonthlySalaryController extends Controller
{
    public function createInvestorSalary()
    {
        $now = Carbon::now();
        // file_get_contents("https://api.telegram.org/bot6028159293:AAET-DsEn5aMG33E2Am7kKPngD1HC00b9z4/sendmessage?chat_id=760582517&text=Test Job");

        if(date('t') == date('d') and $now->format('H:i') == '23:59')
        {
            $firstDayOfMonth = $now->copy()->startOfMonth();
            $lastDayOfMonth = $now->copy()->endOfMonth();

            $payment = Payment::whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])->sum('amount') + Order::whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])->sum('initial_payment');

            $cost = Cost::whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth])->sum('amount');

            $benefit = $payment - $cost;

            $investors = Investor::all();

            foreach ($investors as $investor) {
                $fraction = ($investor->percentage * $benefit) / 100;

                InvestorMonthlySalary::create([
                    'investor_id' => $investor->id,
                    'amount' => $fraction,
                    'month' => date('Y-m'),
                    'status' =>  0
                ]);
            }
        }
    }
}
