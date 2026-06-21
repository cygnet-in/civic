<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Modules\Media\Frontend\MediaRenderer;
use CivicPlatform\Services\MediaService;

/**
 * Registers and renders the public schedules listing shortcode.
 */
class ScheduleListShortcode
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
     * Register the public schedules shortcode.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('civic_schedules', [$this, 'render']);
    }

    /**
     * Render public non-archived schedules.
     *
     * @param mixed $atts Shortcode attributes.
     * @return string Rendered shortcode output.
     */
    public function render($atts = []): string
    {
        if (!is_array($atts)) {
            $atts = [];
        }

        $atts = shortcode_atts(
            [
                'detail_page_id' => 0,
            ],
            $atts,
            'civic_schedules'
        );

        $page = $this->currentPage();
        $result = $this->schedules->getPaginated(
            [
                'page' => $page,
                'per_page' => 20,
                'is_public' => 1,
                'is_archived' => 0,
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;
        $detailPageId = absint($atts['detail_page_id']);

        ob_start();

        echo '<div class="civic-schedules">';

        if (empty($items)) {
            echo '<p class="civic-schedules__empty">' . esc_html__('No public schedules are currently available.', 'civic-engagement') . '</p>';
        }

        foreach ($items as $schedule) {
            $this->renderSchedule($schedule, $detailPageId);
        }

        $this->renderPagination($page, $totalPages);

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Render a single schedule summary.
     *
     * @param array<string, mixed> $schedule Schedule row.
     * @param int $detailPageId Detail page ID.
     * @return void
     */
    private function renderSchedule(array $schedule, int $detailPageId): void
    {
        $scheduleId = isset($schedule['id']) ? (int) $schedule['id'] : 0;

        echo '<article class="civic-card civic-schedules__item">';
        echo MediaRenderer::listThumbnail($this->media->getPrimary('schedule', $scheduleId));
        echo '<div class="civic-card__content">';
        echo '<h2 class="civic-card__title civic-schedules__title">' . esc_html((string) ($schedule['title'] ?? '')) . '</h2>';
        echo '<p class="civic-card__type civic-schedules__type">' . esc_html($this->typeLabel((string) ($schedule['type'] ?? ''))) . '</p>';
        echo '<p class="civic-card__date civic-schedules__date">Date: From <span class="civic-schedules__date-start">' . esc_html($this->dates->formatDate($schedule['start_date'] ?? null)) . '</span> to <span class="civic-schedules__date-end">' . esc_html($this->dates->formatDate($schedule['end_date'] ?? null)) . '</span></p>';

        if (!empty($schedule['details'])) {
            echo '<p class="civic-card__details civic-schedules__details">' . esc_html($this->shortDetails((string) $schedule['details'])) . '</p>';
        }

        if (!empty($schedule['recent_update'])) {
            echo '<p class="civic-card__recent-update civic-schedules__recent-update">' . esc_html((string) $schedule['recent_update']) . '</p>';
        }

        echo '<p class="civic-card__actions civic-schedules__actions">';
        echo '<a href="' . esc_url($this->readMoreUrl($scheduleId, $detailPageId)) . '">' . esc_html__('Read more', 'civic-engagement') . '</a>';
        echo '</p>';
        echo '</div>';
        echo '</article>';
    }

    /**
     * Build a read-more URL using the configured detail page.
     *
     * @param int $scheduleId Schedule ID.
     * @param int $detailPageId Detail page ID.
     * @return string Read-more URL.
     */
    private function readMoreUrl(int $scheduleId, int $detailPageId): string
    {
        return add_query_arg(
            ['schedule_id' => $scheduleId],
            get_permalink($detailPageId)
        );
    }

    /**
     * Render lightweight query-string pagination.
     *
     * @param int $page Current page.
     * @param int $totalPages Total pages.
     * @return void
     */
    private function renderPagination(int $page, int $totalPages): void
    {
        if ($totalPages <= 1) {
            return;
        }

        echo '<nav class="civic-schedules__pagination" aria-label="' . esc_attr__('Schedule pages', 'civic-engagement') . '">';

        if ($page > 1) {
            echo '<a class="civic-schedules__pagination-previous" href="' . esc_url($this->pageUrl($page - 1)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a>';
        }

        echo '<span class="civic-schedules__pagination-current">' . esc_html(
            sprintf(
                /* translators: 1: current page, 2: total pages */
                __('Page %1$d of %2$d', 'civic-engagement'),
                $page,
                $totalPages
            )
        ) . '</span>';

        if ($page < $totalPages) {
            echo '<a class="civic-schedules__pagination-next" href="' . esc_url($this->pageUrl($page + 1)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</nav>';
    }

    /**
     * Build a pagination URL.
     *
     * @param int $page Page number.
     * @return string Page URL.
     */
    private function pageUrl(int $page): string
    {
        return add_query_arg(
            ['schedule_page' => max(1, $page)],
            get_permalink()
        );
    }

    /**
     * Get sanitized current frontend page number.
     *
     * @return int Current page.
     */
    private function currentPage(): int
    {
        if (!isset($_GET['schedule_page'])) {
            return 1;
        }

        $page = wp_unslash($_GET['schedule_page']);

        if (is_array($page) || is_object($page)) {
            return 1;
        }

        return max(1, absint($page));
    }

    /**
     * Build a short details excerpt.
     *
     * @param string $details Schedule details.
     * @return string Short details.
     */
    private function shortDetails(string $details): string
    {
        return wp_trim_words(wp_strip_all_tags($details), 40, '...');
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
}
