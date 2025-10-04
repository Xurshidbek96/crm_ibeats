<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Accessory;
use App\Models\Device;

class FilterController extends BaseController
{

    // Client filter
    public function clients(Request $request)
    {
        $id = $request->get('id');
        $passport = $request->get('passport');
        $passport_status = $request->get('passport_status');
        $client = $request->get('client');

        $data = DB::table('clients');
        if ($id != null) $data = $data->where('id', $id);
        if ($passport != null) $data = $data->where('passport', $passport);
        if ($passport_status != null) $data = $data->where('passport_status', $passport_status);
        if ($client != null) $data = $data->where('name', 'like', '%' . $client . '%')
            ->orWhere('surname', 'like', '%' . $client . '%')
            ->orWhere('phones', 'like', '%' . $client . '%');

        $data = $data->orderBy('id', 'DESC')->get();
        $result = $data->count();

        return $this->successResponse(['result' => $result, 'data' => $data], 'Clients filtered successfully');
    }

    // Admin filter
    public function admins(Request $request)
    {
        $id = $request->get('id');
        $passport = $request->get('passport');
        $phone = $request->get('phone');

        $data = DB::table('users');
        if ($id != null) $data = $data->where('id', $id);
        if ($passport != null) $data = $data->where('passport', $passport);
        if ($phone != null) $data = $data->where('phone1', 'like', '%' . $phone . '%')
            ->orWhere('phone2', 'like', '%' . $phone . '%');

        $data = $data->orderBy('id', 'DESC')->get();
        $result = $data->count();

        return $this->successResponse(['result' => $result, 'data' => $data], 'Admins filtered successfully');
    }

    // Device filter
    public function devices(Request $request)
    {
        $id = $request->get('id');
        $imei = $request->get('imei');
        $model = $request->get('model');
        $provider = $request->get('provider');
        $account = $request->get('account');
        $status = $request->get('status');

        $data = Device::query();
        if ($id != null) $data = $data->where('id', $id);
        if ($imei != null) $data = $data->where('imei', 'like', '%' . $imei . '%');
        if ($model != null) $data = $data->where('model', 'like', '%' . $model . '%');
        if ($provider != null) $data = $data->where('provider', 'like', '%' . $provider . '%');
        if ($account != null) $data = $data->where('account', 'like', '%' . $account . '%');
        if ($status != null) {
            if ($status == 'cash') {
                $data = $data->whereHas('order', function ($query) {
                    $query->where('is_cash', 1)->where('type', 'device');
                });
            }
            if ($status == 'credit') {
                $data = $data->whereHas('order', function ($query) {
                    $query->where('is_cash', 0)->where('type', 'device');
                });
            }
            if ($status == 'in_stock') {
                $data = $data->where('status', 0);
            }
        }

        $data = $data->orderBy('id', 'DESC')->get();
        $result = $data->count();

        return $this->successResponse(['result' => $result, 'data' => $data], 'Devices filtered successfully');
    }

    // Investor filter
    public function investors(Request $request)
    {
        $id = $request->get('id');
        $name = $request->get('name');
        $passport = $request->get('passport');
        $phone = $request->get('phone');
        $percentage = $request->get('percentage');

        $data = DB::table('investors');
        if ($id != null) $data = $data->where('id', $id);
        if ($name != null) $data = $data->where('name', 'like', '%' . $name . '%');
        if ($passport != null) $data = $data->where('passport', 'like', '%' . $passport . '%');
        if ($phone != null) $data = $data->where('phone', 'like', '%' . $phone . '%');
        if ($percentage != null) $data = $data->where('percentage', 'like', '%' . $percentage . '%');

        $data = $data->orderBy('id', 'DESC')->get();
        $result = $data->count();

        return $this->successResponse(['result' => $result, 'data' => $data], 'Investors filtered successfully');
    }

    // Cost filter
    public function costs(Request $request)
    {
        $id = $request->get('id');
        $name = $request->get('name');
        $amount = $request->get('amount');
        $type = $request->get('type');
        $date = $request->get('date');

        if ($date) {
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
        }

        $data = DB::table('costs');
        if ($id != null) $data = $data->where('id', $id);
        if ($name != null) $data = $data->where('name', 'like', '%' . $name . '%');
        if ($amount != null) $data = $data->where('amount', 'like', '%' . $amount . '%');
        if ($type != null) $data = $data->where('type',  $type);
        if ($date != null) $data = $data->whereDate('created_at', '>=', "$startYear-$startMonth-$startDay")
            ->whereDate('created_at', '<=', "$endYear-$endMonth-$endDay");

        $data = $data->orderBy('id', 'DESC')->get();
        $result = $data->count();

        return $this->successResponse(['result' => $result, 'data' => $data], 'Costs filtered successfully');
    }
    
    // Accessories filter
    public function accessories(Request $request)
    {
        $id = $request->get('id');
        $seria_number = $request->get('seria_number');
        $model = $request->get('model');
        $provider = $request->get('provider');
        $account = $request->get('account');
        $status = $request->get('status');

        $data = Accessory::query();
        if ($id != null) $data = $data->where('id', $id);
        if ($seria_number != null) $data = $data->where('seria_number', 'like', '%' . $seria_number . '%');
        if ($model != null) $data = $data->where('model', 'like', '%' . $model . '%');
        if ($provider != null) $data = $data->where('provider', 'like', '%' . $provider . '%');
        if ($account != null) $data = $data->where('account', 'like', '%' . $account . '%');
        if ($status != null) {
            if ($status == 'cash') {
                $data = $data->whereHas('order', function ($query) {
                    $query->where('is_cash', 1)->where('type', 'accessory');
                });
            }
            if ($status == 'credit') {
                $data = $data->whereHas('order', function ($query) {
                    $query->where('is_cash', 0)->where('type', 'accessory');
                });
            }
            if ($status == 'in_stock') {
                $data = $data->where('status', 0);
            }
        }

        $data = $data->orderBy('id', 'DESC')->get();
        $result = $data->count();

        return $this->successResponse(['result' => $result, 'data' => $data], 'Accessories filtered successfully');
    }
}

