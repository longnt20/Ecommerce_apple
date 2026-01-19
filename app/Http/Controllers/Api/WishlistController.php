<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // Danh sách yêu thích
    public function index(Request $request)
    {
        return $request->user()
            ->wishlists()
            ->with([
                'product:id,name,slug',
                'variant:id,price,cost_price,thumbnail,storage,product_id'
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,

                    'product' => [
                        'name_product' =>
                        $item->product->name . ' ' . $item->variant->storage,
                        'slug' => $item->product->slug,
                    ],

                    'variant' => [
                        'price' => $item->variant->price,
                        'cost_price' => $item->variant->cost_price,
                        'thumbnail_url' => $item->variant->thumbnail_url,
                    ],
                ];
            });
    }


    // Thêm / Bỏ yêu thích (toggle)
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'required|exists:product_variants,id',
        ]);

        $wishlist = Wishlist::where('user_id', $request->user()->id)
            ->where('product_variant_id', $request->product_variant_id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return response()->json(['status' => 'removed']);
        }

        Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
            'product_variant_id' => $request->product_variant_id,
        ]);

        return response()->json(['status' => 'added']);
    }
    public function destroy($id)
    {
        $wishlist = auth()->user()
            ->wishlists()
            ->where('id', $id)
            ->firstOrFail();

        $wishlist->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}
