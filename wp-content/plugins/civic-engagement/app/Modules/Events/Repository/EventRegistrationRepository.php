<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for event registration records.
 *
 * Handles civic_event_registrations database operations only. Contact updates,
 * activity logging, workflow decisions, and rendering belong elsewhere.
 */
class EventRegistrationRepository extends BaseRepository
{
    /**
     * Columns accepted when creating event registrations.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'event_id' => '%d',
        'contact_id' => '%d',
        'name_snapshot' => '%s',
        'email_snapshot' => '%s',
        'phone_snapshot' => '%s',
        'address_snapshot' => '%s',
        'eircode_snapshot' => '%s',
        'electoral_area_snapshot' => '%s',
        'registration_data' => '%s',
        'created_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_event_registrations');
    }

    /**
     * Create an event registration.
     *
     * Snapshot data is stored directly on the registration row.
     * registration_data is stored as JSON when provided as an array or object.
     *
     * @param array<string, mixed> $data Registration data keyed by civic_event_registrations columns.
     * @return int Inserted registration ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = $this->filterDataByFormats($data, $this->insertFormats);

        if (empty($insertData['event_id']) || empty($insertData['contact_id'])) {
            return 0;
        }

        if (isset($insertData['registration_data'])) {
            $insertData['registration_data'] = $this->prepareJsonValue($insertData['registration_data']);
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
     * Find an event registration by ID.
     *
     * @param int $id Registration ID.
     * @return array<string, mixed>|null Registration row or null when not found.
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
     * Get paginated registrations for an event.
     *
     * Supported args: page, per_page, contact_id, orderby, order.
     *
     * @param int $eventId Event ID.
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function findByEventId(int $eventId, array $args = []): array
    {
        if ($eventId <= 0) {
            return $this->emptyPagedResult($args);
        }

        $args['event_id'] = $eventId;

        return $this->getPaginated($args);
    }

    /**
     * Get a paginated registration listing.
     *
     * Supported args: page, per_page, event_id, contact_id, orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildRegistrationFilters($args);
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Search event registrations by snapshot and registration data.
     *
     * Supported args: page, per_page, event_id, contact_id, orderby, order.
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
        $where = $this->buildRegistrationFilters($args);
        $search = $this->buildSearchClause($keyword, $this->getSearchColumns());

        if ('' !== $search['sql']) {
            $where['sql'][] = $search['sql'];
            $where['values'] = array_merge($where['values'], $search['values']);
        }

        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Build supported registration filters.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    private function buildRegistrationFilters(array $args): array
    {
        return $this->buildFilterClause(
            $args,
            [
                'event_id' => ['column' => 'event_id', 'format' => '%d'],
                'contact_id' => ['column' => 'contact_id', 'format' => '%d'],
            ]
        );
    }

    /**
     * Execute a paginated registration query.
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
     * Prepare registration data for JSON storage.
     *
     * @param mixed $value Raw registration data.
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
            'event_id',
            'contact_id',
            'name_snapshot',
            'email_snapshot',
            'eircode_snapshot',
            'electoral_area_snapshot',
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
            'registration_data',
        ];
    }
}
