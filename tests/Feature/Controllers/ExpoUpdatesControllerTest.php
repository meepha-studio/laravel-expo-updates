<?php

namespace LaravelExpoUpdates\Tests\Feature\Controllers;

use LaravelExpoUpdates\Tests\TestCase;
use LaravelExpoUpdates\Models\Project;
use LaravelExpoUpdates\Models\Manifest;
use LaravelExpoUpdates\Models\Asset;
use Illuminate\Support\Facades\Config;

class ExpoUpdatesControllerTest extends TestCase
{
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::factory()->create([
            'slug' => 'test-project',
            'config' => [
                'server_headers' => ['test-header' => 'test-value']
            ]
        ]);
    }

    /** @test */
    public function it_returns_manifest_for_project()
    {
        $manifest = Manifest::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'ios',
            'runtime_version' => '1.0.0'
        ]);

        $asset = Asset::factory()->create([
            'manifest_id' => $manifest->id,
            'key' => 'test.js',
            'content_type' => 'application/javascript',
            'url' => 'https://example.com/test.js'
        ]);

        $response = $this->getJson('/expo-updates/test-project/manifest?platform=ios&runtimeVersion=1.0.0');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $manifest->id,
                'runtimeVersion' => '1.0.0',
                'assets' => [
                    [
                        'key' => 'test.js',
                        'contentType' => 'application/javascript',
                        'url' => 'https://example.com/test.js'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_project()
    {
        $response = $this->getJson('/expo-updates/nonexistent/manifest?platform=ios&runtimeVersion=1.0.0');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_manifest()
    {
        $response = $this->getJson('/expo-updates/test-project/manifest?platform=ios&runtimeVersion=1.0.0');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_asset_for_project()
    {
        $manifest = Manifest::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'ios',
            'runtime_version' => '1.0.0'
        ]);

        $asset = Asset::factory()->create([
            'manifest_id' => $manifest->id,
            'key' => 'test.js',
            'content_type' => 'application/javascript',
            'content' => 'console.log("test");'
        ]);

        $response = $this->getJson('/expo-updates/test-project/asset/test.js');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/javascript')
            ->assertSee('console.log("test");');
    }

    /** @test */
    public function it_returns_404_for_nonexistent_asset()
    {
        $response = $this->getJson('/expo-updates/test-project/asset/nonexistent.js');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_uses_project_from_header()
    {
        Config::set('expo-updates.default_project', 'test-project');

        $manifest = Manifest::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'ios',
            'runtime_version' => '1.0.0'
        ]);

        $response = $this->withHeaders([
            'expo-project-id' => $this->project->id
        ])->getJson('/expo-updates/manifest?platform=ios&runtimeVersion=1.0.0');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $manifest->id,
                'runtimeVersion' => '1.0.0'
            ]);
    }

    /** @test */
    public function it_uses_default_project()
    {
        Config::set('expo-updates.default_project', 'test-project');

        $manifest = Manifest::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'ios',
            'runtime_version' => '1.0.0'
        ]);

        $response = $this->getJson('/expo-updates/manifest?platform=ios&runtimeVersion=1.0.0');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $manifest->id,
                'runtimeVersion' => '1.0.0'
            ]);
    }

    /** @test */
    public function it_returns_server_headers()
    {
        $manifest = Manifest::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'ios',
            'runtime_version' => '1.0.0'
        ]);

        $response = $this->getJson('/expo-updates/test-project/manifest?platform=ios&runtimeVersion=1.0.0');

        $response->assertStatus(200)
            ->assertHeader('test-header', 'test-value');
    }

    /** @test */
    public function it_returns_manifest_signature_when_enabled()
    {
        Config::set('expo-updates.code_signing.enabled', true);
        Config::set('expo-updates.code_signing.private_key_path', __DIR__ . '/../../test-keys/private.key');

        $manifest = Manifest::factory()->create([
            'project_id' => $this->project->id,
            'platform' => 'ios',
            'runtime_version' => '1.0.0'
        ]);

        $response = $this->getJson('/expo-updates/test-project/manifest?platform=ios&runtimeVersion=1.0.0');

        $response->assertStatus(200)
            ->assertHeader('expo-manifest-signature');
    }
} 