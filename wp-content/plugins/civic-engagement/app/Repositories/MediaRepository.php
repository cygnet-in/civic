<?php

declare(strict_types=1);

namespace CivicPlatform\Repositories;

/**
 * Repository for Media Library attachments associated with civic entities.
 */
class MediaRepository extends BaseRepository
{
    public function __construct(\wpdb $wpdb)
    {
        parent::__construct($wpdb, 'civic_media');
    }

    /**
     * Create an entity media association.
     *
     * @param array<string, mixed> $data Media association data.
     * @return int New media association ID, or zero on failure.
     */
    public function create(array $data): int
    {
        $insert = [
            'entity_type' => (string) ($data['entity_type'] ?? ''),
            'entity_id' => (int) ($data['entity_id'] ?? 0),
            'attachment_id' => (int) ($data['attachment_id'] ?? 0),
            'caption' => (string) ($data['caption'] ?? ''),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'created_by' => (int) ($data['created_by'] ?? 0),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        if ('' === $insert['entity_type'] || $insert['entity_id'] <= 0 || $insert['attachment_id'] <= 0) {
            return 0;
        }

        $created = $this->wpdb->insert(
            $this->table,
            $insert,
            ['%s', '%d', '%d', '%s', '%d', '%d', '%s', '%s']
        );

        return false === $created ? 0 : (int) $this->wpdb->insert_id;
    }

    /**
     * Find entity media ordered by primary image then subsequent images.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        if ('' === $entityType || $entityId <= 0) {
            return [];
        }

        $items = $this->wpdb->get_results(
            $this->prepare(
                "SELECT * FROM {$this->table} WHERE entity_type = %s AND entity_id = %d ORDER BY sort_order ASC, id ASC",
                [$entityType, $entityId]
            ),
            ARRAY_A
        );

        return is_array($items) ? $items : [];
    }

    /**
     * Update a media caption scoped to its entity.
     */
    public function updateCaption(int $id, string $entityType, int $entityId, string $caption): bool
    {
        if ($id <= 0 || '' === $entityType || $entityId <= 0) {
            return false;
        }

        return false !== $this->wpdb->update(
            $this->table,
            ['caption' => $caption, 'updated_at' => current_time('mysql')],
            ['id' => $id, 'entity_type' => $entityType, 'entity_id' => $entityId],
            ['%s', '%s'],
            ['%d', '%s', '%d']
        );
    }

    /**
     * Remove a media association without deleting the Media Library attachment.
     */
    public function deleteForEntity(int $id, string $entityType, int $entityId): bool
    {
        if ($id <= 0 || '' === $entityType || $entityId <= 0) {
            return false;
        }

        return false !== $this->wpdb->delete(
            $this->table,
            ['id' => $id, 'entity_type' => $entityType, 'entity_id' => $entityId],
            ['%d', '%s', '%d']
        );
    }

    /**
     * Get the next stable display order for an entity image.
     */
    public function nextSortOrder(string $entityType, int $entityId): int
    {
        $max = $this->wpdb->get_var(
            $this->prepare(
                "SELECT MAX(sort_order) FROM {$this->table} WHERE entity_type = %s AND entity_id = %d",
                [$entityType, $entityId]
            )
        );

        return null === $max ? 0 : ((int) $max + 1);
    }
}
