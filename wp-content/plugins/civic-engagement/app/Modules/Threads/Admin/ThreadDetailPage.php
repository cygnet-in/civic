<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\StatusLabelHelper;
use CivicPlatform\Modules\Media\Admin\MediaAdminPanel;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Services\MediaService;

/**
 * Renders a single consultation detail page.
 *
 * This page handles request sanitization and presentation only. Data access is
 * delegated to ThreadRepository.
 */
class ThreadDetailPage
{
    /**
     * Required capability for viewing thread details.
     */
    private const CAPABILITY = 'manage_civic_threads';

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
    private MediaAdminPanel $mediaPanel;

    /**
     * @param ThreadRepository $threads Thread repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(ThreadRepository $threads, DateHelper $dates, MediaService $media)
    {
        $this->threads = $threads;
        $this->dates = $dates;
        $this->mediaPanel = new MediaAdminPanel($media);
    }

    /**
     * Render the thread detail page.
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
        echo '<h1>' . esc_html__('Thread Detail', 'civic-engagement') . '</h1>';
        echo '<p><a href="' . esc_url($this->listUrl()) . '">' . esc_html__('Back to Threads', 'civic-engagement') . '</a></p>';

        if (!is_array($thread)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        $this->renderDetails($thread);
        $this->mediaPanel->renderReadOnly('consultation', (int) ($thread['id'] ?? 0));

        echo '</div>';
    }

    /**
     * Render thread details.
     *
     * @param array<string, mixed> $thread Thread row.
     * @return void
     */
    private function renderDetails(array $thread): void
    {
        echo '<table class="widefat striped"><tbody>';
        $this->renderDetailRow(__('ID', 'civic-engagement'), (string) ($thread['id'] ?? ''));
        $this->renderDetailRow(__('Title', 'civic-engagement'), (string) ($thread['title'] ?? ''));
        $this->renderDetailRow(__('Slug', 'civic-engagement'), (string) ($thread['slug'] ?? ''));
        $this->renderDetailRow(__('Summary', 'civic-engagement'), (string) ($thread['summary'] ?? ''));
        $this->renderDetailRow(__('Description', 'civic-engagement'), (string) ($thread['description'] ?? ''));
        $this->renderDetailRow(__('Status', 'civic-engagement'), StatusLabelHelper::format($thread['status'] ?? ''));
        $this->renderDetailRow(__('Starting Response Count', 'civic-engagement'), (string) ($thread['starting_response_count'] ?? 0));
        $this->renderDetailRow(__('Response Enabled', 'civic-engagement'), $this->yesNo($thread['response_enabled'] ?? 0));
        $this->renderDetailRow(__('Public', 'civic-engagement'), $this->yesNo($thread['is_public'] ?? 0));
        $this->renderDetailRow(__('Created By', 'civic-engagement'), $this->userDisplayName($thread['created_by'] ?? 0));
        $this->renderDetailRow(__('Created At', 'civic-engagement'), $this->dates->formatDateTime($thread['created_at'] ?? null));
        $this->renderDetailRow(__('Updated At', 'civic-engagement'), $this->dates->formatDateTime($thread['updated_at'] ?? null));
        $this->renderDetailRow(__('Start Date', 'civic-engagement'), $this->dates->formatDate($thread['start_date'] ?? null));
        $this->renderDetailRow(__('End Date', 'civic-engagement'), $this->dates->formatDate($thread['end_date'] ?? null));
        echo '</tbody></table>';
    }

    /**
     * Render a table detail row.
     *
     * @param string $label Row label.
     * @param string $value Row value.
     * @return void
     */
    private function renderDetailRow(string $label, string $value): void
    {
        echo '<tr>';
        echo '<th scope="row">' . esc_html($label) . '</th>';
        echo '<td>' . nl2br(esc_html($value)) . '</td>';
        echo '</tr>';
    }

    /**
     * Render an admin error when the thread cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Thread not found.', 'civic-engagement') . '</p></div>';
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

    /**
     * Build the list page URL.
     *
     * @return string List URL.
     */
    private function listUrl(): string
    {
        return add_query_arg(
            ['page' => 'civic-threads'],
            admin_url('admin.php')
        );
    }
}
