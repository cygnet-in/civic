<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for civic representation submissions.
 *
 * Handles database access only. Contact updates, activity creation, form
 * processing, and workflow orchestration belong in services/controllers.
 */
class RepRepository extends BaseRepository
{
    /**
     * Columns accepted when creating reps.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'contact_id' => '%d',
        'name_snapshot' => '%s',
        'email_snapshot' => '%s',
        'phone_snapshot' => '%s',
        'whatsapp_snapshot' => '%s',
        'address_snapshot' => '%s',
        'eircode_snapshot' => '%s',
        'electoral_area_id' => '%d',
        'electoral_area_snapshot' => '%s',
        'title' => '%s',
        'details' => '%s',
        'image_attachment_id' => '%d',
        'map_lat' => '%f',
        'map_lng' => '%f',
        'status' => '%s',
        'schedule_id' => '%d',
        'created_at' => '%s',
        'updated_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_reps');
    }

    /**
     * Insert a representation submission.
     *
     * @param array<string, mixed> $data Rep data keyed by civic_reps columns.
     * @return int Inserted rep ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = $this->filterInsertData($data);

        if (empty($insertData)) {
            return 0;
        }

        $now = current_time('mysql');

        if (!isset($insertData['created_at'])) {
            $insertData['created_at'] = $now;
        }

        if (!isset($insertData['updated_at'])) {
            $insertData['updated_at'] = $now;
        }

        $formats = $this->getFormatsForData($insertData);
        $inserted = $this->wpdb->insert($this->table, $insertData, $formats);

        if (false === $inserted) {
            return 0;
        }

        return (int) $this->wpdb->insert_id;
    }

    /**
     * Find a representation by ID.
     *
     * @param int $id Rep ID.
     * @return array<string, mixed>|null Rep row or null when not found.
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
     * Find the representation linked to a schedule.
     *
     * @param int $scheduleId Schedule ID.
     * @return array<string, mixed>|null Rep row or null when not found.
     */
    public function findByScheduleId(int $scheduleId): ?array
    {
        if ($scheduleId <= 0) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE schedule_id = %d LIMIT 1",
                [$scheduleId]
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Get a paginated representation listing.
     *
     * Supported args: page, per_page, status, contact_id, orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildWhereClause($args);
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Search reps by keyword with pagination.
     *
     * Supported args: page, per_page, status, contact_id, orderby, order.
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
        $where = $this->buildWhereClause($args);
        $search = $this->buildSearchClause($keyword, $this->getSearchColumns());

        if ('' !== $search['sql']) {
            $where['sql'][] = $search['sql'];
            $where['values'] = array_merge($where['values'], $search['values']);
        }

        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Update the workflow status of a representation.
     *
     * @param int $id Rep ID.
     * @param string $status New workflow status.
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
     * Update mutable administrative representation metadata.
     *
     * Submitted snapshots remain unchanged.
     *
     * @param int $id Rep ID.
     * @param string $status Administrative status.
     * @param string $internalComment Internal comment.
     * @return bool True when the update succeeds.
     */
    public function updateAdministrativeDetails(int $id, string $status, string $internalComment): bool
    {
        if ($id <= 0 || '' === trim($status)) {
            return false;
        }

        $updated = $this->wpdb->update(
            $this->table,
            [
                'status' => trim($status),
                'internal_comment' => $internalComment,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        return false !== $updated;
    }

    /**
     * Link a representation to its created schedule and update the audit note.
     *
     * @param int $id Rep ID.
     * @param int $scheduleId Created schedule ID.
     * @param string $internalComment Full internal comment after appending audit text.
     * @return bool True when the link succeeds.
     */
    public function linkSchedule(int $id, int $scheduleId, string $internalComment): bool
    {
        if ($id <= 0 || $scheduleId <= 0) {
            return false;
        }

        $updated = $this->wpdb->query(
            $this->prepare(
                "UPDATE {$this->table}
                SET schedule_id = %d, internal_comment = %s, updated_at = %s
                WHERE id = %d AND (schedule_id IS NULL OR schedule_id = 0)",
                [
                    $scheduleId,
                    $internalComment,
                    current_time('mysql'),
                    $id,
                ]
            )
        );

        return false !== $updated && $updated > 0;
    }

    /**
     * Keep only known insert columns.
     *
     * @param array<string, mixed> $data Raw insert data.
     * @return array<string, mixed>
     */
    private function filterInsertData(array $data): array
    {
        return array_intersect_key($data, $this->insertFormats);
    }

    /**
     * Get wpdb formats matching the insert data order.
     *
     * @param array<string, mixed> $data Filtered insert data.
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

    /**
     * Build supported filter conditions.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    private function buildWhereClause(array $args): array
    {
        return $this->buildFilterClause(
            $args,
            [
                'status' => ['column' => 'status', 'format' => '%s'],
                'contact_id' => ['column' => 'contact_id', 'format' => '%d'],
            ]
        );
    }

    /**
     * Execute a paginated query and return rows with metadata.
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
     * Get safe columns accepted for ordering.
     *
     * @return array<int, string>
     */
    private function getAllowedOrderColumns(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'status',
            'title',
            'email_snapshot',
            'electoral_area_snapshot',
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
            'name_snapshot',
            'email_snapshot',
            'eircode_snapshot',
            'electoral_area_snapshot',
        ];
    }
}
