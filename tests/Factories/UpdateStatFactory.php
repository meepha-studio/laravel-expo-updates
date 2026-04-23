<?php

namespace LaravelExpoUpdates\Tests\Factories;

use LaravelExpoUpdates\Models\UpdateStat;
use LaravelExpoUpdates\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class UpdateStatFactory extends Factory
{
    protected $model = UpdateStat::class;

    public function definition()
    {
        return [
            'project_id' => Project::factory(),
            'platform' => $this->faker->randomElement(['ios', 'android']),
            'runtime_version' => $this->faker->semver(),
            'type' => $this->faker->randomElement(['request', 'upgrade']),
            'count' => $this->faker->numberBetween(1, 100),
            'date' => $this->faker->date()
        ];
    }
} 