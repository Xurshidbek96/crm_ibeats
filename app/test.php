<?php

// 1. MODELS

// app/Models/User.php (Super Admin - public schema)
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    
    protected $connection = 'pgsql'; // public schema
    protected $guard = 'user'; // alohida guard
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }
}

// app/Models/Company.php (public schema)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $connection = 'pgsql'; // public schema
    
    protected $fillable = [
        'name',
        'schema_name',
        'domain',
        'owner_id',
        'subscription_plan',
        'subscription_expires_at',
        'is_active'
    ];

    protected $casts = [
        'subscription_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

// app/Models/Employee.php (Tenant schema)
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends Authenticatable
{
    use HasApiTokens;
    
    protected $guard = 'employee'; // alohida guard
    // Connection middleware orqali dynamic o'rnatiladi
    
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'position',
        'department',
        'hire_date',
        'salary',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'employee_roles');
    }

    public function hasPermission($permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function($query) use ($permission) {
                $query->where('name', $permission);
            })->exists();
    }

    public function hasRole($roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }
}

// app/Models/Role.php (Tenant schema)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description'
    ];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_roles');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}

// app/Models/Permission.php (Tenant schema)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group'
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

// app/Models/Device.php (Tenant schema)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'name',
        'model',
        'serial_number',
        'brand',
        'price',
        'quantity',
        'description',
        'category',
        'created_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}

// app/Models/Accessory.php (Tenant schema)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accessory extends Model
{
    protected $fillable = [
        'name',
        'type',
        'brand',
        'price',
        'quantity',
        'description',
        'created_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}

// app/Models/Order.php (Tenant schema)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total_amount',
        'status',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}

// app/Models/OrderItem.php (Tenant schema)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'item_type', // 'device' yoki 'accessory'
        'item_id',
        'item_name',
        'quantity',
        'price',
        'total'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Polymorphic relationship
    public function item()
    {
        if ($this->item_type === 'device') {
            return Device::find($this->item_id);
        } elseif ($this->item_type === 'accessory') {
            return Accessory::find($this->item_id);
        }
        return null;
    }
}

// 2. MIDDLEWARE

// app/Http/Middleware/SetTenantConnection.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;

class SetTenantConnection
{
    public function handle(Request $request, Closure $next)
    {
        $employee = $request->user('employee');
        
        if ($employee && $employee->getConnection()) {
            $tenantSchema = $employee->getConnection()->getConfig('search_path');
            $schemaName = explode(',', $tenantSchema)[0];
            
            // Barcha tenant modellar uchun connection o'rnatish
            $this->setTenantModelsConnection($schemaName);
        }

        return $next($request);
    }

    private function setTenantModelsConnection($schemaName)
    {
        $tenantModels = [
            \App\Models\Employee::class,
            \App\Models\Role::class,
            \App\Models\Permission::class,
            \App\Models\Device::class,
            \App\Models\Accessory::class,
            \App\Models\Order::class,
            \App\Models\OrderItem::class,
        ];

        foreach ($tenantModels as $model) {
            app($model)->setConnection('tenant');
        }
    }
}

// 3. AUTHENTICATION CONTROLLERS

// app/Http/Controllers/Auth/UserAuthController.php (Super Admin)
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)
                   ->where('is_active', true)
                   ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('admin-token', ['admin'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => $user->load('companies'),
                'token' => $token,
                'type' => 'admin'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data' => $request->user()->load('companies')
        ]);
    }
}

// app/Http/Controllers/Auth/EmployeeAuthController.php
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

// 4. COMPANY MANAGEMENT (Super Admin)

// app/Http/Controllers/Admin/CompanyController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\TenantService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    protected $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->middleware('auth:sanctum');
        $this->tenantService = $tenantService;
    }

    public function index(Request $request)
    {
        $companies = $request->user()->companies()->with('owner')->get();
        
        return response()->json([
            'data' => $companies
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|unique:companies,domain',
            'subscription_plan' => 'required|string'
        ]);

        $schemaName = 'tenant_' . uniqid();

        $company = $request->user()->companies()->create([
            'name' => $request->name,
            'schema_name' => $schemaName,
            'domain' => $request->domain,
            'subscription_plan' => $request->subscription_plan,
            'subscription_expires_at' => now()->addYear(),
            'is_active' => true
        ]);

        // Tenant schema yaratish
        $this->tenantService->createTenantSchema($schemaName);

        return response()->json([
            'message' => 'Company created successfully',
            'data' => $company
        ], 201);
    }

    public function show($id)
    {
        $company = request()->user()->companies()->findOrFail($id);
        
        return response()->json([
            'data' => $company
        ]);
    }

    public function update(Request $request, $id)
    {
        $company = $request->user()->companies()->findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|unique:companies,domain,' . $company->id,
            'subscription_plan' => 'sometimes|string',
            'is_active' => 'sometimes|boolean'
        ]);

        $company->update($request->all());

        return response()->json([
            'message' => 'Company updated successfully',
            'data' => $company
        ]);
    }

    public function destroy($id)
    {
        $company = request()->user()->companies()->findOrFail($id);
        
        // Tenant schema o'chirish
        $this->tenantService->dropTenantSchema($company->schema_name);
        
        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully'
        ]);
    }
}

// 5. TENANT CONTROLLERS (Employee foydalanishi uchun)

// app/Http/Controllers/Tenant/DeviceController.php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:employee', 'tenant']);
    }

    public function index()
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('devices.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $devices = Device::with('creator')->get();
        
        return response()->json([
            'data' => $devices
        ]);
    }

    public function store(Request $request)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('devices.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'serial_number' => 'required|string|unique:devices,serial_number',
            'brand' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100'
        ]);

        $device = Device::create(array_merge(
            $request->all(),
            ['created_by' => $user->id]
        ));

        return response()->json([
            'message' => 'Device created successfully',
            'data' => $device->load('creator')
        ], 201);
    }

    public function show($id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('devices.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $device = Device::with('creator')->findOrFail($id);
        
        return response()->json([
            'data' => $device
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('devices.update')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $device = Device::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'serial_number' => 'sometimes|string|unique:devices,serial_number,' . $device->id,
            'brand' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|max:100'
        ]);

        $device->update($request->all());

        return response()->json([
            'message' => 'Device updated successfully',
            'data' => $device->load('creator')
        ]);
    }

    public function destroy($id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('devices.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $device = Device::findOrFail($id);
        $device->delete();

        return response()->json([
            'message' => 'Device deleted successfully'
        ]);
    }
}

// 6. SERVICES

// app/Services/TenantService.php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class TenantService
{
    public function createTenantSchema($schemaName)
    {
        // Schema yaratish
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
        
        // Tenant connection o'rnatish
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
        
        // Tenant jadvallarini yaratish
        $this->createTenantTables($schemaName);
        $this->seedBasicData($schemaName);
    }

    public function dropTenantSchema($schemaName)
    {
        DB::statement("DROP SCHEMA IF EXISTS {$schemaName} CASCADE");
    }

    private function createTenantTables($schemaName)
    {
        DB::connection('tenant')->unprepared("
            -- Employees table
            CREATE TABLE employees (
                id SERIAL PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                position VARCHAR(100),
                department VARCHAR(100),
                hire_date DATE,
                salary DECIMAL(10, 2),
                is_active BOOLEAN DEFAULT true,
                remember_token VARCHAR(100),
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            );

            -- Roles table
            CREATE TABLE roles (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL,
                display_name VARCHAR(255),
                description TEXT,
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            );

            -- Permissions table
            CREATE TABLE permissions (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL,
                display_name VARCHAR(255),
                description TEXT,
                group_name VARCHAR(100),
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            );

            -- Employee roles table
            CREATE TABLE employee_roles (
                id SERIAL PRIMARY KEY,
                employee_id INTEGER REFERENCES employees(id) ON DELETE CASCADE,
                role_id INTEGER REFERENCES roles(id) ON DELETE CASCADE,
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW(),
                UNIQUE(employee_id, role_id)
            );

            -- Role permissions table
            CREATE TABLE role_permissions (
                id SERIAL PRIMARY KEY,
                role_id INTEGER REFERENCES roles(id) ON DELETE CASCADE,
                permission_id INTEGER REFERENCES permissions(id) ON DELETE CASCADE,
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW(),
                UNIQUE(role_id, permission_id)
            );

            -- Devices table
            CREATE TABLE devices (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                model VARCHAR(255) NOT NULL,
                serial_number VARCHAR(255) UNIQUE NOT NULL,
                brand VARCHAR(255) NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                quantity INTEGER DEFAULT 0,
                description TEXT,
                category VARCHAR(100),
                created_by INTEGER REFERENCES employees(id),
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            );

            -- Accessories table
            CREATE TABLE accessories (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(100),
                brand VARCHAR(255),
                price DECIMAL(10, 2) NOT NULL,
                quantity INTEGER DEFAULT 0,
                description TEXT,
                created_by INTEGER REFERENCES employees(id),
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            );

            -- Orders table
            CREATE TABLE orders (
                id SERIAL PRIMARY KEY,
                order_number VARCHAR(100) UNIQUE NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                customer_email VARCHAR(255),
                customer_phone VARCHAR(20),
                total_amount DECIMAL(10, 2) NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                notes TEXT,
                created_by INTEGER REFERENCES employees(id),
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            );

            -- Order items table
            CREATE TABLE order_items (
                id SERIAL PRIMARY KEY,
                order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
                item_type VARCHAR(20) NOT NULL, -- 'device' or 'accessory'
                item_id INTEGER NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                quantity INTEGER NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                total DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            );

            -- Personal access tokens table for employees
            CREATE TABLE personal_access_tokens (
                id SERIAL PRIMARY KEY,
                tokenable_type VARCHAR(255) NOT NULL,
                tokenable_id BIGINT NOT NULL,
                name VARCHAR(255) NOT NULL,
                token VARCHAR(64) UNIQUE NOT NULL,
                abilities TEXT,
                last_used_at TIMESTAMP,
                expires_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            );
        ");
    }

    private function seedBasicData($schemaName)
    {
        DB::connection('tenant')->table('permissions')->insert([
            // Device permissions
            ['name' => 'devices.view', 'display_name' => 'View Devices', 'group_name' => 'devices'],
            ['name' => 'devices.create', 'display_name' => 'Create Devices', 'group_name' => 'devices'],
            ['name' => 'devices.update', 'display_name' => 'Update Devices', 'group_name' => 'devices'],
            ['name' => 'devices.delete', 'display_name' => 'Delete Devices', 'group_name' => 'devices'],
            
            // Accessory permissions
            ['name' => 'accessories.view', 'display_name' => 'View Accessories', 'group_name' => 'accessories'],
            ['name' => 'accessories.create', 'display_name' => 'Create Accessories', 'group_name' => 'accessories'],
            ['name' => 'accessories.update', 'display_name' => 'Update Accessories', 'group_name' => 'accessories'],
            ['name' => 'accessories.delete', 'display_name' => 'Delete Accessories', 'group_name' => 'accessories'],
            
            // Order permissions
            ['name' => 'orders.view', 'display_name' => 'View Orders', 'group_name' => 'orders'],
            ['name' => 'orders.create', 'display_name' => 'Create Orders', 'group_name' => 'orders'],
            ['name' => 'orders.update', 'display_name' => 'Update Orders', 'group_name' => 'orders'],
            ['name' => 'orders.delete', 'display_name' => 'Delete Orders', 'group_name' => 'orders'],
            
            // Employee permissions
            ['name' => 'employees.view', 'display_name' => 'View Employees', 'group_name' => 'employees'],
            ['name' => 'employees.create', 'display_name' => 'Create Employees', 'group_name' => 'employees'],
            ['name' => 'employees.update', 'display_name' => 'Update Employees', 'group_name' => 'employees'],
            ['name' => 'employees.delete', 'display_name' => 'Delete Employees', 'group_name' => 'employees'],
            
            // Role permissions
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'group_name' => 'roles'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'group_name' => 'roles'],
            ['name' => 'roles.update', 'display_name' => 'Update Roles', 'group_name' => 'roles'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'group_name' => 'roles'],
        ]);

        // Basic roles
        $adminRoleId = DB::connection('tenant')->table('roles')->insertGetId([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full access to all features'
        ]);

        $managerRoleId = DB::connection('tenant')->table('roles')->insertGetId([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Limited administrative access'
        ]);

        $employeeRoleId = DB::connection('tenant')->table('roles')->insertGetId([
            'name' => 'employee',
            'display_name' => 'Employee',
            'description' => 'Basic employee access'
        ]);

        // Assign all permissions to admin role
        $allPermissions = DB::connection('tenant')->table('permissions')->pluck('id');
        foreach ($allPermissions as $permissionId) {
            DB::connection('tenant')->table('role_permissions')->insert([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId
            ]);
        }

        // Assign limited permissions to manager role
        $managerPermissions = DB::connection('tenant')->table('permissions')
            ->whereIn('name', [
                'devices.view', 'devices.create', 'devices.update',
                'accessories.view', 'accessories.create', 'accessories.update',
                'orders.view', 'orders.create', 'orders.update',
                'employees.view'
            ])->pluck('id');
        
        foreach ($managerPermissions as $permissionId) {
            DB::connection('tenant')->table('role_permissions')->insert([
                'role_id' => $managerRoleId,
                'permission_id' => $permissionId
            ]);
        }

        // Assign basic permissions to employee role
        $employeePermissions = DB::connection('tenant')->table('permissions')
            ->whereIn('name', [
                'devices.view',
                'accessories.view',
                'orders.view', 'orders.create'
            ])->pluck('id');
        
        foreach ($employeePermissions as $permissionId) {
            DB::connection('tenant')->table('role_permissions')->insert([
                'role_id' => $employeeRoleId,
                'permission_id' => $permissionId
            ]);
        }
    }
}

// 7. ROUTES

// routes/api.php
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Tenant\DeviceController;
use App\Http\Controllers\Tenant\AccessoryController;
use App\Http\Controllers\Tenant\OrderController;
use App\Http\Controllers\Tenant\EmployeeController as TenantEmployeeController;
use App\Http\Controllers\Tenant\RoleController;

// Super Admin Routes (Users)
Route::prefix('admin')->group(function () {
    Route::post('login', [UserAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [UserAuthController::class, 'logout']);
        Route::get('me', [UserAuthController::class, 'me']);
        Route::apiResource('companies', CompanyController::class);
    });
});

// Employee Routes (Tenant)
Route::prefix('employee')->group(function () {
    Route::post('login', [EmployeeAuthController::class, 'login']);
    
    Route::middleware(['auth:employee', 'tenant'])->group(function () {
        Route::post('logout', [EmployeeAuthController::class, 'logout']);
        Route::get('me', [EmployeeAuthController::class, 'me']);
        
        // Tenant resources
        Route::apiResource('devices', DeviceController::class);
        Route::apiResource('accessories', AccessoryController::class);
        Route::apiResource('orders', OrderController::class);
        Route::apiResource('employees', TenantEmployeeController::class);
        Route::apiResource('roles', RoleController::class);
    });
});

// 8. AUTHENTICATION CONFIGURATION

// config/auth.php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
        'employee' => [
            'driver' => 'sanctum',
            'provider' => 'employees',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'employees' => [
            'driver' => 'eloquent',
            'model' => App\Models\Employee::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];

// 9. ADDITIONAL TENANT CONTROLLERS

// app/Http/Controllers/Tenant/AccessoryController.php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use Illuminate\Http\Request;

class AccessoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:employee', 'tenant']);
    }

    public function index()
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('accessories.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $accessories = Accessory::with('creator')->get();
        
        return response()->json(['data' => $accessories]);
    }

    public function store(Request $request)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('accessories.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'brand' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'description' => 'nullable|string'
        ]);

        $accessory = Accessory::create(array_merge(
            $request->all(),
            ['created_by' => $user->id]
        ));

        return response()->json([
            'message' => 'Accessory created successfully',
            'data' => $accessory->load('creator')
        ], 201);
    }

    public function show($id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('accessories.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $accessory = Accessory::with('creator')->findOrFail($id);
        
        return response()->json(['data' => $accessory]);
    }

    public function update(Request $request, $id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('accessories.update')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $accessory = Accessory::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:100',
            'brand' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'description' => 'nullable|string'
        ]);

        $accessory->update($request->all());

        return response()->json([
            'message' => 'Accessory updated successfully',
            'data' => $accessory->load('creator')
        ]);
    }

    public function destroy($id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('accessories.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $accessory = Accessory::findOrFail($id);
        $accessory->delete();

        return response()->json(['message' => 'Accessory deleted successfully']);
    }
}

// app/Http/Controllers/Tenant/OrderController.php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Device;
use App\Models\Accessory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:employee', 'tenant']);
    }

    public function index()
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('orders.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $orders = Order::with(['creator', 'items'])->orderBy('created_at', 'desc')->get();
        
        return response()->json(['data' => $orders]);
    }

    public function store(Request $request)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('orders.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:device,accessory',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            // Calculate total amount
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                if ($item['type'] === 'device') {
                    $product = Device::findOrFail($item['id']);
                } else {
                    $product = Accessory::findOrFail($item['id']);
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'item_type' => $item['type'],
                    'item_id' => $product->id,
                    'item_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal
                ];
            }

            // Create order
            $order = Order::create([
                'order_number' => 'ORD-' . date('Y') . '-' . str_pad(Order::count() + 1, 6, '0', STR_PAD_LEFT),
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => $user->id
            ]);

            // Create order items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'data' => $order->load(['creator', 'items'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('orders.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::with(['creator', 'items'])->findOrFail($id);
        
        return response()->json(['data' => $order]);
    }

    public function update(Request $request, $id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('orders.update')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::findOrFail($id);
        
        $request->validate([
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'sometimes|email',
            'customer_phone' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:pending,processing,completed,cancelled',
            'notes' => 'nullable|string'
        ]);

        $order->update($request->all());

        return response()->json([
            'message' => 'Order updated successfully',
            'data' => $order->load(['creator', 'items'])
        ]);
    }

    public function destroy($id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('orders.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}

// app/Http/Controllers/Tenant/EmployeeController.php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:employee', 'tenant']);
    }

    public function index()
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('employees.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employees = Employee::with('roles')->get();
        
        return response()->json(['data' => $employees]);
    }

    public function store(Request $request)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('employees.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:employees,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'hire_date' => 'required|date',
            'salary' => 'nullable|numeric|min:0',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        $employee = Employee::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'position' => $request->position,
            'department' => $request->department,
            'hire_date' => $request->hire_date,
            'salary' => $request->salary,
            'is_active' => true
        ]);

        // Assign roles
        if ($request->role_ids) {
            $employee->roles()->attach($request->role_ids);
        }

        return response()->json([
            'message' => 'Employee created successfully',
            'data' => $employee->load('roles')
        ], 201);
    }

    public function show($id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('employees.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = Employee::with('roles.permissions')->findOrFail($id);
        
        return response()->json(['data' => $employee]);
    }

    public function update(Request $request, $id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('employees.update')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = Employee::findOrFail($id);
        
        $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:employees,email,' . $employee->id,
            'password' => 'sometimes|string|min:6',
            'phone' => 'sometimes|string|max:20',
            'position' => 'sometimes|string|max:100',
            'department' => 'sometimes|string|max:100',
            'hire_date' => 'sometimes|date',
            'salary' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        $updateData = $request->except(['password', 'role_ids']);
        
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $employee->update($updateData);

        // Update roles
        if ($request->has('role_ids')) {
            $employee->roles()->sync($request->role_ids);
        }

        return response()->json([
            'message' => 'Employee updated successfully',
            'data' => $employee->load('roles')
        ]);
    }

    public function destroy($id)
    {
        $user = auth('employee')->user();
        
        if (!$user->hasPermission('employees.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = Employee::findOrFail($id);
        
        // Prevent self-deletion
        if ($employee->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully']);
    }
}

// 10. MIDDLEWARE REGISTRATION

// app/Http/Kernel.php
protected $middlewareAliases = [
    // ... other middlewares
    'tenant' => \App\Http\Middleware\SetTenantConnection::class,
];

// 11. CONSOLE COMMANDS

// app/Console/Commands/CreateTenantCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TenantService;
use App\Models\User;
use App\Models\Company;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create {user_email} {company_name} {schema_name?}';
    protected $description = 'Create a new tenant for a user';

    public function handle(TenantService $tenantService)
    {
        $userEmail = $this->argument('user_email');
        $companyName = $this->argument('company_name');
        $schemaName = $this->argument('schema_name') ?: 'tenant_' . uniqid();

        $user = User::where('email', $userEmail)->first();
        
        if (!$user) {
            $this->error("User with email {$userEmail} not found!");
            return 1;
        }

        $company = $user->companies()->create([
            'name' => $companyName,
            'schema_name' => $schemaName,
            'subscription_plan' => 'basic',
            'subscription_expires_at' => now()->addYear(),
            'is_active' => true
        ]);

        $tenantService->createTenantSchema($schemaName);

        $this->info("Tenant created successfully!");
        $this->info("Company: {$company->name}");
        $this->info("Schema: {$company->schema_name}");
        
        return 0;
    }
}