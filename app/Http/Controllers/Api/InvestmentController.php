<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvestmentResource;
use App\Models\Balance;
use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request()->query('per_page', 20);
        $page =  request()->query('page', 1);
        $offset = ($page - 1) * $perPage;
        $total = Investment::count();

        $investments = InvestmentResource::collection(Investment::skip($offset)->take($perPage)->orderBy('id', 'DESC')->get()) ;
        $all = Investment::sum('amount') ;

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'all' => $all,
            'data' => $investments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'investor_id' => 'required|numeric',
            'amount' => 'required|numeric',
        ]);

         // Balance update minus
         DB::transaction(function () use ($request) {
            $balance = Balance::find(1);
            $summa = $balance->summa + $request->amount;
            $balance->update(['summa' => $summa]);
        });
        // Balance update end

        $data = Investment::create($request->all());

        return $this->check_data($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(Investment $investment)
    {
        $data = new InvestmentResource($investment) ;
        return $this->check_data($data) ;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Investment $investment)
    {
        $request->validate([
            'investor_id' => 'required|numeric',
            'amount' => 'required|numeric',
        ]);

        // Balance update minus
        DB::transaction(function () use ($request, $investment) {
            $balance = Balance::find(1);

            if($investment->amount > $request->amount)
            {
                $diff = $investment->amount - $request->amount ;
                $summa = $balance->summa - $diff;
            }
            elseif($investment->amount < $request->amount)
            {
                $diff = $request->amount - $investment->amount ;
                $summa = $balance->summa + $diff;
            }
            else
                $summa = $balance->summa;

            $balance->update(['summa' => $summa]);
        });
        // Balance update end

        $data = $investment->update($request->all());

        return $this->check_data($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Investment $investment)
    {
        $data = $investment->delete();

        return $this->check_data($data);
    }

    // Extra functions

    public function check_data($data){
        if (!$data )
            return response()->json(['status' => false, 'data' => null]);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
