<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

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
        echo '<h1>' . esc_html__('Threads / Consultations', 'civic-engagement') . '</h1>';
        $this->renderSearchForm($search);
        $this->renderTable($items);
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
    }

    /**
     * Render the threads table.
     *
     * @param array<int, array<string, mixed>> $items Thread rows.
     * @return void
     */
    private function renderTable(array $items): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Title', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Slug', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Status', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Responses', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Created By', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Created', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="8">' . esc_html__('No threads found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item);
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
    private function renderRow(array $item): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html((string) ($item['title'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['slug'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['status'] ?? '')) . '</td>';
        echo '<td>' . esc_html(!empty($item['response_enabled']) ? __('Enabled', 'civic-engagement') : __('Disabled', 'civic-engagement')) . '</td>';
        echo '<td>' . esc_html($this->userDisplayName($item['created_by'] ?? 0)) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['created_at'] ?? null)) . '</td>';
        echo '<td><a href="' . esc_url($this->viewUrl($id)) . '">' . esc_html__('View', 'civic-engagement') . '</a> | <a href="' . esc_url($this->editUrl($id)) . '">' . esc_html__('Edit', 'civic-engagement') . '</a> | <a href="' . esc_url($this->fieldsUrl($id)) . '">' . esc_html__('Fields', 'civic-engagement') . '</a> | <a href="' . esc_url($this->responsesUrl($id)) . '">' . esc_html__('Responses', 'civic-engagement') . '</a></td>';
        echo '</tr>';
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
