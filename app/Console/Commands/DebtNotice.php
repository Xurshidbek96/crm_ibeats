<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DebtNotice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debt:notice';

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
        [$one_day, $two_day, $third_day, $today] = [
            date('d', strtotime(Carbon::now()->addDays(3))), 
            date('d', strtotime(Carbon::now()->addDays(2))), 
            date('d', strtotime(Carbon::now()->addDays(1))), 
            date('d', strtotime(Carbon::now()))
        ];

        $time = 4;
        $month = date('Y-m', strtotime(Carbon::now()));
        $orders = Order::query()
            ->where('orders.is_cash',0)
            ->whereIn('pay_day', [$one_day, $two_day, $third_day, $today])
            ->join('monthlies', function ($query) use ($month) {
                $query->on('monthlies.order_id', '=', 'orders.id')
                    ->where('monthlies.month', $month)->whereNot('monthlies.status', 1);
            })
            ->join('clients', function ($query)  {
                $query->on('clients.id', '=', 'orders.client_id');
            })
            ->select('clients.phones')
            ->get();
        
        
        $phones = [];

        foreach ($orders as $order) {
            if (!is_null($order['phones'])) {
                $phones = array_merge($phones, json_decode($order['phones'], true));
            }
        }

        foreach ($phones as $phone) {
            for ($i = 0; $i <= $time; $i++) {
                SmsService::send($phone, 'Macintosh\'dan olgan qurilmangizga to\'lov qilishni unutmang!');
            }
        }
    }
}
