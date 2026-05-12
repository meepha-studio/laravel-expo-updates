<?php

namespace LaravelExpoUpdates\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use LaravelExpoUpdates\Services\ManifestService;
use LaravelExpoUpdates\Services\AssetService;
use LaravelExpoUpdates\Models\Project;

/**
 * Controller for handling Expo Updates protocol requests.
 */
class ExpoUpdatesController extends Controller
{
    protected $manifestService;
    protected $assetService;

    /**
     * Create a new controller instance.
     *
     * @param ManifestService $manifestService
     * @param AssetService $assetService
     */
    public function __construct(ManifestService $manifestService, AssetService $assetService)
    {
        $this->manifestService = $manifestService;
        $this->assetService = $assetService;
    }

    /**
     * Get the latest manifest for a project.
     *
     * @param Request $request
     * @param string|null $projectSlug
     * @return Response
     */
    public function manifest(Request $request, ?string $projectSlug = null)
    {
        $project = $this->resolveProject($request, $projectSlug);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $contentType = $request->prefers([
            'application/expo+json',
            'application/json',
            'multipart/mixed',
        ]);

        if ($contentType === 'multipart/mixed') {
            return response()->json(['error' => 'Multipart responses are not implemented'], 406);
        }

        if (!$contentType) {
            return response()->json(['error' => 'Unsupported response content type'], 406);
        }

        $platform = $request->header('expo-platform');
        $runtimeVersion = $request->header('expo-runtime-version');
        $manifestFilters = $this->parseManifestFilters($request->header('expo-manifest-filters'));

        $manifest = $this->manifestService->getLatestManifest($project, $platform, $runtimeVersion, $manifestFilters);

        if (!$manifest) {
            // No update available, return 204 No Content with appropriate headers
            return response('', 204)
                ->header('expo-protocol-version', '1')
                ->header('expo-sfv-version', '0')
                ->header('cache-control', 'private, max-age=0');
        }

        $response = response()->json($manifest, 200, [
            'content-type' => $contentType,
        ]);

        // Add required headers
        $response->header('expo-protocol-version', '1');
        $response->header('expo-sfv-version', '0');
        $response->header('cache-control', 'private, max-age=0');

        // Add manifest filters if provided
        if ($manifestFilters) {
            $response->header('expo-manifest-filters', $this->formatManifestFilters($manifestFilters));
        }

        // Add server-defined headers if any
        $serverHeaders = $this->manifestService->getServerDefinedHeaders($project);
        if ($serverHeaders) {
            $response->header('expo-server-defined-headers', $this->formatServerHeaders($serverHeaders));
        }

        // Handle code signing if enabled
        if (config('expo-updates.code_signing.enabled')) {
            $signature = $this->manifestService->signManifest($project, $manifest);
            if ($signature) {
                $response->header('expo-signature', $signature);
            }
        }

        return $response;
    }

    /**
     * Get an asset file.
     *
     * @param Request $request
     * @param string $key
     * @param string|null $projectSlug
     * @return Response
     */
    public function asset(Request $request, string $key, ?string $projectSlug = null)
    {
        $project = $this->resolveProject($request, $projectSlug);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $asset = $this->assetService->getAsset($project, $key);

        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        $response = response()->file(
            Storage::disk(config('expo-updates.assets.disk'))->path($asset->path),
            [
                'Content-Type' => $asset->content_type,
                'Cache-Control' => 'public, max-age=' . config('expo-updates.cache.asset_ttl') . ', immutable',
            ]
        );

        // Add any asset-specific headers from extensions
        $assetHeaders = $this->assetService->getAssetHeaders($project, $key);
        foreach ($assetHeaders as $header => $value) {
            $response->header($header, $value);
        }

        return $response;
    }

    /**
     * Parse manifest filters from SFV dictionary format.
     *
     * @param string|null $filters
     * @return array|null
     */
    protected function parseManifestFilters(?string $filters): ?array
    {
        if (!$filters) {
            return null;
        }

        // Parse SFV dictionary format
        $result = [];
        $pairs = explode(',', $filters);
        foreach ($pairs as $pair) {
            $parts = explode('=', trim($pair));
            if (count($parts) === 2) {
                $result[trim($parts[0])] = trim($parts[1], '"');
            }
        }

        return $result;
    }

    /**
     * Format manifest filters to SFV dictionary format.
     *
     * @param array $filters
     * @return string
     */
    protected function formatManifestFilters(array $filters): string
    {
        $pairs = [];
        foreach ($filters as $key => $value) {
            $pairs[] = $key . '="' . $value . '"';
        }
        return implode(', ', $pairs);
    }

    /**
     * Format server headers to SFV dictionary format.
     *
     * @param array $headers
     * @return string
     */
    protected function formatServerHeaders(array $headers): string
    {
        $pairs = [];
        foreach ($headers as $key => $value) {
            $pairs[] = $key . '="' . $value . '"';
        }
        return implode(', ', $pairs);
    }

    /**
     * Resolve a project from the request or slug.
     *
     * @param Request $request
     * @param string|null $projectSlug
     * @return Project|null
     */
    protected function resolveProject(Request $request, ?string $projectSlug = null): ?Project
    {
        // First try to get project from slug in URL
        if ($projectSlug) {
            return Project::where('slug', $projectSlug)->first();
        }

        // Then try to get project from header
        $projectId = $request->header('expo-project-id');
        if ($projectId) {
            return Project::find($projectId);
        }

        // Finally, try to get default project from config
        $defaultProject = config('expo-updates.default_project');
        if ($defaultProject) {
            return Project::where('slug', $defaultProject)->first();
        }

        return null;
    }
} 