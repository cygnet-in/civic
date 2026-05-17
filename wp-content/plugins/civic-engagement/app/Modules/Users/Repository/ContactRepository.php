<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Users\Repository;

use CivicPlatform\Repositories\BaseRepository;

/**
 * Repository for latest contact records.
 *
 * Handles database operations for civic_contacts only. Workflow orchestration,
 * activity logging, and form handling belong in services/controllers.
 */
class ContactRepository extends BaseRepository
{
    /**
     * Columns accepted when creating contacts.
     *
     * @var array<string, string>
     */
    private array $insertFormats = [
        'email' => '%s',
        'latest_name' => '%s',
        'latest_phone' => '%s',
        'latest_whatsapp' => '%s',
        'latest_address' => '%s',
        'latest_eircode' => '%s',
        'latest_electoral_area' => '%s',
        'created_at' => '%s',
        'updated_at' => '%s',
    ];

    /**
     * Columns accepted when updating latest contact details.
     *
     * @var array<string, string>
     */
    private array $updateFormats = [
        'latest_name' => '%s',
        'latest_phone' => '%s',
        'latest_whatsapp' => '%s',
        'latest_address' => '%s',
        'latest_eircode' => '%s',
        'latest_electoral_area' => '%s',
        'updated_at' => '%s',
    ];

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_contacts');
    }

    /**
     * Create a contact.
     *
     * @param array<string, mixed> $data Contact data keyed by civic_contacts columns.
     * @return int Inserted contact ID, or 0 on failure.
     */
    public function create(array $data): int
    {
        $insertData = $this->filterDataByFormats($data, $this->insertFormats);

        if (empty($insertData) || empty($insertData['email'])) {
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
     * Find a contact by email address.
     *
     * @param string $email Contact email address.
     * @return array<string, mixed>|null Contact row or null when not found.
     */
    public function findByEmail(string $email): ?array
    {
        $email = trim($email);

        if ('' === $email) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE email = %s LIMIT 1",
                [$email]
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Find a contact by ID.
     *
     * @param int $id Contact ID.
     * @return array<string, mixed>|null Contact row or null when not found.
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
     * Update the latest contact details.
     *
     * Email is intentionally excluded because it is the primary identity key.
     *
     * @param int $id Contact ID.
     * @param array<string, mixed> $data Latest contact detail data.
     * @return bool True when the update succeeds.
     */
    public function updateLatestDetails(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $updateData = $this->filterDataByFormats($data, $this->updateFormats);

        if (empty($updateData)) {
            return false;
        }

        if (!isset($updateData['updated_at'])) {
            $updateData['updated_at'] = current_time('mysql');
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
     * Get a paginated contact listing.
     *
     * Supported args: page, per_page, orderby, order.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        $pagination = $this->parsePaginationArgs($args);
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'updated_at', 'DESC');

        return $this->getPagedResults([], [], $order, $pagination);
    }

    /**
     * Search contacts by keyword with pagination.
     *
     * Supported args: page, per_page, orderby, order.
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
        $search = $this->buildSearchClause($keyword, $this->getSearchColumns());
        $order = $this->buildOrderClause($args, $this->getAllowedOrderColumns(), 'updated_at', 'DESC');

        $whereSql = '' === $search['sql'] ? [] : [$search['sql']];

        return $this->getPagedResults($whereSql, $search['values'], $order, $pagination);
    }

    /**
     * Execute a paginated contact query.
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
            'email',
            'latest_name',
            'latest_eircode',
            'latest_electoral_area',
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
            'email',
            'latest_name',
            'latest_phone',
            'latest_whatsapp',
            'latest_address',
            'latest_eircode',
            'latest_electoral_area',
        ];
    }
}
