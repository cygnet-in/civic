<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Frontend;

use CivicPlatform\Core\CanonicalSlugRouter;
use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Media\Frontend\MediaRenderer;
use CivicPlatform\Services\MediaService;

/**
 * Registers and renders the public archived events shortcode.
 */
class EventArchiveShortcode
{
    private EventRepository $events;

    private DateHelper $dates;

    private MediaService $media;

    public function __construct(EventRepository $events, DateHelper $dates, MediaService $media)
    {
        $this->events = $events;
        $this->dates = $dates;
        $this->media = $media;
    }

    public function register(): void
    {
        add_shortcode('civic_events_archive', [$this, 'render']);
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
            'civic_events_archive'
        );

        $limit = absint($atts['limit']);
        $isCompact = $limit > 0;
        $page = $isCompact ? 1 : $this->currentPage();
        $perPage = $isCompact ? $limit : 20;
        $detailPageId = absint($atts['detail_page_id']);
        $result = $this->events->getPublicArchivedEvents(
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

        echo '<div class="civic-events-archive' . ($isCompact ? ' civic-archive-list-wrap' : ' civic-cards-main-list') . '">';

        if (empty($items)) {
            echo '<p class="civic-events__empty">' . esc_html__('No archived events are currently available.', 'civic-engagement') . '</p>';
        } elseif ($isCompact) {
            $this->renderCompactList($items, $detailPageId);
        } else {
            foreach ($items as $event) {
                $this->renderEvent($event, $detailPageId);
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
        echo '<ul class="civic-archive-list civic-events-archive__list">';

        foreach ($items as $item) {
            $eventId = isset($item['id']) ? (int) $item['id'] : 0;
            echo '<li class="civic-archive-list__item civic-events-archive__item">';
            echo '<a href="' . esc_url($this->readMoreUrl((string) ($item['slug'] ?? ''), $eventId, $detailPageId)) . '">' . esc_html((string) ($item['title'] ?? '')) . '</a>';
            echo '</li>';
        }

        echo '</ul>';
    }

    private function renderEvent(array $event, int $detailPageId): void
    {
        $eventId = isset($event['id']) ? (int) $event['id'] : 0;

        echo '<article class="civic-card civic-list-card civic-events__item">';
        echo MediaRenderer::listThumbnail($this->media->getPrimary('event', $eventId));
        echo '<div class="civic-card__content">';
        echo '<h2 class="civic-card__title civic-events__title">' . esc_html((string) ($event['title'] ?? '')) . '</h2>';

        if (!empty($event['summary'])) {
            echo '<p class="civic-card__summary civic-events__summary">' . esc_html((string) $event['summary']) . '</p>';
        }

        echo '<div class="civic-card__meta">';
        if (!empty($event['location'])) {
            echo '<p class="civic-card__location civic-events__location"><span class="civic-events__location-name">' . esc_html((string) $event['location']) . '</span></p>';
        }
        echo '<p class="civic-card__date civic-events__date">From <span class="civic-events__date-start">' . esc_html($this->dates->formatDate($event['start_date'] ?? null)) . '</span> to <span class="civic-events__date-end">' . esc_html($this->dates->formatDate($event['end_date'] ?? null)) . '</span></p>';
        echo '</div>';
        echo '<div class="civic-card__footer">';
        echo '<span class="civic-card__status civic-card__left civic-events__registration-status ' . esc_attr($this->registrationContainerStatus($event)) . '">' . esc_html($this->registrationStatus($event)) . '</span>';
        echo '<span class="civic-card__actions civic-card__right civic-events__actions">';
        echo '<a href="' . esc_url($this->readMoreUrl((string) ($event['slug'] ?? ''), $eventId, $detailPageId)) . '">' . esc_html__('More', 'civic-engagement') . '</a>';
        echo '</span>';
        echo '</div>';
        echo '</div>';
        echo '</article>';
    }

    private function registrationStatus(array $event): string
    {
        return $this->events->isAcceptingRegistrations($event)
            ? __('Registration: Open', 'civic-engagement')
            : __('Registration: Closed', 'civic-engagement');
    }

    private function registrationContainerStatus(array $event): string
    {
        return $this->events->isAcceptingRegistrations($event)
            ? 'civic-card__status--open'
            : 'civic-card__status--closed';
    }

    private function readMoreUrl(string $slug, int $eventId, int $detailPageId): string
    {
        if ('' !== $slug) {
            return CanonicalSlugRouter::url('event', $slug);
        }

        return add_query_arg(['event_id' => $eventId], get_permalink($detailPageId));
    }

    private function renderPagination(int $page, int $totalPages): void
    {
        if ($totalPages <= 1) {
            return;
        }

        echo '<nav class="civic-events__pagination" aria-label="' . esc_attr__('Archived event pages', 'civic-engagement') . '">';

        if ($page > 1) {
            echo '<a class="civic-events__pagination-previous" href="' . esc_url($this->pageUrl($page - 1)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a>';
        }

        echo '<span class="civic-events__pagination-current">' . esc_html(sprintf(__('Page %1$d of %2$d', 'civic-engagement'), $page, $totalPages)) . '</span>';

        if ($page < $totalPages) {
            echo '<a class="civic-events__pagination-next" href="' . esc_url($this->pageUrl($page + 1)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</nav>';
    }

    private function pageUrl(int $page): string
    {
        return add_query_arg(['event_archive_page' => max(1, $page)], get_permalink());
    }

    private function currentPage(): int
    {
        if (!isset($_GET['event_archive_page'])) {
            return 1;
        }

        $page = wp_unslash($_GET['event_archive_page']);

        if (is_array($page) || is_object($page)) {
            return 1;
        }

        return max(1, absint($page));
    }
}
