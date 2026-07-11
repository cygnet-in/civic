<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for configurable event registration fields.
 *
 * Handles civic_event_fields database operations only. Rendering and workflow
 * decisions belong in controllers/services/templates.
 */
class EventFieldRepository extends BaseRepository
{
    /**
     * Supported event field types.
     *
     * @var array<int, string>
     */
    private array $fieldTypes = [
        'text',
        'textarea',
        'dropdown',
    ];

    /**
     * Columns accepted when creating fields.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'event_id' => '%d',
        'field_label' => '%s',
        'field_key' => '%s',
        'field_type' => '%s',
        'field_options' => '%s',
        'sort_order' => '%d',
        'is_required' => '%d',
        'created_at' => '%s',
    ];

    /**
     * Columns accepted when updating fields.
     *
     * @var array<string, string>
     */
    private array $updateFormats = [
        'field_label' => '%s',
        'field_key' => '%s',
        'field_type' => '%s',
        'field_options' => '%s',
        'sort_order' => '%d',
        'is_required' => '%d',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_event_fields');
    }

    /**
     * Create a dynamic event registration field.
     *
     * @param array<string, mixed> $data Field data keyed by civic_event_fields columns.
     * @return int Inserted field ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = $this->filterDataByFormats($data, $this->insertFormats);

        if (
            empty($insertData['event_id'])
            || empty($insertData['field_label'])
            || empty($insertData['field_key'])
            || empty($insertData['field_type'])
            || !$this->isSupportedFieldType((string) $insertData['field_type'])
        ) {
            return 0;
        }

        if (!isset($insertData['sort_order'])) {
            $insertData['sort_order'] = 0;
        }

        if (!isset($insertData['is_required'])) {
            $insertData['is_required'] = 0;
        }

        if (!isset($insertData['created_at'])) {
            $insertData['created_at'] = current_time('mysql');
        }

        $inserted = $this->wpdb->insert(
            $this->table,
            $insertData,
            $this->getFormatsForData($insertData, $this->insertFormats)
        );

        if (false === $inserted) {
            return 0;
        }

        return (int) $this->wpdb->insert_id;
    }

    /**
     * Find a dynamic event registration field by ID.
     *
     * @param int $id Field ID.
     * @return array<string, mixed>|null Field row or null when not found.
     */
    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
                [$id]
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Get fields for an event ordered by sort order.
     *
     * @param int $eventId Event ID.
     * @return array<int, array<string, mixed>> Field rows.
     */
    public function findByEventId(int $eventId): array
    {
        if ($eventId <= 0) {
            return [];
        }

        $items = $this->wpdb->get_results(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE event_id = %d ORDER BY sort_order ASC, id ASC",
                [$eventId]
            ),
            ARRAY_A
        );

        return is_array($items) ? $items : [];
    }

    /**
     * Check whether a field key already exists for an event.
     *
     * @param int $eventId Event ID.
     * @param string $fieldKey Field key.
     * @param int $excludeId Optional field ID to exclude when editing.
     * @return bool True when the key exists for the event.
     */
    public function fieldKeyExists(int $eventId, string $fieldKey, int $excludeId = 0): bool
    {
        $fieldKey = sanitize_key($fieldKey);

        if ($eventId <= 0 || '' === $fieldKey) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE event_id = %d AND field_key = %s";
        $values = [$eventId, $fieldKey];

        if ($excludeId > 0) {
            $sql .= ' AND id != %d';
            $values[] = $excludeId;
        }

        return (int) $this->wpdb->get_var($this->prepare($sql, $values)) > 0;
    }

    /**
     * Update a dynamic event registration field.
     *
     * @param int $id Field ID.
     * @param array<string, mixed> $data Field data keyed by civic_event_fields columns.
     * @return bool True when the update succeeds.
     */
    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $updateData = $this->filterDataByFormats($data, $this->updateFormats);

        if (empty($updateData)) {
            return false;
        }

        if (isset($updateData['field_key']) && '' === trim((string) $updateData['field_key'])) {
            return false;
        }

        if (
            isset($updateData['field_type'])
            && !$this->isSupportedFieldType((string) $updateData['field_type'])
        ) {
            return false;
        }

        $updated = $this->wpdb->update(
            $this->table,
            $updateData,
            ['id' => $id],
            $this->getFormatsForData($updateData, $this->updateFormats),
            ['%d']
        );

        return false !== $updated;
    }

    /**
     * Delete a dynamic event registration field.
     *
     * @param int $id Field ID.
     * @return bool True when the delete succeeds.
     */
    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $deleted = $this->wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        return false !== $deleted;
    }

    /**
     * Keep only fields supported by a format map.
     *
     * @param array<string, mixed> $data Raw data.
     * @param array<string, string> $formats Format map keyed by column.
     * @return array<string, mixed>
     */
    private function filterDataByFormats(array $data, array $formats): array
    {
        return array_intersect_key($data, $formats);
    }

    /**
     * Get wpdb formats matching data column order.
     *
     * @param array<string, mixed> $data Filtered data.
     * @param array<string, string> $formats Format map keyed by column.
     * @return array<int, string>
     */
    private function getFormatsForData(array $data, array $formats): array
    {
        $dataFormats = [];

        foreach (array_keys($data) as $column) {
            $dataFormats[] = $formats[$column];
        }

        return $dataFormats;
    }

    /**
     * Check whether a field type is supported.
     *
     * @param string $fieldType Field type.
     * @return bool True when supported.
     */
    private function isSupportedFieldType(string $fieldType): bool
    {
        return in_array(trim($fieldType), $this->fieldTypes, true);
    }
}
