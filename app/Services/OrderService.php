<?php

namespace App\Services;

use App\Models\Accessory;
use App\Models\Balance;
use App\Models\Device;
use App\Models\Monthly;
use App\Models\Order;
use DateTime;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create a new order with all related operations.
     */
    public function createOrder(array $orderData): Order
    {
        return DB::transaction(function () use ($orderData) {
            // Update balance
            $this->updateBalance($orderData);
            
            // Generate order number
            $orderData['NumberOrder'] = $this->generateOrderNumber();
            
            // Calculate additional fields
            $orderData = $this->calculateOrderFields($orderData);
            
            // Create the order
            $order = Order::create($orderData);
            
            // Handle device/accessory inventory
            $this->updateInventory($orderData);
            
            // Create monthly payments if not cash
            if (!$orderData['is_cash']) {
                $this->createMonthlyPayments($order, $orderData);
            }
            
            return $order;
        });
    }

    /**
     * Delete an order and revert all related operations.
     */
    public function deleteOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            // Revert balance changes
            $this->revertBalanceChanges($order);
            
            // Delete monthly payments
            Monthly::where('order_id', $order->id)->delete();
            
            // Restore device/accessory status
            $this->restoreInventory($order);
            
            return $order->delete();
        });
    }

    /**
     * Update balance based on order data.
     */
    private function updateBalance(array $orderData): void
    {
        $balance = Balance::find(1);
        $additionalAmount = $orderData['is_cash'] 
            ? $orderData['summa'] 
            : $orderData['initial_payment'];
            
        $balance->update(['summa' => $balance->summa + $additionalAmount]);
    }

    /**
     * Revert balance changes when deleting an order.
     */
    private function revertBalanceChanges(Order $order): void
    {
        $balance = Balance::find(1);
        $revertAmount = ($balance->summa - $order->initial_payment) + $order->body_price;
        $balance->update(['summa' => $revertAmount]);
    }

    /**
     * Generate a new order number.
     */
    private function generateOrderNumber(): int
    {
        $latestNumber = Order::max('NumberOrder');
        return $latestNumber ? $latestNumber + 1 : 100000;
    }

    /**
     * Calculate additional order fields.
     */
    private function calculateOrderFields(array $orderData): array
    {
        $device = $this->getDevice($orderData['device_id'], $orderData['type']);
        
        $orderData['rest_summa'] = $orderData['is_cash'] 
            ? 0 
            : $orderData['summa'] - $orderData['initial_payment'];
            
        $orderData['user_id'] = auth()->id() ?? 1;
        $orderData['discount'] = 0;
        
        $quantity = $orderData['quantity'] ?? 1;
        $orderData['benefit'] = ($orderData['summa'] - $device->incoming_price) * $quantity;
        
        $orderData['startDate'] = $orderData['startDate'] ?? now()->format('Y-m-d H:i:s');
        $orderData['body_price'] = $orderData['body_price'] ?? $device->incoming_price;
        $orderData['box'] = $orderData['is_cash'] ? 1 : 0;
        
        return $orderData;
    }

    /**
     * Get device or accessory based on type.
     */
    private function getDevice(int $deviceId, string $type)
    {
        return $type === 'device' 
            ? Device::find($deviceId) 
            : Accessory::find($deviceId);
    }

    /**
     * Update inventory for device or accessory.
     */
    private function updateInventory(array $orderData): void
    {
        $device = $this->getDevice($orderData['device_id'], $orderData['type']);
        
        if ($orderData['type'] === 'device') {
            $device->update(['status' => 0]); // Mark as sold
        } else {
            $quantity = $orderData['quantity'] ?? 1;
            $device->decreaseQuantity($quantity);
        }
    }

    /**
     * Restore inventory when deleting an order.
     */
    private function restoreInventory(Order $order): void
    {
        if ($order->type === 'device') {
            Device::find($order->device_id)->update(['status' => 1]);
        } else {
            $accessory = Accessory::find($order->device_id);
            $accessory->increment('quantity', $order->quantity ?? 1);
        }
    }

    /**
     * Create monthly payment schedule.
     */
    private function createMonthlyPayments(Order $order, array $orderData): void
    {
        $payType = $orderData['pay_type'];
        $monthlySumma = $orderData['rest_summa'] / $payType;
        
        $startDate = new DateTime($order->startDate);
        $currentYear = (int) $startDate->format('Y');
        $currentMonth = (int) $startDate->format('m');
        
        $nextMonth = $currentMonth + 1;
        $nextYear = $currentYear;
        
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        
        for ($i = 1; $i <= $payType; $i++) {
            Monthly::create([
                'order_id' => $order->id,
                'payment_month' => $i,
                'month' => $nextYear . '-' . str_pad($nextMonth, 2, '0', STR_PAD_LEFT),
                'summa' => $monthlySumma,
                'rest_summa' => $monthlySumma,
            ]);
            
            $nextMonth++;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
        }
    }

    /**
     * Check if device/accessory is available for order.
     */
    public function checkAvailability(int $deviceId, string $type, int $quantity = 1): bool
    {
        $device = $this->getDevice($deviceId, $type);
        
        if (!$device) {
            return false;
        }
        
        if ($type === 'device') {
            return $device->isAvailable();
        }
        
        return $device->hasAvailableQuantity($quantity);
    }
}