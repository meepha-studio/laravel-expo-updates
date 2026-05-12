<?php

namespace LaravelExpoUpdates\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelExpoUpdates\Services\StatsService;
use LaravelExpoUpdates\Models\Project;

/**
 * Middleware for tracking update requests.
 */
class AuthenticateExpoUploadRequest
{
    protected $statsService;

    /**
     * Create a new middleware instance.
     *
     * @param StatsService $statsService
     */
    public function __construct(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $expected = config('expo-updates.upload_token');

        if (!$expected) {
            return response()->json(['error' => 'Upload endpoint is not configured'], 403);
        }

        $provided = $request->bearerToken();

        if (!$provided || !hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
