<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Tariff;
use App\Services\SendUpdateToBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function __construct(public SendUpdateToBilling $updateService) {}

    public function index(Request $request)
    {
        $query = Invoice::query()->where('user_id', auth()->id());

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        $perPage = $request->input('perpage', 15);
        $page = $request->input('page', 1);

        $invoices = $query->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'status' => true,
            'message' => 'List of invoices',
            'data' => $invoices
        ]);
    }

    public function create()
    {
        $validator = Validator::make(request()->all(), [
            'tariff_id' => 'required|exists:tariffs,id',
            'company_uid' => 'required|exists:companies,company_uid',
            'provider' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
        
        $tariff = Tariff::find(request('tariff_id'));

        $discount = self::discountCounter($tariff['price'], $tariff['discount']);

        $data = [
            'tariff_id' => request('tariff_id'),
            'user_id' => auth()->id(),
            'company_id' => request('company_uid'),
            'discount_summa' => $discount['discount'],
            'discount_percentage' => $tariff->discount,
            'total_amount' => $discount['final'],
            'date' => now(),
            'provider' => request('provider'),
            'status' => 'pending'
        ];

        $bill_id = $this->updateService->send( 'create', 'invoice', $data);

        $data['billing_id'] = $bill_id;

        $invoice = Invoice::create($data);

        return response()->json([
            'status' => true, 
            'message' => 'Invoice created successfully',
            'data' => [
                'invoice_id' => $invoice->id
            ]
        ], 201);
    }    
    
    private static function discountCounter(int $summa, int $percentage): array
    {
        // Calculate the discount amount
        $discount = $summa * ($percentage / 100);

        // Calculate the final amount after discount
        $finalAmount = $summa - $discount;

        return [
            'final' => $finalAmount,
            'discount' => $discount
        ];
    }
}
