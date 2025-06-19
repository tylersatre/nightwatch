<?php

namespace Laravel\Nightwatch\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nightwatch\Core;
use Laravel\Nightwatch\State\CommandState;
use Laravel\Nightwatch\State\RequestState;

final class OnlyExceptions
{
    /**
     * @param  Core<RequestState|CommandState>  $nightwatch
     */
    public function __construct(private readonly Core $nightwatch) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->nightwatch->shouldSample = false;

        return $next($request);
    }
}
