<?php

namespace Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Nightwatch\Middleware\DisableNightwatch;
use Laravel\Nightwatch\Middleware\DisableNightwatchLogs;
use RuntimeException;
use Tests\TestCase;

class DisableNightwatchTest extends TestCase
{
    public function it_disables_all_ingest_activity_even_when_exceptions_occur(): void
    {
        $this->forceRequestExecutionState();
        $ingest = $this->fakeIngest();

        Route::middleware(DisableNightwatch::class)
            ->get('/users', function (): void {
                throw new RuntimeException('Unhandled error');
            });

        $response = $this->get('/users');
        $response->assertServerError();

        $ingest->assertWrittenTimes(0);
        $this->assertFalse($this->core->shouldSample);
        $this->assertFalse($this->core->shouldSampleOnException);
    }

    public function it_disables_sampling_for_all_events(): void
    {
        $request = Request::create('/test');

        $nextCalledWith = null;
        $next = function ($request) use (&$nextCalledWith) {
            $nextCalledWith = $request;

            return 'response';
        };

        $middleware = new DisableNightwatch($this->core);
        $response = $middleware->handle($request, $next);

        $this->assertSame('response', $response);
        $this->assertSame($request, $nextCalledWith);
        $this->assertFalse($this->core->shouldSample);
        $this->assertFalse($this->core->shouldSampleOnException);
    }

    public function it_disables_log_ingest_but_allows_exception_ingest(): void
    {
        $this->forceRequestExecutionState();
        $ingest = $this->fakeIngest();

        Route::middleware(DisableNightwatchLogs::class)
            ->get('/users', function (): void {
                Log::channel('nightwatch')->info('Hello world');
                throw new RuntimeException('Unhandled error');
            });

        $response = $this->get('/users');
        $response->assertServerError();
        $ingest->digest();

        $ingest->assertWrittenTimes(1);
        $ingest->assertLatestWrite('exception:0.message', 'Unhandled error');

        $this->assertTrue($this->core->shouldSample);
        $this->assertTrue($this->core->shouldSampleOnException);
    }

    public function it_disables_sampling_for_logs_only(): void
    {
        $this->core->shouldSample = true;
        $this->core->shouldSampleOnException = true;

        $request = Request::create('/test');

        $nextCalledWith = null;
        $next = function ($request) use (&$nextCalledWith) {
            $nextCalledWith = $request;

            return 'response';
        };

        $middleware = new DisableNightwatchLogs($this->core);
        $response = $middleware->handle($request, $next);

        $this->assertSame('response', $response);
        $this->assertSame($request, $nextCalledWith);
        $this->assertFalse($this->core->shouldSample);
        $this->assertTrue($this->core->shouldSampleOnException);
    }
}
