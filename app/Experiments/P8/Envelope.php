<?php

namespace App\Experiments\P8;

use InvalidArgumentException;

final class Envelope
{
    public static function validate(array $event): void
    {
        if (($event['schema_version'] ?? null) !== 1) {
            throw new InvalidArgumentException('unsupported_schema');
        }
        if (($event['type'] ?? null) !== 'catalog.product-snapshot') {
            throw new InvalidArgumentException('unsupported_event_type');
        }
        self::keys($event, ['id', 'type', 'schema_version', 'product_id', 'aggregate_version', 'occurred_at', 'payload']);
        if (! is_string($event['id']) || ! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D', $event['id'])
            || ! is_int($event['product_id']) || $event['product_id'] < 1
            || ! is_int($event['aggregate_version']) || $event['aggregate_version'] < 1
            || ! is_string($event['occurred_at']) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\+00:00|Z)$/D', $event['occurred_at'])
            || ! is_array($event['payload'])) {
            throw new InvalidArgumentException('invalid_envelope');
        }
        $payload = $event['payload'];
        self::keys($payload, ['name', 'slug', 'category_id', 'listed', 'sku_count', 'min_price', 'max_price']);
        foreach (['name', 'slug'] as $field) {
            if (! is_string($payload[$field]) || mb_strlen($payload[$field]) > 255) {
                throw new InvalidArgumentException('invalid_payload');
            }
        }
        if (! is_bool($payload['listed']) || ! is_int($payload['sku_count']) || $payload['sku_count'] < 0 || $payload['sku_count'] > 4294967295
            || ($payload['category_id'] !== null && (! is_int($payload['category_id']) || $payload['category_id'] < 1))) {
            throw new InvalidArgumentException('invalid_payload');
        }
        foreach (['min_price', 'max_price'] as $field) {
            if ($payload[$field] !== null && (! is_string($payload[$field]) || ! preg_match('/^\d{1,10}\.\d{2}$/D', $payload[$field]))) {
                throw new InvalidArgumentException('invalid_money');
            }
        }
        if (($payload['sku_count'] === 0) !== ($payload['min_price'] === null && $payload['max_price'] === null)
            || (($payload['min_price'] === null) !== ($payload['max_price'] === null))
            || ($payload['min_price'] !== null && (int) str_replace('.', '', $payload['min_price']) > (int) str_replace('.', '', $payload['max_price']))) {
            throw new InvalidArgumentException('invalid_totals');
        }
    }

    public static function hash(array $event): string
    {
        $sort = function (array $value) use (&$sort): array {
            ksort($value);
            foreach ($value as &$item) {
                if (is_array($item)) {
                    $item = $sort($item);
                }
            }

            return $value;
        };

        return hash('sha256', json_encode($sort($event), JSON_THROW_ON_ERROR));
    }

    private static function keys(array $data, array $expected): void
    {
        if (array_diff(array_keys($data), $expected) || array_diff($expected, array_keys($data))) {
            throw new InvalidArgumentException('invalid_fields');
        }
    }
}
