<?php

namespace LaravelExpoUpdates\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelExpoUpdates\Services\StatsService;
use LaravelExpoUpdates\Models\Project;

/**
 * Middleware for tracking update requests.
 */
class TrackUpdateRequests
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
        $response = $next($request);

        // Only track manifest requests
        if ($request->is('*/manifest')) {
            $project = $request->route('projectSlug')
                ? Project::where('slug', $request->route('projectSlug'))->first()
                : Project::find($request->header('expo-project-id'));

            if ($project) {
                // Get platform and runtime version from headers (Expo protocol)
                $platform = $request->header('expo-platform');
                $runtimeVersion = $request->header('expo-runtime-version');
                
                $this->statsService->recordRequest(
                    $project,
                    $platform,
                    $runtimeVersion
                );
            }
        }

        return $response;
    }
} 