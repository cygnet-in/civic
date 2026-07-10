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
        'slug' => '%s',
        'short_code' => '%s',
        'summary' => '%s',
        'description' => '%s',
        'location' => '%s',
        'is_public' => '%d',
        'registration_enabled' => '%d',
        'start_date' => '%s',
        'end_date' => '%s',
        'status' => '%s',
        'created_at' => '%s',
        'updated_at' => '%s',
    ];

    /**
     * Columns accepted when updating events.
     *
     * @var array<string, string>
     */
    private array $updateFormats = [
        'title' => '%s',
        'slug' => '%s',
        'short_code' => '%s',
        'summary' => '%s',
        'description' => '%s',
        'location' => '%s',
        'is_public' => '%d',        
        'registration_enabled' => '%d',
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
        $this->normalizeShortCode($insertData);

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
     * Update an event.
     *
     * @param int $id Event ID.
     * @param array<string, mixed> $data Event data keyed by editable civic_events columns.
     * @return bool True when the update succeeds.
     */
    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $updateData = $this->filterDataByFormats($data, $this->updateFormats);
        $this->normalizeShortCode($updateData);

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

    /** @return array<string, mixed>|null */
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

    /** @return array<string, mixed>|null */
    public function findPublicById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d AND is_public = 1 AND status IN (%s, %s) LIMIT 1",
                [$id, 'published', 'closed']
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findPublicBySlug(string $slug): ?array
    {
        $slug = sanitize_title($slug);

        if ('' === $slug) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE slug = %s AND is_public = 1 AND status IN (%s, %s) LIMIT 1",
                [$slug, 'published', 'closed']
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

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
     * Get public events.
     *
     * Public listing is limited to records with is_public = 1. If no status is
     * supplied, published events are returned by default.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicEvents(array $args = []): array
    {
        return $this->getPublicActiveEvents($args);
    }

    /**
     * Get public Active events.
     *
     * Active events are public, published, and not past their configured end
     * date.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicActiveEvents(array $args = []): array
    {
        $args['is_public'] = 1;
        $args['status'] = 'published';

        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildEventFilters($args);
        $where['sql'][] = '(end_date IS NULL OR end_date = "" OR DATE(end_date) >= %s)';
        $where['values'][] = current_time('Y-m-d');
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'start_date', 'ASC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Get public Archived events.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicArchivedEvents(array $args = []): array
    {
        $args['is_public'] = 1;

        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildEventFilters($args);
        $where['sql'][] = '(status = %s OR (status = %s AND end_date IS NOT NULL AND end_date != "" AND DATE(end_date) < %s))';
        $where['values'][] = 'closed';
        $where['values'][] = 'published';
        $where['values'][] = current_time('Y-m-d');
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'end_date', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Determine whether a public event can accept registrations.
     *
     * This mirrors the documented Active event lifecycle: the event must be
     * public, published, registration-enabled, and not past its configured end
     * date.
     *
     * @param array<string, mixed> $event Event row.
     * @return bool True when public registrations are currently accepted.
     */
    public function isAcceptingRegistrations(array $event): bool
    {
        if (empty($event['is_public']) || 'published' !== (string) ($event['status'] ?? '')) {
            return false;
        }

        if (empty($event['registration_enabled'])) {
            return false;
        }

        $endDate = trim((string) ($event['end_date'] ?? ''));

        if ('' === $endDate) {
            return true;
        }

        $endTimestamp = strtotime($endDate);

        if (false === $endTimestamp) {
            return true;
        }

        return wp_date('Y-m-d', $endTimestamp) >= current_time('Y-m-d');
    }

    /**
     * Get a paginated event listing.
     *
     * Supported args: page, per_page, status, is_public,
     * registration_enabled, start_date_from, orderby, order.
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
     * Search events by keyword with pagination.
     *
     * Supported args: page, per_page, status, is_public, registration_enabled,
     * start_date_from, orderby, order.
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
        $where = $this->buildEventFilters($args);
        $search = $this->buildSearchClause($keyword, $this->getSearchColumns());

        if ('' !== $search['sql']) {
            $where['sql'][] = $search['sql'];
            $where['values'] = array_merge($where['values'], $search['values']);
        }

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
                'registration_enabled' => ['column' => 'registration_enabled', 'format' => '%d'],
                'start_date_from' => ['column' => 'start_date', 'format' => '%s', 'operator' => '>='],
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
     * Normalize optional date fields for persistence.
     *
     * Empty admin date inputs should be stored as NULL, not zero dates.
     *
     * @param array<string, mixed> $data Event data.
     * @return array<string, mixed> Normalized event data.
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
    /** @param array<string, mixed> $data */
    private function normalizeShortCode(array &$data): void
    {
        if (array_key_exists('short_code', $data) && '' === trim((string) $data['short_code'])) {
            $data['short_code'] = null;
        }
    }

    private function getAllowedOrderColumns(): array
    {
        return [
            'id',
            'title',
            'slug',
            'is_public',
            'registration_enabled',
            'location',
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
