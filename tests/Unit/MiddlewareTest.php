<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Nightwatch\Middleware\DisableNightwatch;
use Laravel\Nightwatch\Middleware\DisableNightwatchLogs;
use RuntimeException;
use Tests\TestCase;

use function report;

class MiddlewareTest extends TestCase
{
    public function test_it_disables_all_logging(): void
    {
        $ingest = $this->fakeIngest();
        $this->core->config['sampling']['exceptions'] = 1.0;
        Route::middleware(DisableNightwatch::class)->get('/users', function (): string {
            Log::channel('nightwatch')->info('Hello');
            report(new RuntimeException('Whoops!'));

            return 'ok';
        });

        $response = $this->get('/users');

        $response->assertOk();
        $ingest->assertWrittenTimes(0);
        $this->assertSame(0, $this->core->executionState->logs);
        $this->assertSame(0, $this->core->executionState->exceptions);
    }

    public function test_it_disables_regular_logging_but_not_exceptions(): void
    {
        $ingest = $this->fakeIngest();
        $this->core->config['sampling']['exceptions'] = 1.0;
        Route::middleware(DisableNightwatchLogs::class)->get('/users', function (): string {
            Log::channel('nightwatch')->info('Hello');
            report(new RuntimeException('Whoops!'));

            return 'ok';
        });

        $response = $this->get('/users');

        $response->assertOk();
        $ingest->assertWrittenTimes(1);
        $ingest->assertLatestWrite('exception:0.message', 'Whoops!');
        $this->assertSame(0, $this->core->executionState->logs);
    }
}
