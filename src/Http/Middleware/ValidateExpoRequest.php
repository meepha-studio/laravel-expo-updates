<?php

namespace LaravelExpoUpdates\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateExpoRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        // Validate protocol version
        if ($request->header('expo-protocol-version') !== '1') {
            return response()->json(['error' => 'Unsupported protocol version'], 406);
        }

        // Validate platform
        $platform = $request->header('expo-platform');
        if (!in_array($platform, ['ios', 'android'])) {
            return response()->json(['error' => 'Invalid platform'], 400);
        }

        // Validate runtime version
        if (!$request->header('expo-runtime-version')) {
            return response()->json(['error' => 'Runtime version is required'], 400);
        }

        // Validate accept header
        $accept = $request->header('accept');
        if (!$accept || !preg_match('/(application\/expo\+json|application\/json|multipart\/mixed)/', $accept)) {
            return response()->json(['error' => 'Invalid accept header'], 406);
        }

        return $next($request);
    }
} 