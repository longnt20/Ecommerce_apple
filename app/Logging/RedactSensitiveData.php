<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\LogRecord;
use Throwable;

final class RedactSensitiveData
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(fn (LogRecord $record) => $this->process($record));
    }

    public function process(LogRecord $record): LogRecord
    {
        return $record->with(message: $this->text($record->message),
            context: $this->clean($record->context), extra: $this->clean($record->extra));
    }

    private function clean(array $values, int $depth = 0): array
    {
        if ($depth >= 5) {
            return ['redacted' => true];
        }
        $result = [];
        foreach (array_slice($values, 0, 50, true) as $key => $value) {
            if (preg_match('/password|secret|token|authorization|cookie|email|phone|address|payload|bindings|^(request|response)$|(^|_)(data|body|headers|url)($|_)/i', (string) $key)) {
                $result[$key] = '[REDACTED]';
            } elseif ($value instanceof Throwable) {
                $result[$key] = ['type' => $value::class, 'code' => $value->getCode(),
                    'file' => basename($value->getFile()), 'line' => $value->getLine()];
            } elseif (is_array($value)) {
                $result[$key] = $this->clean($value, $depth + 1);
            } elseif (is_object($value) || is_resource($value)) {
                $result[$key] = '[OBJECT REDACTED]';
            } else {
                $result[$key] = is_string($value) ? $this->text($value) : $value;
            }
        }

        return $result;
    }

    private function text(string $value): string
    {
        // SQL exception messages contain interpolated bindings; never retain them.
        if (preg_match('/SQLSTATE|\b(select|insert into|update|delete from)\b.+\b(from|values|set|where)\b/is', $value)) {
            return '[DATABASE ERROR REDACTED]';
        }
        $value = preg_replace('/Bearer\s+\S+|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|(password|token|secret|code_verifier)\s*[=:]\s*\S+/i', '[REDACTED]', $value);

        return mb_substr($value, 0, 2000);
    }
}
