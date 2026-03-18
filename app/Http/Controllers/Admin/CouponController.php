<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CouponsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Coupons\ImportCouponRequest;
use App\Http\Requests\Admin\Coupon\StoreCouponRequest;
use App\Http\Requests\Admin\Coupon\UpdateCouponRequest;
use App\Imports\CouponsImport;
use App\Jobs\AssignCouponJob;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\User;
use App\Traits\LoggableTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CouponController extends Controller
{
    use LoggableTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $queryCoupons = Coupon::query()->with('user');

        if ($request->has('query') && $request->input('query')) {
            $search = $request->input('query');
            $queryCoupons->where('name', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%");
        }

        $couponCounts = Coupon::query()
            ->selectRaw('
                COUNT(id) as total_coupons,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_coupons,
                SUM(CASE WHEN expire_date < NOW() THEN 1 ELSE 0 END) as expire_coupons,
                SUM(CASE WHEN used_count > 0 THEN 1 ELSE 0 END) as used_coupons
            ')
            ->first();

        $queryCouponCounts = Coupon::query()
            ->selectRaw('
            COUNT(id) as total_coupons,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_coupons,
            SUM(CASE WHEN expire_date < NOW() THEN 1 ELSE 0 END) as expire_coupons,
            SUM(CASE WHEN used_count > 0 THEN 1 ELSE 0 END) as used_coupons
        ');

        // Lấy dữ liệu và phân trang
        $coupons = $queryCoupons->orderBy('id', 'desc')->paginate(10);

        return view('admin.coupons.index', compact('coupons', 'couponCounts'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(StoreCouponRequest $request)
    {
        try {
            // dd($request->all());
            DB::beginTransaction();

            $user = Auth::user();

            $data = $request->validated();

            $data['discount_max_value'] = (empty($data['discount_max_value']) || $data['discount_type'] == 'fixed') ? 0 : $data['discount_max_value'];
            $data['user_id'] = $user->id;

            $coupon = Coupon::create($data);

            $userIds = $request->system_wide
                ? User::whereNotIn('role', ['admin', 'employee'])
                ->where('status', 'active')
                ->whereNotNull('email_verified_at')
                ->pluck('id')
                ->toArray()
                : $request->selected_users;

            AssignCouponJob::dispatch($coupon, $userIds);

            DB::commit();

            return redirect()->route('admin.coupons.index')->with('success', 'Thêm mới thành công');
        } catch (\Exception $e) {
            DB::rollBack();

            $this->logError($e);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(string $id)
    {
        $coupon = Coupon::findOrFail($id);

        return view('admin.coupons.show', compact('coupon'));
    }

    public function edit(string $id)
    {
        $coupon = Coupon::query()
            ->with(['couponUses' => function ($query) {
                $query->select('id', 'coupon_id', 'user_id', 'status');
            }, 'couponUses.user:id,name,email,avatar'])
            ->findOrFail($id);

        $couponUses = CouponUse::query()
            ->with('user:id,name,email,avatar')
            ->where('coupon_id', $coupon->id)
            ->get();

        return view('admin.coupons.edit', compact('coupon', 'couponUses'));
    }

    public function update(UpdateCouponRequest $request, string $id)
    {
        try {
            // dd($request->all());
            DB::beginTransaction();

            $coupon = Coupon::findOrFail($id);
            $data = $request->validated();

            $data['discount_max_value'] = (empty($data['discount_max_value']) || $data['discount_type'] == 'fixed') ? 0 : $data['discount_max_value'];
            $coupon->update($data);

            if ($request->has('selected_users')) {
                $newUserIds = is_array($request->selected_users) ? $request->selected_users : [];

                $existingUserIds = $coupon->couponUses()->pluck('user_id')->toArray();

                $usersToAdd = array_diff($newUserIds, $existingUserIds);
                $usersToDelete = array_diff($existingUserIds, $newUserIds);

                if (!empty($usersToDelete)) {
                    $coupon->couponUses()->whereIn('user_id', $usersToDelete)->delete();
                }

                if (!empty($usersToAdd)) {
                    $now = now();
                    $batchData = collect($usersToAdd)->map(function ($userId) use ($coupon, $now) {
                        return [
                            'user_id' => $userId,
                            'coupon_id' => $coupon->id,
                            'status' => 'unused',
                            'expired_at' => $now->clone()->addDays(7),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })->toArray();

                    if (!empty($batchData)) {
                        DB::table('coupon_uses')->insert($batchData);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.coupons.edit', $coupon->id)->with('success', 'Cập nhật thành công');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError($e);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function suggestionCounpoun(Request $request)
    {
        try {
            $suggestCounpons = [];

            $counpon = $request->input('code', 'SALE');

            if (Coupon::where('code', $counpon)->exists()) {
                for ($i = 1; $i <= 3; $i++) {
                    do {
                        $counponCode = '';
                        $counponCode = Str::upper($counpon . substr(str_replace('-', '', Str::uuid()), 0, 6));
                    } while (Coupon::where('code', $counponCode)->exists());

                    $suggestCounpons[] = $counponCode;
                }

                return response()->json($suggestCounpons);
            }

            return;
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    public function couponUserSearch(Request $request)
    {
        try {
            $searchQuery = $request->input('search', '');
            $excludeIds = $request->input('exclude', []);

            $users = User::query()
                ->whereNotIn('role', ['admin', 'employee'])
                ->where('status', 'active')
                ->whereNotNull('email_verified_at')
                ->when(!empty($excludeIds), function ($query) use ($excludeIds) {
                    $query->whereNotIn('id', $excludeIds);
                })
                ->where(function ($query) use ($searchQuery) {
                    $query->where('name', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('email', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('code', 'LIKE', "%{$searchQuery}%");
                })
                ->select('id', 'name', 'email', 'avatar')
                ->limit(10)
                ->get();

            return response()->json([
                'users' => $users,
                'pagination' => [
                    'more' => false,
                    'total' => $users->count()
                ]
            ]);
        } catch (\Exception $e) {
            $this->logError($e);

            return response()->json([
                'error' => true,
                'message' => 'Có lỗi xảy ra, vui lòng thử lại'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
