<?php

namespace LaravelExpoUpdates\Tests\Factories;

use LaravelExpoUpdates\Models\Manifest;
use LaravelExpoUpdates\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManifestFactory extends Factory
{
    protected $model = Manifest::class;

    public function definition()
    {
        return [
            'project_id' => Project::factory(),
            'platform' => $this->faker->randomElement(['ios', 'android']),
            'runtime_version' => $this->faker->semver(),
            'metadata' => [
                'commitHash' => $this->faker->sha256,
                'commitMessage' => $this->faker->sentence
            ],
            'extra' => [
                'expoClientVersion' => $this->faker->semver(),
                'expoClientVersionExtra' => [
                    'test' => 'value'
                ]
            ]
        ];
    }
} 