<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class EmployeeAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'company_schema' => 'required|string' // yoki company domain
        ]);

        // Company mavjudligini tekshirish
        $company = Company::where('schema_name', $request->company_schema)
                          ->where('is_active', true)
                          ->first();

        if (!$company) {
            return response()->json([
                'message' => 'Company not found or inactive'
            ], 404);
        }

        // Tenant connection o'rnatish
        $this->setTenantConnection($company->schema_name);

        // Employee topish
        $employee = Employee::on('tenant')
                           ->where('email', $request->email)
                           ->where('is_active', true)
                           ->with('roles.permissions')
                           ->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Employee uchun maxsus token
        $token = $employee->createToken('employee-token', [
            'employee',
            'company:' . $company->id,
            'schema:' . $company->schema_name
        ])->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'employee' => $employee,
                'company' => $company,
                'token' => $token,
                'type' => 'employee',
                'permissions' => $employee->roles->pluck('permissions')->flatten()->pluck('name')->unique()
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user('employee')->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $employee = $request->user('employee')->load('roles.permissions');
        
        return response()->json([
            'data' => [
                'employee' => $employee,
                'permissions' => $employee->roles->pluck('permissions')->flatten()->pluck('name')->unique()
            ]
        ]);
    }

    private function setTenantConnection($schemaName)
    {
        Config::set("database.connections.tenant", [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => $schemaName . ',public',
            'sslmode' => 'prefer',
        ]);

        DB::purge('tenant');
    }
}
