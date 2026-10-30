<?php

namespace Tests\Unit\P8;

use App\Experiments\P8\Envelope;
use App\Experiments\P8\SandboxGuard;
use PHPUnit\Framework\TestCase;

class ProtocolTest extends TestCase
{
    private function event(): array
    {
        return ['id' => '00000000-0000-4000-8000-000000000001', 'type' => 'catalog.product-snapshot',
            'schema_version' => 1, 'product_id' => 1, 'aggregate_version' => 1, 'occurred_at' => '2026-10-30T00:00:00Z',
            'payload' => ['name' => 'Synthetic', 'slug' => 'synthetic', 'category_id' => null, 'listed' => true,
                'sku_count' => 1, 'min_price' => '123456.00', 'max_price' => '123456.00']];
    }

    public function test_canonical_hash_ignores_object_key_order_and_rejects_protocol_drift(): void
    {
        $valid = $this->event();
        Envelope::validate($valid);
        $this->assertSame(Envelope::hash($valid), Envelope::hash(array_reverse($valid, true)));
        foreach (['schema_version' => 0, 'aggregate_version' => 0, 'product_id' => '1', 'type' => 'unknown'] as $key => $value) {
            try {
                Envelope::validate(array_replace($valid, [$key => $value]));
                $this->fail('Invalid protocol must fail closed.');
            } catch (\InvalidArgumentException $error) {
                $this->assertNotSame('', $error->getMessage());
            }
        }
        $valid['payload']['email'] = 'must-not-cross-boundary@example.test';
        $this->expectException(\InvalidArgumentException::class);
        Envelope::validate($valid);
    }

    public function test_source_production_and_persistent_test_databases_are_rejected(): void
    {
        foreach (['laravel', 'apple_project', 'apple_project_p0_tests', 'apple_project_p1_restore_0123456789ab', 'apple_project_p1_fresh_invalid'] as $name) {
            try {
                SandboxGuard::database($name);
                $this->fail('Only disposable fresh P8 schemas may be used.');
            } catch (\RuntimeException $error) {
                $this->assertSame('p8_sandbox_required', $error->getMessage());
            }
        }
    }
}
