<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Activities\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for contact activity records.
 *
 * Handles civic_activities database operations only. Workflow orchestration and
 * module-specific activity decisions belong in services.
 */
class ActivityRepository extends BaseRepository
{
    /**
     * Supported activity type values.
     *
     * @var array<int, string>
     */
    private array $activityTypes = [
        'rep',
        'thread_response',
        'event_registration',
        'schedule',
        'manual',
    ];

    /**
     * Columns accepted when creating activities.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'contact_id' => '%d',
        'activity_type' => '%s',
        'related_id' => '%d',
        'summary' => '%s',
        'created_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_activities');
    }

    /**
     * Create an activity record.
     *
     * @param array<string, mixed> $data Activity data keyed by civic_activities columns.
     * @return int Inserted activity ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = $this->filterDataByFormats($data, $this->insertFormats);

        if (
            empty($insertData['activity_type'])
            || !$this->isSupportedActivityType((string) $insertData['activity_type'])
        ) {
            return 0;
        }

        if (!isset($insertData['contact_id'])) {
            $insertData['contact_id'] = 0;
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
     * Get paginated activities for a contact.
     *
     * Supported args: page, per_page, activity_type, orderby, order.
     *
     * @param int $contactId Contact ID.
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function findByContactId(int $contactId, array $args = []): array
    {
        if ($contactId <= 0) {
            return $this->emptyPagedResult($args);
        }

        $args['contact_id'] = $contactId;

        return $this->getPaginated($args);
    }

    /**
     * Get a paginated activity listing.
     *
     * Supported args: page, per_page, activity_type, contact_id, related_id,
     * orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $where = $this->buildActivityFilters($args);
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'created_at', 'DESC');

        return $this->getPagedResults($where['sql'], $where['values'], $order, $pagination);
    }

    /**
     * Build supported activity filters.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    private function buildActivityFilters(array $args): array
    {
        $where = $this->buildFilterClause(
            $args,
            [
                'contact_id' => ['column' => 'contact_id', 'format' => '%d'],
                'related_id' => ['column' => 'related_id', 'format' => '%d'],
            ]
        );

        if (
            isset($args['activity_type'])
            && $this->isSupportedActivityType((string) $args['activity_type'])
        ) {
            $where['sql'][] = 'activity_type = %s';
            $where['values'][] = trim((string) $args['activity_type']);
        }

        return $where;
    }

    /**
     * Execute a paginated activity query.
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
     * Check whether an activity type is supported.
     *
     * @param string $activityType Activity type.
     * @return bool True when supported.
     */
    private function isSupportedActivityType(string $activityType): bool
    {
        return in_array(trim($activityType), $this->activityTypes, true);
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
            'contact_id',
            'activity_type',
            'related_id',
            'created_at',
        ];
    }
}
