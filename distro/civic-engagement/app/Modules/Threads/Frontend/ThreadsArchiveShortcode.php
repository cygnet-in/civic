<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Frontend;

use CivicPlatform\Core\CanonicalSlugRouter;
use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Media\Frontend\MediaRenderer;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Services\MediaService;

/**
 * Registers and renders the public archived consultations shortcode.
 */
class ThreadsArchiveShortcode
{
    private ThreadRepository $threads;

    private DateHelper $dates;

    private MediaService $media;

    public function __construct(ThreadRepository $threads, DateHelper $dates, MediaService $media)
    {
        $this->threads = $threads;
        $this->dates = $dates;
        $this->media = $media;
    }

    public function register(): void
    {
        add_shortcode('civic_threads_archive', [$this, 'render']);
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
            'civic_threads_archive'
        );

        $limit = absint($atts['limit']);
        $isCompact = $limit > 0;
        $page = $isCompact ? 1 : $this->currentPage();
        $perPage = $isCompact ? $limit : 20;
        $detailPageId = absint($atts['detail_page_id']);
        $result = $this->threads->getPublicArchivedThreads(
            [
                'page' => $page,
                'per_page' => $perPage,
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        ob_start();

        echo '<div class="civic-threads-archive' . ($isCompact ? ' civic-archive-list-wrap' : ' civic-cards-main-list') . '">';

        if (empty($items)) {
            echo '<p class="civic-threads__empty">' . esc_html__('No archived consultations are currently available.', 'civic-engagement') . '</p>';
        } elseif ($isCompact) {
            $this->renderCompactList($items, $detailPageId);
        } else {
            foreach ($items as $thread) {
                $this->renderThread($thread, $detailPageId);
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
        echo '<ul class="civic-archive-list civic-threads-archive__list">';

        foreach ($items as $item) {
            $threadId = isset($item['id']) ? (int) $item['id'] : 0;
            echo '<li class="civic-archive-list__item civic-threads-archive__item">';
            echo '<a href="' . esc_url($this->readMoreUrl((string) ($item['slug'] ?? ''), $threadId, $detailPageId)) . '">' . esc_html((string) ($item['title'] ?? '')) . '</a>';
            echo '</li>';
        }

        echo '</ul>';
    }

    private function renderThread(array $thread, int $detailPageId): void
    {
        $threadId = isset($thread['id']) ? (int) $thread['id'] : 0;

        echo '<article class="civic-card civic-list-card civic-threads__item">';
        echo MediaRenderer::listThumbnail($this->media->getPrimary('consultation', $threadId));
        echo '<div class="civic-card__content">';
        echo '<h2 class="civic-card__title civic-threads__title">' . esc_html((string) ($thread['title'] ?? '')) . '</h2>';

        if (!empty($thread['summary'])) {
            echo '<p class="civic-card__summary civic-threads__summary">' . esc_html((string) $thread['summary']) . '</p>';
        }

        echo '<div class="civic-card__footer">';
        echo '<span class="civic-card__date civic-card__left civic-threads__date">' . esc_html($this->dates->formatDate((string) ($thread['created_at'] ?? ''))) . '</span>';
        echo '<span class="civic-card__actions civic-card__right civic-threads__actions">';
        echo '<a href="' . esc_url($this->readMoreUrl((string) ($thread['slug'] ?? ''), $threadId, $detailPageId)) . '">' . esc_html__('More', 'civic-engagement') . '</a>';
        echo '</span>';
        echo '</div>';
        echo '</div>';
        echo '</article>';
    }

    private function readMoreUrl(string $slug, int $threadId, int $detailPageId): string
    {
        if ('' !== $slug) {
            return CanonicalSlugRouter::url('consultation', $slug);
        }

        return add_query_arg(
            ['thread_id' => $threadId],
            $this->detailBaseUrl($detailPageId)
        );
    }

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

    private function renderPagination(int $page, int $totalPages): void
    {
        if ($totalPages <= 1) {
            return;
        }

        echo '<nav class="civic-threads__pagination" aria-label="' . esc_attr__('Archived consultation pages', 'civic-engagement') . '">';

        if ($page > 1) {
            echo '<a class="civic-threads__pagination-previous" href="' . esc_url($this->pageUrl($page - 1)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a>';
        }

        echo '<span class="civic-threads__pagination-current">' . esc_html(sprintf(__('Page %1$d of %2$d', 'civic-engagement'), $page, $totalPages)) . '</span>';

        if ($page < $totalPages) {
            echo '<a class="civic-threads__pagination-next" href="' . esc_url($this->pageUrl($page + 1)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</nav>';
    }

    private function pageUrl(int $page): string
    {
        return add_query_arg(['thread_archive_page' => max(1, $page)], get_permalink());
    }

    private function currentPage(): int
    {
        if (!isset($_GET['thread_archive_page'])) {
            return 1;
        }

        $page = wp_unslash($_GET['thread_archive_page']);

        if (is_array($page) || is_object($page)) {
            return 1;
        }

        return max(1, absint($page));
    }
}
