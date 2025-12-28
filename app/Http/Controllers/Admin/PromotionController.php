<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Promotion\StorePromotionRequest;
use App\Models\Category;
use App\Models\Frame;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Traits\LoggableTrait;
use App\Traits\UploadToLocalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromotionController extends Controller
{
    use LoggableTrait, UploadToLocalTrait;
    const FOLDER = 'promotions';
    public function index()
    {
        $promotions = Promotion::with(['category', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.promotions.index', compact('promotions'));
    }
    public function create()
    {
        $categories = Category::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']); // Chỉ lấy cột cần thiết

        $baseProducts = Product::query()
            ->where('status', 'published')
            ->whereNull('deleted_at') // Nếu có soft delete
            ->orderBy('name')
            ->get(['id', 'name', 'default_price', 'thumbnail']); // Chỉ lấy cột cần dùng
        $frames = Frame::where('is_active', 1)->get();
        $variantProducts = ProductVariant::query()
            ->with(['product:id,name']) // Chỉ load id & name của product
            ->get(['id', 'product_id','sku','color','storage', 'price', 'thumbnail']); // Chỉ lấy cột cần

        return view('admin.promotions.create', compact('categories', 'baseProducts', 'variantProducts','frames'));
    }
    public function store(StorePromotionRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            if ($request->hasFile('thumbnail')) {
                $urlThumbnail = $this->uploadToLocal($request->file('thumbnail'), 'promotions');
                $data['thumbnail'] = $urlThumbnail;
            }
            $data['slug'] = Str::slug($data['name']);
            $data['is_featured'] = $request->has('is_featured');
            $promotion = Promotion::create($data);

            foreach ($request->items as $item) {
                PromotionItem::create([
                    'promotion_id' => $promotion->id,
                    'item_id' => $item['item_id'],
                    'item_type' => $item['item_type'],
                ]);
            }
                        // dd($data);
            DB::commit();
            return redirect()->route('admin.promotions.index')->with('success', 'Thêm mới chương trình thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($urlThumbnail) && filter_var($urlThumbnail, FILTER_VALIDATE_URL)) {
                $this->deleteFromLocal($urlThumbnail, self::FOLDER);
            }

            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
}
