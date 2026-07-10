<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Responses\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;
use CivicPlatform\Services\Export\ExportManager;

/**
 * Renders the admin consultation response listing.
 *
 * This page handles request sanitization and presentation only. Data access is
 * delegated to ThreadResponseRepository.
 */
class ThreadResponsesListPage
{
    /**
     * Required capability for viewing consultation responses.
     */
    private const CAPABILITY = 'manage_civic_threads';

    /**
     * Admin page slug.
     */
    private const PAGE_SLUG = 'civic-thread-responses';

    /**
     * Thread response repository.
     *
     * @var ThreadResponseRepository
     */
    private ThreadResponseRepository $responses;

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
    private ExportManager $exports;

    /**
     * @param ThreadResponseRepository $responses Thread response repository.
     * @param ThreadRepository $threads Thread repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(ThreadResponseRepository $responses, ThreadRepository $threads, DateHelper $dates, ?ExportManager $exports = null)
    {
        $this->responses = $responses;
        $this->threads = $threads;
        $this->dates = $dates;
        $this->exports = $exports ?? new ExportManager();
    }

    /**
     * Render the paginated consultation response listing.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $threadId = $this->threadId();
        $thread = $threadId > 0 ? $this->threads->findById($threadId) : null;
        $search = $this->searchKeyword();
        $page = $this->currentPage();
        $result = $this->getResult($search, $page, $threadId);
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->pageTitle($threadId, $thread)) . '</h1>';
        $this->renderSearchForm($search, $threadId);
        $threadIds = array_map(static fn(array $item): int => (int) ($item['thread_id'] ?? 0), $items);
        $this->renderTable($items, $this->threads->getTitlesByIds($threadIds));
        $this->renderPagination($page, $totalPages, $search, $threadId);
        echo '</div>';
    }

    /**
     * Get paginated responses, optionally searched by keyword.
     *
     * @param string $search Search keyword.
     * @param int $page Current page.
     * @param int $threadId Optional thread ID filter.
     * @return array<string, mixed> Paginated repository result.
     */
    private function getResult(string $search, int $page, int $threadId): array
    {
        $args = [
            'page' => $page,
            'per_page' => 20,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        if ($threadId > 0) {
            $args['thread_id'] = $threadId;
        }

        if ('' !== $search) {
            return $this->responses->search($search, $args);
        }

        return $this->responses->getPaginated($args);
    }

    /**
     * Render the search form.
     *
     * @param string $search Search keyword.
     * @param int $threadId Optional thread ID filter.
     * @return void
     */
    private function renderSearchForm(string $search, int $threadId): void
    {
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::PAGE_SLUG) . '">';
        if ($threadId > 0) {
            echo '<input type="hidden" name="thread_id" value="' . esc_attr((string) $threadId) . '">';
        }
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="civic-thread-responses-search-input">' . esc_html__('Search Responses', 'civic-engagement') . '</label>';
        echo '<input type="search" id="civic-thread-responses-search-input" name="s" value="' . esc_attr($search) . '">';
        submit_button(__('Search Responses', 'civic-engagement'), '', '', false);
        echo '</p>';
        echo '</form>';
        echo '<p><a class="button" href="' . esc_url($this->exportUrl($search, $threadId)) . '">' . esc_html__('Export (.xlsx)', 'civic-engagement') . '</a></p>';
    }

    public function export(): void
    {
        $threadId = $this->threadId();
        $search = $this->searchKeyword();
        $args = [
            'search' => $search,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        if ($threadId > 0) {
            $args['thread_id'] = $threadId;
        }

        $items = $this->responses->getForExport($args);
        $threadIds = array_map(static fn(array $item): int => (int) ($item['thread_id'] ?? 0), $items);

        $this->exports->download($items, $this->exportColumns($this->threads->getTitlesByIds($threadIds)), $this->exportFilename('consultation-responses'));
    }

    /**
     * Render the responses table.
     *
     * @param array<int, array<string, mixed>> $items Response rows.
     * @return void
     */
    private function renderTable(array $items, array $threadTitles): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Consultation', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Name', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Email', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Electoral Area', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Public', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Created At', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="8">' . esc_html__('No consultation responses found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item, $threadTitles);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single response row.
     *
     * @param array<string, mixed> $item Response row.
     * @return void
     */
    private function renderRow(array $item, array $threadTitles): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html($threadTitles[(int) ($item['thread_id'] ?? 0)] ?? '') . '</td>';
        echo '<td>' . esc_html((string) ($item['name_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['email_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['electoral_area_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html(!empty($item['is_public']) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement')) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['created_at'] ?? null)) . '</td>';
        echo '<td><a href="' . esc_url($this->viewUrl($id, (int) ($item['thread_id'] ?? 0))) . '">' . esc_html__('View', 'civic-engagement') . '</a></td>';
        echo '</tr>';
    }

    /**
     * Render pagination links.
     *
     * @param int $page Current page.
     * @param int $totalPages Total pages.
     * @param string $search Search keyword.
     * @param int $threadId Optional thread ID filter.
     * @return void
     */
    private function renderPagination(int $page, int $totalPages, string $search, int $threadId): void
    {
        if ($totalPages <= 1) {
            return;
        }

        echo '<div class="tablenav bottom">';
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . esc_html(sprintf(_n('%s page', '%s pages', $totalPages, 'civic-engagement'), number_format_i18n($totalPages))) . '</span>';

        if ($page > 1) {
            echo '<a class="button" href="' . esc_url($this->pageUrl($page - 1, $search, $threadId)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a> ';
        }

        echo '<span class="paging-input">' . esc_html((string) $page) . ' / ' . esc_html((string) $totalPages) . '</span>';

        if ($page < $totalPages) {
            echo ' <a class="button" href="' . esc_url($this->pageUrl($page + 1, $search, $threadId)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Build a pagination URL.
     *
     * @param int $page Page number.
     * @param string $search Search keyword.
     * @param int $threadId Optional thread ID filter.
     * @return string Page URL.
     */
    private function pageUrl(int $page, string $search, int $threadId): string
    {
        $args = [
            'page' => self::PAGE_SLUG,
            'paged' => $page,
        ];

        if ($threadId > 0) {
            $args['thread_id'] = $threadId;
        }

        if ('' !== $search) {
            $args['s'] = $search;
        }

        return add_query_arg($args, admin_url('admin.php'));
    }

    private function exportUrl(string $search, int $threadId): string
    {
        $args = [
            'page' => self::PAGE_SLUG,
            'civic_export' => 'consultation-responses',
        ];

        if ($threadId > 0) {
            $args['thread_id'] = $threadId;
        }

        if ('' !== $search) {
            $args['s'] = $search;
        }

        return wp_nonce_url(add_query_arg($args, admin_url('admin.php')), 'civic_thread_responses_export');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportColumns(array $threadTitles): array
    {
        return [
            ['key' => 'id', 'label' => __('ID', 'civic-engagement')],
            ['key' => 'thread_id', 'label' => __('Consultation', 'civic-engagement'), 'callback' => static fn(array $item): string => $threadTitles[(int) ($item['thread_id'] ?? 0)] ?? ''],
            ['key' => 'name_snapshot', 'label' => __('Name', 'civic-engagement')],
            ['key' => 'email_snapshot', 'label' => __('Email', 'civic-engagement')],
            ['key' => 'electoral_area_snapshot', 'label' => __('Electoral Area', 'civic-engagement')],
            ['key' => 'is_public', 'label' => __('Public', 'civic-engagement'), 'callback' => static fn(array $item): string => !empty($item['is_public']) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement')],
            ['key' => 'created_at', 'label' => __('Created At', 'civic-engagement'), 'callback' => fn(array $item): string => $this->dates->formatDateTime($item['created_at'] ?? null)],
        ];
    }

    private function exportFilename(string $prefix): string
    {
        return $prefix . '-' . current_time('Y-m-d-Hi');
    }

    /**
     * Build the page title for global or contextual response listings.
     *
     * @param int $threadId Optional thread ID filter.
     * @param array<string, mixed>|null $thread Thread row.
     * @return string Page title.
     */
    private function pageTitle(int $threadId, ?array $thread): string
    {
        if ($threadId <= 0) {
            return __('Consultation Responses', 'civic-engagement');
        }

        $title = isset($thread['title']) ? trim((string) $thread['title']) : '';

        if ('' === $title) {
            $title = sprintf(__('ID %d', 'civic-engagement'), $threadId);
        }

        return sprintf(
            __('Responses from Consultation: %s', 'civic-engagement'),
            $title
        );
    }

    /**
     * Build a placeholder detail URL for a future response detail page.
     *
     * @param int $id Response ID.
     * @param int $threadId Thread ID.
     * @return string View URL.
     */
    private function viewUrl(int $id, int $threadId): string
    {
        $args = [
            'page' => 'civic-thread-response-view',
            'response_id' => $id,
        ];

        if ($threadId > 0) {
            $args['thread_id'] = $threadId;
        }

        return add_query_arg(
            $args,
            admin_url('admin.php')
        );
    }

    /**
     * Get sanitized search keyword.
     *
     * @return string Search keyword.
     */
    private function searchKeyword(): string
    {
        if (!isset($_GET['s'])) {
            return '';
        }

        $search = wp_unslash($_GET['s']);

        if (is_array($search) || is_object($search)) {
            return '';
        }

        return sanitize_text_field((string) $search);
    }

    /**
     * Get sanitized contextual thread ID.
     *
     * @return int Thread ID, or 0 for the global response listing.
     */
    private function threadId(): int
    {
        if (!isset($_GET['thread_id'])) {
            return 0;
        }

        $threadId = wp_unslash($_GET['thread_id']);

        if (is_array($threadId) || is_object($threadId)) {
            return 0;
        }

        return max(0, absint($threadId));
    }

    /**
     * Get sanitized current page number.
     *
     * @return int Current page.
     */
    private function currentPage(): int
    {
        if (!isset($_GET['paged'])) {
            return 1;
        }

        $page = wp_unslash($_GET['paged']);

        if (is_array($page) || is_object($page)) {
            return 1;
        }

        return max(1, absint($page));
    }
}
