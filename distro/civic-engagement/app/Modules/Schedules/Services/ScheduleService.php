<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Services;

use CivicPlatform\Modules\Schedules\Repository\ScheduleNoteRepository;
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
     * Schedule note repository.
     *
     * @var ScheduleNoteRepository
     */
    private ScheduleNoteRepository $notes;

    /**
     * @param ScheduleRepository $schedules Schedule repository.
     * @param ScheduleNoteRepository $notes Schedule note repository.
     */
    public function __construct(ScheduleRepository $schedules, ScheduleNoteRepository $notes)
    {
        $this->schedules = $schedules;
        $this->notes = $notes;
    }

    /**
     * Create a schedule.
     *
     * @param array<string, mixed> $data Schedule data.
     * @param string $historyNote Optional append-only history note.
     * @param int $createdBy User ID creating the note.
     * @return int Created schedule ID, or 0 on failure.
     */
    public function create(array $data, string $historyNote = '', int $createdBy = 0): int
    {
        $scheduleId = $this->schedules->create($data);

        if ($scheduleId > 0) {
            $this->createHistoryNote($scheduleId, $historyNote, $createdBy);
        }

        return $scheduleId;
    }

    /**
     * Update a schedule.
     *
     * @param int $id Schedule ID.
     * @param array<string, mixed> $data Schedule data.
     * @param string $historyNote Optional append-only history note.
     * @param int $createdBy User ID creating the note.
     * @return bool True when update succeeds.
     */
    public function update(int $id, array $data, string $historyNote = '', int $createdBy = 0): bool
    {
        $updated = $this->schedules->update($id, $data);

        if ($updated) {
            $this->createHistoryNote($id, $historyNote, $createdBy);
        }

        return $updated;
    }

    /**
     * Create a history note when provided.
     *
     * @param int $scheduleId Schedule ID.
     * @param string $historyNote Optional note text.
     * @param int $createdBy User ID creating the note.
     * @return void
     */
    private function createHistoryNote(int $scheduleId, string $historyNote, int $createdBy): void
    {
        $historyNote = trim($historyNote);

        if ($scheduleId <= 0 || '' === $historyNote) {
            return;
        }

        $this->notes->create(
            [
                'schedule_id' => $scheduleId,
                'note' => $historyNote,
                'created_by' => $createdBy,
            ]
        );
    }
}
