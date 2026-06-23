<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for consultation thread records.
 *
 * Handles civic_threads database operations only. Workflow orchestration,
 * response processing, and rendering belong in services/controllers/templates.
 */
class ThreadRepository extends BaseRepository
{
    /**
     * Columns accepted when creating threads.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'title' => '%s',
        'slug' => '%s',
        'summary' => '%s',
        'description' => '%s',
        'response_enabled' => '%d',
        'is_public' => '%d',
        'starting_response_count' => '%d',
        'created_by' => '%d',
        'start_date' => '%s',
        'end_date' => '%s',
        'status' => '%s',
        'created_at' => '%s',
        'updated_at' => '%s',
    ];

    /**
     * Columns accepted when updating threads.
     *
     * Slug and created_by are intentionally excluded so they remain stable
     * after initial creation.
     *
     * @var array<string, string>
     */
    private array $updateFormats = [
        'title' => '%s',
        'summary' => '%s',
        'description' => '%s',
        'response_enabled' => '%d',
        'is_public' => '%d',
        'starting_response_count' => '%d',
        'start_date' => '%s',
        'end_date' => '%s',
        'status' => '%s',
        'updated_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_threads');
    }

    /**
     * Create a consultation thread.
     *
     * @param array<string, mixed> $data Thread data keyed by civic_threads columns.
     * @return int Inserted thread ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = $this->filterDataByFormats($data, $this->insertFormats);

        if (empty($insertData['title'])) {
            return 0;
        }

        $insertData = $this->normalizeDateFields($insertData);
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
     * Find a thread by ID.
     *
     * @param int $id Thread ID.
     * @return array<string, mixed>|null Thread row or null when not found.
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
     * Find a consultation by its module-local slug.
     *
     * @param string $slug Consultation slug.
     * @return array<string, mixed>|null Consultation row or null when not found.
     */
    public function findBySlug(string $slug): ?array
    {
        $slug = sanitize_title($slug);

        if ('' === $slug) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare("SELECT * FROM {$this->table} WHERE slug = %s LIMIT 1", [$slug]),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /** @param array<int, int> $ids @return array<int, string> */
    public function getTitlesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '%d'));
        $rows = $this->wpdb->get_results($this->prepare("SELECT id, title FROM {$this->table} WHERE id IN ({$placeholders})", $ids), ARRAY_A);
        $titles = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $titles[(int) ($row['id'] ?? 0)] = (string) ($row['title'] ?? '');
        }
        return $titles;
    }

    /**
     * Find a published public thread by ID.
     *
     * @param int $id Thread ID.
     * @return array<string, mixed>|null Thread row or null when unavailable.
     */
    public function findPublicById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d AND is_public = 1 AND status = %s LIMIT 1",
                [$id, 'published']
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Find a published public consultation by slug.
     *
     * @param string $slug Consultation slug.
     * @return array<string, mixed>|null Consultation row or null when unavailable.
     */
    public function findPublicBySlug(string $slug): ?array
    {
        $slug = sanitize_title($slug);

        if ('' === $slug) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE slug = %s AND is_public = 1 AND status = %s LIMIT 1",
                [$slug, 'published']
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Determine whether a slug is already used by another consultation.
     *
     * @param string $slug Consultation slug.
     * @param int|null $excludeId Consultation ID to exclude.
     * @return bool True when the slug exists.
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $slug = sanitize_title($slug);

        if ('' === $slug) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = %s";
        $values = [$slug];

        if (null !== $excludeId && $excludeId > 0) {
            $sql .= ' AND id != %d';
            $values[] = $excludeId;
        }

        return (int) $this->wpdb->get_var($this->prepare($sql, $values)) > 0;
    }

    /**
     * Update a consultation thread.
     *
     * @param int $id Thread ID.
     * @param array<string, mixed> $data Thread data keyed by editable civic_threads columns.
     * @return bool True when the update succeeds.
     */
    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $updateData = $this->filterDataByFormats($data, $this->updateFormats);

        if (empty($updateData) || empty($updateData['title'])) {
            return false;
        }

        $updateData = $this->normalizeDateFields($updateData);
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
     * Get a paginated thread listing.
     *
     * Supported args: page, per_page, status, is_public, response_enabled,
     * created_by, orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildThreadFilters($args);
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Search threads by keyword with pagination.
     *
     * Supported args: page, per_page, status, is_public, response_enabled,
     * created_by, orderby, order.
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
        $where = $this->buildThreadFilters($args);
        $search = $this->buildSearchClause($keyword, $this->getSearchColumns());

        if ('' !== $search['sql']) {
            $where['sql'][] = $search['sql'];
            $where['values'] = array_merge($where['values'], $search['values']);
        }

        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Get public threads.
     *
     * Public listing is limited to records with is_public = 1. If no status is
     * supplied, published threads are returned by default.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicThreads(array $args = []): array
    {
        $args['is_public'] = 1;

        if (!isset($args['status']) || '' === trim((string) $args['status'])) {
            $args['status'] = 'published';
        }

        return $this->getPaginated($args);
    }

    /**
     * Update the workflow status of a thread.
     *
     * @param int $id Thread ID.
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
     * Build supported thread filters.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    private function buildThreadFilters(array $args): array
    {
        return $this->buildFilterClause(
            $args,
            [
                'status' => ['column' => 'status', 'format' => '%s'],
                'is_public' => ['column' => 'is_public', 'format' => '%d'],
                'response_enabled' => ['column' => 'response_enabled', 'format' => '%d'],
                'created_by' => ['column' => 'created_by', 'format' => '%d'],
            ]
        );
    }

    /**
     * Execute a paginated thread query.
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
     * Normalize optional date fields for persistence.
     *
     * Empty admin date inputs should be stored as NULL, not zero dates.
     *
     * @param array<string, mixed> $data Thread data.
     * @return array<string, mixed> Normalized thread data.
     */
    private function normalizeDateFields(array $data): array
    {
        foreach (['start_date', 'end_date'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if (null === $data[$field] || '' === trim((string) $data[$field])) {
                $data[$field] = null;
            }
        }

        return $data;
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
            'slug',
            'response_enabled',
            'is_public',
            'created_by',
            'start_date',
            'end_date',
            'status',
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
            'slug',
            'summary',
            'description',
            'status',
        ];
    }
}
