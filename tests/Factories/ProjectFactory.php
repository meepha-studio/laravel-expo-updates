<?php

namespace LaravelExpoUpdates\Tests\Factories;

use LaravelExpoUpdates\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition()
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->company,
            'config' => [
                'server_headers' => [
                    'test-header' => 'test-value'
                ],
                'asset_headers' => [
                    'Cache-Control' => 'public, max-age=31536000'
                ]
            ]
        ];
    }
} 