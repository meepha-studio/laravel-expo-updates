<div align="center">
    <img align="center" src="https://github.com/timgws/laravel-expo-updates/raw/development/assets/expo_updates_icon.png" />
    <h3 align="center">Laravel Expo Updates</h3>
    <p align="center">
        ⚓ Ship like you mean it 💥
    </p>
</div>
<pre style="margin: 0 auto; width: 50%;">
# composer require meepha-studio/laravel-expo-updates 
</pre>

> **Note:** This is a fork of [timgws/laravel-expo-updates](https://github.com/timgws/laravel-expo-updates) with critical fixes for production use.

## What's New in This Fork?

This fork implements **immutable manifest architecture** and full **Expo Updates v1 protocol compliance** to fix the "updates only work once" bug present in the original package.

### Key Improvements

✅ **Immutable Manifests** - Each deployment creates a new manifest with a unique UUID instead of updating existing ones, preventing file collisions and ensuring update reliability

✅ **Asset Isolation** - Assets are stored in manifest-specific directories (`updates/{manifest_uuid}/{key}`) to prevent race conditions when multiple versions are deployed

✅ **Byte-Matching Signatures** - Fixed signature generation to ensure single JSON encoding, guaranteeing OpenSSL signatures match the payload sent to clients

✅ **Multipart/Mixed Protocol** - Full implementation of Expo Updates v1 multipart response format with proper `Content-Disposition` headers

✅ **Laravel 12+ Compatibility** - Updated dependencies (Orchestra Testbench ^10.0, PHPUnit ^11.5) for modern Laravel apps

✅ **Production Ready** - Includes migration for existing data, comprehensive test suite (30/30 passing), and complete documentation

### Migration from Original Package

See the [Upgrading Guide](docs/UPGRADING.md) for detailed migration instructions. The package includes an automatic migration that converts existing assets to the new isolated structure.

## What is Laravel Expo Updates?

A lightweight Laravel package that lets you **self-host over-the-air (OTA) updates** for React Native apps using the
**Expo Updates protocol**.

It takes care of the boring parts (manifest generation, asset serving, protocol compliance) so your existing Laravel
backend becomes the control plane for fast, reliable updates.

`expo-updates` is a React Native library that enables your app to manage remote updates to your application code.
It communicates with the configured remote update service to get information about available updates.

`Laravel Expo Updates` is designed to be a PHP implementation of the [Expo Updates protocol](https://docs.expo.dev/technical-specs/expo-updates-1/),
making it compatible with any React Native app using the `expo-updates` library. If you write your app's backend in PHP,
this package lets you keep everything in one stack.

### Why use it?

* **One stack**: Keep everything server-side in PHP/Laravel. Simpler infrastructure, fewer moving parts.
* **Self-hosted**: Own your release cadence, storage, and logs.
* **Full control**: Gate who gets which updates and when.

## Documentation

- **[Upgrading Guide](docs/UPGRADING.md)** - Migration steps for upgrading to immutable manifest architecture
- **[Manifest Management](docs/MANIFEST_MANAGEMENT.md)** - How to query, display, and manage manifests in your Laravel app

## Reporting Bugs

Spotted a bug? Thanks for helping improve Laravel Expo Updates!
[Please open a GitHub issue](../../issues/new?labels=bug).

## Installation (Server Side)

You can install this fork via composer:

```bash
composer require meepha-studio/laravel-expo-updates
```

Or if you want to use the original package (without the critical fixes):

```bash
composer require timgws/laravel-expo-updates
```

After installing the package, publish the configuration file:

```bash
php artisan vendor:publish --provider="LaravelExpoUpdates\ExpoUpdatesServiceProvider" --tag="config"
```

Run the migrations:

```bash
php artisan migrate
```

## Server side configuration

Sane defaults are provided, but you should review and adjust the configuration file that is published to your config
folder.

The configuration file is located at `config/expo-updates.php`. Here you can configure:

- Route prefix for the update endpoints
- Code signing settings
- Asset storage settings
- Cache settings

Make sure to set the appropriate environment variables in your `.env` file. Here are the relevant variables:

```shell
# cat .env | grep ^EXPO_
EXPO_UPDATES_ROUTE_PREFIX=updates
EXPO_UPDATES_DEFAULT_PROJECT=default
EXPO_UPDATES_CODE_SIGNING_ENABLED=true
EXPO_UPDATES_CERTIFICATE_PATH=app/expo-updates/public.key
EXPO_UPDATES_PRIVATE_KEY_PATH=app/expo-updates/private.key
EXPO_UPDATES_SIGNATURE_CACHE_ENABLED=true
EXPO_UPDATES_SIGNATURE_CACHE_TTL=600
EXPO_UPDATES_ASSETS_DISK=local
EXPO_UPDATES_ASSETS_PATH=expo-updates/assets
EXPO_UPDATES_ASSETS_URL=https://www.myapplication.com/assets/
EXPO_UPDATES_CACHE_ENABLED=true
EXPO_UPDATES_CACHE_TTL=60
```

## Installation (Client Side)
Check out the [expo-updates configuration guide](https://docs.expo.dev/versions/latest/sdk/updates/#usage).

> [!WARNING]  
> `updates.url` must point to https://YOUR-DOMAIN/updates/api/manifest (note the /api/manifest path). Pointing to the
> site root will return HTML, not a manifest.

## Replacing the default models

The package comes with default Eloquent models for `Update`, `Asset`, and `Manifest`.

You can replace the default models by creating your own models that extend the package's models and updating the
configuration file to use them.

Alternatively, you can create new implementations, as long as they fulfil the Interfaces provided.

## Usage

### Routes

The package automatically registers the following routes:

- `GET /expo-updates/manifest` - Returns the latest manifest for the specified platform and runtime version
- `GET /expo-updates/asset/{key}` - Serves an asset file

### Headers

The manifest endpoint requires the following headers:

- `expo-protocol-version: 1`
- `expo-platform: ios|android`
- `expo-runtime-version: *`
- `accept: application/expo+json, application/json, multipart/mixed`

Optional headers:

- `expo-manifest-filters: *` - Filter manifests by metadata
- `expo-expect-signature: *` - Request code signing

### Code Signing

To enable code signing:

1. Set `EXPO_UPDATES_CODE_SIGNING_ENABLED=true` in your `.env` file
2. Configure the paths to your certificate and private key:
   ```
   EXPO_UPDATES_CERTIFICATE_PATH=/path/to/certificate.pem
   EXPO_UPDATES_PRIVATE_KEY_PATH=/path/to/private.key
   ```

### Asset Storage

Assets are stored using Laravel's storage system. By default, they are stored in the `public` disk under the
`expo-updates` directory. You can configure this in the config file.

### Quick checks & commands

Confirm the manifest route is registered:

```
php artisan route:list | grep -i manifest
```

Fetch the manifest like the client would (adjust headers/values as needed):

```
curl -i \
-H "Accept: application/expo+json" \
-H "Expo-Platform: ios" \
-H "Expo-Runtime-Version: 1.0.0" \
-H "Expo-Channel-Name: production" \
https://your-domain.example/api/manifest
```

> [!NOTE]  
> If you receive HTML instead of JSON, you’re likely hitting the wrong path or an HTML middleware.
> Double-check the URL and route group.

## License

This project is licensed under the terms of the MIT license (MIT). Please see [License File](LICENSE.md) for more information. 
