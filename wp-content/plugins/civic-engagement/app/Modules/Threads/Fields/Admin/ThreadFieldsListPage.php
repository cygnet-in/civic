<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Fields\Admin;

use CivicPlatform\Modules\Threads\Repository\ThreadFieldRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

/**
 * Renders the admin listing for consultation response fields.
 *
 * Fields belong to a single consultation. This page handles request
 * sanitization and presentation only; field persistence stays in
 * ThreadFieldRepository.
 */
class ThreadFieldsListPage
{
    /**
     * Required capability for thread field administration.
     */
    private const CAPABILITY = 'manage_civic_threads';

    /**
     * Admin page slug.
     */
    private const PAGE_SLUG = 'civic-thread-fields';

    /**
     * Thread field repository.
     *
     * @var ThreadFieldRepository
     */
    private ThreadFieldRepository $fields;

    /**
     * Thread repository.
     *
     * @var ThreadRepository
     */
    private ThreadRepository $threads;

    /**
     * @param ThreadFieldRepository $fields Thread field repository.
     * @param ThreadRepository $threads Thread repository.
     */
    public function __construct(ThreadFieldRepository $fields, ThreadRepository $threads)
    {
        $this->fields = $fields;
        $this->threads = $threads;
    }

    /**
     * Render the field listing page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $threadId = $this->threadId();
        $thread = $this->threads->findById($threadId);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->pageTitle($thread)) . '</h1>';
        echo '<p><a href="' . esc_url($this->threadsUrl()) . '">' . esc_html__('Back to Threads', 'civic-engagement') . '</a></p>';

        if (!is_array($thread)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        echo '<p><a class="button button-primary" href="' . esc_url($this->addUrl($threadId)) . '">' . esc_html__('Add Field', 'civic-engagement') . '</a></p>';
        $this->renderTable($this->fields->findByThreadId($threadId), $threadId);

        echo '</div>';
    }

    /**
     * Render the fields table.
     *
     * @param array<int, array<string, mixed>> $items Field rows.
     * @param int $threadId Thread ID.
     * @return void
     */
    private function renderTable(array $items, int $threadId): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Label', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Key', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Field Type', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Required', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Sort Order', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="7">' . esc_html__('No fields found for this consultation.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item, $threadId);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single field row.
     *
     * @param array<string, mixed> $item Field row.
     * @param int $threadId Thread ID.
     * @return void
     */
    private function renderRow(array $item, int $threadId): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html((string) ($item['field_label'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['field_key'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['field_type'] ?? '')) . '</td>';
        echo '<td>' . esc_html($this->yesNo($item['is_required'] ?? 0)) . '</td>';
        echo '<td>' . esc_html((string) ($item['sort_order'] ?? '')) . '</td>';
        echo '<td><a href="' . esc_url($this->editUrl($threadId, $id)) . '">' . esc_html__('Edit', 'civic-engagement') . '</a></td>';
        echo '</tr>';
    }

    /**
     * Build the page title.
     *
     * @param array<string, mixed>|null $thread Thread row.
     * @return string Page title.
     */
    private function pageTitle(?array $thread): string
    {
        if (!is_array($thread)) {
            return __('Consultation Fields', 'civic-engagement');
        }

        $title = trim((string) ($thread['title'] ?? ''));

        if ('' === $title) {
            return __('Consultation Fields', 'civic-engagement');
        }

        return sprintf(__('Fields for Consultation: %s', 'civic-engagement'), $title);
    }

    /**
     * Render an admin error when the consultation cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Consultation not found.', 'civic-engagement') . '</p></div>';
    }

    /**
     * Convert truthy values to a display label.
     *
     * @param mixed $value Raw value.
     * @return string Display label.
     */
    private function yesNo($value): string
    {
        return !empty($value)
            ? __('Yes', 'civic-engagement')
            : __('No', 'civic-engagement');
    }

    /**
     * Build the Threads list URL.
     *
     * @return string Threads URL.
     */
    private function threadsUrl(): string
    {
        return add_query_arg(
            ['page' => 'civic-threads'],
            admin_url('admin.php')
        );
    }

    /**
     * Build an Add Field placeholder URL.
     *
     * @param int $threadId Thread ID.
     * @return string Add URL.
     */
    private function addUrl(int $threadId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-thread-field-edit',
                'thread_id' => $threadId,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build an Edit Field placeholder URL.
     *
     * @param int $threadId Thread ID.
     * @param int $fieldId Field ID.
     * @return string Edit URL.
     */
    private function editUrl(int $threadId, int $fieldId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-thread-field-edit',
                'thread_id' => $threadId,
                'field_id' => $fieldId,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Get sanitized requested thread ID.
     *
     * @return int Thread ID.
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

        return absint($threadId);
    }
}
