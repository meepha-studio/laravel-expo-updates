<?php

namespace LaravelExpoUpdates\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelExpoUpdates\Contracts\UpdateStatInterface;

/**
 * Model for tracking update statistics.
 */
class UpdateStat extends Model implements UpdateStatInterface
{
    protected $table = 'expo_update_stats';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'project_id',
        'platform',
        'runtime_version',
        'type',
        'count',
        'date'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'count' => 'integer'
    ];

    /**
     * Get the project that owns the stat.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getRuntimeVersion(): string
    {
        return $this->runtime_version;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }
} 