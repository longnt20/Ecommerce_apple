<?php

namespace App\Experiments\P8;

use RuntimeException;

/** Local durable transport experiment, not a network broker or production service. */
final class FileSpool
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = SandboxGuard::spool($directory);
    }

    public function publish(array $event): void
    {
        Envelope::validate($event);
        $file = $this->directory.'/'.$event['id'].'.json';
        if (is_file($file)) {
            if (Envelope::hash($this->read($file)) !== Envelope::hash($event)) {
                throw new RuntimeException('spool_identity_conflict');
            }

            return;
        }
        $temporary = $file.'.'.bin2hex(random_bytes(6)).'.tmp';
        $stream = fopen($temporary, 'xb');
        try {
            $bytes = json_encode($event, JSON_THROW_ON_ERROR);
            if (strlen($bytes) > 16384 || fwrite($stream, $bytes) !== strlen($bytes) || ! fflush($stream) || ! fsync($stream)) {
                throw new RuntimeException('spool_write_failed');
            }
        } finally {
            fclose($stream);
        }
        try {
            if (! rename($temporary, $file)) {
                throw new RuntimeException('spool_publish_failed');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    public function files(int $limit = 100, int $offset = 0): array
    {
        // Fixture transport: retained files support replay; operator supplies an offset for paging.
        $files = glob($this->directory.'/*.json');
        sort($files);

        return array_slice($files, max(0, $offset), max(1, min(1000, $limit)));
    }

    public function read(string $file): array
    {
        return json_decode($this->bytes($file), true, 32, JSON_THROW_ON_ERROR);
    }

    public function bytes(string $file): string
    {
        if (dirname($file) !== $this->directory || is_link($file) || ! is_file($file) || filesize($file) > 16384) {
            throw new RuntimeException('invalid_spool_file');
        }

        return file_get_contents($file);
    }
}
