<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

/**
 * Registers and renders the public threads listing shortcode.
 *
 * Rendering remains lightweight and data access is delegated to the thread
 * repository.
 */
class ThreadsListShortcode
{
    /**
     * Thread repository.
     *
     * @var ThreadRepository
     */
    private ThreadRepository $threads;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    /**
     * @param ThreadRepository $threads Thread repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(ThreadRepository $threads, DateHelper $dates)
    {
        $this->threads = $threads;
        $this->dates = $dates;
    }

    /**
     * Register the public threads shortcode.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('civic_threads', [$this, 'render']);
    }

    /**
     * Render published public consultations.
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
            'civic_threads'
        );

        $page = $this->currentPage();
        $detailPageId = absint($atts['detail_page_id']);
        $result = $this->threads->getPublicThreads(
            [
                'page' => $page,
                'per_page' => 20,
                'orderby' => 'created_at',
                'order' => 'DESC',
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        ob_start();

        echo '<div class="civic-threads">';

        if (empty($items)) {
            echo '<p class="civic-threads__empty">' . esc_html__('No consultations are currently available.', 'civic-engagement') . '</p>';
        }

        foreach ($items as $thread) {
            $this->renderThread($thread, $detailPageId);
        }

        $this->renderPagination($page, $totalPages);

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Render a single consultation summary.
     *
     * @param array<string, mixed> $thread Thread row.
     * @param int $detailPageId Detail page ID.
     * @return void
     */
    private function renderThread(array $thread, int $detailPageId): void
    {
        $threadId = isset($thread['id']) ? (int) $thread['id'] : 0;

        echo '<article class="civic-threads__item">';
        echo '<h2 class="civic-threads__title">' . esc_html((string) ($thread['title'] ?? '')) . '</h2>';

        if (!empty($thread['summary'])) {
            echo '<p class="civic-threads__summary">' . esc_html((string) $thread['summary']) . '</p>';
        }

        echo '<p class="civic-threads__date">' . esc_html($this->dates->formatDate((string) ($thread['created_at'] ?? ''))) . '</p>';

        echo '<p class="civic-threads__actions">';
        echo '<a href="' . esc_url($this->readMoreUrl($threadId, $detailPageId)) . '">' . esc_html__('Read more', 'civic-engagement') . '</a>';
        echo '</p>';
        echo '</article>';
    }

    /**
     * Build a placeholder read-more URL.
     *
     * @param int $threadId Thread ID.
     * @param int $detailPageId Detail page ID.
     * @return string Read-more URL.
     */
    private function readMoreUrl(int $threadId, int $detailPageId): string
    {
        return add_query_arg(
            ['thread_id' => $threadId],
            $this->detailBaseUrl($detailPageId)
        );
    }

    /**
     * Resolve the detail page base URL.
     *
     * @param int $detailPageId Detail page ID.
     * @return string Detail base URL or current page fallback.
     */
    private function detailBaseUrl(int $detailPageId): string
    {
        if ($detailPageId > 0) {
            $permalink = get_permalink($detailPageId);

            if (is_string($permalink) && '' !== $permalink) {
                return $permalink;
            }
        }

        $fallback = get_permalink();

        return is_string($fallback) ? $fallback : '';
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

        echo '<nav class="civic-threads__pagination" aria-label="' . esc_attr__('Consultation pages', 'civic-engagement') . '">';

        if ($page > 1) {
            echo '<a class="civic-threads__pagination-previous" href="' . esc_url($this->pageUrl($page - 1)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a>';
        }

        echo '<span class="civic-threads__pagination-current">' . esc_html(
            sprintf(
                /* translators: 1: current page, 2: total pages */
                __('Page %1$d of %2$d', 'civic-engagement'),
                $page,
                $totalPages
            )
        ) . '</span>';

        if ($page < $totalPages) {
            echo '<a class="civic-threads__pagination-next" href="' . esc_url($this->pageUrl($page + 1)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
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
            ['thread_page' => max(1, $page)],
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
        if (!isset($_GET['thread_page'])) {
            return 1;
        }

        $page = wp_unslash($_GET['thread_page']);

        if (is_array($page) || is_object($page)) {
            return 1;
        }

        return max(1, absint($page));
    }
}
