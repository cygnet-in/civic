<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Repositories\MediaRepository;

/**
 * Coordinates Media Library image attachments with civic entity media records.
 */
class MediaService
{
    /** @var array<string, string> */
    private const IMAGE_MIMES = [
        'jpg|jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /** @var array<int, string> */
    private const ENTITY_TYPES = ['consultation', 'event', 'schedule'];

    private MediaRepository $media;

    public function __construct(MediaRepository $media)
    {
        $this->media = $media;
    }

    /** @return array<int, array<string, mixed>> */
    public function getByEntity(string $entityType, int $entityId): array
    {
        return $this->isEntityValid($entityType, $entityId)
            ? $this->media->findByEntity($entityType, $entityId)
            : [];
    }

    /** @return array<string, mixed>|null */
    public function getPrimary(string $entityType, int $entityId): ?array
    {
        $items = $this->getByEntity($entityType, $entityId);

        return $items[0] ?? null;
    }

    /**
     * Apply caption changes, association deletes, and optional uploaded images.
     *
     * @param array<string, mixed> $requestData Namespaced civic_media POST data.
     * @param array<string, mixed> $uploadData Namespaced civic_media FILES data.
     * @return array{errors: array<int, string>, created: int}
     */
    public function synchronize(string $entityType, int $entityId, array $requestData, array $uploadData, int $createdBy): array
    {
        if (!$this->isEntityValid($entityType, $entityId)) {
            return ['errors' => [__('Media could not be saved.', 'civic-engagement')], 'created' => 0];
        }

        $this->updateCaptions($entityType, $entityId, $requestData['captions'] ?? []);
        $this->deleteMedia($entityType, $entityId, $requestData['delete'] ?? []);

        $created = 0;
        $errors = [];

        foreach ($this->normalizeUploads($uploadData) as $file) {
            $result = $this->attachUpload($entityType, $entityId, $file, $createdBy);

            if (null !== $result['error']) {
                $errors[] = $result['error'];
                continue;
            }

            if ($result['created']) {
                $created++;
            }
        }

        return ['errors' => array_values(array_unique($errors)), 'created' => $created];
    }

    /** @param mixed $captions */
    private function updateCaptions(string $entityType, int $entityId, $captions): void
    {
        if (!is_array($captions)) {
            return;
        }

        foreach ($captions as $id => $caption) {
            if (is_array($caption) || is_object($caption)) {
                continue;
            }

            $this->media->updateCaption(absint($id), $entityType, $entityId, sanitize_textarea_field((string) $caption));
        }
    }

    /** @param mixed $ids */
    private function deleteMedia(string $entityType, int $entityId, $ids): void
    {
        if (!is_array($ids)) {
            return;
        }

        foreach ($ids as $id) {
            if (is_array($id) || is_object($id)) {
                continue;
            }

            $this->media->deleteForEntity(absint($id), $entityType, $entityId);
        }
    }

    /**
     * @param array{name: string, tmp_name: string, error: int, size: int} $file
     * @return array{created: bool, error: string|null}
     */
    private function attachUpload(string $entityType, int $entityId, array $file, int $createdBy): array
    {
        if (UPLOAD_ERR_NO_FILE === $file['error']) {
            return ['created' => false, 'error' => null];
        }

        if (UPLOAD_ERR_OK !== $file['error'] || '' === $file['name'] || '' === $file['tmp_name'] || $file['size'] <= 0 || !is_uploaded_file($file['tmp_name'])) {
            return ['created' => false, 'error' => __('One image could not be uploaded.', 'civic-engagement')];
        }

        $fileType = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], self::IMAGE_MIMES);

        if (empty($fileType['ext']) || empty($fileType['type'])) {
            return ['created' => false, 'error' => __('Images must be JPG, PNG, or WebP files.', 'civic-engagement')];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($file, ['test_form' => false, 'mimes' => self::IMAGE_MIMES]);

        if (!is_array($upload) || !empty($upload['error']) || empty($upload['file'])) {
            return ['created' => false, 'error' => __('One image could not be uploaded.', 'civic-engagement')];
        }

        $attachmentType = wp_check_filetype(basename((string) $upload['file']), self::IMAGE_MIMES);
        $attachmentId = wp_insert_attachment(
            [
                'post_mime_type' => $attachmentType['type'] ?? '',
                'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
                'post_status' => 'inherit',
            ],
            (string) $upload['file']
        );

        if (is_wp_error($attachmentId) || $attachmentId <= 0) {
            return ['created' => false, 'error' => __('One image could not be saved.', 'civic-engagement')];
        }

        $metadata = wp_generate_attachment_metadata($attachmentId, (string) $upload['file']);
        if (is_array($metadata)) {
            wp_update_attachment_metadata($attachmentId, $metadata);
        }

        $created = $this->media->create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'attachment_id' => (int) $attachmentId,
            'sort_order' => $this->media->nextSortOrder($entityType, $entityId),
            'created_by' => max(0, $createdBy),
        ]);

        return $created > 0
            ? ['created' => true, 'error' => null]
            : ['created' => false, 'error' => __('One image could not be associated with this item.', 'civic-engagement')];
    }

    /**
     * @param array<string, mixed> $uploadData
     * @return array<int, array{name: string, tmp_name: string, error: int, size: int}>
     */
    private function normalizeUploads(array $uploadData): array
    {
        $uploads = [];
        $keys = ['name', 'tmp_name', 'error', 'size'];

        foreach ($keys as $key) {
            if (!isset($uploadData[$key]) || !is_array($uploadData[$key]) || !isset($uploadData[$key]['uploads']) || !is_array($uploadData[$key]['uploads'])) {
                return [];
            }
        }

        foreach ($uploadData['name']['uploads'] as $index => $name) {
            $tmpName = $uploadData['tmp_name']['uploads'][$index] ?? null;
            $error = $uploadData['error']['uploads'][$index] ?? null;
            $size = $uploadData['size']['uploads'][$index] ?? null;

            if (is_array($name) || is_object($name) || is_array($tmpName) || is_object($tmpName) || !is_numeric($error) || !is_numeric($size)) {
                continue;
            }

            $uploads[] = [
                'name' => (string) $name,
                'tmp_name' => (string) $tmpName,
                'error' => (int) $error,
                'size' => (int) $size,
            ];
        }

        return $uploads;
    }

    private function isEntityValid(string $entityType, int $entityId): bool
    {
        return $entityId > 0 && in_array($entityType, self::ENTITY_TYPES, true);
    }
}
