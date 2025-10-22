<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banner\StoreBannerRequest;
use App\Http\Requests\Admin\Banner\UpdateBannerRequest;
use App\Models\Banner;
use App\Traits\LoggableTrait;
use App\Traits\UploadToLocalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    use LoggableTrait, UploadToLocalTrait;
    const FOLDER = 'banners';
    public function index(Request $request)
    {
        $queryBanners = Banner::orderBy('order', 'asc');
        if ($request->filled('title')) {
            $queryBanners->where('title', 'like', '%' . $request->title . '%');
        }
        if ($request->filled('status')) {
            $queryBanners->where('status', $request->status);
        }
        $banners_total = Banner::query()->count();
        $banners_active = Banner::query()->where('status', 1)->count();
        $banners_inactive = Banner::query()->where('status', 0)->count();
        $banners_deleted = Banner::onlyTrashed()->count();
        $items = $queryBanners->paginate(10);
        return view('admin.banners.index', compact('items', 'banners_total','banners_active','banners_inactive','banners_deleted'));
    }
    public function create()
    {
        try {
            return view('admin.banners.create');
        } catch (\Exception $e) {

            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
    public function store(StoreBannerRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->all();

            if ($request->hasFile('image')) {
                $data['image'] = $this->uploadToLocal($request->file('image'), self::FOLDER);
            }
            $data['redirect_url'] = !empty($data['title']) ? Str::slug($data['title']) : null;
            $data['status'] ??= 0;
            $lastBanner = Banner::orderBy('order', 'desc')->first();
            $data['order'] = $lastBanner ? $lastBanner->order + 1 : 0;
            Banner::query()->create($data);

            DB::commit();

            return redirect()->route('admin.banners.index')->with('success', 'Thêm mới thành công');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($data['image']) && !empty($data['image']) && filter_var($data['image'], FILTER_VALIDATE_URL)) {
                $this->deleteFromLocal($data['image'], self::FOLDER);
            }

            $this->logError($e);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Banner $banner)
    {
        return view('admin.banners.show', compact('banner'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerRequest $request, $id)
    {
        try {
            $data = $request->all();

            DB::beginTransaction();

            $banner = Banner::findOrFail($id);

            $imageOld = $banner->image;

            if ($request->hasFile('image')) {
                $data['image'] = $this->uploadToLocal($request->file('image'), self::FOLDER);

                if (
                    isset($data['image']) && !empty($data['image'])
                    && filter_var($data['image'], FILTER_VALIDATE_URL)
                    && !empty($imageOld)
                ) {
                    $this->deleteFromLocal($imageOld, self::FOLDER);
                }
            } else {
                $data['image'] = $imageOld;
            }

            $banner->update($data);

            DB::commit();

            return redirect()->route('admin.banners.edit', $banner->id)->with('success', 'Cập nhật thành công');
        } catch (\Exception $e) {

            DB::rollBack();

            if (isset($data['image']) && !empty($data['image']) && filter_var($data['image'], FILTER_VALIDATE_URL)) {
                $this->deleteFromLocal($data['image'], self::FOLDER);
            }

            $this->logError($e);

            return back()->with('success', false)->with('error', $e->getMessage());
        }
    }
    public function updateOrder(Request $request)
    {
        $data = $request->input('orderData'); // Nhận dữ liệu sắp xếp từ phía client

        foreach ($data as $order => $id) {
            Banner::where('id', $id)->update(['order' => $order]);
        }

        return response()->json(['status' => 'success','message' => 'Cập nhật thứ tự thành công']);
    }
        public function destroy(Banner $banner)
    {
        try {
            $banner->delete();

            return redirect()
                ->route('admin.banners.index')
                ->with('success', 'Đã chuyển vào thùng rác!');
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()
                ->back()
                ->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }

    public function trash()
    {
        try {
            $bannersDeleted = Banner::onlyTrashed()->latest('id')->paginate(10);
            return view('admin.banners.trash', compact('bannersDeleted'));
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
    public function restore($id)
    {
        try {
            $banner = Banner::withTrashed()->findOrFail($id);
            $banner->restore();
            return redirect()->route('admin.banners.trash')->with('success', 'Khôi phục người dùng thành công');
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
    public function forceDelete($id)
    {
        try {
            $banner = Banner::withTrashed()->findOrFail($id);
            $banner->forceDelete();
            return redirect()->route('admin.banners.trash')->with('success', 'Xóa cứng người dùng thành công');
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
}
