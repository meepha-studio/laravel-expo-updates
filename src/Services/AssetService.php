<?php

namespace LaravelExpoUpdates\Services;

use LaravelExpoUpdates\Models\Asset;
use LaravelExpoUpdates\Models\Project;
use Illuminate\Support\Facades\Storage;

/**
 * Service for handling asset operations.
 */
class AssetService
{
    /**
     * Get an asset by its key for a specific project.
     *
     * @param Project|string $project Project instance or slug
     * @param string $key Asset key
     * @return Asset|null
     */
    public function getAsset($project, string $key): ?Asset
    {
        $project = $this->resolveProject($project);
        if (!$project) {
            return null;
        }

        return Asset::where('project_id', $project->id)
            ->where('key', $key)
            ->first();
    }

    /**
     * Get asset-specific headers for a project.
     *
     * @param Project|string $project Project instance or slug
     * @param string $key Asset key
     * @return array
     */
    public function getAssetHeaders($project, string $key): array
    {
        $project = $this->resolveProject($project);
        if (!$project) {
            return [];
        }

        return $project->config['asset_headers'][$key] ?? config('expo-updates.asset_headers.' . $key, []);
    }

    /**
     * Store an asset for a project.
     *
     * @param Project|string $project Project instance or slug
     * @param string $key Asset key
     * @param string $content Asset content
     * @param string $contentType Asset content type
     * @param string|null $fileExtension Optional file extension
     * @return Asset
     */
    public function storeAsset($project, string $key, string $content, string $contentType, ?string $fileExtension = null): Asset
    {
        $project = $this->resolveProject($project);
        if (!$project) {
            throw new \InvalidArgumentException('Invalid project');
        }

        $path = config('expo-updates.assets.path') . '/' . $project->slug . '/' . $key;
        if ($fileExtension) {
            $path .= '.' . ltrim($fileExtension, '.');
        }

        // Store the file
        Storage::disk(config('expo-updates.assets.disk'))->put($path, $content);

        // Create or update the asset record
        return Asset::updateOrCreate(
            [
                'project_id' => $project->id,
                'key' => $key,
            ],
            [
                'content_type' => $contentType,
                'file_extension' => $fileExtension,
                'path' => $path,
                'hash' => base64_encode(hash('sha256', $content, true)),
                'url' => Storage::disk(config('expo-updates.assets.disk'))->url($path),
            ]
        );
    }

    /**
     * Resolve a project from a slug or instance.
     *
     * @param Project|string $project
     * @return Project|null
     */
    protected function resolveProject($project): ?Project
    {
        if ($project instanceof Project) {
            return $project;
        }

        return Project::where('slug', $project)->first();
    }
} 