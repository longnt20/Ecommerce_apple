<?php

namespace App\Modules\Catalog\Infrastructure;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Media belongs to this save attempt; existing files survive every failed save. */
final class CatalogMedia
{
    private array $created = [];

    private array $obsolete = [];

    public function upload(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'public');
        if ($path === false) {
            throw new \RuntimeException('Cannot store catalog image.');
        }
        $this->created[] = $path;

        return $path;
    }

    public function replace(?string $path): void
    {
        if ($path && preg_match('~^(products|variants)/[a-zA-Z0-9_./-]+$~D', $path) && ! str_contains($path, '..')) {
            $this->obsolete[] = $path;
        }
    }

    public function afterCommit(): void
    {
        $paths = array_values(array_diff(array_unique($this->obsolete), $this->created));
        DB::afterCommit(static function () use ($paths) {
            try {
                foreach ($paths as $path) {
                    if (! Product::withTrashed()->where('thumbnail', $path)->exists()
                        && ! Product::withTrashed()->whereJsonContains('gallery', $path)->exists()
                        && ! ProductVariant::withTrashed()->where('thumbnail', $path)->exists()) {
                        Storage::disk('public')->delete($path);
                    }
                }
            } catch (\Throwable $error) {
                // A cleanup failure must never turn a committed save into a rollback response.
                report($error);
            }
        });
    }

    public function rollback(): void
    {
        Storage::disk('public')->delete($this->created);
    }
}
