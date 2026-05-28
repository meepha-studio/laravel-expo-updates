<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrates existing assets from root/fixed paths to manifest-isolated directories.
     * Each manifest gets its own UUID-based folder: updates/{manifest_uuid}/
     */
    public function up()
    {
        // Step 1: Update unique constraint from project_id+key to manifest_id+key
        Schema::table('expo_assets', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'key']);
            $table->unique(['manifest_id', 'key']);
        });

        // Step 2: Get all existing assets with their manifests
        $assets = DB::table('expo_assets')
            ->whereNotNull('manifest_id')
            ->get();

        foreach ($assets as $asset) {
            // Skip if already in isolated path format
            if (str_starts_with($asset->path, 'updates/' . $asset->manifest_id . '/')) {
                continue;
            }

            $oldPath = $asset->path;
            $disk = config('expo-updates.assets.disk', 'public');
            
            // Check if old file exists
            if (!Storage::disk($disk)->exists($oldPath)) {
                continue;
            }

            // Generate new isolated path
            $filename = basename($oldPath);
            $newPath = "updates/{$asset->manifest_id}/{$filename}";

            // Copy file to new location
            $content = Storage::disk($disk)->get($oldPath);
            Storage::disk($disk)->put($newPath, $content);

            // Update database record
            DB::table('expo_assets')
                ->where('id', $asset->id)
                ->update([
                    'path' => $newPath,
                    'url' => Storage::disk($disk)->url($newPath),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     * Restore original unique constraint
        Schema::table('expo_assets', function (Blueprint $table) {
            $table->dropUnique(['manifest_id', 'key']);
            $table->unique(['project_id', 'key']);
        });
     * Rollback not implemented - asset paths will remain in isolated structure.
     * Manual cleanup required if you need to revert to old structure.
     */
    public function down()
    {
        // No-op: manual cleanup required
    }
};
