<?php

namespace App\Modules\Identity\Infrastructure;

use App\Events\UserStatusChanged;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class SaveAccount
{
    public function save(array $input, ?int $id = null): User
    {
        $newPath = null;
        try {
            return DB::transaction(function () use ($input, $id, &$newPath) {
                $user = $id === null ? new User : User::whereKey($id)->lockForUpdate()->firstOrFail();
                $data = Arr::only($input, $id === null ? ['name', 'email', 'password', 'role', 'status', 'phone_number', 'address'] : ['name', 'role', 'status']);
                if ($id === auth()->id() && (($data['role'] ?? $user->role) !== 'admin' || ($data['status'] ?? $user->status) !== 'active')) {
                    throw ValidationException::withMessages(['role' => 'Không thể tự hạ quyền hoặc khóa tài khoản đang đăng nhập.']);
                }
                $oldAvatar = $user->avatar;
                $oldRole = $user->role;
                $oldStatus = $user->status;
                if (($input['avatar'] ?? null) instanceof UploadedFile) {
                    $newPath = $input['avatar']->store('users', 'public');
                    if ($newPath === false) {
                        throw new \RuntimeException('Cannot store account avatar.');
                    }
                    $data['avatar'] = $newPath;
                }
                if ($id === null) {
                    $data['email_verified_at'] = now();
                }
                $user->fill($data)->save();
                if ($oldStatus !== null && ($oldRole !== $user->role || $oldStatus !== $user->status)) {
                    $user->tokens()->delete();
                    $event = new UserStatusChanged($user, $oldStatus, $user->status, $oldRole, $user->role);
                    DB::afterCommit(static function () use ($event) {
                        try {
                            event($event);
                        } catch (\Throwable $error) {
                            report($error);
                        }
                    });
                }
                if ($newPath && $oldAvatar && preg_match('~^users/[a-zA-Z0-9_.-]+$~D', $oldAvatar)) {
                    DB::afterCommit(static function () use ($oldAvatar) {
                        try {
                            Storage::disk('public')->delete($oldAvatar);
                        } catch (\Throwable $error) {
                            report($error);
                        }
                    });
                }

                return $user;
            });
        } catch (\Throwable $error) {
            if (is_string($newPath)) {
                Storage::disk('public')->delete($newPath);
            }
            throw $error;
        }
    }
}
