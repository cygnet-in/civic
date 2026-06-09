<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Services;

use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;

/**
 * Coordinates schedule workflows.
 *
 * The initial module foundation keeps workflows lightweight while preserving a
 * service boundary for future create-from-source behavior.
 */
class ScheduleService
{
    /**
     * Schedule repository.
     *
     * @var ScheduleRepository
     */
    private ScheduleRepository $schedules;

    /**
     * @param ScheduleRepository $schedules Schedule repository.
     */
    public function __construct(ScheduleRepository $schedules)
    {
        $this->schedules = $schedules;
    }

    /**
     * Create a schedule.
     *
     * @param array<string, mixed> $data Schedule data.
     * @return int Created schedule ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        return $this->schedules->create($data);
    }

    /**
     * Update a schedule.
     *
     * @param int $id Schedule ID.
     * @param array<string, mixed> $data Schedule data.
     * @return bool True when update succeeds.
     */
    public function update(int $id, array $data): bool
    {
        return $this->schedules->update($id, $data);
    }
}
