# Upgrading to Immutable Manifest Architecture

## Overview

Version 2.0 introduces **immutable manifests** and **asset isolation** to fix the "updates only work once" bug. This ensures each deployment creates a new manifest with isolated assets, preventing file collisions.

## Breaking Changes

### 1. Manifest Creation
- ❌ **Before**: `updateOrCreate()` reused the same manifest UUID
- ✅ **After**: Each upload creates a NEW manifest with a fresh UUID

### 2. Asset Storage
- ❌ **Before**: Assets stored at root or fixed paths (`updates/index.bundle`)
- ✅ **After**: Each manifest has isolated directory (`updates/{manifest_uuid}/index.bundle`)

### 3. Code Signing
- ❌ **Before**: `signManifest()` accepted `array $manifest`
- ✅ **After**: `signManifest()` accepts `string $manifestJson` for byte-matching

## Migration Steps

### Step 1: Backup Your Data
```bash
# Backup database
php artisan db:backup

# Backup storage directory
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/updates/
```

### Step 2: Update Dependencies
```bash
composer update
```

### Step 3: Run Migration
This migrates existing assets to the new isolated structure:
```bash
php artisan migrate
```

**What it does:**
- Copies each asset from old path to `updates/{manifest_uuid}/{filename}`
- Updates database `path` and `url` columns
- Leaves original files intact (manual cleanup recommended after verification)

### Step 4: Verify Migration
Check a few assets manually:
```bash
php artisan tinker
>>> $asset = \LaravelExpoUpdates\Models\Asset::first();
>>> $asset->path; // Should be: updates/{uuid}/filename
>>> Storage::disk('public')->exists($asset->path); // Should return true
```

### Step 5: Update Custom Code

If you have custom controllers/services calling `ManifestService::signManifest()`:

**Before:**
```php
$manifest = ['id' => '...', 'assets' => [...]];
$signature = $manifestService->signManifest($project, $manifest);
```

**After:**
```php
$manifest = ['id' => '...', 'assets' => [...]];
$manifestJson = json_encode($manifest); // Encode ONCE
$signature = $manifestService->signManifest($project, $manifestJson);
```

### Step 6: Test Uploads
1. Deploy a new OTA update
2. Verify new manifest created with unique UUID
3. Check assets stored in `updates/{new_manifest_uuid}/`
4. Test on device - update should download correctly

### Step 7: Clean Up Old Assets (Optional)
After verifying everything works:

```bash
# List old assets (not in manifest-isolated directories)
php artisan expo:cleanup --dry-run

# Actually delete them
php artisan expo:cleanup
```

## Troubleshooting

### Assets Not Found (404)
Check the `expo_assets` table - ensure `path` column uses new format:
```sql
SELECT id, path FROM expo_assets LIMIT 5;
-- Should show: updates/{uuid}/filename.js
```

### Signature Verification Fails
Ensure you're encoding JSON only once before signing:
```php
// ❌ Wrong
$sig = $service->signManifest($project, $manifest); // array
$json = json_encode($manifest); // Different encoding!

// ✅ Correct  
$json = json_encode($manifest);
$sig = $service->signManifest($project, $json);
```

### Migration Fails
If migration stops with errors:
1. Check storage disk permissions: `storage/app/updates/` must be writable
2. Run migration with verbose output: `php artisan migrate --verbose`
3. Check Laravel logs: `storage/logs/laravel.log`

## Rollback (Not Recommended)

There is no automatic rollback. If you must revert:
1. Restore database backup
2. Restore storage backup
3. Run `composer install` with previous version

## Support

For issues or questions, check:
- [GitHub Issues](https://github.com/your-repo/laravel-expo-updates/issues)
- [Documentation](./MANIFEST_MANAGEMENT.md)
