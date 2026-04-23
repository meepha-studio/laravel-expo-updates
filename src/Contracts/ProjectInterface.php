<?php

namespace LaravelExpoUpdates\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface ProjectInterface
{
    /**
     * Get the project's manifests.
     */
    public function manifests(): HasMany;

    /**
     * Get the project's assets.
     */
    public function assets(): HasMany;

    /**
     * Get the project's update stats.
     */
    public function updateStats(): HasMany;

    /**
     * Get the project's server headers.
     */
    public function getServerHeaders(): array;

    /**
     * Get the project's asset headers.
     */
    public function getAssetHeaders(): array;
} 