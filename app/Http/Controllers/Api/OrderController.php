<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Accessory;
use App\Models\Balance;
use App\Models\Client;
use App\Models\Cost;
use App\Models\Device;
use App\Models\Monthly;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseController
{
    private OrderService $orderService;
    private ValidationService $validationService;

    public function __construct(OrderService $orderService, ValidationService $validationService)
    {
        $this->orderService = $orderService;
        $this->validationService = $validationService;
    }

    /**
     * Get all orders with pagination and relationships.
     */
    public function getAllOrders(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        
        $orders = Order::with(['client', 'user'])
            ->leftJoin('devices', function ($join) {
                $join->on('orders.device_id', '=', 'devices.id')
                     ->where('orders.type', '=', 'device');
            })
            ->leftJoin('accessories', function ($join) {
                $join->on('orders.device_id', '=', 'accessories.id')
                     ->where('orders.type', '=', 'accessory');
            })
            ->select([
                'orders.*',
                DB::raw('COALESCE(devices.name, accessories.name) as device_name'),
                DB::raw('COALESCE(devices.model, accessories.model) as device_model'),
                DB::raw('COALESCE(devices.color, accessories.color) as device_color')
            ])
            ->orderBy('orders.created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($orders, 'Orders retrieved successfully');
    }

    /**
     * Create a new order.
     */
    public function createOrder(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $validatedData = $this->validationService->validateOrderData($request);
            
            // Validate business rules
            $this->validationService->validateBusinessRules($validatedData);
            
            // Check availability
            $quantity = $validatedData['quantity'] ?? 1;
            if (!$this->orderService->checkAvailability(
                $validatedData['device_id'], 
                $validatedData['type'], 
                $quantity
            )) {
                return $this->errorResponse('Device/Accessory is not available', 400);
            }
            
            // Create the order
            $order = $this->orderService->createOrder($validatedData);
            
            return $this->successResponse($order, 'Order created successfully', 201);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an order.
     */
    public function deleteOrder(Request $request, Order $order): JsonResponse
    {
        try {
            // Delete associated cost record
            Cost::where('imei', $order->imei)->delete();
            
            // Delete the order and revert changes
            $result = $this->orderService->deleteOrder($order);
            
            if ($result) {
                return $this->successResponse(null, 'Order deleted successfully');
            }
            
            return $this->errorResponse('Failed to delete order', 500);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an order.
     */
    public function updateOrder(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $this->validationService->validateOrderUpdateData($request);
            
            $order = Order::findOrFail($id);
            
            // Handle status change to completed (2)
            if ($validatedData['status'] == 2) {
                $this->handleOrderCompletion($order);
            }
            
            $order->update($validatedData);
            
            return $this->successResponse($order, 'Order updated successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle order completion logic.
     */
    private function handleOrderCompletion(Order $order): void
    {
        // Add any completion logic here
        // For example, sending notifications, updating inventory, etc.
    }

    /**
     * Get order statistics.
     */
    public function getOrderStats(): JsonResponse
    {
        $stats = [
            'total_orders' => Order::count(),
            'cash_orders' => Order::where('is_cash', true)->count(),
            'installment_orders' => Order::where('is_cash', false)->count(),
            'completed_orders' => Order::where('status', 2)->count(),
            'pending_orders' => Order::where('status', 0)->count(),
            'total_revenue' => Order::sum('summa'),
            'total_profit' => Order::sum('benefit'),
        ];

        return $this->successResponse($stats, 'Order statistics retrieved successfully');
    }

    /**
     * Get orders by client.
     */
    public function getOrdersByClient(int $clientId): JsonResponse
    {
        $orders = Order::with(['user'])
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse($orders, 'Client orders retrieved successfully');
    }
}





// qurilmaga 2-imei qoshish kere

// 100054