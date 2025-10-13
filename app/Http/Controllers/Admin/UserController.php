<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Mail\AccountBlockedMail;
use App\Mail\AccountUnblockedMail;
use App\Mail\RoleChangedMail;
use App\Mail\UserRoleChangedMail;
use App\Models\User;
use App\Traits\LoggableTrait;
use App\Traits\UploadToLocalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use LoggableTrait, UploadToLocalTrait;

    const FOLDER = 'users';
    const URLIMAGEDEFAULT = "https://res.cloudinary.com/dvrexlsgx/image/upload/v1732148083/Avatar-trang-den_apceuv_pgbce6.png";
    public function index(Request $request)
    {
        $queryUsers = User::query()->latest('id');

        if ($request->filled('name')) {
            $queryUsers->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $queryUsers->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('phone_number')) {
            $queryUsers->where('phone_number', 'like', '%' . $request->phone_number . '%');
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
            //throw $th;
        }
    }
    public function store(StoreUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->except('avatar');

            if ($request->hasFile('avatar')) {
                $urlAvatar = $this->uploadToLocal($request->file('avatar'), self::FOLDER);
            }

            do {
                $data['code'] = str_replace('-', '', Str::uuid()->toString());
            } while (User::query()->where('code', $data['code'])->exists());

            $data['email_verified_at'] = now();
            // dd($data);
            $user = User::query()->create($data);

            DB::commit();

            return redirect()->route('admin.users.index')->with('success', 'Thêm mới thành công');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($urlAvatar) && filter_var($urlAvatar, FILTER_VALIDATE_URL)) {
                $this->deleteFromLocal($urlAvatar, self::FOLDER);
            }

            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
    public function updateEmailVerified(Request $request, User $user)
    {
        try {
            $isChecked = !empty($request->input('email_verified'));

            // Nếu user đã xác thực mà admin cố tắt
            if ($user->email_verified_at && !$isChecked) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Email đã xác thực, không thể hủy xác thực.'
                ]);
            }

            // Nếu admin bật xác thực cho user chưa xác thực
            if (!$user->email_verified_at && $isChecked) {
                $user->update(['email_verified_at' => now()]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cập nhật thành công.'
            ]);
        } catch (\Exception $e) {
            $this->logError($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Cập nhật thất bại.'
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

            $validator = $request->validated();

            $data = $request->except('avatar', 'email', 'email_verified');

            DB::beginTransaction();

            $currencyAvatar = $user->avatar;
            $oldRole = $user->role;
            $oldStatus = $user->status;
            if ($request->hasFile('avatar')) {
                $data['avatar'] = $this->uploadToLocal($request->file('avatar'), self::FOLDER);
            }

            $user->update($data);

            // Kiểm tra thay đổi vai trò hoặc trạng thái
            $newRole = $user->role;
            $newStatus = $user->status;

            if ($oldRole !== $newRole || $oldStatus !== $newStatus) {
                // Bắn event duy nhất, listener sẽ tự gửi mail phù hợp
                event(new UserStatusChanged($user, $oldStatus, $newStatus, $oldRole, $newRole));
            }

            if (
                isset($data['avatar']) && !empty($data['avatar'])
                && filter_var($data['avatar'], FILTER_VALIDATE_URL)
                && !empty($currencyAvatar) && $currencyAvatar !== self::URLIMAGEDEFAULT
            ) {
                $this->deleteFromLocal($currencyAvatar, self::FOLDER);
            }
            // dd($data);
            DB::commit();
            return redirect()->route('admin.users.edit', $user)->with('success', 'Cập nhật thành công');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($data['avatar']) && !empty($data['avatar']) && filter_var($data['avatar'], FILTER_VALIDATE_URL)) {
                $this->deleteFromLocal($data['avatar']);
            }

            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
    public function destroy(User $user)
    {
        try {
            if ($user->role = "admin") {
                return redirect()->back()->with('warning', 'Không thể xóa tài khoản quản trị viên!');
            }

            $user->delete();

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
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->forceDelete();
            return redirect()->route('admin.users.trash')->with('success', 'Xóa cứng người dùng thành công');
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại sau');
        }
    }
}
