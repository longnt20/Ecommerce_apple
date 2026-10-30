<?php

namespace App\Modules\Catalog\Infrastructure;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\GeneratesBarcode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Admin persistence adapter. UploadedFile never crosses a business module contract. */
final class SaveProduct
{
    use GeneratesBarcode;

    public function save(array $input, ?int $id = null): int
    {
        $media = new CatalogMedia;
        try {
            return DB::transaction(function () use ($input, $id, $media) {
                $product = $id === null ? new Product : Product::whereKey($id)->lockForUpdate()->firstOrFail();
                $data = Arr::only($input, ['name', 'short_description', 'description', 'category_id', 'default_price', 'visibility', 'status']);
                $data['slug'] = Str::slug($data['name']);
                if (($input['thumbnail'] ?? null) instanceof UploadedFile) {
                    $data['thumbnail'] = $media->upload($input['thumbnail'], 'products');
                    $media->replace($product->thumbnail);
                }
                $gallery = $product->gallery ?? [];
                foreach ($input['remove_gallery'] ?? [] as $path) {
                    if (! in_array($path, $gallery, true)) {
                        throw ValidationException::withMessages(['remove_gallery' => 'Ảnh không thuộc sản phẩm này.']);
                    }
                    $gallery = array_values(array_diff($gallery, [$path]));
                    $media->replace($path);
                }
                foreach ($input['gallery'] ?? [] as $file) {
                    $gallery[] = $media->upload($file, 'products/gallery');
                }
                $data['gallery'] = $gallery;
                $product->fill($data)->save();
                $specIds = [];
                foreach ($input['specs'] ?? [] as $index => $spec) {
                    $row = empty($spec['id']) ? $product->specs()->make()
                        : $product->specs()->whereKey($spec['id'])->lockForUpdate()->first();
                    if (! $row) {
                        throw ValidationException::withMessages(["specs.$index.id" => 'Thông số không thuộc sản phẩm này.']);
                    }
                    if (($spec['name'] ?? '') === '' || ($spec['value'] ?? '') === '') {
                        continue;
                    }
                    $row->fill(['spec_name' => $spec['name'], 'spec_value' => $spec['value']])->save();
                    $specIds[] = $row->id;
                }
                $product->specs()->whereNotIn('id', $specIds)->delete();
                // Preserve the previous contract: an absent/empty variants array does not archive all SKUs.
                if (! empty($input['variants'])) {
                    $variantIds = [];
                    foreach ($input['variants'] as $index => $data) {
                        if (empty($data['id']) && empty($data['sku']) && ! isset($data['price']) && empty($data['thumbnail'])) {
                            continue;
                        }
                        $variant = empty($data['id']) ? $product->variants()->make()
                            : $product->variants()->whereKey($data['id'])->lockForUpdate()->first();
                        if (! $variant) {
                            throw ValidationException::withMessages(["variants.$index.id" => 'Biến thể không thuộc sản phẩm này.']);
                        }
                        $this->fillVariant($variant, $data, $media);
                        $variantIds[] = $variant->id;
                    }
                    $product->variants()->whereNotIn('id', $variantIds)->delete();
                }
                $media->afterCommit();

                return $product->id;
            });
        } catch (\Throwable $error) {
            $media->rollback();
            throw $error;
        }
    }

    public function variant(array $data, ?int $id = null): int
    {
        $media = new CatalogMedia;
        try {
            return DB::transaction(function () use ($data, $id, $media) {
                // Use the same product -> variant lock order as aggregate editing.
                $productId = $id === null ? (int) $data['product_id'] : (int) ProductVariant::findOrFail($id)->product_id;
                Product::whereKey($productId)->lockForUpdate()->firstOrFail();
                $variant = $id === null ? new ProductVariant(['product_id' => $productId]) : ProductVariant::whereKey($id)->lockForUpdate()->firstOrFail();
                if (isset($data['product_id']) && (int) $data['product_id'] !== $productId) {
                    throw ValidationException::withMessages(['product_id' => 'Không thể chuyển SKU sang sản phẩm khác.']);
                }
                $this->fillVariant($variant, $data, $media);
                $media->afterCommit();

                return $variant->id;
            });
        } catch (\Throwable $error) {
            $media->rollback();
            throw $error;
        }
    }

    private function fillVariant(ProductVariant $variant, array $data, CatalogMedia $media): void
    {
        $variant->fill(Arr::only($data, ['sku', 'price', 'cost_price', 'color', 'storage', 'barcode']));
        if (! $variant->exists && empty($variant->barcode)) {
            $variant->barcode = $this->generateBarcode();
        }
        if (($data['thumbnail'] ?? null) instanceof UploadedFile) {
            $path = $media->upload($data['thumbnail'], 'variants');
            $media->replace($variant->thumbnail);
            $variant->thumbnail = $path;
        }
        $variant->save();
    }
}
