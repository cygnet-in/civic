<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for schedule records.
 *
 * Handles civic_schedules database operations only. Workflow orchestration and
 * rendering belong in services/controllers/templates.
 */
class ScheduleRepository extends BaseRepository
{
    /**
     * Supported schedule types.
     *
     * @var array<int, string>
     */
    private array $types = [
        'meeting',
        'motion',
        'question',
        'rep_followup',
        'public_announcement',
        'other',
    ];

    /**
     * Supported schedule statuses.
     *
     * @var array<int, string>
     */
    private array $statuses = [
        'open',
        'pending',
        'scheduled',
        'completed',
        'cancelled',
    ];

    /**
     * Columns accepted when creating schedules.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'type' => '%s',
        'title' => '%s',
        'details' => '%s',
        'status' => '%s',
        'internal_comment' => '%s',
        'recent_update' => '%s',
        'priority' => '%d',
        'is_public' => '%d',
        'is_archived' => '%d',
        'start_date' => '%s',
        'end_date' => '%s',
        'source_type' => '%s',
        'source_id' => '%d',
        'created_by' => '%d',
        'created_at' => '%s',
        'updated_at' => '%s',
    ];

    /**
     * Columns accepted when updating schedules.
     *
     * @var array<string, string>
     */
    private array $updateFormats = [
        'type' => '%s',
        'title' => '%s',
        'details' => '%s',
        'status' => '%s',
        'internal_comment' => '%s',
        'recent_update' => '%s',
        'priority' => '%d',
        'is_public' => '%d',
        'is_archived' => '%d',
        'start_date' => '%s',
        'end_date' => '%s',
        'source_type' => '%s',
        'source_id' => '%d',
        'created_by' => '%d',
        'updated_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_schedules');
    }

    /**
     * Create a schedule.
     *
     * @param array<string, mixed> $data Schedule data keyed by civic_schedules columns.
     * @return int Inserted schedule ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = $this->filterDataByFormats($data, $this->insertFormats);

        if (!$this->isValidScheduleData($insertData)) {
            return 0;
        }

        $insertData = $this->normalizeNullableFields($insertData);
        $now = current_time('mysql');

        if (!isset($insertData['created_at'])) {
            $insertData['created_at'] = $now;
        }

        if (!isset($insertData['updated_at'])) {
            $insertData['updated_at'] = $now;
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
     * Update a schedule.
     *
     * @param int $id Schedule ID.
     * @param array<string, mixed> $data Schedule data keyed by editable civic_schedules columns.
     * @return bool True when the update succeeds.
     */
    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $updateData = $this->filterDataByFormats($data, $this->updateFormats);

        if (!$this->isValidScheduleData($updateData)) {
            return false;
        }

        $updateData = $this->normalizeNullableFields($updateData);
        $updateData['updated_at'] = current_time('mysql');

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
     * Find a schedule by ID.
     *
     * @param int $id Schedule ID.
     * @return array<string, mixed>|null Schedule row or null when not found.
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
     * Get a paginated schedule listing.
     *
     * Supported args: page, per_page, type, status, is_public, is_archived,
     * start_date_from, orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildScheduleFilters($args);
        $order = $this->buildScheduleOrder($args);

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Search schedules by keyword with pagination.
     *
     * @param string $keyword Search keyword.
     * @param array<string, mixed> $args Search arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function search(string $keyword, array $args = []): array
    {
        $keyword = trim($keyword);

        if ('' === $keyword) {
            return $this->getPaginated($args);
        }

        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildScheduleFilters($args);
        $search = $this->buildSearchClause($keyword, $this->getSearchColumns());

        if ('' !== $search['sql']) {
            $where['sql'][] = $search['sql'];
            $where['values'] = array_merge($where['values'], $search['values']);
        }

        $order = $this->buildScheduleOrder($args);

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Build supported schedule filters.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    private function buildScheduleFilters(array $args): array
    {
        return $this->buildFilterClause(
            $args,
            [
                'type' => ['column' => 'type', 'format' => '%s'],
                'status' => ['column' => 'status', 'format' => '%s'],
                'is_public' => ['column' => 'is_public', 'format' => '%d'],
                'is_archived' => ['column' => 'is_archived', 'format' => '%d'],
                'start_date_from' => ['column' => 'start_date', 'format' => '%s', 'operator' => '>='],
            ]
        );
    }

    /**
     * Execute a paginated schedule query.
     *
     * @param array<int, string> $whereSql Where SQL fragments.
     * @param array<int, mixed> $whereValues Prepared statement values.
     * @param string $order Order clause.
     * @param array{page: int, per_page: int, offset: int} $pagination Pagination data.
     * @return array<string, mixed>
     */
    private function getPagedResults(array $whereSql, array $whereValues, string $order, array $pagination): array
    {
        $whereClause = $this->buildWhereSql($whereSql);

        $totalSql = "SELECT COUNT(*) FROM {$this->table}{$whereClause}";
        $total = (int) $this->wpdb->get_var($this->prepare($totalSql, $whereValues));

        $queryValues = array_merge($whereValues, [$pagination['per_page'], $pagination['offset']]);
        $itemsSql = "SELECT * FROM {$this->table}{$whereClause} ORDER BY {$order} LIMIT %d OFFSET %d";
        $items = $this->wpdb->get_results($this->prepare($itemsSql, $queryValues), ARRAY_A);

        return array_merge(
            ['items' => is_array($items) ? $items : []],
            $this->buildPaginationMeta($total, $pagination)
        );
    }

    /**
     * Validate required enum-backed schedule data.
     *
     * @param array<string, mixed> $data Schedule data.
     * @return bool True when valid.
     */
    private function isValidScheduleData(array $data): bool
    {
        if (empty($data) || empty($data['title'])) {
            return false;
        }

        if (empty($data['type']) || !in_array((string) $data['type'], $this->types, true)) {
            return false;
        }

        if (empty($data['status']) || !in_array((string) $data['status'], $this->statuses, true)) {
            return false;
        }

        return true;
    }

    /**
     * Normalize optional date/reference fields for persistence.
     *
     * @param array<string, mixed> $data Schedule data.
     * @return array<string, mixed> Normalized data.
     */
    private function normalizeNullableFields(array $data): array
    {
        foreach (['start_date', 'end_date'] as $field) {
            if (array_key_exists($field, $data) && '' === trim((string) $data[$field])) {
                $data[$field] = null;
            }
        }

        foreach (['source_type', 'source_id', 'created_by'] as $field) {
            if (array_key_exists($field, $data) && '' === trim((string) $data[$field])) {
                $data[$field] = null;
            }
        }

        return $data;
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
     * Get safe columns accepted for ordering.
     *
     * @return array<int, string>
     */
    private function getAllowedOrderColumns(): array
    {
        return [
            'id',
            'type',
            'title',
            'status',
            'is_public',
            'is_archived',
            'start_date',
            'end_date',
            'priority',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get safe columns included in keyword search.
     *
     * @return array<int, string>
     */
    private function getSearchColumns(): array
    {
        return [
            'title',
            'details',
            'internal_comment',
            'source_type',
        ];
    }

    /**
     * Build schedule ordering with priority as the default sort key.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return string Safe order clause.
     */
    private function buildScheduleOrder(array $args): string
    {
        if (isset($args['orderby']) && '' !== trim((string) $args['orderby'])) {
            return $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'priority', 'DESC');
        }

        return 'priority DESC, start_date ASC';
    }
}
