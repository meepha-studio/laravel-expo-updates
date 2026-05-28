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
        // Accept Manifest instance (preferred) or manifest UUID string for backward compatibility
        $manifest_id = null;
        if ($project instanceof \LaravelExpoUpdates\Models\Manifest) {
            $manifest_id = $project->id;
            $project_id = $project->project_id;
        } elseif ($project instanceof \LaravelExpoUpdates\Models\Project) {
            throw new \InvalidArgumentException('storeAsset doit être appelé avec le Manifest, pas le Project');
        } elseif (is_string($project)) {
            $manifest_id = $project;
            $project_id = null;
        } else {
            throw new \InvalidArgumentException('Invalid $project parameter - must be Manifest instance or UUID string');
        }

        $manifest = \LaravelExpoUpdates\Models\Manifest::findOrFail($manifest_id);
        $project_id = $manifest->project_id;

        // Isolated path per manifest: updates/{manifest_uuid}/{key}
        $basePath = 'updates/' . $manifest_id;
        $path = $basePath . '/' . $key;
        if ($fileExtension) {
            $path .= '.' . ltrim($fileExtension, '.');
        }

        // Store the file
        Storage::disk(config('expo-updates.assets.disk'))->put($path, $content);

        // Immutable creation - always INSERT, never UPDATE
        $asset = new \LaravelExpoUpdates\Models\Asset();
        $asset->manifest_id = $manifest_id;
        $asset->project_id = $project_id;
        $asset->key = $key;
        $asset->content_type = $contentType;
        $asset->file_extension = $fileExtension;
        $asset->path = $path;
        $asset->hash = base64_encode(hash('sha256', $content, true));
        $asset->url = Storage::disk(config('expo-updates.assets.disk'))->url($path);
        $asset->save();

        return $asset;
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
