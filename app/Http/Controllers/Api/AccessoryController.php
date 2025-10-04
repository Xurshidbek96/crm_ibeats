<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Balance;
use App\Models\Cost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccessoryController extends Controller
{
    public function index()
    {
        $accessory = Accessory::orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $accessory,
        ]);

    }

    public function show(Accessory $accessory)
    {
        return $this->check_data($accessory);
    }

    public function store(Request $request)
    {
        $request->validate([
            'seria_number' => 'required|string|unique:accessories',
            'model' => 'required',
            'provider' => 'required',
            'incoming_price' => 'required',
            'quantity' => 'required|integer',
            // 'account' => 'email',
        ]);
        
        Cost::create([
            'amount' => $request->incoming_price * $request->quantity,
            'name' => 'Aksesuar',
            'type' => 'Tannarx',
            'model' => $request->model,
            'imei' => $request->seria_number
        ]);

        DB::transaction(function () use ($request) {
            $balance = Balance::find(1);
            $summa = $balance->summa - ($request->incoming_price * $request->quantity);
            $balance->update(['summa' => $summa]);
        });

        $data = Accessory::create($request->all());

        return $this->check_data($data);

    }

    public function update(Accessory $accessory, Request $request)
    {
        $request->validate([
            'seria_number' => 'string',
            'model' => 'required',
            'provider' => 'required',
            'incoming_price' => 'required',
            'quantity' => 'required',
            // 'account' => 'email',
        ]);

        Cost::where('imei', $accessory->seria_number)->where('type', 'Tannarx')->first()->update([
            'amount' => $request->incoming_price,
            'name' => 'Aksesuar',
            'type' => 'Tannarx',
            'model' => $request->model,
            'imei' => $request->seria_number
        ]);

        if (
            ($accessory->incoming_price != $request->incoming_price) || 
            ($request->quantity != $accessory->quantity)
        ) {
            DB::transaction(function () use ($request, $accessory) {
                $balance = Balance::find(1);
    
                $summa = $balance->summa + (($accessory->incoming_price * $accessory->quantity) - ($request->incoming_price * $request->quantity));
    
                $balance->update(['summa' => $summa]);
            });
        }

        $accessory->update($request->all());

        return $this->check_data($accessory);
    }

    public function delete(Accessory $accessory)
    {
        if($accessory->status == 0)
        {
            $data = $accessory->delete();
            return $this->check_data($data);
        }
        else
            return response()->json(['status' => false]);

    }

    public function check_data($data){
        if (!$data )
            return response()->json(['status' => false, 'data' => null]);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
