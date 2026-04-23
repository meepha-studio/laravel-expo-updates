<?php

namespace LaravelExpoUpdates\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface ManifestInterface
{
    /**
     * Get the manifest's project.
     */
    public function project(): BelongsTo;

    /**
     * Get the manifest's assets.
     */
    public function assets(): HasMany;

    /**
     * Get the manifest's update stats.
     */
    public function updateStats(): HasMany;

    /**
     * Get the manifest's metadata.
     */
    public function getMetadata(): array;

    /**
     * Get the manifest's extra data.
     */
    public function getExtra(): array;
} 