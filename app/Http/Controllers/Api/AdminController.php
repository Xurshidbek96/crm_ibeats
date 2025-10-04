<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request()->query('per_page', 20);
        $page =  request()->query('page', 1);
        $offset = ($page - 1) * $perPage;

        $clients = User::skip($offset)->take($perPage)->latest()->get();
        $total = User::count();

        return response()->json([
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'data' => $clients,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'surname' => 'required',
            'email' => 'nullable|email',
            'phone1' => 'required',
			'password' => 'required|min:8|max:20',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
             return response()->json(['status'=>false,'errors'=>$validator->errors()]);
        }

        $input = $request->only('name','surname','email','phone1','password');

        $input['role_id'] = 1;
        $user = User::create($input);

        return $this->check_data($user);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $admin)
    {
        return $this->check_data($admin);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $admin)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'surname' => 'required',
            // 'roles' => 'required',
            'email' => 'nullable|email',
            'phone1' => 'required',
			'password' => 'nullable|min:8|max:20',
            'confirm_password' => 'nullable|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>false,'errors'=>$validator->errors()]);
        }
        $input = $request->only('name','surname','email','phone1','password');
		
        //$input['password'] = Hash::make($input['password']);
		
        $update = $admin->update($input);
        // $admin->assignRole([2]);
		
		if (!$update) {
            return response()->json(['status'=>false,'errors'=>["Update error."]]);
        }

        return $this->check_data($admin);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $admin)
    {
        $data = $admin->delete();

        return $this->check_data($data);
    }

    // Extra functions

    public function check_data($data)
    {
        if (!$data)
            return response()->json(['status' => false, 'data' => null]);

        return response()->json(['status' => true, 'data' => $data]);
    }
}
