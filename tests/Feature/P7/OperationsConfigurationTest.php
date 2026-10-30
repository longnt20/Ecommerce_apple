<?php

namespace Tests\Feature\P7;

use App\Logging\RedactSensitiveData;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class OperationsConfigurationTest extends TestCase
{
    public function test_ci_is_fail_closed_and_enables_all_critical_sandbox_suites(): void
    {
        $workflow = Yaml::parseFile(base_path('.github/workflows/quality.yml'));
        $this->assertSame(['contents' => 'read'], $workflow['permissions']);
        $job = $workflow['jobs']['regression'];
        foreach (['P1', 'P2', 'P3', 'P4', 'P6', 'P7', 'P8'] as $phase) {
            $this->assertSame('1', $job['env'][$phase.'_SCHEMA_TESTS']);
        }
        foreach ($job['steps'] as $step) {
            $this->assertFalse($step['continue-on-error'] ?? false);
        }
        $this->assertStringContainsString('php vendor/bin/phpunit', json_encode($job['steps'], JSON_UNESCAPED_SLASHES));
        $this->assertSame('array', $job['env']['MAIL_MAILER']);
        $this->assertSame('false', $job['env']['TELESCOPE_ENABLED']);
        $before = hash_file('sha256', base_path('.env'));
        $guard = new Process([PHP_BINARY, 'tests/Support/prepare-ci.php'], base_path(), ['GITHUB_ACTIONS' => 'false', 'CI' => 'true']);
        $this->assertSame(2, $guard->run());
        $this->assertSame($before, hash_file('sha256', base_path('.env')));
    }

    public function test_framework_channel_applies_redaction_before_writing(): void
    {
        config(['logging.channels.p7_test' => ['driver' => 'monolog', 'handler' => TestHandler::class,
            'tap' => [RedactSensitiveData::class], 'replace_placeholders' => true]]);
        $logger = Log::channel('p7_test');
        $logger->error('Failure {token}', ['token' => 'private-token', 'exception' => new \Error('private-trace'),
            'order_id' => 123, 'request_id' => 'synthetic-correlation']);
        $records = $logger->getHandlers()[0]->getRecords();
        $this->assertStringNotContainsString('private-token', json_encode($records));
        $this->assertStringNotContainsString('private-trace', json_encode($records));
        $this->assertSame(123, $records[0]->context['order_id']);
        $this->assertSame('synthetic-correlation', $records[0]->context['request_id']);
        $this->assertTrue(config('queue.connections.database.after_commit'));
    }

    public function test_telescope_requires_active_admin_middleware_even_in_local_environment(): void
    {
        $this->assertContains('auth', config('telescope.middleware'));
        $this->assertContains('is_admin', config('telescope.middleware'));
        foreach (config('telescope.watchers') as $watcher) {
            $this->assertFalse(is_array($watcher) ? $watcher['enabled'] : $watcher);
        }
    }
}
