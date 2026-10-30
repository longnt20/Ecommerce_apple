<?php

namespace Tests\Unit\Review;

use App\Experiments\P8\FileSpool;
use PHPUnit\Framework\TestCase;

class FileSpoolFailureTest extends TestCase
{
    public function test_serialization_failure_removes_temporary_file_without_publishing(): void
    {
        $directory = dirname(__DIR__, 3).'/tests/.runtime/p8-spool-'.bin2hex(random_bytes(6));
        $this->assertTrue(mkdir($directory, 0700));
        try {
            $event = ['id' => '00000000-0000-4000-8000-000000000001', 'type' => 'catalog.product-snapshot',
                'schema_version' => 1, 'product_id' => 1, 'aggregate_version' => 1, 'occurred_at' => '2026-10-30T00:00:00Z',
                'payload' => ['name' => "\xB1", 'slug' => 'demo', 'category_id' => null, 'listed' => true,
                    'sku_count' => 1, 'min_price' => '100.00', 'max_price' => '100.00']];
            try {
                (new FileSpool($directory))->publish($event);
                $this->fail('Invalid UTF-8 must not produce a partial message.');
            } catch (\JsonException) {
                $this->assertSame([], glob($directory.'/*'));
            }
        } finally {
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
