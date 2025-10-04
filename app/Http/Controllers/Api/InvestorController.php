<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvestorResource;
use App\Models\Investor;
use App\Models\InvestorMonthlySalary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorController extends Controller
{

    public function index()
    {
        $perPage = request()->query('per_page', 20);
        $page =  request()->query('page', 1);
        $offset = ($page - 1) * $perPage;
        $total = Investor::count();

        $investors = Investor::skip($offset)->take($perPage)->orderBy('id', 'DESC')->get();
        $allPercentage = Investor::sum('percentage') ;

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'allPercentage' => $allPercentage,
            'data' => $investors,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'passport' => 'nullable|size:9|unique:investors',
            'percentage' => 'numeric',
        ]);

        $data = Investor::create($request->all());

        return $this->check_data($data);
    }

    public function show(Investor $investor)
    {
        $data = new InvestorResource($investor) ;
        return $this->check_data($data) ;
    }

    public function update(Request $request, Investor $investor)
    {
        $request->validate([
            'name' => 'required',
            'passport' => 'nullable|size:9|unique:investors',
            'percentage' => 'numeric',
        ]);

        $data = $investor->update($request->all());

        return $this->check_data($data);
    }

    public function destroy(Investor $investor)
    {
        $data = $investor->delete();

        return $this->check_data($data);
    }

    public function investorSalaryUpdate(Request $request, $id)
    {
        $data = InvestorMonthlySalary::where('id', $id)->update([
            'status' => $request->status,
        ]);

        return $this->check_data($data);
    }

    // Extra functions
    public function check_data($data){
        if (!$data )
            return response()->json(['status' => false, 'data' => null]);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
