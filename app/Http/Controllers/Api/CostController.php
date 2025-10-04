<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CostResource;
use App\Models\Balance;
use App\Models\Cost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostController extends Controller
{

    public function index()
    {
        $perPage = request()->query('per_page', 20);
        $page =  request()->query('page', 1);
        $offset = ($page - 1) * $perPage;
        $total = Cost::count();

        $costs = CostResource::collection(Cost::skip($offset)->take($perPage)->orderBy('id', 'DESC')->get()) ;
        $allSumma = Cost::sum('amount') ;

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'allSumma' => $allSumma,
            'data' => $costs,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'name' => 'required',
            'amount' => 'numeric',
        ]);

        // Balance update minus
        DB::transaction(function () use ($request) {
            $balance = Balance::find(1);
            $summa = $balance->summa - $request->amount;
            $balance->update(['summa' => $summa]);
        });
        // Balance update end

        $data = Cost::create($request->all());

        return $this->check_data($data);
    }

    public function show(Cost $cost)
    {
        return $this->check_data(new CostResource($cost));
    }

    public function update(Request $request, Cost $cost)
    {
        $request->validate([
            'type' => 'required',
            'name' => 'required',
            'amount' => 'numeric',
        ]);

        // Balance update minus
        DB::transaction(function () use ($request, $cost) {
            $balance = Balance::find(1);
            if($cost->amount > $request->amount)
            {
                $diff = $cost->amount - $request->amount ;
                $summa = $balance->summa - $diff;
            }
            elseif($cost->amount < $request->amount)
            {
                $diff = $request->amount - $cost->amount ;
                $summa = $balance->summa + $diff;
            }
            else
                $summa = $balance->summa;
            $balance->update(['summa' => $summa]);
        });
        // Balance update end

        $cost->update($request->all());

        return $this->check_data($cost);
    }

    public function destroy(Cost $cost)
    {
        $data = $cost->delete();

        return $this->check_data($data);
    }

    // Extra functions

    public function check_data($data){
        if (!$data )
            return response()->json(['status' => false, 'data' => null]);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
