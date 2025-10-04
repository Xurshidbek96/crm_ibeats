<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SettingPermission;
use App\Models\SettingRole;
use App\Models\SettingAction;
use App\Models\User;
use App\Services\SendUpdateToBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(public SendUpdateToBilling $updateService) {}

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone1' => 'required|digits:9|unique:users,phone1',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }

        $input = $request->only(["name", "email", "phone1", "password"]);
        $input['password'] = bcrypt($input['password']);

        $bill_id = $this->updateService->send('create', 'client', $input);

        $input['billing_id'] = $bill_id; // Store the billing ID

        $user = User::create($input);
        
        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $success['name'] =  $user->name;

        return response()->json([
            'token' => $success['token'],
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->only('phone1', 'password'), [
            'phone1' => 'required|min:7|max:12',
            'password' => 'required|min:5|max:25'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }
        $credentials = $request->only('phone1', 'password');
        if (Auth::attempt(['phone1' => $credentials['phone1'], 'password' => $credentials['password']])) {
            $user = Auth::user();
            $role = Role::find($user->role_id);
            $user->role = $role;
            
			if($role->name == 'admin') {
				$permissions = SettingAction::get();
			}
			else		
            	$permissions = SettingPermission::where(['role_id' => $role->id])->with(['controller', 'action'])->get();

            $pers = [];
            foreach ($permissions as $k => $permission) {
                $pers[$k]['action'] = ($role->name == 'admin') ? $permission->code : $permission->action->code;
                $pers[$k]['subject'] = $permission->controller->name ?? '';
            }

            return response()->json([
                'token' => $user->createToken('LaravelSanctumAuth')->plainTextToken,
                'token_type' => 'Bearer',
                'user' => $user,
                'permissions' => $pers,
            ]);
        }

        return response()->json(['status' => false, 'errors' => ['Unauthorized']], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => true, 'message' => 'Logged out']);
    }
}
