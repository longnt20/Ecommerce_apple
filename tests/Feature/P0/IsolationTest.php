<?php

namespace Tests\Feature\P0;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\TestingEnvironment;
use Tests\TestCase;

class IsolationTest extends TestCase
{
    public function test_application_boots_with_safe_adapters_and_no_production_environment(): void
    {
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('.env.testing', $this->app->environmentFile());
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('session.driver'));
        $this->assertFalse(config('telescope.enabled'));
        $this->assertTrue(Http::preventingStrayRequests());
        Bus::assertNothingDispatched();
        Queue::assertNothingPushed();
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
    }

    public function test_default_mode_rejects_even_an_explicit_mysql_connection(): void
    {
        if (TestingEnvironment::database()['mode'] !== 'disabled') {
            $this->markTestSkipped('This assertion applies to the default database-disabled mode.');
        }

        $this->assertSame([], config('database.connections'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Database connection [mysql] not configured.');
        DB::connection('mysql');
    }
}
