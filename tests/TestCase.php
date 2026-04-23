<?php

namespace LaravelExpoUpdates\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use LaravelExpoUpdates\ExpoUpdatesServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ExpoUpdatesServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup expo-updates config
        $app['config']->set('expo-updates', [
            'route_prefix' => 'expo-updates',
            'default_project' => 'test-project',
            'code_signing' => [
                'enabled' => false,
                'certificate_path' => null,
                'private_key_path' => null,
            ],
            'assets' => [
                'disk' => 'public',
                'path' => 'expo-updates',
            ],
            'cache' => [
                'manifest_ttl' => 0,
                'asset_ttl' => 31536000,
                'signature_ttl' => 3600,
            ],
            'server_headers' => [
                'test-header' => 'test-value'
            ],
            'asset_headers' => [
                'Cache-Control' => 'public, max-age=31536000'
            ],
        ]);
    }
} 