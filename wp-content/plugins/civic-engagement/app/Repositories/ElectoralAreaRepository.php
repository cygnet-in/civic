<?php

declare(strict_types=1);

namespace CivicPlatform\Repositories;

/**
 * Repository for shared electoral area reference data.
 *
 * Handles wp_civic_electoral_areas database access only. Administration,
 * imports, hierarchy, and GIS workflows belong elsewhere.
 */
class ElectoralAreaRepository extends BaseRepository
{
    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_electoral_areas');
    }

    /**
     * Get all active electoral areas ordered by name.
     *
     * @return array<int, array<string, mixed>> Active electoral area rows.
     */
    public function getAllActive(): array
    {
        $items = $this->wpdb->get_results(
            "SELECT id, name, slug, is_active FROM {$this->table} WHERE is_active = 1 ORDER BY name ASC",
            ARRAY_A
        );

        return is_array($items) ? $items : [];
    }

    /**
     * Find an electoral area by ID.
     *
     * @param int $id Electoral area ID.
     * @return array<string, mixed>|null Electoral area row or null when not found.
     */
    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->wpdb->get_row(
            $this->prepare(
                "SELECT id, name, slug, is_active FROM {$this->table} WHERE id = %d LIMIT 1",
                [$id]
            ),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }
}
