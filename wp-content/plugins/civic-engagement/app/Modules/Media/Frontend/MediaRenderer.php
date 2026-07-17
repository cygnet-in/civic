<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Media\Frontend;

/** Small shared renderer for ordered civic image associations. */
class MediaRenderer
{
    /** @param array<string, mixed>|null $media */
    public static function listThumbnail(?array $media): string
    {
        $attachmentId = is_array($media) ? (int) ($media['attachment_id'] ?? 0) : 0;
        $url = $attachmentId > 0 ? wp_get_attachment_image_url($attachmentId, 'thumbnail') : false;

        return is_string($url) && '' !== $url
            ? '<div class="civic-card__media"><img src="' . esc_url($url) . '" alt="' . esc_attr((string) ($media['caption'] ?? '')) . '"></div>'
            : '<div class="civic-card__media"></div>';
    }

    /** @param array<int, array<string, mixed>> $media */
    public static function gallery(array $media, string $context): string
    {
        if (empty($media)) {
            return '';
        }

        $primary = $media[0];
        $attachmentId = (int) ($primary['attachment_id'] ?? 0);
        $primaryUrl = $attachmentId > 0 ? wp_get_attachment_image_url($attachmentId, 'large') : false;

        if (!is_string($primaryUrl) || '' === $primaryUrl) {
            return '';
        }

        $id = 'civic-media-' . sanitize_html_class($context);
        $caption = (string) ($primary['caption'] ?? '');
        $html = '<section id="' . esc_attr($id) . '" class="civic-media-gallery">';
        $html .= '<img class="civic-media-gallery__primary" src="' . esc_url($primaryUrl) . '" alt="' . esc_attr($caption) . '">';
        $html .= '<p class="civic-media-gallery__caption">' . esc_html($caption) . '</p>';

        if (count($media) > 1) {
            $html .= '<div class="civic-media-gallery__thumbnails">';
            foreach ($media as $item) {
                $thumbId = (int) ($item['attachment_id'] ?? 0);
                $thumbUrl = $thumbId > 0 ? wp_get_attachment_image_url($thumbId, 'thumbnail') : false;
                $fullUrl = $thumbId > 0 ? wp_get_attachment_image_url($thumbId, 'large') : false;

                if (!is_string($thumbUrl) || '' === $thumbUrl || !is_string($fullUrl) || '' === $fullUrl) {
                    continue;
                }

                $itemCaption = (string) ($item['caption'] ?? '');
                $html .= '<button type="button" class="civic-media-gallery__thumbnail" data-image-url="' . esc_attr($fullUrl) . '" data-caption="' . esc_attr($itemCaption) . '">';
                $html .= '<img src="' . esc_url($thumbUrl) . '" alt="' . esc_attr($itemCaption) . '">';
                $html .= '</button>';
            }
            $html .= '</div>';
        }

        $html .= '</section>';
        $html .= '<script>(function(){var gallery=document.getElementById(' . wp_json_encode($id) . ');if(!gallery){return;}gallery.addEventListener("click",function(event){var button=event.target.closest(".civic-media-gallery__thumbnail");if(!button){return;}var image=gallery.querySelector(".civic-media-gallery__primary");var caption=gallery.querySelector(".civic-media-gallery__caption");image.src=button.dataset.imageUrl;image.alt=button.dataset.caption;caption.textContent=button.dataset.caption;});}());</script>';

        return $html;
    }
}
