<?php

namespace LaravelExpoUpdates\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface UpdateStatInterface
{
    /**
     * Get the stat's project.
     */
    public function project(): BelongsTo;

    /**
     * Get the stat's platform.
     */
    public function getPlatform(): string;

    /**
     * Get the stat's runtime version.
     */
    public function getRuntimeVersion(): string;

    /**
     * Get the stat's type.
     */
    public function getType(): string;

    /**
     * Get the stat's count.
     */
    public function getCount(): int;

    /**
     * Get the stat's date.
     */
    public function getDate(): \DateTimeInterface;
} 