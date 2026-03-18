<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;

class UserController extends Controller
{
    /**
     * Profile + Orders + Favorites + Vouchers
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        /* ======================
         | 1. ĐƠN HÀNG (có filter)
         ====================== */
        $ordersQuery = Order::with('items.variant:id,thumbnail')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $ordersQuery->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $ordersQuery->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $ordersQuery->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $ordersQuery->paginate(10);

        /* ======================
         | 2. SẢN PHẨM YÊU THÍCH
         ====================== */
        // giả sử có bảng favorites (user_id, product_id)
        $favorites = Product::whereHas('wishlists', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->limit(10)->get();

        /* ======================
         | 3. ƯU ĐÃI / VOUCHER
         ====================== */
        // giả sử có bảng voucher_user
        $vouchers = Coupon::whereHas('user', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('expire_date', '>', now())
        ->get();

        /* ======================
         | RESPONSE
         ====================== */
        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'avatar'=> $user->avatar ? asset('storage/' . $user->avatar)
                            : null,
                'email' => $user->email,
                'role'  => $user->role,
            ],

            'orders' => $orders,

            'favorites' => $favorites,

            'vouchers' => $vouchers,
        ]);
    }

    /**
     * Chi tiết đơn hàng (modal)
     */
    public function orderDetail(Request $request, $id)
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
