<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Core\CanonicalSlugRouter;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Media\Frontend\MediaRenderer;
use CivicPlatform\Services\MediaService;

/**
 * Registers and renders the public events listing shortcode.
 *
 * Rendering remains lightweight and data access is delegated to the event
 * repository.
 */
class EventListShortcode
{
    /**
     * Event repository.
     *
     * @var EventRepository
     */
    private EventRepository $events;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    private MediaService $media;

    /**
     * @param EventRepository $events Event repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(EventRepository $events, DateHelper $dates, MediaService $media)
    {
        $this->events = $events;
        $this->dates = $dates;
        $this->media = $media;
    }

    /**
     * Register the public events shortcode.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('civic_events', [$this, 'render']);
    }

    /**
     * Render published public events.
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
            'civic_events'
        );

        $page = $this->currentPage();
        $perPage = $this->perPage($atts);
        $paginationEnabled = $this->paginationEnabled($atts);
        $result = $this->events->getPublicActiveEvents(
            [
                'page' => $page,
                'per_page' => $perPage,
                'orderby' => 'start_date',
                'order' => 'ASC',
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;
        $detailPageId = absint($atts['detail_page_id']);

        ob_start();

        echo '<div class="civic-events ' . esc_attr($this->cardsWrapperClass($paginationEnabled)) . '">';

        if (empty($items)) {
            echo '<p class="civic-events__empty">' . esc_html__('No public events are currently available.', 'civic-engagement') . '</p>';
        }

        foreach ($items as $event) {
            $this->renderEvent($event, $detailPageId);
        }

        if ($paginationEnabled) {
            $this->renderPagination($page, $totalPages);
        }

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Render a single event summary.
     *
     * @param array<string, mixed> $event Event row.
     * @param int $detailPageId Detail page ID.
     * @return void
     */
    private function renderEvent(array $event, $detailPageId): void
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
            echo '<p class="civic-card__location civic-events__location">📍 <span class="civic-events__location-name">' . esc_html((string) $event['location']) . '</span></p>';
        }        
        echo '<p class="civic-card__date civic-events__date">📅 From <span class="civic-events__date-start">' . esc_html($this->dates->formatDate($event['start_date'] ?? null)) . '</span> to <span class="civic-events__date-end">' . esc_html($this->dates->formatDate($event['end_date'] ?? null)) . '</span></p>';
        echo '</div>';

        
        echo '<div class="civic-card__footer">';
        echo '<span class="civic-card__status civic-card__left civic-events__registration-status '.$this->registrationContainerStatus($event).' ">' . esc_html($this->registrationStatus($event)) . '</span>';

        echo '<span class="civic-card__actions civic-card__right civic-events__actions">';
        echo '<a href="' . esc_url($this->readMoreUrl((string) ($event['slug'] ?? ''), $eventId, $detailPageId)) . '">' . esc_html__('More →', 'civic-engagement') . '</a>';
        echo '</span>';
        echo '</div>';
        echo '</div>';
        echo '</article>';
    }

    /**
     * Build a registration status label.
     *
     * @param array<string, mixed> $event Event row.
     * @return string Registration status.
     */
    private function registrationStatus(array $event): string
    {
        return !empty($event['registration_enabled'])
            ? __('Registration: Open', 'civic-engagement')
            : __('Registration: Closed', 'civic-engagement');
    }

    private function registrationContainerStatus(array $event): string
    {
        return !empty($event['registration_enabled'])
            ? 'civic-card__status--open'
            : 'civic-card__status--closed';
    }

    /**
     * Build a placeholder read-more URL.
     *
     * @param int $eventId Event ID.
     * @return string Read-more URL.
     */
    private function readMoreUrl(string $slug, int $eventId, int $detailPageId): string
    {
        if ('' !== $slug) {
            return CanonicalSlugRouter::url('event', $slug);
        }

        return add_query_arg(
            ['event_id' => $eventId],
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

        echo '<nav class="civic-events__pagination" aria-label="' . esc_attr__('Event pages', 'civic-engagement') . '">';

        if ($page > 1) {
            echo '<a class="civic-events__pagination-previous" href="' . esc_url($this->pageUrl($page - 1)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a>';
        }

        echo '<span class="civic-events__pagination-current">' . esc_html(
            sprintf(
                /* translators: 1: current page, 2: total pages */
                __('Page %1$d of %2$d', 'civic-engagement'),
                $page,
                $totalPages
            )
        ) . '</span>';

        if ($page < $totalPages) {
            echo '<a class="civic-events__pagination-next" href="' . esc_url($this->pageUrl($page + 1)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
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
            ['event_page' => max(1, $page)],
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
        if (!isset($_GET['event_page'])) {
            return 1;
        }

        $page = wp_unslash($_GET['event_page']);

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
}
