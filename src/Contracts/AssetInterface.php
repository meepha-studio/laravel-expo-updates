<?php

namespace LaravelExpoUpdates\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface AssetInterface
{
    /**
     * Get the asset's manifest.
     */
    public function manifest(): BelongsTo;

    /**
     * Get the asset's project.
     */
    public function project(): BelongsTo;

    /**
     * Get the asset's content.
     */
    public function getContent(): string;

    /**
     * Get the asset's content type.
     */
    public function getContentType(): string;

    /**
     * Get the asset's URL.
     */
    public function getUrl(): string;

    /**
     * Get the asset's hash.
     */
    public function getHash(): string;

    /**
     * Get the asset's file extension.
     */
    public function getFileExtension(): string;
} 