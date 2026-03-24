<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create a new order with items.
     */
    public function createOrder(array $data, int $userId): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => Order::STATUS_PENDING,
                'subtotal' => 0,
                'total' => 0,
                'created_by' => $userId,
            ]);

            $this->createOrderItems($order, $data['items']);
            $this->recalculateTotals($order);

            return $order;
        });
    }

    /**
     * Update an existing order and its items.
     */
    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'customer_name' => $data['customer_name'] ?? $order->customer_name,
                'customer_phone' => $data['customer_phone'] ?? $order->customer_phone,
                'customer_email' => $data['customer_email'] ?? $order->customer_email,
                'notes' => $data['notes'] ?? $order->notes,
            ]);

            if (isset($data['items'])) {
                $order->items()->delete();
                $this->createOrderItems($order, $data['items']);
                $this->recalculateTotals($order);
            }

            return $order;
        });
    }

    /**
     * Update order status with transition validation.
     */
    public function updateStatus(Order $order, string $newStatus): Order
    {
        if (!$order->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Invalid order status transition from {$order->status} to {$newStatus}.");
        }

        $order->update(['status' => $newStatus]);
        return $order;
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', now())->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        
        $orderNumber = "ORD-{$date}-{$sequence}";
        
        // Ensure uniqueness just in case
        while (Order::where('order_number', $orderNumber)->exists()) {
            $count++;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $orderNumber = "ORD-{$date}-{$sequence}";
        }

        return $orderNumber;
    }

    /**
     * Create order items from provided data.
     */
    protected function createOrderItems(Order $order, array $itemsData): void
    {
        foreach ($itemsData as $itemData) {
            $product = Product::findOrFail($itemData['product_id']);
            
            $unitPrice = $product->base_price;
            $quantity = $itemData['quantity'];
            $lineTotal = $unitPrice * $quantity;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'product_sku_snapshot' => $product->sku,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ]);
        }
    }

    /**
     * Recalculate order subtotal and total.
     */
    protected function recalculateTotals(Order $order): void
    {
        $subtotal = $order->items()->sum('line_total');
        $order->update([
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);
    }
}
