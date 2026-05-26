<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for thread response records.
 *
 * Handles civic_thread_responses database operations only. Contact updates,
 * activity logging, workflow decisions, and rendering belong elsewhere.
 */
class ThreadResponseRepository extends BaseRepository
{
    /**
     * Columns accepted when creating thread responses.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'thread_id' => '%d',
        'contact_id' => '%d',
        'name_snapshot' => '%s',
        'email_snapshot' => '%s',
        'phone_snapshot' => '%s',
        'address_snapshot' => '%s',
        'eircode_snapshot' => '%s',
        'electoral_area_snapshot' => '%s',
        'response_data' => '%s',
        'is_public' => '%d',
        'created_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_thread_responses');
    }

    /**
     * Create a thread response.
     *
     * Snapshot data is stored directly on the response row. response_data is
     * stored as JSON when provided as an array or object.
     *
     * @param array<string, mixed> $data Response data keyed by civic_thread_responses columns.
     * @return int Inserted response ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = $this->filterDataByFormats($data, $this->insertFormats);

        if (empty($insertData['thread_id']) || empty($insertData['contact_id'])) {
            return 0;
        }

        if (isset($insertData['response_data'])) {
            $insertData['response_data'] = $this->prepareJsonValue($insertData['response_data']);
        }

        if (!isset($insertData['is_public'])) {
            $insertData['is_public'] = 0;
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
     * Find a thread response by ID.
     *
     * @param int $id Response ID.
     * @return array<string, mixed>|null Response row or null when not found.
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
     * Update public visibility for a response.
     *
     * Response content is intentionally immutable; moderation only controls
     * whether a response can be displayed publicly.
     *
     * @param int $id Response ID.
     * @param bool $isPublic Whether the response should be public.
     * @return bool True when the update succeeded.
     */
    public function updatePublicVisibility(int $id, bool $isPublic): bool
    {
        if ($id <= 0) {
            return false;
        }

        $updated = $this->wpdb->update(
            $this->table,
            ['is_public' => $isPublic ? 1 : 0],
            ['id' => $id],
            ['%d'],
            ['%d']
        );

        return false !== $updated;
    }

    /**
     * Get paginated responses for a thread.
     *
     * Supported args: page, per_page, is_public, contact_id, orderby, order.
     *
     * @param int $threadId Thread ID.
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function findByThreadId(int $threadId, array $args = []): array
    {
        if ($threadId <= 0) {
            return $this->emptyPagedResult($args);
        }

        $args['thread_id'] = $threadId;

        return $this->getPaginated($args);
    }

    /**
     * Get a paginated response listing.
     *
     * Supported args: page, per_page, thread_id, contact_id, is_public, orderby,
     * order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildResponseFilters($args);
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Get public responses with optional filters.
     *
     * Supported args: page, per_page, thread_id, contact_id, orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicResponses(array $args = []): array
    {
        $args['is_public'] = 1;

        return $this->getPaginated($args);
    }

    /**
     * Search thread responses by snapshot and response data.
     *
     * Supported args: page, per_page, thread_id, contact_id, is_public, orderby,
     * order.
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
        $where = $this->buildResponseFilters($args);
        $search = $this->buildSearchClause($keyword, $this->getSearchColumns());

        if ('' !== $search['sql']) {
            $where['sql'][] = $search['sql'];
            $where['values'] = array_merge($where['values'], $search['values']);
        }

        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Build supported response filters.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    private function buildResponseFilters(array $args): array
    {
        return $this->buildFilterClause(
            $args,
            [
                'thread_id' => ['column' => 'thread_id', 'format' => '%d'],
                'contact_id' => ['column' => 'contact_id', 'format' => '%d'],
                'is_public' => ['column' => 'is_public', 'format' => '%d'],
            ]
        );
    }

    /**
     * Execute a paginated response query.
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
     * Return an empty paginated result.
     *
     * @param array<string, mixed> $args Pagination arguments.
     * @return array<string, mixed>
     */
    private function emptyPagedResult(array $args): array
    {
        $pagination = $this->parsePaginationArgs($args);

        return array_merge(
            ['items' => []],
            $this->buildPaginationMeta(0, $pagination)
        );
    }

    /**
     * Prepare response data for JSON storage.
     *
     * @param mixed $value Raw response data.
     * @return string JSON string or original scalar cast to string.
     */
    private function prepareJsonValue($value): string
    {
        if (is_array($value) || is_object($value)) {
            $encoded = wp_json_encode($value);

            return false === $encoded ? '{}' : $encoded;
        }

        return (string) $value;
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
            'thread_id',
            'contact_id',
            'name_snapshot',
            'email_snapshot',
            'eircode_snapshot',
            'electoral_area_snapshot',
            'is_public',
            'created_at',
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
            'name_snapshot',
            'email_snapshot',
            'phone_snapshot',
            'address_snapshot',
            'eircode_snapshot',
            'electoral_area_snapshot',
            'response_data',
        ];
    }
}
