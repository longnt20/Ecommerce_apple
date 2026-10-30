<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Models\User;
use App\Traits\LoggableTrait;
use App\Traits\UploadToLocalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use LoggableTrait, UploadToLocalTrait;

    const FOLDER = 'users';

    const URLIMAGEDEFAULT = 'https://res.cloudinary.com/dvrexlsgx/image/upload/v1732148083/Avatar-trang-den_apceuv_pgbce6.png';

    public function index(Request $request)
    {
        $queryUsers = User::query()->latest('id');

        if ($request->filled('name')) {
            $queryUsers->where('name', 'like', '%'.$request->name.'%');
        }
        if ($request->filled('email')) {
            $queryUsers->where('email', 'like', '%'.$request->email.'%');
        }
        if ($request->filled('phone_number')) {
            $queryUsers->where('phone_number', 'like', '%'.$request->phone_number.'%');
        }
        if ($request->filled('status')) {
            $queryUsers->where('status', $request->status);
        }
        if ($request->filled('role')) {
            $queryUsers->where('role', $request->role);
        }

        $queryUserCounts = User::query()
            ->selectRaw('
                    count(id) as total_users,
                    sum(status = "active") as active_users,
                    sum(status = "inactive") as inactive_users,
                    sum(status = "blocked") as blocked_users
                ');
        $items = $queryUsers->paginate(10);
        $userCounts = $queryUserCounts->first();

        return view('admin.users.index', compact('items', 'userCounts'));
    }

    public function create()
    {
        try {
            return view('admin.users.create');
        } catch (\Throwable $th) {
            // throw $th;
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {
            app(\App\Modules\Identity\Infrastructure\SaveAccount::class)->save($request->validated());

            return redirect()->route('admin.users.index')->with('success', 'Thêm mới thành công');
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            $this->logError($error);

            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }

    public function updateEmailVerified(Request $request, User $user)
    {
        try {
            $isChecked = ! empty($request->input('email_verified'));

            // Nếu user đã xác thực mà admin cố tắt
            if ($user->email_verified_at && ! $isChecked) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Email đã xác thực, không thể hủy xác thực.',
                ]);
            }

            // Nếu admin bật xác thực cho user chưa xác thực
            if (! $user->email_verified_at && $isChecked) {
                $user->update(['email_verified_at' => now()]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật thành công.',
            ]);
        } catch (\Exception $e) {
            $this->logError($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Cập nhật thất bại.',
            ]);
        }
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            app(\App\Modules\Identity\Infrastructure\SaveAccount::class)->save($request->validated(), $user->id);

            return redirect()->route('admin.users.edit', $user)->with('success', 'Cập nhật thành công');
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            $this->logError($error);

            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }

    public function destroy(User $user)
    {
        try {
            if ($user->role === 'admin' || $user->id === auth()->id()) {
                return redirect()->back()->with('warning', 'Không thể xóa tài khoản quản trị viên!');
            }

            DB::transaction(function () use ($user) {
                $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                if ($locked->role === 'admin' || $locked->id === auth()->id()) {
                    abort(403);
                }
                $locked->tokens()->delete();
                $locked->delete();
            });

            return redirect()
                ->route('admin.users.index')
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
            $usersDeleted = User::onlyTrashed()->latest('id')->paginate(10);

            return view('admin.users.trash', compact('usersDeleted'));
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }

    public function restore($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            return redirect()->route('admin.users.trash')->with('success', 'Khôi phục người dùng thành công');
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }

    public function forceDelete($id)
    {
        return back()->with('warning', 'Chỉ hỗ trợ lưu trữ để bảo toàn lịch sử nghiệp vụ.');
    }
}
