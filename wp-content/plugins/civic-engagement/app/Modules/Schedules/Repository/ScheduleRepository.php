<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for schedule and calendar records.
 *
 * Handles civic_schedules database operations only. Schedule lifecycle
 * automation, source prefill workflows, and rendering belong outside this class.
 */
class ScheduleRepository extends BaseRepository
{
    /**
     * Columns accepted when creating schedules.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'type' => '%s',
        'title' => '%s',
        'details' => '%s',
        'reported_by' => '%s',
        'status' => '%s',
        'review_date' => '%s',
        'internal_comment' => '%s',
        'response' => '%s',
        'is_public' => '%d',
        'start_date' => '%s',
        'end_date' => '%s',
        'source_type' => '%s',
        'source_id' => '%d',
        'created_by' => '%d',
        'created_at' => '%s',
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

        if (empty($insertData['title'])) {
            return 0;
        }

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
     * Get public schedules visible as of now.
     *
     * Public schedules are limited to is_public = 1 and start_date <= current
     * time. Expired schedules are excluded unless include_expired is truthy.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicSchedules(array $args = []): array
    {
        $args['is_public'] = 1;
        $args['starts_before'] = current_time('mysql');

        if (empty($args['include_expired'])) {
            $args['ends_after'] = current_time('mysql');
        }

        return $this->getPaginated($args);
    }

    /**
     * Get a paginated schedule listing.
     *
     * Supported args: page, per_page, type, status, is_public, source_type,
     * source_id, created_by, starts_before, starts_after, ends_before,
     * ends_after, orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildScheduleFilters($args);
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'start_date', 'ASC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Get archived schedules.
     *
     * Archive listing treats schedules with end_date before the current time as
     * archived. Passing a status arg can further narrow the listing.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getArchive(array $args = []): array
    {
        $args['ends_before'] = current_time('mysql');

        if (!isset($args['orderby'])) {
            $args['orderby'] = 'end_date';
        }

        if (!isset($args['order'])) {
            $args['order'] = 'DESC';
        }

        return $this->getPaginated($args);
    }

    /**
     * Update the status of a schedule.
     *
     * @param int $id Schedule ID.
     * @param string $status New status.
     * @return bool True when the update succeeds.
     */
    public function updateStatus(int $id, string $status): bool
    {
        if ($id <= 0 || '' === trim($status)) {
            return false;
        }

        $updated = $this->wpdb->update(
            $this->table,
            [
                'status' => trim($status),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        return false !== $updated;
    }

    /**
     * Build supported schedule filters.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    private function buildScheduleFilters(array $args): array
    {
        $where = $this->buildFilterClause(
            $args,
            [
                'type' => ['column' => 'type', 'format' => '%s'],
                'status' => ['column' => 'status', 'format' => '%s'],
                'is_public' => ['column' => 'is_public', 'format' => '%d'],
                'source_type' => ['column' => 'source_type', 'format' => '%s'],
                'source_id' => ['column' => 'source_id', 'format' => '%d'],
                'created_by' => ['column' => 'created_by', 'format' => '%d'],
            ]
        );

        $dateFilters = [
            'starts_before' => ['column' => 'start_date', 'operator' => '<='],
            'starts_after' => ['column' => 'start_date', 'operator' => '>='],
            'ends_before' => ['column' => 'end_date', 'operator' => '<'],
            'ends_after' => ['column' => 'end_date', 'operator' => '>='],
        ];

        foreach ($dateFilters as $argName => $filter) {
            if (!isset($args[$argName]) || '' === trim((string) $args[$argName])) {
                continue;
            }

            $where['sql'][] = sprintf('%s %s %%s', $filter['column'], $filter['operator']);
            $where['values'][] = trim((string) $args[$argName]);
        }

        return $where;
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
            'review_date',
            'is_public',
            'start_date',
            'end_date',
            'source_type',
            'source_id',
            'created_by',
            'created_at',
            'updated_at',
        ];
    }
}
