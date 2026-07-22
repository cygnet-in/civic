<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Modules\Media\Frontend\MediaRenderer;
use CivicPlatform\Services\MediaService;

/**
 * Registers and renders the public schedule detail shortcode.
 */
class ScheduleDetailShortcode
{
    /**
     * Schedule repository.
     *
     * @var ScheduleRepository
     */
    private ScheduleRepository $schedules;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    private MediaService $media;

    /**
     * @param ScheduleRepository $schedules Schedule repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(ScheduleRepository $schedules, DateHelper $dates, MediaService $media)
    {
        $this->schedules = $schedules;
        $this->dates = $dates;
        $this->media = $media;
    }

    /**
     * Register the public schedule detail shortcode.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('civic_schedule_detail', [$this, 'render']);
    }

    /**
     * Render a public schedule detail.
     *
     * @param mixed $atts Shortcode attributes.
     * @return string Rendered shortcode output.
     */
    public function render($atts = []): string
    {
        unset($atts);

        $scheduleId = $this->scheduleId();
        $slug = $this->slug();

        ob_start();

        echo '<div class="civic-schedule-detail">';

        if ($scheduleId <= 0 && '' === $slug) {
            echo '<p class="civic-schedule-detail__empty">' . esc_html__('No schedule selected.', 'civic-engagement') . '</p>';
            echo '</div>';

            return (string) ob_get_clean();
        }

        $schedule = '' !== $slug
            ? $this->schedules->findPublicBySlug($slug)
            : $this->schedules->findPublicById($scheduleId);

        if (!is_array($schedule)) {
            echo '<p class="civic-schedule-detail__empty">' . esc_html__('Schedule not found.', 'civic-engagement') . '</p>';
            echo '</div>';

            return (string) ob_get_clean();
        }

        $this->renderSchedule($schedule);

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Render public schedule content.
     *
     * @param array<string, mixed> $schedule Schedule row.
     * @return void
     */
    private function renderSchedule(array $schedule): void
    {
        echo '<article class="civic-card civic-card-main-details civic-schedule-detail__content">';
        echo '<div class="civic-card__content">';
        echo '<h1 class="civic-card-detail__title civic-card__title civic-schedule-detail__title">' . esc_html((string) ($schedule['title'] ?? '')) . '</h1>';
        echo MediaRenderer::gallery($this->media->getByEntity('schedule', (int) ($schedule['id'] ?? 0)), 'schedule-' . (int) ($schedule['id'] ?? 0));
        echo '<p class="civic-card__type civic-schedule-detail__type"><strong>' . esc_html__('Type:', 'civic-engagement') . '</strong> ' . esc_html($this->typeLabel((string) ($schedule['type'] ?? ''))) . '</p>';
        echo '<p class="civic-card__status civic-schedule-detail__status"><strong>' . esc_html__('Status:', 'civic-engagement') . '</strong> ' . esc_html($this->statusLabel((string) ($schedule['status'] ?? ''))) . '</p>';

        if (!empty($schedule['details'])) {
            echo '<div class="civic-card__details civic-schedule-detail__details">' . wpautop(esc_html((string) $schedule['details'])) . '</div>';
        }

        if (!empty($schedule['recent_update'])) {
            echo '<p class="civic-card__recent-update civic-schedule-detail__recent-update"><strong>' . esc_html__('Recent Update:', 'civic-engagement') . '</strong> ' . esc_html((string) $schedule['recent_update']) . '</p>';
        }

        echo '<p class="civic-card__date civic-schedule-detail__date">';
        echo '<strong>' . esc_html__('Date:', 'civic-engagement') . '</strong><br>';
        echo 'Start Date: <span class="civic-schedule-detail__date-start">' . esc_html($this->dates->formatDate($schedule['start_date'] ?? null)) . '</span> Status Date: <span class="civic-schedule-detail__date-end">' . esc_html($this->dates->formatDate($schedule['end_date'] ?? null)) . '</span>';
        echo '</p>';
        echo '</div>';
        echo '</article>';
    }

    /**
     * Get sanitized requested schedule ID.
     *
     * @return int Schedule ID.
     */
    private function scheduleId(): int
    {
        if (!isset($_GET['schedule_id'])) {
            return 0;
        }

        $scheduleId = wp_unslash($_GET['schedule_id']);

        if (is_array($scheduleId) || is_object($scheduleId)) {
            return 0;
        }

        return absint($scheduleId);
    }

    private function slug(): string
    {
        $slug = get_query_var('civic_slug');

        return is_scalar($slug) ? sanitize_title((string) $slug) : '';
    }

    /**
     * Convert a stored schedule type to a readable label.
     *
     * @param string $type Stored schedule type.
     * @return string Type label.
     */
    private function typeLabel(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Convert a stored schedule status to a readable label.
     *
     * @param string $status Stored schedule status.
     * @return string Status label.
     */
    private function statusLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}
