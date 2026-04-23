<?php

namespace LaravelExpoUpdates\Services;

use LaravelExpoUpdates\Models\Project;
use LaravelExpoUpdates\Models\UpdateStat;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling update statistics.
 */
class StatsService
{
    /**
     * Record an update request.
     *
     * @param Project $project
     * @param string $platform
     * @param string $runtimeVersion
     * @return void
     */
    public function recordRequest(Project $project, string $platform, string $runtimeVersion): void
    {
        $this->incrementStat($project, $platform, $runtimeVersion, 'request');
    }

    /**
     * Record a successful upgrade.
     *
     * @param Project $project
     * @param string $platform
     * @param string $runtimeVersion
     * @return void
     */
    public function recordUpgrade(Project $project, string $platform, string $runtimeVersion): void
    {
        $this->incrementStat($project, $platform, $runtimeVersion, 'upgrade');
    }

    /**
     * Get stats for a project.
     *
     * @param Project $project
     * @param string|null $platform
     * @param string|null $type
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getStats(
        Project $project,
        ?string $platform = null,
        ?string $type = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = UpdateStat::where('project_id', $project->id);

        if ($platform) {
            $query->where('platform', $platform);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->get()->toArray();
    }

    /**
     * Get daily stats for a project.
     *
     * @param Project $project
     * @param string|null $platform
     * @param string|null $type
     * @param int $days
     * @return array
     */
    public function getDailyStats(
        Project $project,
        ?string $platform = null,
        ?string $type = null,
        int $days = 30
    ): array {
        $query = UpdateStat::where('project_id', $project->id)
            ->where('date', '>=', now()->subDays($days));

        if ($platform) {
            $query->where('platform', $platform);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->select(
            'date',
            'platform',
            'type',
            DB::raw('SUM(count) as total')
        )
            ->groupBy('date', 'platform', 'type')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Increment a stat counter.
     *
     * @param Project $project
     * @param string $platform
     * @param string $runtimeVersion
     * @param string $type
     * @return void
     */
    protected function incrementStat(Project $project, string $platform, string $runtimeVersion, string $type): void
    {
        UpdateStat::updateOrCreate(
            [
                'project_id' => $project->id,
                'platform' => $platform,
                'runtime_version' => $runtimeVersion,
                'type' => $type,
                'date' => now()->toDateString(),
            ],
            [
                'count' => DB::raw('count + 1'),
            ]
        );
    }
} 