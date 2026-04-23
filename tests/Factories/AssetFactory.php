<?php

namespace LaravelExpoUpdates\Tests\Factories;

use LaravelExpoUpdates\Models\Asset;
use LaravelExpoUpdates\Models\Manifest;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition()
    {
        return [
            'manifest_id' => Manifest::factory(),
            'key' => $this->faker->unique()->word . '.js',
            'content_type' => 'application/javascript',
            'content' => 'console.log("test");',
            'url' => $this->faker->url,
            'hash' => $this->faker->sha256,
            'file_extension' => 'js'
        ];
    }
} 