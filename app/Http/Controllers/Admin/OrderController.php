<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::latest()->paginate(10);

        return view('admin.orders.index', [
            'orders' => $orders,
            'orders_total' => Order::count(),
            'orders_pending' => Order::where('status', 'pending')->count(),
            'orders_shipping' => Order::where('status', 'shipping')->count(),
            'orders_delivered' => Order::where('status', 'completed')->count(),
        ]);
    }
    public function show($id)
    {
        $order = Order::with('items.product', 'items.variant')->findOrFail($id);
        $normalFlow = ['pending', 'confirmed', 'shipping', 'completed'];
        $cancelFlow = ['pending', 'confirmed', 'shipping', 'cancelled'];

         if ($order->status === 'cancelled') {
        // Nếu hủy → chỉ active cancelled
        $activeSteps = ['cancelled'];
    } else {
        // Active từ đầu tới trạng thái hiện tại
        $currentIndex = array_search($order->status, $normalFlow);
        $activeSteps = array_slice($normalFlow, 0, $currentIndex + 1);
    }
    $allowedTransitions = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping'],
        'shipping'  => ['completed'],
    ];

    $nextStatuses = $allowedTransitions[$order->status] ?? [];

        return view('admin.orders.show', compact('order','activeSteps','nextStatuses'));
    }
    public function updateStatus(Request $request, Order $order)
{
    $request->validate([
        'status' => 'required|in:confirmed,shipping,completed,cancelled',
    ]);

    $allowedTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping'],
        'shipping' => ['completed'],
    ];

    abort_if(
        !isset($allowedTransitions[$order->status]) ||
        !in_array($request->status, $allowedTransitions[$order->status]),
        403
    );

    $order->update(['status' => $request->status]);

    return back()->with('success', 'Cập nhật trạng thái thành công');
}

}
