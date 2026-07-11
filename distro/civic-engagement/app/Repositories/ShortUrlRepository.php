<?php

declare(strict_types=1);

namespace CivicPlatform\Repositories;

/**
 * Looks up short URL codes across Civic's public entity tables.
 */
class ShortUrlRepository
{
    private \wpdb $wpdb;

    /** @var array<string, string> */
    private array $tables;

    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->tables = [
            'consultation' => $wpdb->prefix . 'civic_threads',
            'event' => $wpdb->prefix . 'civic_events',
            'schedule' => $wpdb->prefix . 'civic_schedules',
        ];
    }

    /**
     * Find an entity that owns a short code.
     *
     * @return array{entity_type: string, id: int, slug: string}|null
     */
    public function findByShortCode(string $shortCode): ?array
    {
        foreach ($this->tables as $entityType => $table) {
            $row = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT id, slug FROM {$table} WHERE short_code = %s LIMIT 1",
                    $shortCode
                ),
                ARRAY_A
            );

            if (is_array($row)) {
                return [
                    'entity_type' => $entityType,
                    'id' => (int) ($row['id'] ?? 0),
                    'slug' => (string) ($row['slug'] ?? ''),
                ];
            }
        }

        return null;
    }

    /**
     * Determine whether a short code belongs to another entity.
     */
    public function existsForAnotherEntity(string $shortCode, string $entityType, ?int $entityId = null): bool
    {
        $record = $this->findByShortCode($shortCode);

        if (!is_array($record)) {
            return false;
        }

        return $record['entity_type'] !== $entityType || $record['id'] !== (int) $entityId;
    }
}
