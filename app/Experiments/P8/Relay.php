<?php

namespace App\Experiments\P8;

use Closure;
use PDO;

final class Relay
{
    public function __construct(private PDO $pdo, private FileSpool $spool)
    {
        SandboxGuard::role($pdo, 'producer');
    }

    public function publish(int $limit = 100, ?Closure $afterPublish = null): array
    {
        $result = ['published' => 0, 'failed' => 0];
        for ($i = 0; $i < max(1, min(1000, $limit)); $i++) {
            $message = null;
            $this->pdo->beginTransaction();
            try {
                $message = $this->pdo->query('SELECT * FROM p8_outbox_messages WHERE published_at IS NULL ORDER BY created_at,id LIMIT 1 FOR UPDATE SKIP LOCKED')->fetch();
                if (! $message) {
                    $this->pdo->commit();
                    break;
                }
                $event = json_decode($message['envelope'], true, 32, JSON_THROW_ON_ERROR);
                $this->spool->publish($event);
                $afterPublish?->__invoke(); // Test barrier: crash after durable publish, before DB acknowledgement.
                $this->pdo->prepare('UPDATE p8_outbox_messages SET published_at=UTC_TIMESTAMP(6),attempts=attempts+1 WHERE id=?')->execute([$message['id']]);
                $this->pdo->prepare("INSERT INTO p8_publish_attempts (event_id,outcome,recorded_at) VALUES (?,'published',UTC_TIMESTAMP(6))")->execute([$message['id']]);
                $this->pdo->commit();
                $result['published']++;
            } catch (\Throwable $error) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                if (! isset($message['id'])) {
                    throw $error;
                }
                $this->pdo->beginTransaction();
                $this->pdo->prepare('UPDATE p8_outbox_messages SET attempts=attempts+1 WHERE id=?')->execute([$message['id']]);
                $this->pdo->prepare("INSERT INTO p8_publish_attempts (event_id,outcome,recorded_at) VALUES (?,'transport_failed',UTC_TIMESTAMP(6))")->execute([$message['id']]);
                $this->pdo->commit();
                $result['failed']++;
                break; // Avoid spinning on an undeliverable head message.
            }
        }

        return $result;
    }

    public function replay(string $eventId): void
    {
        // Preserve the original event ID/version; never regenerate a new business event for retry.
        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('SELECT id FROM p8_outbox_messages WHERE id=? FOR UPDATE');
            $query->execute([$eventId]);
            if (! $query->fetch()) {
                throw new \RuntimeException('unknown_event');
            }
            $this->pdo->prepare('UPDATE p8_outbox_messages SET published_at=NULL WHERE id=?')->execute([$eventId]);
            $this->pdo->prepare("INSERT INTO p8_publish_attempts (event_id,outcome,recorded_at) VALUES (?,'replay_requested',UTC_TIMESTAMP(6))")->execute([$eventId]);
            $this->pdo->commit();
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }
}
