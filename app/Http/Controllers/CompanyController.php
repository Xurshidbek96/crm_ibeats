<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Company;
use App\Services\SendUpdateToBilling;

class CompanyController extends Controller
{
    public function __construct(public SendUpdateToBilling $updateService) {}

    public function index()
    {
        $query = Company::query()->where('client_id', auth()->id());

        if ($search = request('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        $perPage = request('per_page', 15);
        $page = request('page', 1);
        $companies = $query->paginate(
            perPage: $perPage, 
            page: $page
        );

        return response()->json(
            ['status' => true, 'data' => $companies],
            200
        );
    }

    public function show($id)
    {
        $company = Company::where('client_id', auth()->id())->findOrFail($id);
        return response()->json([
            'status' => true, 
            'data' => $company
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $company = Company::where('client_id', auth()->id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required',
            'domain' => 'sometimes|required|unique:companies,domain,' . $company->id,
            'colors' => 'nullable|array',
            'logo' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'domain', 'colors']);
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }
        
        $data['billing_id'] = $company->billing_id; // Ensure billing_id is preserved

        $this->updateService->send('update', 'company', $data);
        
        $company->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Company updated successfully',
            'data' => $company
        ]);
    }

    public function delete($id)
    {
        $company = Company::where('client_id', auth()->id())->findOrFail($id);

        // Optionally: drop tenant schema if needed
        // DB::statement("DROP SCHEMA IF EXISTS {$company->schema_name} CASCADE");
        $this->updateService->send('delete', 'company', ['billing_id' => $company->billing_id]);

        $company->delete();

        return response()->json([
            'status' => true,
            'message' => 'Company deleted successfully'
        ]);
    }

    public function create(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'domain' => 'required|unique:companies,domain',
            'colors' => 'nullable|array',
            'logo' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'domain', 'colors']);
        $data['client_id'] = auth()->id();
        $data['logo'] = $request->file('logo') ? $request->file('logo')->store('logos', 'public') : null;
        $data['company_uid'] = strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
        $schemaName = 'tenant_' . $data['company_uid'];
        $data['schema_name'] = $schemaName;

        $bill_id = $this->updateService->send('create', 'company', $data);

        $data['billing_id'] = $bill_id; // Store the billing ID

        $company = Company::create($data);

        $this->createTenantSchema($schemaName);

        return response()->json([
            'status' => true,
            'message' => 'Company created successfully',
            'data' => $company
        ], 201);
    }

    private function createTenantSchema(string $schemaName): void
    {
        // 1. Schema yaratish
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");

        // 2. Schema'ga o'tish
        DB::statement("SET search_path TO {$schemaName}");

        // 3. Tenant migration'larni ishlatish
        $migrationPath = database_path('migrations/tenant');

        if (is_dir($migrationPath)) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true
            ]);
        }

        // 4. Default ma'lumotlar
        $this->seedTenantDefaults($schemaName);

        // 5. Search path'ni qaytarish
        DB::statement("SET search_path TO public");
    }
}
