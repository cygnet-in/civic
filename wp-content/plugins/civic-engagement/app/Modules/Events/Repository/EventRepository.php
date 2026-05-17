<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for event records.
 *
 * Handles civic_events database operations only. Registration workflows,
 * activity logging, and rendering belong in services/controllers/templates.
 */
class EventRepository extends BaseRepository
{
    /**
     * Columns accepted when creating events.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'title' => '%s',
        'description' => '%s',
        'location' => '%s',
        'start_date' => '%s',
        'end_date' => '%s',
        'is_public' => '%d',
        'status' => '%s',
        'created_at' => '%s',
        'updated_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_events');
    }

    /**
     * Create an event.
     *
     * @param array<string, mixed> $data Event data keyed by civic_events columns.
     * @return int Inserted event ID, or 0 on failure.
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
     * Find an event by ID.
     *
     * @param int $id Event ID.
     * @return array<string, mixed>|null Event row or null when not found.
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
     * Get public events.
     *
     * Public listing is limited to records with is_public = 1. If no status is
     * supplied, active events are returned by default.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicEvents(array $args = []): array
    {
        $args['is_public'] = 1;

        if (!isset($args['status']) || '' === trim((string) $args['status'])) {
            $args['status'] = 'active';
        }

        return $this->getPaginated($args);
    }

    /**
     * Get a paginated event listing.
     *
     * Supported args: page, per_page, status, is_public, orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildEventFilters($args);
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'start_date', 'ASC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Update the status of an event.
     *
     * @param int $id Event ID.
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
     * Build supported event filters.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    private function buildEventFilters(array $args): array
    {
        return $this->buildFilterClause(
            $args,
            [
                'status' => ['column' => 'status', 'format' => '%s'],
                'is_public' => ['column' => 'is_public', 'format' => '%d'],
            ]
        );
    }

    /**
     * Execute a paginated event query.
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
            'title',
            'location',
            'start_date',
            'end_date',
            'is_public',
            'status',
            'created_at',
            'updated_at',
        ];
    }
}
