<?php

namespace App\Experiments\P8;

use Closure;
use PDO;

final class ProjectionConsumer
{
    public function __construct(private PDO $pdo)
    {
        SandboxGuard::role($pdo, 'consumer');
    }

    public function consume(array $event, ?Closure $barrier = null): string
    {
        $hash = Envelope::hash($event);
        $id = is_string($event['id'] ?? null) && preg_match('/^[a-f0-9-]{36}$/D', $event['id']) ? $event['id'] : 'invalid';
        try {
            Envelope::validate($event);
            $this->pdo->beginTransaction();
            $insert = $this->pdo->prepare("INSERT IGNORE INTO p8_inbox (event_id,envelope_sha256,product_id,aggregate_version,outcome,consumed_at) VALUES (?,?,?,?,'processing',UTC_TIMESTAMP(6))");
            $insert->execute([$id, $hash, $event['product_id'], $event['aggregate_version']]);
            if ($insert->rowCount() === 0) {
                $query = $this->pdo->prepare('SELECT envelope_sha256 FROM p8_inbox WHERE event_id=? FOR UPDATE');
                $query->execute([$id]);
                $existingHash = $query->fetchColumn();
                if ($existingHash === false) {
                    throw new \InvalidArgumentException('stream_version_conflict');
                }
                if (! hash_equals($hash, $existingHash)) {
                    throw new \InvalidArgumentException('event_identity_conflict');
                }
                $outcome = 'duplicate';
            } else {
                $barrier?->__invoke('before-effect');
                $this->pdo->prepare("INSERT INTO p8_catalog_cards (product_id,name,slug,listed,sku_count,last_event_id) VALUES (?,'','',0,0,?) ON DUPLICATE KEY UPDATE product_id=product_id")->execute([$event['product_id'], $id]);
                $query = $this->pdo->prepare('SELECT aggregate_version,last_event_id FROM p8_catalog_cards WHERE product_id=? FOR UPDATE');
                $query->execute([$event['product_id']]);
                $current = $query->fetch();
                if ((int) $current['aggregate_version'] === $event['aggregate_version'] && $current['last_event_id'] !== $id) {
                    throw new \InvalidArgumentException('stream_version_conflict');
                }
                $outcome = (int) $current['aggregate_version'] < $event['aggregate_version'] ? 'applied' : 'stale';
                if ($outcome === 'applied') {
                    $p = $event['payload'];
                    $this->pdo->prepare('UPDATE p8_catalog_cards SET aggregate_version=?,name=?,slug=?,category_id=?,listed=?,sku_count=?,min_price=?,max_price=?,last_event_id=?,apply_count=apply_count+1 WHERE product_id=?')
                        ->execute([$event['aggregate_version'], $p['name'], $p['slug'], $p['category_id'], (int) $p['listed'], $p['sku_count'], $p['min_price'], $p['max_price'], $id, $event['product_id']]);
                }
                $barrier?->__invoke('after-effect-before-commit');
                $this->pdo->prepare('UPDATE p8_inbox SET outcome=? WHERE event_id=?')->execute([$outcome, $id]);
            }
            $this->audit($id, $outcome);
            $this->pdo->commit();
            $barrier?->__invoke('after-commit');

            return $outcome;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if (! $error instanceof \InvalidArgumentException) {
                throw $error;
            }

            return $this->quarantine($hash, $id, $error->getMessage());
        }
    }

    public function malformed(string $bytes): string
    {
        return $this->quarantine(hash('sha256', $bytes), 'invalid', 'invalid_json');
    }

    private function quarantine(string $hash, string $id, string $reason): string
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('INSERT INTO p8_quarantine (envelope_sha256,event_id,reason_code,first_seen_at,last_seen_at) VALUES (?,?,?,UTC_TIMESTAMP(6),UTC_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE attempts=attempts+1,last_seen_at=UTC_TIMESTAMP(6)')->execute([$hash, $id, $reason]);
            $this->audit($id, 'quarantined');
            $this->pdo->commit();

            return 'quarantined';
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }

    private function audit(string $id, string $outcome): void
    {
        $this->pdo->prepare('INSERT INTO p8_delivery_audit (event_id,outcome,recorded_at) VALUES (?,?,UTC_TIMESTAMP(6))')->execute([$id, $outcome]);
    }
}
