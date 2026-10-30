<?php

namespace Tests\Integration\P6;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Modules\Catalog\Infrastructure\SaveProduct;
use App\Modules\Ordering\Contracts\Checkout;
use App\SharedKernel\ActorContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\MySqlTestCase;

class CatalogSafetyTest extends MySqlTestCase
{
    private function data(array $changes = []): array
    {
        return array_replace(['name' => 'P6 Phone', 'category_id' => 910001, 'status' => 'published', 'visibility' => 'public',
            'specs' => [['name' => 'Screen', 'value' => 'OLED']],
            'variants' => [['sku' => 'P6-SKU', 'price' => 100000, 'cost_price' => 60000]]], $changes);
    }

    public function test_foreign_variant_or_spec_and_foreign_gallery_are_rejected_atomically(): void
    {
        $this->seedCommerce();
        $other = Product::create(['name' => 'Other', 'slug' => 'other', 'category_id' => 910001]);
        $spec = $other->specs()->create(['spec_name' => 'Other', 'spec_value' => 'Keep']);
        $before = DB::table('products')->orderBy('id')->get()->toJson();
        foreach ([[$other->id, ['variants' => [['id' => 910001, 'sku' => 'HACK', 'price' => 1]]]], [910001, ['specs' => [['id' => $spec->id, 'name' => 'Hack', 'value' => 'No']]]], [910001, ['remove_gallery' => ['users/private.png']]]] as [$target, $attack]) {
            try {
                app(SaveProduct::class)->save($this->data($attack), $target);
                $this->fail('Foreign data must be rejected.');
            } catch (ValidationException) {
                $this->assertSame($before, DB::table('products')->orderBy('id')->get()->toJson());
                $this->assertDatabaseHas('product_variants', ['id' => 910001, 'sku' => 'P0-SKU']);
            }
        }
    }

    public function test_failed_variant_save_preserves_old_files_and_rolls_back_product_specs_and_new_uploads(): void
    {
        $this->seedCommerce();
        Storage::fake('public');
        Storage::disk('public')->put('products/old.png', 'old');
        Storage::disk('public')->put('products/gallery/old.png', 'old gallery');
        Product::findOrFail(910001)->update(['thumbnail' => 'products/old.png', 'gallery' => ['products/gallery/old.png']]);
        Event::listen('eloquent.saving: '.ProductVariant::class, fn () => throw new \RuntimeException('Synthetic SKU failure'));
        try {
            app(SaveProduct::class)->save($this->data(['thumbnail' => UploadedFile::fake()->create('new.png', 10),
                'gallery' => [UploadedFile::fake()->create('new-gallery.png', 10)], 'remove_gallery' => ['products/gallery/old.png']]), 910001);
            $this->fail('Failure must propagate.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Synthetic SKU failure', $error->getMessage());
            $this->assertDatabaseHas('products', ['id' => 910001, 'name' => 'P0 phone', 'thumbnail' => 'products/old.png']);
            $this->assertDatabaseCount('product_specs', 0);
            $this->assertEqualsCanonicalizing(['products/old.png', 'products/gallery/old.png'], Storage::disk('public')->allFiles());
        } finally {
            Event::forget('eloquent.saving: '.ProductVariant::class);
        }
    }

    public function test_archive_product_sku_and_user_preserves_order_snapshots_and_blocks_hard_delete(): void
    {
        $this->seedCommerce();
        DB::table('users')->where('id', 910002)->update(['role' => 'admin']);
        $order = app(Checkout::class)->place($this->checkoutPayload(), new ActorContext(910001));
        $snapshot = DB::table('order_items')->where('order_id', $order->id)->get()->toJson();
        $user = User::findOrFail(910001);
        $user->createToken('synthetic');
        $this->actingAs(User::findOrFail(910002));
        $this->delete('/admin/products/910001')->assertRedirect();
        $this->delete('/admin/product_variants/910001')->assertRedirect();
        $this->delete('/admin/users/910001')->assertRedirect();
        foreach (['products', 'product_variants', 'users'] as $resource) {
            $this->delete('/admin/'.$resource.'/910001/force-delete')->assertRedirect()->assertSessionHas('warning');
            $this->assertNotNull(DB::table($resource)->where('id', 910001)->value('deleted_at'));
        }
        $this->delete('/admin/categories/910001/force-delete')->assertRedirect()->assertSessionHas('warning');
        $this->assertDatabaseHas('categories', ['id' => 910001]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertSame($snapshot, DB::table('order_items')->where('order_id', $order->id)->get()->toJson());
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => 910001]);
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('P0 phone');
        $this->get('/admin/inventory')->assertOk()->assertSee('P0-SKU');
        $this->get('/admin/inventory/transactions')->assertOk()->assertSee('P0-SKU');
        $coupon = \App\Models\Coupon::create(['user_id' => 910001, 'code' => 'P6-LEGACY-CREATOR', 'name' => 'Legacy creator', 'discount_type' => 'fixed', 'discount_value' => 100000]);
        $this->assertSame(910001, $coupon->fresh()->user->id);
        $this->get('/admin/coupons')->assertOk()->assertSee('P6-LEGACY-CREATOR');
    }

    public function test_upload_failure_cleans_preceding_files_and_does_not_touch_existing_product(): void
    {
        $this->seedCommerce();
        Storage::fake('public');
        $temporary = UploadedFile::fake()->create('bad.png', 1);
        $bad = new class($temporary->getPathname(), 'bad.png', null, null, true) extends UploadedFile
        {
            public function store($path = '', $options = [])
            {
                throw new \RuntimeException('Synthetic filesystem failure');
            }
        };
        try {
            app(SaveProduct::class)->save($this->data(['thumbnail' => UploadedFile::fake()->create('first.png', 1), 'gallery' => [$bad]]), 910001);
            $this->fail('Upload failure must propagate.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Synthetic filesystem failure', $error->getMessage());
            $this->assertSame([], Storage::disk('public')->allFiles());
            $this->assertDatabaseHas('products', ['id' => 910001, 'name' => 'P0 phone']);
            $this->assertDatabaseCount('product_specs', 0);
        }
    }
}
