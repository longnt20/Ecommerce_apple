<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        try {
            $banners = Banner::with(['products:id,name,slug'])
            ->where('status', 1)
            ->orderBy('order')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $banners->map(fn($b) => [
                    'title' => $b->title,
                    'image' => $b->image
                        ? asset('storage/' . $b->image)
                        : null,
                    'order' => $b->order,
                    'products' =>$b->products->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'slug' => $product->slug,
                    ];
                }),
                ])
        ],200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
