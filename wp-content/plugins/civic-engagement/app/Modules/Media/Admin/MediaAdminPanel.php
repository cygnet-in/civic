<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Media\Admin;

use CivicPlatform\Services\MediaService;

/** Renders shared civic image controls inside existing admin edit forms. */
class MediaAdminPanel
{
    private MediaService $media;

    public function __construct(MediaService $media)
    {
        $this->media = $media;
    }

    public function render(string $entityType, int $entityId): void
    {
        $items = $entityId > 0 ? $this->media->getByEntity($entityType, $entityId) : [];

        $uploadId = 'civic-media-upload-' . esc_attr($entityType) . '-' . max(0, $entityId);
        echo '<h2>' . esc_html__('Images', 'civic-engagement') . '</h2>';
        echo '<p><label for="' . $uploadId . '">' . esc_html__('Upload images', 'civic-engagement') . '</label><br><input id="' . $uploadId . '" type="file" name="civic_media[uploads][]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></p>';

        if (empty($items)) {
            return;
        }

        echo '<div class="civic-media-admin-list">';
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $attachmentId = (int) ($item['attachment_id'] ?? 0);
            $thumbnailUrl = $attachmentId > 0 ? wp_get_attachment_image_url($attachmentId, 'thumbnail') : false;

            if ($id <= 0 || !is_string($thumbnailUrl) || '' === $thumbnailUrl) {
                continue;
            }

            echo '<div class="civic-media-admin-list__item">';
            echo '<img src="' . esc_url($thumbnailUrl) . '" alt="' . esc_attr__('Uploaded image', 'civic-engagement') . '">';
            echo '<p><label>' . esc_html__('Caption', 'civic-engagement') . '<br><input class="regular-text" type="text" name="civic_media[captions][' . esc_attr((string) $id) . ']" value="' . esc_attr((string) ($item['caption'] ?? '')) . '"></label></p>';
            echo '<p><label><input type="checkbox" name="civic_media[delete][]" value="' . esc_attr((string) $id) . '"> ' . esc_html__('Delete image', 'civic-engagement') . '</label></p>';
            echo '</div>';
        }
        echo '</div>';
    }

    /** Render associated images and captions without edit controls. */
    public function renderReadOnly(string $entityType, int $entityId): void
    {
        $items = $this->media->getByEntity($entityType, $entityId);

        if (empty($items)) {
            return;
        }

        echo '<h2>' . esc_html__('Images', 'civic-engagement') . '</h2><div class="civic-media-admin-list">';
        foreach ($items as $item) {
            $attachmentId = (int) ($item['attachment_id'] ?? 0);
            $thumbnailUrl = $attachmentId > 0 ? wp_get_attachment_image_url($attachmentId, 'thumbnail') : false;
            $fullUrl = $attachmentId > 0 ? wp_get_attachment_url($attachmentId) : false;

            if (!is_string($thumbnailUrl) || '' === $thumbnailUrl || !is_string($fullUrl) || '' === $fullUrl) {
                continue;
            }

            echo '<div class="civic-media-admin-list__item"><a href="' . esc_url($fullUrl) . '" target="_blank" rel="noopener"><img src="' . esc_url($thumbnailUrl) . '" alt="' . esc_attr((string) ($item['caption'] ?? '')) . '"></a>';
            if ('' !== (string) ($item['caption'] ?? '')) {
                echo '<p>' . esc_html((string) $item['caption']) . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}
