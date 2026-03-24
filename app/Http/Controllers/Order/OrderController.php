<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,processing,completed,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Order::with(['creator']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->query('per_page', 15);
        
        return OrderResource::collection($query->latest()->paginate($perPage));
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        $order = $this->orderService->createOrder($request->validated(), $request->user()->id);
        return new OrderResource($order->load(['items', 'creator']));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['items', 'creator']));
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $order = $this->orderService->updateOrder($order, $request->validated());
        return new OrderResource($order->load(['items', 'creator']));
    }

    /**
     * Update order status.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        try {
            $order = $this->orderService->updateStatus($order, $request->status);
            return new OrderResource($order->load(['items', 'creator']));
        } catch (\InvalidArgumentException $e) {
            return new OrderResource($order->load(['items', 'quantity']));
            // return response()->json([
            //     'message' => $e->getMessage()
            // ], 422);
        }
    }
}
