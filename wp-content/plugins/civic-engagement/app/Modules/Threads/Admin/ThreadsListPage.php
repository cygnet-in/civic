<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\StatusLabelHelper;
use CivicPlatform\Modules\Threads\Repository\ThreadFieldRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Services\Export\ExportManager;
use CivicPlatform\Services\MediaService;

/**
 * Renders the admin consultation listing.
 *
 * This page handles request sanitization and presentation only. Data access is
 * delegated to ThreadRepository.
 */
class ThreadsListPage
{
    /**
     * Required capability for viewing threads.
     */
    private const CAPABILITY = 'manage_civic_threads';

    /**
     * Admin list page slug.
     */
    private const PAGE_SLUG = 'civic-threads';

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
    private ThreadResponseRepository $responses;
    private ThreadFieldRepository $fields;
    private MediaService $media;
    private ExportManager $exports;

    /**
     * @param ThreadRepository $threads Thread repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(ThreadRepository $threads, DateHelper $dates, ThreadResponseRepository $responses, ThreadFieldRepository $fields, MediaService $media, ?ExportManager $exports = null)
    {
        $this->threads = $threads;
        $this->dates = $dates;
        $this->responses = $responses;
        $this->fields = $fields;
        $this->media = $media;
        $this->exports = $exports ?? new ExportManager();
    }

    /**
     * Render the paginated thread listing.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $search = $this->searchKeyword();
        $page = $this->currentPage();
        $result = $this->getResult($search, $page);
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Consultations', 'civic-engagement') . '</h1>';
        echo ' <a href="' . esc_url($this->addUrl()) . '" class="page-title-action">' . esc_html__('Add Consultation', 'civic-engagement') . '</a>';
        $this->renderSearchForm($search);
        $ids = array_map(static fn(array $item): int => (int) ($item['id'] ?? 0), $items);
        $this->renderTable($items, $this->responses->getCountsByThreadIds($ids), $this->fields->getCountsByThreadIds($ids), $this->media->getCountsByEntityIds('consultation', $ids));
        $this->renderPagination($page, $totalPages, $search);
        echo '</div>';
    }

    /**
     * Get paginated threads, optionally searched by keyword.
     *
     * @param string $search Search keyword.
     * @param int $page Current page.
     * @return array<string, mixed> Paginated repository result.
     */
    private function getResult(string $search, int $page): array
    {
        $args = [
            'page' => $page,
            'per_page' => 20,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        if ('' !== $search) {
            return $this->threads->search($search, $args);
        }

        return $this->threads->getPaginated($args);
    }

    /**
     * Render the search form.
     *
     * @param string $search Search keyword.
     * @return void
     */
    private function renderSearchForm(string $search): void
    {
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::PAGE_SLUG) . '">';
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="civic-threads-search-input">' . esc_html__('Search Threads', 'civic-engagement') . '</label>';
        echo '<input type="search" id="civic-threads-search-input" name="s" value="' . esc_attr($search) . '">';
        submit_button(__('Search Threads', 'civic-engagement'), '', '', false);
        echo '</p>';
        echo '</form>';
        echo '<p><a class="button" href="' . esc_url($this->exportUrl($search)) . '">' . esc_html__('Export (.xlsx)', 'civic-engagement') . '</a></p>';
    }

    public function export(): void
    {
        $search = $this->searchKeyword();
        $items = $this->threads->getForExport([
            'search' => $search,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ]);
        $ids = array_map(static fn(array $item): int => (int) ($item['id'] ?? 0), $items);
        $responseCounts = $this->responses->getCountsByThreadIds($ids);
        $mediaCounts = $this->media->getCountsByEntityIds('consultation', $ids);

        $this->exports->download($items, $this->exportColumns($responseCounts, $mediaCounts), $this->exportFilename('consultations'));
    }

    /**
     * Render the threads table.
     *
     * @param array<int, array<string, mixed>> $items Thread rows.
     * @return void
     */
    private function renderTable(array $items, array $responseCounts, array $fieldCounts, array $mediaCounts): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Title', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Slug', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Status', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Responses', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Images', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Created By', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Created', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="9">' . esc_html__('No threads found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item, $responseCounts, $fieldCounts, $mediaCounts);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single thread row.
     *
     * @param array<string, mixed> $item Thread row.
     * @return void
     */
    private function renderRow(array $item, array $responseCounts, array $fieldCounts, array $mediaCounts): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;
        $fieldCount = (int) ($fieldCounts[$id] ?? 0);

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html((string) ($item['title'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['slug'] ?? '')) . '</td>';
        echo '<td>' . esc_html($this->statusLabel($item, $fieldCount)) . '</td>';
        echo '<td>' . esc_html(!empty($item['response_enabled']) ? __('Enabled', 'civic-engagement') : __('Disabled', 'civic-engagement')) . '</td>';
        echo '<td>' . esc_html((string) ($mediaCounts[$id] ?? 0)) . '</td>';
        echo '<td>' . esc_html($this->userDisplayName($item['created_by'] ?? 0)) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['created_at'] ?? null)) . '</td>';
        echo '<td><a href="' . esc_url($this->viewUrl($id)) . '">' . esc_html__('View', 'civic-engagement') . '</a> | <a href="' . esc_url($this->editUrl($id)) . '">' . esc_html__('Edit', 'civic-engagement') . '</a> | ' . $this->fieldsLink($id, $fieldCount) . ' | <a href="' . esc_url($this->responsesUrl($id)) . '">' . esc_html(sprintf(__('View Responses (%d)', 'civic-engagement'), (int) ($responseCounts[$id] ?? 0))) . '</a></td>';
        echo '</tr>';
    }

    private function statusLabel(array $item, int $fieldCount): string
    {
        if ('draft' === (string) ($item['status'] ?? '') && 0 === $fieldCount) {
            return __('Draft (Incomplete)', 'civic-engagement');
        }

        return StatusLabelHelper::format($item['status'] ?? '');
    }

    private function fieldsLink(int $id, int $fieldCount): string
    {
        return '<a class="' . esc_attr('count-' . $fieldCount . '-fields') . '" title="' . esc_attr__('Add response fields to configure the questions users will answer.', 'civic-engagement') . '" href="' . esc_url($this->fieldsUrl($id)) . '">' . esc_html(sprintf(__('Fields (%d)', 'civic-engagement'), $fieldCount)) . '</a>';
    }

    /**
     * Resolve a WordPress user display name.
     *
     * @param mixed $userId User ID.
     * @return string Display name, login, or numeric ID fallback.
     */
    private function userDisplayName($userId): string
    {
        $userId = (int) $userId;

        if ($userId <= 0) {
            return '';
        }

        $user = get_userdata($userId);

        if (is_object($user) && !empty($user->display_name)) {
            return (string) $user->display_name;
        }

        if (is_object($user) && !empty($user->user_login)) {
            return (string) $user->user_login;
        }

        return (string) $userId;
    }

    /**
     * Render pagination links.
     *
     * @param int $page Current page.
     * @param int $totalPages Total pages.
     * @param string $search Search keyword.
     * @return void
     */
    private function renderPagination(int $page, int $totalPages, string $search): void
    {
        if ($totalPages <= 1) {
            return;
        }

        $baseArgs = ['page' => self::PAGE_SLUG];

        if ('' !== $search) {
            $baseArgs['s'] = $search;
        }

        echo '<div class="tablenav bottom">';
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . esc_html(sprintf(_n('%s page', '%s pages', $totalPages, 'civic-engagement'), number_format_i18n($totalPages))) . '</span>';

        if ($page > 1) {
            echo '<a class="button" href="' . esc_url(add_query_arg(array_merge($baseArgs, ['paged' => $page - 1]), admin_url('admin.php'))) . '">' . esc_html__('Previous', 'civic-engagement') . '</a> ';
        }

        echo '<span class="paging-input">' . esc_html((string) $page) . ' / ' . esc_html((string) $totalPages) . '</span>';

        if ($page < $totalPages) {
            echo ' <a class="button" href="' . esc_url(add_query_arg(array_merge($baseArgs, ['paged' => $page + 1]), admin_url('admin.php'))) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Build add consultation URL.
     *
     * @return string Add URL.
     */
    private function addUrl(): string
    {
        return add_query_arg(['page' => 'civic-thread-create'], admin_url('admin.php'));
    }

    /**
     * Build a placeholder detail URL for future view page.
     *
     * @param int $id Thread ID.
     * @return string View URL.
     */
    private function viewUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-thread-view',
                'thread_id' => $id,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build a placeholder edit URL for future edit page.
     *
     * @param int $id Thread ID.
     * @return string Edit URL.
     */
    private function editUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-thread-edit',
                'thread_id' => $id,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build the contextual fields URL for a thread.
     *
     * @param int $id Thread ID.
     * @return string Fields URL.
     */
    private function fieldsUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-thread-fields',
                'thread_id' => $id,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build the contextual responses URL for a thread.
     *
     * @param int $id Thread ID.
     * @return string Responses URL.
     */
    private function responsesUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-thread-responses',
                'thread_id' => $id,
            ],
            admin_url('admin.php')
        );
    }

    private function exportUrl(string $search): string
    {
        $args = [
            'page' => self::PAGE_SLUG,
            'civic_export' => 'consultations',
        ];

        if ('' !== $search) {
            $args['s'] = $search;
        }

        return wp_nonce_url(add_query_arg($args, admin_url('admin.php')), 'civic_threads_export');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exportColumns(array $responseCounts, array $mediaCounts): array
    {
        return [
            ['key' => 'id', 'label' => __('ID', 'civic-engagement')],
            ['key' => 'title', 'label' => __('Title', 'civic-engagement')],
            ['key' => 'slug', 'label' => __('Slug', 'civic-engagement')],
            ['key' => 'status', 'label' => __('Status', 'civic-engagement'), 'callback' => static fn(array $item): string => StatusLabelHelper::format($item['status'] ?? '')],
            ['key' => 'response_enabled', 'label' => __('Responses', 'civic-engagement'), 'callback' => static fn(array $item): string => !empty($item['response_enabled']) ? __('Enabled', 'civic-engagement') : __('Disabled', 'civic-engagement')],
            ['key' => 'response_count', 'label' => __('Response Count', 'civic-engagement'), 'callback' => static fn(array $item): string => (string) ($responseCounts[(int) ($item['id'] ?? 0)] ?? 0)],
            ['key' => 'images', 'label' => __('Images', 'civic-engagement'), 'callback' => static fn(array $item): string => (string) ($mediaCounts[(int) ($item['id'] ?? 0)] ?? 0)],
            ['key' => 'created_by', 'label' => __('Created By', 'civic-engagement'), 'callback' => fn(array $item): string => $this->userDisplayName($item['created_by'] ?? 0)],
            ['key' => 'created_at', 'label' => __('Created', 'civic-engagement'), 'callback' => fn(array $item): string => $this->dates->formatDateTime($item['created_at'] ?? null)],
        ];
    }

    private function exportFilename(string $prefix): string
    {
        return $prefix . '-' . current_time('Y-m-d-Hi');
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
