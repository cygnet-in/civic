<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for append-only schedule notes.
 *
 * Notes are admin-only history entries. They are created and listed only; no
 * update method is provided by design.
 */
class ScheduleNoteRepository extends BaseRepository
{
    /**
     * Columns accepted when creating notes.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'schedule_id' => '%d',
        'note' => '%s',
        'created_by' => '%d',
        'created_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_schedule_notes');
    }

    /**
     * Create an append-only schedule note.
     *
     * @param array<string, mixed> $data Note data keyed by civic_schedule_notes columns.
     * @return int Inserted note ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = array_intersect_key($data, $this->insertFormats);

        if (empty($insertData['schedule_id']) || empty($insertData['note'])) {
            return 0;
        }

        if (empty($insertData['created_by'])) {
            $insertData['created_by'] = 0;
        }

        if (!isset($insertData['created_at'])) {
            $insertData['created_at'] = current_time('mysql');
        }

        $inserted = $this->wpdb->insert(
            $this->table,
            $insertData,
            $this->getFormatsForData($insertData)
        );

        if (false === $inserted) {
            return 0;
        }

        return (int) $this->wpdb->insert_id;
    }

    /**
     * Get notes for a schedule, newest first.
     *
     * @param int $scheduleId Schedule ID.
     * @return array<int, array<string, mixed>> Note rows.
     */
    public function findByScheduleId(int $scheduleId): array
    {
        if ($scheduleId <= 0) {
            return [];
        }

        $items = $this->wpdb->get_results(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE schedule_id = %d ORDER BY created_at DESC, id DESC",
                [$scheduleId]
            ),
            ARRAY_A
        );

        return is_array($items) ? $items : [];
    }

    /**
     * Get wpdb formats matching data column order.
     *
     * @param array<string, mixed> $data Filtered data.
     * @return array<int, string>
     */
    private function getFormatsForData(array $data): array
    {
        $formats = [];

        foreach (array_keys($data) as $column) {
            $formats[] = $this->insertFormats[$column];
        }

        return $formats;
    }
}
