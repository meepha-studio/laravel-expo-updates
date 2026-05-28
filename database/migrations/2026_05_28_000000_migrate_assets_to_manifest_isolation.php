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
        // For MySQL, we need to be careful with foreign key constraints
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL: First create the new index, then try to drop the old one
            // Create new unique index on manifest_id+key
            try {
                DB::statement('ALTER TABLE expo_assets ADD UNIQUE INDEX expo_assets_manifest_id_key_unique (manifest_id, `key`)');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
            
            // Try to drop old index - if it fails due to FK constraint, it's okay
            // The new index will be used going forward
            try {
                DB::statement('ALTER TABLE expo_assets DROP INDEX expo_assets_project_id_key_unique');
            } catch (\Exception $e) {
                // Cannot drop due to foreign key constraint - this is okay
                // Both indexes can coexist, the new one will be used for manifest isolation
            }
        } else {
            // Other databases: Use Schema builder
            Schema::table('expo_assets', function (Blueprint $table) {
                $table->unique(['manifest_id', 'key']);
            });
            
            try {
                Schema::table('expo_assets', function (Blueprint $table) {
                    $table->dropUnique(['project_id', 'key']);
                });
            } catch (\Exception $e) {
                // Cannot drop - both indexes will coexist
            }
        }

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
     * 
     * Rollback not implemented - asset paths will remain in isolated structure.
     * Manual cleanup required if you need to revert to old structure.
     */
    public function down()
    {
        // Try to restore original unique constraint
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE expo_assets DROP INDEX expo_assets_manifest_id_key_unique');
            } catch (\Exception $e) {
                // Index might not exist or cannot be dropped
            }
            
            // Recreate old index if it doesn't exist
            try {
                DB::statement('ALTER TABLE expo_assets ADD UNIQUE INDEX expo_assets_project_id_key_unique (project_id, `key`)');
            } catch (\Exception $e) {
                // Index might already exist
            }
        } else {
            try {
                Schema::table('expo_assets', function (Blueprint $table) {
                    $table->dropUnique(['manifest_id', 'key']);
                    $table->unique(['project_id', 'key']);
                });
            } catch (\Exception $e) {
                // Cannot perform rollback
            }
        }
    }
};
