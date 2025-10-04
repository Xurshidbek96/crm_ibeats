<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $employees = Employee::with('roles')->get();
            return $this->successResponse($employees, 'Employees retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve employees: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $this->validateRequest($request, $this->getEmployeeValidationRules());
            
            $employee = Employee::create($validatedData);
            
            return $this->createdResponse($employee, 'Employee created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create employee: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $employee = Employee::with('roles')->findOrFail($id);
            return $this->successResponse($employee, 'Employee retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('Employee not found');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            
            $rules = $this->getEmployeeValidationRules();
            $rules['email'] = 'required|email|unique:employees,email,' . $id;
            
            $validatedData = $this->validateRequest($request, $rules);
            
            $employee->update($validatedData);
            
            return $this->successResponse($employee, 'Employee updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update employee: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->delete();
            
            return $this->successResponse(null, 'Employee deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete employee: ' . $e->getMessage());
        }
    }
}
