<?php

use App\Http\Controllers\Api\AccessoryController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CostController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FilterController;
use App\Http\Controllers\Api\InvestmentController;
use App\Http\Controllers\Api\InvestorController;
use App\Http\Controllers\Api\InvestorMonthlySalaryController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('admin')->group(function () {
    Route::post('login', [LoginController::class, 'login'])->name('admin.login');
    Route::post('register', [LoginController::class, 'register'])->name('admin.register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('company')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('admin.company.index');
            Route::get('{id}', [AdminController::class, 'show'])->name('admin.company.show');
            Route::post('update/{id}', [AdminController::class, 'update'])->name('admin.company.update');
            Route::post('create', [AdminController::class, 'create'])->name('admin.company.create');
        });

        Route::post('logout', [LoginController::class, 'logout'])->name('admin.logout');
        Route::post('me', [LoginController::class, 'me'])->name('admin.me');        
    });
});

Route::prefix('employee')->group(function () {
    Route::post('login', [EmployeeAuthController::class, 'login'])->name('employee.login');

    Route::middleware(['auth:employee', 'tenant'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('employee.logout');
        Route::post('me', [LoginController::class, 'me'])->name('employee.me');
    });
});

Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');
Route::post('updateClient/{client}', [ClientController::class, 'updateClient']);
Route::post('investorSalaryUpdate/{id}', [InvestorController::class, 'investorSalaryUpdate']);

Route::middleware(['auth:sanctum', 'auth:employee'])->group(function(){

    Route::group(['middleware' => ['role:admin|SuperAdmin']], function () {

        Route::apiResources([
            'clients' => ClientController::class,
            'devices' => DeviceController::class,
            'accessories' => AccessoryController::class,
            'costs' => CostController::class,
            'investors' => InvestorController::class,
            'investments' => InvestmentController::class,
        ]);

        Route::auto('/filter', FilterController::class);
        Route::auto('/orders', OrderController::class);
        Route::auto('/profile', ProfileController::class);
        Route::auto('/dashboard', DashboardController::class);
        //Route::auto('/reports', ReportController::class);
    });

    Route::group(['middleware' => ['api','role:SuperAdmin']], function () {
		Route::apiResources([
            'roles' => RoleController::class,
            'permissions' => PermissionController::class,
            'admins' => AdminController::class,
        ]);
    });

    Route::post('logout', [LoginController::class, 'logout']);

});

Route::get('/artisan/{name}', function ($name) {
    $command = \Illuminate\Support\Facades\Artisan::call($name);
    return 'OK';
});

Route::get('createInvestorSalary', [InvestorMonthlySalaryController::class, 'createInvestorSalary']);
Route::auto('/reports', ReportController::class);
