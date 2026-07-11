<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Core\CanonicalSlugRouter;
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
                'limit' => '',
                'pagination' => '',
            ],
            $atts,
            'civic_schedules'
        );

        $page = $this->currentPage();
        $perPage = $this->perPage($atts);
        $paginationEnabled = $this->paginationEnabled($atts);
        $result = $this->schedules->getPublicActiveSchedules(
            [
                'page' => $page,
                'per_page' => $perPage,
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;
        $detailPageId = absint($atts['detail_page_id']);

        ob_start();

        echo '<div class="civic-schedules ' . esc_attr($this->cardsWrapperClass($paginationEnabled)) . '">';

        if (empty($items)) {
            echo '<p class="civic-schedules__empty">' . esc_html__('No public schedules are currently available.', 'civic-engagement') . '</p>';
        }

        foreach ($items as $schedule) {
            $this->renderSchedule($schedule, $detailPageId);
        }

        if ($paginationEnabled) {
            $this->renderPagination($page, $totalPages);
        }

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

        echo '<article class="civic-card civic-list-card civic-schedules__item">';
        echo MediaRenderer::listThumbnail($this->media->getPrimary('schedule', $scheduleId));
        echo '<div class="civic-card__content">';
        echo '<h2 class="civic-card__title civic-schedules__title">' . esc_html((string) ($schedule['title'] ?? '')) . '</h2>';
        echo '<div class="civic-card__meta">';
        echo '<p class="civic-card__date civic-schedules__date">📅 From <span class="civic-schedules__date-start">' . esc_html($this->dates->formatDate($schedule['start_date'] ?? null)) . '</span> to <span class="civic-schedules__date-end">' . esc_html($this->dates->formatDate($schedule['end_date'] ?? null)) . '</span></p>';
        echo '</div>';

        if (!empty($schedule['details'])) {
            echo '<p class="civic-card__details civic-card__summary civic-schedules__details">' . esc_html($this->shortDetails((string) $schedule['details'])) . '</p>';
        }

        if (!empty($schedule['recent_update'])) {
            echo '<p class="civic-card__recent-update civic-schedules__recent-update">' . esc_html((string) $schedule['recent_update']) . '</p>';
        }
        echo '<div class="civic-card__footer">';
        echo '<span class="civic-card__type civic-card__left civic-schedules__type">📌 ' . esc_html($this->typeLabel((string) ($schedule['type'] ?? ''))) . '</span>';
        echo '<span class="civic-card__actions civic-card__right civic-schedules__actions">';
        echo '<a href="' . esc_url($this->readMoreUrl((string) ($schedule['slug'] ?? ''), $scheduleId, $detailPageId)) . '">' . esc_html__('More →', 'civic-engagement') . '</a>';
        echo '</span>';
        echo '</div>';
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
    private function readMoreUrl(string $slug, int $scheduleId, int $detailPageId): string
    {
        if ('' !== $slug) {
            return CanonicalSlugRouter::url('schedule', $slug);
        }

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
     * Resolve shortcode page size.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return int Page size.
     */
    private function perPage(array $atts): int
    {
        $limit = isset($atts['limit']) ? absint($atts['limit']) : 0;

        return $limit > 0 ? $limit : 20;
    }

    /**
     * Resolve whether pagination should be displayed.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return bool True when pagination is enabled.
     */
    private function paginationEnabled(array $atts): bool
    {
        if ('' !== (string) ($atts['pagination'] ?? '')) {
            return filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN);
        }

        return '' === (string) ($atts['limit'] ?? '') || absint($atts['limit']) <= 0;
    }

    private function cardsWrapperClass(bool $paginationEnabled): string
    {
        return $paginationEnabled ? 'civic-cards-main-list' : 'civic-cards-home-list';
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
