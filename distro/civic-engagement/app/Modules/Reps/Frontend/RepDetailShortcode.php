<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Frontend;

use CivicPlatform\Services\RepService;

/**
 * Renders the public representation detail view.
 *
 * Contact snapshot data is deliberately excluded from this public output.
 */
class RepDetailShortcode
{
    private RepService $reps;

    public function __construct(RepService $reps)
    {
        $this->reps = $reps;
    }

    /**
     * Render a representation using the rep_id attribute or request value.
     *
     * @param mixed $atts Shortcode attributes.
     * @return string Detail markup, or an empty string when no rep is available.
     */
    public function render($atts = []): string
    {
        $atts = is_array($atts) ? $atts : [];
        $atts = shortcode_atts(['rep_id' => 0], $atts, 'civic_rep_detail');
        $repId = absint($atts['rep_id']);

        if ($repId <= 0 && isset($_GET['rep_id']) && !is_array($_GET['rep_id']) && !is_object($_GET['rep_id'])) {
            $repId = absint(wp_unslash($_GET['rep_id']));
        }

        if ($repId <= 0) {
            return '';
        }

        $rep = $this->reps->findById($repId);

        if (!is_array($rep)) {
            return '';
        }

        $image = $this->renderImage($rep);

        return '<article class="civic-rep-detail">'
            . '<h2>' . esc_html((string) ($rep['title'] ?? '')) . '</h2>'
            . '<div class="civic-rep-detail__details">' . nl2br(esc_html((string) ($rep['details'] ?? ''))) . '</div>'
            . $image
            . '</article>';
    }

    /**
     * Render the optional uploaded image using WordPress attachment URLs.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return string Image markup, or an empty string when no valid image exists.
     */
    private function renderImage(array $rep): string
    {
        $attachmentId = isset($rep['image_attachment_id']) ? (int) $rep['image_attachment_id'] : 0;

        if ($attachmentId <= 0) {
            return '';
        }

        $imageUrl = wp_get_attachment_image_url($attachmentId, 'large');
        $fullUrl = wp_get_attachment_url($attachmentId);

        if (!is_string($imageUrl) || '' === $imageUrl || !is_string($fullUrl) || '' === $fullUrl) {
            return '';
        }

        return '<p class="civic-rep-detail__image"><a href="' . esc_url($fullUrl) . '">'
            . '<img src="' . esc_url($imageUrl) . '" alt="' . esc_attr__('Uploaded representation image', 'civic-engagement') . '">'
            . '</a></p>';
    }
}
