<?php

namespace Tests\Integration\P6;

use App\Models\User;
use App\Modules\Identity\Infrastructure\SaveAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MySqlTestCase;

class IdentitySafetyTest extends MySqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        $this->actingAs(User::findOrFail(910002));
    }

    public function test_admin_update_uses_only_validated_fields_and_cannot_lock_or_demote_itself(): void
    {
        $this->put('/admin/users/910001', ['name' => 'Updated user', 'role' => 'user', 'status' => 'active',
            'password' => 'Injected password', 'code' => 'INJECTED', 'email_verified_at' => '2001-01-01'])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => 910001, 'name' => 'Updated user', 'password' => 'not-a-login-credential', 'code' => 'P0-910001', 'email_verified_at' => null]);
        $this->putJson('/admin/users/910002', ['name' => 'Self', 'role' => 'user', 'status' => 'blocked'])->assertUnprocessable();
        $this->delete('/admin/users/910002')->assertRedirect()->assertSessionHas('warning');
        $this->assertDatabaseHas('users', ['id' => 910002, 'role' => 'admin', 'status' => 'active', 'deleted_at' => null]);
    }

    public function test_failed_account_update_preserves_avatar_and_tokens_and_cleans_new_upload(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/old.png', 'old avatar');
        User::findOrFail(910001)->update(['avatar' => 'users/old.png']);
        User::findOrFail(910001)->createToken('synthetic');
        Event::listen('eloquent.updating: '.User::class, fn () => throw new \RuntimeException('Synthetic account failure'));
        try {
            app(SaveAccount::class)->save(['name' => 'New name', 'role' => 'user', 'status' => 'blocked', 'avatar' => UploadedFile::fake()->create('new.png', 10)], 910001);
            $this->fail('Failure must propagate.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Synthetic account failure', $error->getMessage());
            $this->assertSame(['users/old.png'], Storage::disk('public')->allFiles());
            $this->assertDatabaseHas('users', ['id' => 910001, 'avatar' => 'users/old.png', 'status' => 'active']);
            $this->assertDatabaseCount('personal_access_tokens', 1);
        } finally {
            Event::forget('eloquent.updating: '.User::class);
        }
    }
}
