<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewExperience;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        // --- FILTERS ---
        $status = $request->status ?? null;        // public / hidden
        $rating = $request->rating ?? null;        // 1-5
        $product = $request->product ?? null;      // ID sản phẩm

        $query = Review::with([
            'user:id,name,email',
            'product:id,name',
            'productVariant',
            'media',
            'experiences'
        ]);

        // Lọc theo trạng thái
        if ($status) {
            $query->where('status', $status);
        }

        // Lọc theo số sao
        if ($rating) {
            $query->where('rating', $rating);
        }

        // Lọc theo sản phẩm
        if ($product) {
            $query->where('product_id', $product);
        }

        $reviews = $query->latest()->paginate(10);

        // --- SUMMARY THỐNG KÊ ---
        $summary = [
            'total'   => Review::count(),
            'public'  => Review::where('status', 'public')->count(),
            'hidden'  => Review::where('status', 'hidden')->count(),
            'bad_reviews' => Review::where('rating', '<', 3)->count(),
        ];

        // List sản phẩm để lọc
        $products = Product::select('id', 'name')->get();

        return view('admin.reviews.index', compact('reviews', 'summary', 'products'));
    }



    public function show($id)
    {
        $review = Review::with(['user', 'product', 'productVariant', 'media', 'experiences'])
            ->findOrFail($id);

        return view('admin.reviews.show', compact('review'));
    }

    public function toggleStatus($id)
{
    $review = Review::findOrFail($id);

    // Đảo trạng thái
    $review->status = $review->status === 'public' ? 'hidden' : 'public';
    $review->save();

        $message = $review->status === 'public'
        ? 'Hiển thị đánh giá thành công!'
        : 'Ẩn đánh giá thành công!';

    return redirect()->back()->with('success', $message);
}


}
