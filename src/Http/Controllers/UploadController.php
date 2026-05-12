<?php

namespace LaravelExpoUpdates\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use LaravelExpoUpdates\Services\ManifestService;
use LaravelExpoUpdates\Services\AssetService;
use LaravelExpoUpdates\Models\Project;
use ZipArchive;

/**
 * Controller for handling OTA updates uploads.
 */
class UploadController extends Controller
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
     * Handle the upload of an OTA update.
     *
     * @param Request $request
     * @return Response
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:512000',
            'runtimeVersion' => 'required|string|max:255',
            'commitHash' => 'required|string|max:128',
            'commitMessage' => 'required|string|max:1000',
            'projectSlug' => 'required|string|exists:expo_projects,slug',
        ]);

        $project = Project::where('slug', $request->projectSlug)->first();
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $zipFile = $request->file('file');
        $tempPath = storage_path('app/temp/' . uniqid());
        mkdir($tempPath, 0755, true);

        try {
            // Extract the zip file
            $zip = new ZipArchive;
            if ($zip->open($zipFile->getPathname()) !== true) {
                throw new \Exception('Failed to open zip file');
            }
            $zip->extractTo($tempPath);
            $zip->close();

            // Read the expo config
            $expoConfig = json_decode(file_get_contents($tempPath . '/expoconfig.json'), true);
            if (!$expoConfig) {
                throw new \Exception('Failed to read expo config');
            }

            // Process iOS and Android assets
            $platforms = ['ios', 'android'];
            foreach ($platforms as $platform) {
                $platformPath = $tempPath . '/' . $platform;
                if (!is_dir($platformPath)) {
                    continue;
                }

                // Create manifest
                $manifest = $this->manifestService->createManifest(
                    $project,
                    $platform,
                    $request->runtimeVersion,
                    [
                        'commitHash' => $request->commitHash,
                        'commitMessage' => $request->commitMessage,
                    ],
                    $expoConfig
                );

                // Process assets
                $this->processAssets($project, $manifest, $platformPath);
            }

            return response()->json(['message' => 'Update uploaded successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            // Clean up
            if (is_dir($tempPath)) {
                $this->removeDirectory($tempPath);
            }
        }
    }

    /**
     * Process assets for a platform.
     *
     * @param Project $project
     * @param Manifest $manifest
     * @param string $platformPath
     * @return void
     */
    protected function processAssets(Project $project, Manifest $manifest, string $platformPath)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($platformPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($platformPath) + 1);
            $content = file_get_contents($file->getPathname());
            $contentType = mime_content_type($file->getPathname());
            $fileExtension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            $this->assetService->storeAsset(
                $project,
                $relativePath,
                $content,
                $contentType,
                $fileExtension
            );
        }
    }

    /**
     * Recursively remove a directory.
     *
     * @param string $dir
     * @return void
     */
    protected function removeDirectory(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
} 