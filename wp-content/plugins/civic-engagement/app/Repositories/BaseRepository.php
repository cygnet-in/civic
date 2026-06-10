<?php

declare(strict_types=1);

namespace CivicPlatform\Repositories;

/**
 * Base repository for shared database helper behavior.
 *
 * This class contains generic query, pagination, ordering, and search helpers
 * only. Module-specific repositories remain responsible for their own tables,
 * columns, and workflow rules.
 */
abstract class BaseRepository
{
    /**
     * WordPress database adapter.
     *
     * @var \wpdb
     */
    protected \wpdb $wpdb;

    /**
     * Fully-prefixed table name.
     *
     * @var string
     */
    protected string $table;

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     * @param string $tableName Table name, with or without the WordPress prefix.
     */
    public function __construct(\wpdb $wpdb, string $tableName)
    {
        $this->wpdb = $wpdb;
        $this->table = $this->prefixTableName($tableName);
    }

    /**
     * Prepare a SQL query when placeholder values are present.
     *
     * @param string $sql SQL query with optional placeholders.
     * @param array<int, mixed> $values Placeholder values.
     * @return string Prepared SQL query.
     */
    protected function prepare(string $sql, array $values = []): string
    {
        if (empty($values)) {
            return $sql;
        }

        return $this->wpdb->prepare($sql, $values);
    }

    /**
     * Parse pagination arguments into a normalized structure.
     *
     * Supported args: page, per_page.
     *
     * @param array<string, mixed> $args Pagination arguments.
     * @param int $defaultPerPage Default rows per page.
     * @param int $maxPerPage Maximum allowed rows per page.
     * @return array{page: int, per_page: int, offset: int}
     */
    protected function parsePaginationArgs(
        array $args,
        int $defaultPerPage = 20,
        int $maxPerPage = 100
    ): array {
        $page = isset($args['page']) ? max(1, (int) $args['page']) : 1;
        $perPage = isset($args['per_page']) ? (int) $args['per_page'] : $defaultPerPage;
        $perPage = min(max(1, $maxPerPage), max(1, $perPage));

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    /**
     * Build an ORDER BY clause from whitelisted columns.
     *
     * Supported args: orderby, order.
     *
     * @param array<string, mixed> $args Ordering arguments.
     * @param array<int, string> $allowedColumns Safe column names accepted for ordering.
     * @param string $defaultColumn Default order column.
     * @param string $defaultOrder Default direction, ASC or DESC.
     * @return string Safe order clause without the ORDER BY keyword.
     */
    protected function buildOrderClause(
        array $args,
        array $allowedColumns,
        string $defaultColumn = 'id',
        string $defaultOrder = 'DESC'
    ): string {
        $safeColumns = $this->filterSafeIdentifiers($allowedColumns);
        $defaultColumn = in_array($defaultColumn, $safeColumns, true) ? $defaultColumn : 'id';
        $orderby = isset($args['orderby']) ? (string) $args['orderby'] : $defaultColumn;

        if (!in_array($orderby, $safeColumns, true)) {
            $orderby = $defaultColumn;
        }

        $defaultOrder = strtoupper($defaultOrder);
        $defaultOrder = in_array($defaultOrder, ['ASC', 'DESC'], true) ? $defaultOrder : 'DESC';
        $order = isset($args['order']) ? strtoupper((string) $args['order']) : $defaultOrder;

        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = $defaultOrder;
        }

        return sprintf('%s %s', $orderby, $order);
    }

    /**
     * Build filters from whitelisted columns and formats.
     *
     * The $allowedFilters array should be keyed by request arg name. Each value
     * should include a safe column name and a wpdb placeholder format. Equality
     * is used by default; comparison operators may be supplied for internal
     * repository filters.
     *
     * Example:
     * [
     *     'status' => ['column' => 'status', 'format' => '%s'],
     *     'contact_id' => ['column' => 'contact_id', 'format' => '%d'],
     *     'start_date_from' => ['column' => 'start_date', 'format' => '%s', 'operator' => '>='],
     * ]
     *
     * @param array<string, mixed> $args Filter input.
     * @param array<string, array{column: string, format: string, operator?: string}> $allowedFilters Filter definitions.
     * @return array{sql: array<int, string>, values: array<int, mixed>}
     */
    protected function buildFilterClause(array $args, array $allowedFilters): array
    {
        $sql = [];
        $values = [];

        foreach ($allowedFilters as $argName => $filter) {
            if (!array_key_exists($argName, $args) || '' === trim((string) $args[$argName])) {
                continue;
            }

            $column = $this->sanitizeIdentifier($filter['column']);
            $format = $this->sanitizePlaceholderFormat($filter['format']);
            $operator = $this->sanitizeComparisonOperator((string) ($filter['operator'] ?? '='));

            if ('' === $column || '' === $format || '' === $operator) {
                continue;
            }

            $sql[] = sprintf('%s %s %s', $column, $operator, $format);
            $values[] = '%d' === $format ? (int) $args[$argName] : trim((string) $args[$argName]);
        }

        return [
            'sql' => $sql,
            'values' => $values,
        ];
    }

    /**
     * Build a keyword search clause across whitelisted columns.
     *
     * @param string $keyword Search keyword.
     * @param array<int, string> $columns Safe searchable column names.
     * @return array{sql: string, values: array<int, string>}
     */
    protected function buildSearchClause(string $keyword, array $columns): array
    {
        $keyword = trim($keyword);
        $columns = $this->filterSafeIdentifiers($columns);

        if ('' === $keyword || empty($columns)) {
            return [
                'sql' => '',
                'values' => [],
            ];
        }

        $like = $this->buildLikeTerm($keyword);
        $sqlParts = [];
        $values = [];

        foreach ($columns as $column) {
            $sqlParts[] = sprintf('%s LIKE %%s', $column);
            $values[] = $like;
        }

        return [
            'sql' => '(' . implode(' OR ', $sqlParts) . ')',
            'values' => $values,
        ];
    }

    /**
     * Create a safe LIKE term for prepared SQL.
     *
     * @param string $keyword Raw keyword.
     * @return string Escaped keyword wrapped in wildcards.
     */
    protected function buildLikeTerm(string $keyword): string
    {
        return '%' . $this->wpdb->esc_like(trim($keyword)) . '%';
    }

    /**
     * Build a SQL WHERE clause from prepared fragments.
     *
     * @param array<int, string> $conditions SQL condition fragments.
     * @return string WHERE clause, or an empty string.
     */
    protected function buildWhereSql(array $conditions): string
    {
        $conditions = array_values(array_filter($conditions));

        if (empty($conditions)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Build common pagination metadata.
     *
     * @param int $total Total matching rows.
     * @param array{page: int, per_page: int, offset: int} $pagination Pagination data.
     * @return array{total: int, page: int, per_page: int, total_pages: int}
     */
    protected function buildPaginationMeta(int $total, array $pagination): array
    {
        return [
            'total' => $total,
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
            'total_pages' => (int) ceil($total / $pagination['per_page']),
        ];
    }

    /**
     * Ensure table names are stored with the current WordPress prefix.
     *
     * @param string $tableName Table name, with or without prefix.
     * @return string Fully-prefixed table name.
     */
    protected function prefixTableName(string $tableName): string
    {
        $tableName = trim($tableName);

        if (0 === strpos($tableName, $this->wpdb->prefix)) {
            return $tableName;
        }

        return $this->wpdb->prefix . ltrim($tableName, '_');
    }

    /**
     * Keep only safe SQL identifiers from a whitelist.
     *
     * @param array<int, string> $identifiers Candidate identifiers.
     * @return array<int, string> Safe identifiers.
     */
    protected function filterSafeIdentifiers(array $identifiers): array
    {
        $safe = [];

        foreach ($identifiers as $identifier) {
            $identifier = $this->sanitizeIdentifier($identifier);

            if ('' !== $identifier) {
                $safe[] = $identifier;
            }
        }

        return array_values(array_unique($safe));
    }

    /**
     * Validate a SQL identifier intended to come from an internal whitelist.
     *
     * @param string $identifier Candidate column/table identifier.
     * @return string Safe identifier, or an empty string.
     */
    protected function sanitizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if (1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            return $identifier;
        }

        return '';
    }

    /**
     * Validate a wpdb placeholder format.
     *
     * @param string $format Candidate placeholder format.
     * @return string Safe placeholder format, or an empty string.
     */
    protected function sanitizePlaceholderFormat(string $format): string
    {
        return in_array($format, ['%s', '%d', '%f'], true) ? $format : '';
    }

    /**
     * Validate an internally supplied SQL comparison operator.
     *
     * @param string $operator Candidate comparison operator.
     * @return string Safe comparison operator, or an empty string.
     */
    protected function sanitizeComparisonOperator(string $operator): string
    {
        $operator = trim($operator);

        return in_array($operator, ['=', '!=', '<', '<=', '>', '>='], true) ? $operator : '';
    }
}
