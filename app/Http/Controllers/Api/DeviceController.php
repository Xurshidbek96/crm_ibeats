<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use App\Models\Cost;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Return_;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $perPage = request()->query('per_page', 20);
        // $page =  request()->query('page', 1);
        // $offset = ($page - 1) * $perPage;
        // $total = Device::count();

        $devices = Device::orderBy('created_at', 'desc')->with('order')->get();

        return response()->json([
            // 'current_page' => $page,
            // 'per_page' => $perPage,
            // 'total' => $total,
            // 'last_page' => ceil($total / $perPage),
            'data' => $devices,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'imei' => 'required|string|unique:devices',
            'model' => 'required',
            'provider' => 'required',
            'incoming_price' => 'required',
            // 'account' => 'email',
        ]);

        Cost::create([
            'amount' => $request->incoming_price,
            'name' => 'Telefon',
            'type' => 'Tannarx',
            'model' => $request->model,
            'imei' => $request->imei
        ]);

        DB::transaction(function () use ($request) {
            $balance = Balance::find(1);
            $summa = $balance->summa - $request->incoming_price;
            $balance->update(['summa' => $summa]);
        });
        
        $data = Device::create($request->all());

        return $this->check_data($data);
    }

    /**
     * Display the specified resource.
    */
    public function show(Device $device)
    {
        return $this->check_data($device);
    }

    /**
     * Update the specified resource in storage.
    */
    public function update(Request $request, Device $device)
    {
        $request->validate([
            'imei' => 'string',
            'model' => 'required',
            'provider' => 'required',
            'incoming_price' => 'required',
            // 'account' => 'email',
        ]);
        
        Cost::where('imei', $request->seria_number)->first()->update([
            'amount' => $request->incoming_price,
            'name' => 'Telefon',
            'type' => 'Tannarx',
            'model' => $request->model,
            'imei' => $request->imei
        ]);

        if ($device->incoming_price != $request->incoming_price) {
            DB::transaction(function () use ($request,$device) {
                $balance = Balance::find(1);
                $summa = $balance->summa + ($device->incoming_price - $request->incoming_price);
                $balance->update(['summa' => $summa]);
            });
        }
        
        $device->update($request->all());

        return $this->check_data($device);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Device $device)
    {
        if($device->status == 0)
        {
            $data = $device->delete();
            return $this->check_data($data);
        }
        else
            return response()->json(['status' => false]);

    }

    // Extra functions

    public function check_data($data){
        if (!$data )
            return response()->json(['status' => false, 'data' => null]);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
