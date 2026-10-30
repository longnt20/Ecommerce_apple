<?php

namespace Tests\Unit\P7;

use App\Logging\RedactSensitiveData;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LogRedactionTest extends TestCase
{
    public function test_written_record_does_not_expose_sql_bindings_tokens_or_exception_trace(): void
    {
        $handler = new TestHandler;
        $logger = new Logger('test', [$handler]);
        $redactor = new RedactSensitiveData;
        $logger->pushProcessor(fn ($record) => $redactor->process($record));
        $logger->error('SQLSTATE insert into users values (private@example.test, secret-value)', [
            'exception' => new \RuntimeException('private exception secret-value'),
            'payload' => ['token' => 'secret-value'], 'nested' => ['authorization' => 'Bearer private'],
            'order_id' => 123, 'reason_code' => 'inventory_conflict',
        ]);
        $written = json_encode($handler->getRecords());
        foreach (['secret-value', 'private@example.test', 'private exception', 'Bearer private'] as $private) {
            $this->assertStringNotContainsString($private, $written);
        }
        $this->assertStringContainsString('inventory_conflict', $written);
        $this->assertSame(123, $handler->getRecords()[0]->context['order_id']);
    }
}
