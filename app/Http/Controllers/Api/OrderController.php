<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Danh sách lịch sử đơn hàng
     * filter: status, from_date, to_date
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::with('items')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        // lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // lọc theo ngày
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return response()->json([
            'data' => $query->paginate(10)
        ]);
    }

    /**
     * Chi tiết đơn hàng
     */
    public function show(Request $request, $id)
    {
        $order = Order::with([
            'items.product',
            'items.variant'
        ])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'data' => $order
        ]);
    }
}
