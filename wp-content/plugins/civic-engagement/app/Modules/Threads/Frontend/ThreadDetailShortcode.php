<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

/**
 * Registers and renders the public consultation detail shortcode.
 *
 * Rendering remains frontend-focused and read-only. Response submission and
 * response listing are intentionally left for future workflow classes.
 */
class ThreadDetailShortcode
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
     * Register the public thread detail shortcode.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('civic_thread_detail', [$this, 'render']);
    }

    /**
     * Render a published public consultation detail.
     *
     * @param mixed $atts Shortcode attributes.
     * @return string Rendered shortcode output.
     */
    public function render($atts = []): string
    {
        unset($atts);

        $thread = $this->threads->findPublicById($this->threadId());

        ob_start();

        echo '<div class="civic-thread-detail">';

        if (!is_array($thread)) {
            echo '<p class="civic-thread-detail__empty">' . esc_html__('Consultation not found.', 'civic-engagement') . '</p>';
            echo '</div>';

            return (string) ob_get_clean();
        }

        $this->renderThread($thread);
        $this->renderResponsesPlaceholder($thread);

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Render consultation content.
     *
     * @param array<string, mixed> $thread Thread row.
     * @return void
     */
    private function renderThread(array $thread): void
    {
        echo '<article class="civic-thread-detail__content">';
        echo '<h1 class="civic-thread-detail__title">' . esc_html((string) ($thread['title'] ?? '')) . '</h1>';

        if (!empty($thread['summary'])) {
            echo '<p class="civic-thread-detail__summary">' . esc_html((string) $thread['summary']) . '</p>';
        }

        if (!empty($thread['description'])) {
            echo '<div class="civic-thread-detail__description">' . wpautop(esc_html((string) $thread['description'])) . '</div>';
        }

        echo '<dl class="civic-thread-detail__meta">';
        $this->renderMetaItem(__('Created', 'civic-engagement'), $this->dates->formatDate((string) ($thread['created_at'] ?? '')));
        $this->renderMetaItem(__('Start Date', 'civic-engagement'), $this->dates->formatDate($thread['start_date'] ?? null));
        $this->renderMetaItem(__('End Date', 'civic-engagement'), $this->dates->formatDate($thread['end_date'] ?? null));
        echo '</dl>';
        echo '</article>';
    }

    /**
     * Render a future responses placeholder.
     *
     * @param array<string, mixed> $thread Thread row.
     * @return void
     */
    private function renderResponsesPlaceholder(array $thread): void
    {
        echo '<section class="civic-thread-detail__responses">';
        echo '<h2>' . esc_html__('Responses', 'civic-engagement') . '</h2>';

        if (!empty($thread['response_enabled'])) {
            echo '<p>' . esc_html__('Response submission will be available here.', 'civic-engagement') . '</p>';
        } else {
            echo '<p>' . esc_html__('Responses are currently closed for this consultation.', 'civic-engagement') . '</p>';
        }

        echo '</section>';
    }

    /**
     * Render a metadata item.
     *
     * @param string $label Metadata label.
     * @param string $value Metadata value.
     * @return void
     */
    private function renderMetaItem(string $label, string $value): void
    {
        echo '<dt>' . esc_html($label) . '</dt>';
        echo '<dd>' . esc_html($value) . '</dd>';
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
