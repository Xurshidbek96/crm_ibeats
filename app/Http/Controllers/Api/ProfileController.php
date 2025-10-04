<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class ProfileController extends BaseController
{
    public function get_showProfile()
    {
        $id = auth()->user()->id;
        $user = User::find($id);

        return $this->checkDataResponse($user, 'Profile retrieved successfully', 'User not found');
    }

    public function put_update(Request $request)
    {
        try {
            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Password::defaults()],
            ]);

            $user = $request->user()->update([
                'password' => Hash::make($validated['password']),
            ]);

            return $this->successResponse($user, 'Password updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update password: ' . $e->getMessage());
        }
    }
}
