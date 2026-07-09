<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Frontend;

use CivicPlatform\Core\CanonicalSlugRouter;
use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Media\Frontend\MediaRenderer;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Services\MediaService;

/**
 * Registers and renders the public archived schedules shortcode.
 */
class ScheduleArchiveShortcode
{
    private ScheduleRepository $schedules;

    private DateHelper $dates;

    private MediaService $media;

    public function __construct(ScheduleRepository $schedules, DateHelper $dates, MediaService $media)
    {
        $this->schedules = $schedules;
        $this->dates = $dates;
        $this->media = $media;
    }

    public function register(): void
    {
        add_shortcode('civic_schedules_archive', [$this, 'render']);
    }

    public function render($atts = []): string
    {
        if (!is_array($atts)) {
            $atts = [];
        }

        $atts = shortcode_atts(
            [
                'detail_page_id' => 0,
                'limit' => '',
            ],
            $atts,
            'civic_schedules_archive'
        );

        $limit = absint($atts['limit']);
        $isCompact = $limit > 0;
        $page = $isCompact ? 1 : $this->currentPage();
        $perPage = $isCompact ? $limit : 20;
        $detailPageId = absint($atts['detail_page_id']);
        $result = $this->schedules->getPublicArchivedSchedules(
            [
                'page' => $page,
                'per_page' => $perPage,
                'orderby' => 'end_date',
                'order' => 'DESC',
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        ob_start();

        echo '<div class="civic-schedules-archive' . ($isCompact ? ' civic-archive-list-wrap' : ' civic-cards-main-list') . '">';

        if (empty($items)) {
            echo '<p class="civic-schedules__empty">' . esc_html__('No archived schedules are currently available.', 'civic-engagement') . '</p>';
        } elseif ($isCompact) {
            $this->renderCompactList($items, $detailPageId);
        } else {
            foreach ($items as $schedule) {
                $this->renderSchedule($schedule, $detailPageId);
            }

            $this->renderPagination($page, $totalPages);
        }

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function renderCompactList(array $items, int $detailPageId): void
    {
        echo '<ul class="civic-archive-list civic-schedules-archive__list">';

        foreach ($items as $item) {
            $scheduleId = isset($item['id']) ? (int) $item['id'] : 0;
            echo '<li class="civic-archive-list__item civic-schedules-archive__item">';
            echo '<a href="' . esc_url($this->readMoreUrl((string) ($item['slug'] ?? ''), $scheduleId, $detailPageId)) . '">' . esc_html((string) ($item['title'] ?? '')) . '</a>';
            echo '</li>';
        }

        echo '</ul>';
    }

    private function renderSchedule(array $schedule, int $detailPageId): void
    {
        $scheduleId = isset($schedule['id']) ? (int) $schedule['id'] : 0;

        echo '<article class="civic-card civic-list-card civic-schedules__item">';
        echo MediaRenderer::listThumbnail($this->media->getPrimary('schedule', $scheduleId));
        echo '<div class="civic-card__content">';
        echo '<h2 class="civic-card__title civic-schedules__title">' . esc_html((string) ($schedule['title'] ?? '')) . '</h2>';
        echo '<div class="civic-card__meta">';
        echo '<p class="civic-card__date civic-schedules__date">From <span class="civic-schedules__date-start">' . esc_html($this->dates->formatDate($schedule['start_date'] ?? null)) . '</span> to <span class="civic-schedules__date-end">' . esc_html($this->dates->formatDate($schedule['end_date'] ?? null)) . '</span></p>';
        echo '</div>';

        if (!empty($schedule['details'])) {
            echo '<p class="civic-card__details civic-card__summary civic-schedules__details">' . esc_html($this->shortDetails((string) $schedule['details'])) . '</p>';
        }

        if (!empty($schedule['recent_update'])) {
            echo '<p class="civic-card__recent-update civic-schedules__recent-update">' . esc_html((string) $schedule['recent_update']) . '</p>';
        }

        echo '<div class="civic-card__footer">';
        echo '<span class="civic-card__type civic-card__left civic-schedules__type">' . esc_html($this->typeLabel((string) ($schedule['type'] ?? ''))) . '</span>';
        echo '<span class="civic-card__actions civic-card__right civic-schedules__actions">';
        echo '<a href="' . esc_url($this->readMoreUrl((string) ($schedule['slug'] ?? ''), $scheduleId, $detailPageId)) . '">' . esc_html__('More', 'civic-engagement') . '</a>';
        echo '</span>';
        echo '</div>';
        echo '</div>';
        echo '</article>';
    }

    private function readMoreUrl(string $slug, int $scheduleId, int $detailPageId): string
    {
        if ('' !== $slug) {
            return CanonicalSlugRouter::url('schedule', $slug);
        }

        return add_query_arg(['schedule_id' => $scheduleId], get_permalink($detailPageId));
    }

    private function renderPagination(int $page, int $totalPages): void
    {
        if ($totalPages <= 1) {
            return;
        }

        echo '<nav class="civic-schedules__pagination" aria-label="' . esc_attr__('Archived schedule pages', 'civic-engagement') . '">';

        if ($page > 1) {
            echo '<a class="civic-schedules__pagination-previous" href="' . esc_url($this->pageUrl($page - 1)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a>';
        }

        echo '<span class="civic-schedules__pagination-current">' . esc_html(sprintf(__('Page %1$d of %2$d', 'civic-engagement'), $page, $totalPages)) . '</span>';

        if ($page < $totalPages) {
            echo '<a class="civic-schedules__pagination-next" href="' . esc_url($this->pageUrl($page + 1)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</nav>';
    }

    private function pageUrl(int $page): string
    {
        return add_query_arg(['schedule_archive_page' => max(1, $page)], get_permalink());
    }

    private function currentPage(): int
    {
        if (!isset($_GET['schedule_archive_page'])) {
            return 1;
        }

        $page = wp_unslash($_GET['schedule_archive_page']);

        if (is_array($page) || is_object($page)) {
            return 1;
        }

        return max(1, absint($page));
    }

    private function shortDetails(string $details): string
    {
        return wp_trim_words(wp_strip_all_tags($details), 40, '...');
    }

    private function typeLabel(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }
}
