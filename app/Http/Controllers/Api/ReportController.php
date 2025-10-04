<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Cost;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class ReportController extends BaseController
{
    public function daily()
    {
        $today = date('Y-m-d', strtotime(now()));

        $cost = Cost::whereDate('created_at', $today)->sum('amount');
        $income_credit = Order::whereDate('startDate', $today)->where('is_cash', 0)->sum('initial_payment');
        $income_cash = Order::whereDate('startDate', $today)->where('is_cash', 1)->sum('summa');
        $monthly_payment = Payment::whereDate('date', $today)->sum('amount');

        $data = [
            'cost' => $cost,
            'income_credit' => $income_credit,
            'income_cash' => $income_cash,
            'monthly_payment' => $monthly_payment
        ];

        return $this->successResponse($data, 'Daily report retrieved successfully');
    }

    public function weekly() 
    {
        $start = Carbon::now()->startOfWeek();
        $end = Carbon::now()->endOfWeek();

        $cost = Cost::whereBetween('created_at', [$start,$end])->sum('amount');
        $income_credit = Order::whereBetween('startDate', [$start,$end])->where('is_cash',0)->sum('initial_payment');
        $income_cash = Order::whereBetween('startDate', [$start,$end])->where('is_cash',1)->sum('summa');
        $monthly_payment = Payment::whereBetween('date', [$start,$end])->sum('amount');

        $data = [
            'cost' => $cost,
            'income_credit' => $income_credit,
            'income_cash' => $income_cash,
            'monthly_payment' => $monthly_payment
        ];

        return $this->successResponse($data, 'Weekly report retrieved successfully');
    }

    public function monthly() 
    {
        $cost = Cost::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)->sum('amount');

        $income_credit = Order::whereMonth('startDate', Carbon::now()->month)
            ->whereYear('startDate', Carbon::now()->year)->where('is_cash',0)->sum('initial_payment');

        $income_cash = Order::whereMonth('startDate', Carbon::now()->month)
            ->whereYear('startDate', Carbon::now()->year)->where('is_cash',1)->sum('summa');

        $monthly_payment = Payment::whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)->sum('amount');

        $data = [
            'cost' => $cost,
            'income_credit' => $income_credit,
            'income_cash' => $income_cash,
            'monthly_payment' => $monthly_payment
        ];

        return $this->successResponse($data, 'Monthly report retrieved successfully');
    }

    public function yearly() 
    {
        $cost = Cost::whereYear('created_at', Carbon::now()->year)->sum('amount');
        $income_credit = Order::whereYear('startDate', Carbon::now()->year)->where('is_cash',0)->sum('initial_payment');
        $income_cash = Order::whereYear('startDate', Carbon::now()->year)->where('is_cash',1)->sum('summa');
        $monthly_payment = Payment::whereYear('date', Carbon::now()->year)->sum('amount');
        
        $data = [
            'cost' => $cost,
            'income_credit' => $income_credit,
            'income_cash' => $income_cash,
            'monthly_payment' => $monthly_payment
        ];

        return $this->successResponse($data, 'Yearly report retrieved successfully');
    }
}
