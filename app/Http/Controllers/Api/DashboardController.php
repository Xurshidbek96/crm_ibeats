<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Balance;
use App\Models\Cost;
use App\Models\Device;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\InvestorMonthlySalary;
use App\Models\Monthly;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseController
{

    public function getBalance()
    {
        $data = Balance::where('id', 1)->select('summa')->first();

        return $this->check_data($data);
    }

    public function getExpense()
    {
        $expense['summa'] = Cost::sum('amount');
        $expense['Boshqa'] = Cost::where('type', 'Boshqa')->sum('amount');
        $expense['Tannarx'] = Cost::where('type', 'Tannarx')->sum('amount');
        $expense['VozKechilgan'] = Cost::where('type', 'Voz kechilgan')->sum('amount');
        $data['investment'] = Investment::sum('amount');
        $data['money_received'] = Payment::sum('amount') +  Order::sum('initial_payment');
        $data['benefit'] = $data['money_received'] - $expense['summa'];
        $data['investors_salary'] = round(InvestorMonthlySalary::sum('amount'), 2);

        $data['expense'] = $expense;
        return $this->check_data($data);
    }

    public function getExpenseMonthly()
    {
        $data = [];

        if (isset($_GET['date'])) {
            $date = $_GET['date'];
            list($year, $month) = explode('-', $date);
            $parsedMonth = (int)$month; // Convert to integer
            $parsedYear = (int)$year;
        } else {
            $parsedMonth = date('m');
            $parsedYear = date('Y');
        }

        $expense['summa'] = Cost::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->sum('amount');

        $expense['summa'] = Cost::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->sum('amount');
        $expense['Boshqa'] = Cost::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->where('type', 'Boshqa')->sum('amount');
        $expense['Tannarx'] = Cost::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->where('type', 'Tannarx')->sum('amount');
        $expense['VozKechilgan'] = Cost::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->where('type', 'Voz kechilgan')->sum('amount');

        $data['investment'] = Investment::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->sum('amount');
        
        $cash_summa = Order::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->where('is_cash',1)->selectRaw('SUM(quantity * summa) as total_summa')->value('total_summa');
        
        $data['money_received'] = Payment::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->sum('amount') +  Order::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->sum('initial_payment');

        $data['benefit'] = $data['money_received'] + $cash_summa - $expense['summa'];
        $data['investors_salary'] = round(InvestorMonthlySalary::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)->sum('amount'), 2);

        $data['expense'] = $expense;

        return $this->check_data($data);
    }

    public function getExpenseYearly()
    {

        if (isset($_GET['year']))
            $year = $_GET['year'];
        else
            $year = date('Y');

        $expense['summa'] = Cost::whereYear('created_at', $year)->sum('amount');
        $expense['summa'] = Cost::whereYear('created_at', $year)->sum('amount');
        $expense['Boshqa'] = Cost::whereYear('created_at', $year)->where('type', 'Boshqa')->sum('amount');
        $expense['Tannarx'] = Cost::whereYear('created_at', $year)->where('type', 'Tannarx')->sum('amount');
        $expense['VozKechilgan'] = Cost::whereYear('created_at', $year)->where('type', 'Voz kechilgan')->sum('amount');

        $data['investment'] = Investment::whereYear('created_at', $year)->sum('amount');

        $data['money_received'] = Payment::whereYear('created_at', $year)->sum('amount') +  Order::whereYear('created_at', $year)->sum('initial_payment');

        $data['benefit'] = $data['money_received'] - $expense['summa'];
        $data['investors_salary'] = round(InvestorMonthlySalary::whereYear('created_at', $year)->sum('amount'), 2);

        $data['expense'] = $expense;

        return $this->check_data($data);
    }

    public function getExpenseInterval()
    {
        $date = $_GET['date'];

        $dateRange = explode(' - ', $date);
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        list($startYear, $startMonth, $startDay) = explode('-', $startDate);
        list($endYear, $endMonth, $endDay) = explode('-', $endDate);

        $startMonth = (int)$startMonth;
        $startYear = (int)$startYear;
        $startDay = (int)$startDay;

        $endMonth = (int)$endMonth;
        $endYear = (int)$endYear;
        $endDay = (int)$endDay;

        $data = [];

        $expense['summa'] = Cost::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->sum('amount');
        $expense['boshqa'] = Cost::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->where('type', 'Boshqa')->sum('amount');
        $expense['Tannarx'] = Cost::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->where('type', 'Tannarx')->sum('amount');
        $expense['vozKechilgan'] = Cost::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->where('type', 'Voz kechilgan')->sum('amount');

        $data['investment'] = Investment::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->sum('amount');

        $data['money_received'] = Payment::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->sum('amount') +  Order::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->sum('initial_payment');

        $data['benefit'] = $data['money_received'] - $expense['summa'];
        $data['investors_salary'] = round(InvestorMonthlySalary::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->sum('amount'), 2);

        $data['expense'] = $expense;

        return $this->check_data($data);
    }

    public function report()
    {
        $report['devices'] = Order::count();
        $report['body_price'] = Order::sum('body_price');
        $report['summa'] = Order::sum('summa');
        $report['benefit'] = Order::sum('benefit');
        $report['pay_summa'] = Payment::sum('amount') + Order::sum('initial_payment');
        $report['rest_summa'] = Order::sum('rest_summa');

        return $this->checkDataResponse($report);
    }

    public function getReportMonthly()
    {
        $report = [];

        if (isset($_GET['date'])) {
            $date = $_GET['date'];
            list($year, $month) = explode('-', $date);
            $parsedMonth = (int)$month; // Convert to integer
            $parsedYear = (int)$year;
        } else {
            $parsedMonth = date('m');
            $parsedYear = date('Y');
        }

        $order = Order::whereMonth('startDate', $parsedMonth)
            ->whereYear('startDate', $parsedYear);

        $report['devices'] = $order->count();
        
        $report['body_price'] = $order->// sum('body_price');
        selectRaw('SUM(quantity * body_price) as total_body_price')->value('total_body_price') ?? 0;
        
        $report['summa'] = $order->//sum('summa');
        selectRaw('SUM(quantity * summa) as total_summa')->value('total_summa') ?? 0;
       
        $report['benefit'] = $order->sum('benefit');

        $report['pay_summa'] = DB::table('monthlies')
            ->leftJoin('payments', 'payments.monthly_id', '=', 'monthlies.id')
            ->whereMonth('monthlies.created_at', $parsedMonth)
            ->whereYear('monthlies.created_at', $parsedYear)
            ->sum('payments.amount') + $order->sum('initial_payment');

        $report['rest_summa'] =  $order->sum('rest_summa');

        return $this->check_data($report);
    }

    public function getReportYearly()
    {
        $report = [];

        if (isset($_GET['year']))
            $year = $_GET['year'];
        else
            $year = date('Y');

        $order = Order::whereYear('startDate', $year);

        $report['devices'] = $order->count();
        
        $report['body_price'] = $order->selectRaw('SUM(quantity * body_price) as total_body_price')->value('total_body_price') ?? 0;
        
        $report['summa'] = $order->selectRaw('SUM(quantity * summa) as total_summa')->value('total_summa') ?? 0;
       
        $report['benefit'] = $order->sum('benefit');
        $report['pay_summa'] = DB::table('monthlies')
            ->leftJoin('payments', 'payments.monthly_id', '=', 'monthlies.id')
            ->whereYear('monthlies.created_at', $year)->sum('payments.amount') + $order->sum('initial_payment');

        $report['rest_summa'] =  $order->sum('rest_summa');

        return $this->check_data($report);
    }

    public function getReportInterval()
    {
        $date = $_GET['date'];

        $dateRange = explode(' - ', $date);
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        list($startYear, $startMonth, $startDay) = explode('-', $startDate);
        list($endYear, $endMonth, $endDay) = explode('-', $endDate);

        $startMonth = (int)$startMonth;
        $startYear = (int)$startYear;
        $startDay = (int)$startDay;

        $endMonth = (int)$endMonth;
        $endYear = (int)$endYear;
        $endDay = (int)$endDay;

        $report = [];

        $order = Order::whereDate('startDate', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('startDate', '<=', "$endYear-$endMonth-$endDay");

        $report['devices'] = $order->count();

        $report['body_price'] = $order->selectRaw('SUM(quantity * body_price) as total_body_price')->value('total_body_price') ?? 0;
        
        $report['summa'] = $order->selectRaw('SUM(quantity * summa) as total_summa')->value('total_summa') ?? 0;
       
        $report['benefit'] =  $order->sum('benefit');

        $report['pay_summa'] = DB::table('monthlies')
            ->leftJoin('payments', 'payments.monthly_id', '=', 'monthlies.id')
            ->whereDate('monthlies.created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('monthlies.created_at', '<=', "$endYear-$endMonth-$endDay")
            ->sum('payments.amount') +  $order->sum('initial_payment');

        $report['rest_summa'] = $order->sum('rest_summa');

        return $this->check_data($report);
    }

    // Costs - Xarajatlar
    public function getCostMonthly()
    {
        $costMonthly = [];

        if (isset($_GET['date'])) {
            $date = $_GET['date'];
            list($year, $month) = explode('-', $date);
            $parsedMonth = (int)$month;
            $parsedYear = (int)$year;
        } else {
            $parsedMonth = date('m');
            $parsedYear = date('Y');
        }

        $costMonthly['costs'] = Cost::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)
            ->get();

        $costMonthly['summa'] = Cost::whereMonth('created_at', $parsedMonth)
            ->whereYear('created_at', $parsedYear)
            ->sum('amount');

        return $this->check_data($costMonthly);
    }

    public function getCostYearly()
    {
        $costYearly = [];

        if (isset($_GET['year']))
            $year = $_GET['year'];
        else
            $year = date('Y');

        $costYearly['costs'] = Cost::whereYear('created_at', $year)
            ->get();

        $costYearly['summa'] = Cost::whereYear('created_at', $year)
            ->sum('amount');

        return $this->check_data($costYearly);
    }

    public function getCostInterval()
    {
        $date = $_GET['date'];

        $dateRange = explode(' - ', $date);
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        list($startYear, $startMonth, $startDay) = explode('-', $startDate);
        list($endYear, $endMonth, $endDay) = explode('-', $endDate);

        $startMonth = (int)$startMonth;
        $startYear = (int)$startYear;
        $startDay = (int)$startDay;

        $endMonth = (int)$endMonth;
        $endYear = (int)$endYear;
        $endDay = (int)$endDay;

        $costInterval = [];

        $costInterval['costs'] = Cost::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->get();

        $costInterval['summa'] = Cost::whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay")
            ->sum('amount');

        return $this->check_data($costInterval);
    }

    // Naqd hisobotlar
    public function getCashReport()
    {
        $type = $_GET['type'] ?? 'all';

        $query = Order::query()->where('is_cash', 1);

        return $this->cashType($type, $query);
    }

    public function getCashReportMonthly()
    {
        $type = $_GET['type'] ?? 'all';

        if (isset($_GET['date'])) {
            $date = $_GET['date'];
            list($year, $month) = explode('-', $date);
            $parsedMonth = (int)$month; // Convert to integer
            $parsedYear = (int)$year;
        } else {
            $parsedMonth = date('m');
            $parsedYear = date('Y');
        }

        $query = Order::query()->where('is_cash', 1)->whereMonth('startDate', $parsedMonth)
            ->whereYear('startDate', $parsedYear);

        return $this->cashType($type, $query);
    }

    public function getCashReportYearly()
    {
        $type = $_GET['type'] ?? 'all';

        if (isset($_GET['year']))
            $year = $_GET['year'];
        else
            $year = date('Y');

        $query = Order::query()->where('is_cash', 1)->whereYear('startDate', $year);

        return $this->cashType($type, $query);
    }

    public function getCashReportInterval()
    {
        $type = $_GET['type'] ?? 'all';
        $date = $_GET['date'];

        $dateRange = explode(' - ', $date);
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        list($startYear, $startMonth, $startDay) = explode('-', $startDate);
        list($endYear, $endMonth, $endDay) = explode('-', $endDate);

        $startMonth = (int)$startMonth;
        $startYear = (int)$startYear;
        $startDay = (int)$startDay;

        $endMonth = (int)$endMonth;
        $endYear = (int)$endYear;
        $endDay = (int)$endDay;

        $query = Order::query()->where('is_cash', 1)
        ->whereDate('startDate', '>=', "$startYear-$startMonth-$startDay")
        ->whereDate('startDate', '<=', "$endYear-$endMonth-$endDay");;
        
        return $this->cashType($type, $query);
    }

    public function cashType($type, $query)
    {
        if ($type == 'device') {
            $query = $query->where('type', 'device');
        }
        if ($type == 'accessory') {
            $query = $query->where('type', 'accessory');
        }
        return $this->cashReport($query);
    }

    public function cashReport($query)
    {
        $report['devices'] = $query->sum('quantity');        
        $report['body_price'] = $query->selectRaw('SUM(quantity * body_price) as total_body_price')->value('total_body_price') ?? 0;
        $report['summa'] = $query->selectRaw('SUM(quantity * summa) as total_summa')->value('total_summa') ?? 0;
        $report['benefit'] = $query->sum('benefit');
        // $report['pay_summa'] = Payment::sum('amount') + Order::sum('initial_payment');
        // $report['rest_summa'] = Order::sum('rest_summa');    

        return $this->check_data($report);
    }

    // Extra functions
    public function check_data($data)
    {
        if (!$data)
            return response()->json(['status' => false, 'data' => null]);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
