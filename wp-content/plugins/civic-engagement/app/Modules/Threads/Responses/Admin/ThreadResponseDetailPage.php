<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Responses\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Threads\Repository\ThreadFieldRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;

/**
 * Renders a single consultation response detail and moderation page.
 *
 * This page handles request sanitization, nonce validation, and presentation.
 * Response content remains immutable; moderation is limited to public
 * visibility.
 */
class ThreadResponseDetailPage
{
    /**
     * Required capability for response moderation.
     */
    private const CAPABILITY = 'manage_civic_threads';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'civic_thread_response_moderation';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_thread_response_moderation_nonce';

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
     * Thread field repository.
     *
     * @var ThreadFieldRepository
     */
    private ThreadFieldRepository $fields;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    /**
     * @param ThreadResponseRepository $responses Thread response repository.
     * @param ThreadRepository $threads Thread repository.
     * @param ThreadFieldRepository $fields Thread field repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(
        ThreadResponseRepository $responses,
        ThreadRepository $threads,
        ThreadFieldRepository $fields,
        DateHelper $dates
    ) {
        $this->responses = $responses;
        $this->threads = $threads;
        $this->fields = $fields;
        $this->dates = $dates;
    }

    /**
     * Render the response detail page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $responseId = $this->responseId();
        $notice = $this->processModeration($responseId);
        $response = $this->responses->findById($responseId);
        $thread = is_array($response) ? $this->threads->findById((int) ($response['thread_id'] ?? 0)) : null;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Consultation Response Detail', 'civic-engagement') . '</h1>';
        echo '<p><a href="' . esc_url($this->listUrl($response, $thread)) . '">' . esc_html__('Back to Responses', 'civic-engagement') . '</a></p>';

        $this->renderNotice($notice);

        if (!is_array($response)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        $this->renderDetails($response, $thread);
        $this->renderModerationActions($response);

        echo '</div>';
    }

    /**
     * Process public visibility moderation.
     *
     * @param int $responseId Response ID.
     * @return array{type: string, message: string}|null Notice data.
     */
    private function processModeration(int $responseId): ?array
    {
        if (!$this->isModerationSubmission()) {
            return null;
        }

        if (!$this->hasValidNonce()) {
            return [
                'type' => 'error',
                'message' => __('Security check failed. Please try again.', 'civic-engagement'),
            ];
        }

        $visibility = $this->requestedVisibility();

        if ($responseId <= 0 || null === $visibility) {
            return [
                'type' => 'error',
                'message' => __('Invalid moderation request.', 'civic-engagement'),
            ];
        }

        if (!$this->responses->updatePublicVisibility($responseId, $visibility)) {
            return [
                'type' => 'error',
                'message' => __('Response visibility could not be updated.', 'civic-engagement'),
            ];
        }

        return [
            'type' => 'success',
            'message' => __('Response visibility updated.', 'civic-engagement'),
        ];
    }

    /**
     * Render response details.
     *
     * @param array<string, mixed> $response Response row.
     * @param array<string, mixed>|null $thread Thread row.
     * @return void
     */
    private function renderDetails(array $response, ?array $thread): void
    {
        echo '<table class="widefat striped"><tbody>';
        $this->renderDetailRow(__('Response ID', 'civic-engagement'), (string) ($response['id'] ?? ''));
        $this->renderDetailRow(__('Consultation Title', 'civic-engagement'), $this->threadTitle($response, $thread));
        $this->renderDetailRow(__('Name Snapshot', 'civic-engagement'), (string) ($response['name_snapshot'] ?? ''));
        $this->renderDetailRow(__('Email Snapshot', 'civic-engagement'), (string) ($response['email_snapshot'] ?? ''));
        $this->renderDetailRow(__('Phone Snapshot', 'civic-engagement'), (string) ($response['phone_snapshot'] ?? ''));
        $this->renderDetailRow(__('Address Snapshot', 'civic-engagement'), (string) ($response['address_snapshot'] ?? ''));
        $this->renderDetailRow(__('Eircode Snapshot', 'civic-engagement'), (string) ($response['eircode_snapshot'] ?? ''));
        $this->renderDetailRow(__('Electoral Area Snapshot', 'civic-engagement'), (string) ($response['electoral_area_snapshot'] ?? ''));
        $responseText = $this->responseText($response['response_data'] ?? '');
        if ('' !== trim($responseText)) {
            $this->renderDetailRow(__('Response Text', 'civic-engagement'), $responseText);
        }
        $this->renderCustomFieldRows($response);
        $this->renderDetailRow(__('Public', 'civic-engagement'), $this->yesNo($response['is_public'] ?? 0));
        $this->renderDetailRow(__('Created At', 'civic-engagement'), $this->dates->formatDateTime($response['created_at'] ?? null));
        echo '</tbody></table>';
    }

    /**
     * Render submitted custom field values.
     *
     * @param array<string, mixed> $response Response row.
     * @return void
     */
    private function renderCustomFieldRows(array $response): void
    {
        $customFields = $this->customFieldValues($response['response_data'] ?? '');

        if (empty($customFields)) {
            return;
        }

        $labels = $this->fieldLabels((int) ($response['thread_id'] ?? 0));

        foreach ($customFields as $fieldKey => $value) {
            if (empty($labels[$fieldKey])) {
                continue;
            }

            $label = $labels[$fieldKey];
            $this->renderDetailRow($label, $value);
        }
    }

    /**
     * Render moderation actions.
     *
     * @param array<string, mixed> $response Response row.
     * @return void
     */
    private function renderModerationActions(array $response): void
    {
        $responseId = isset($response['id']) ? (int) $response['id'] : 0;

        echo '<h2>' . esc_html__('Moderation', 'civic-engagement') . '</h2>';
        echo '<p>' . esc_html__('Response content is immutable. Moderation only controls public visibility.', 'civic-engagement') . '</p>';
        echo '<form method="post">';
        echo '<input type="hidden" name="response_id" value="' . esc_attr((string) $responseId) . '">';
        echo '<input type="hidden" name="civic_thread_response_moderation" value="1">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        echo '<button type="submit" class="button button-primary" name="visibility" value="show">' . esc_html__('Show Publicly', 'civic-engagement') . '</button>';
        echo ' ';
        echo '<button type="submit" class="button" name="visibility" value="hide">' . esc_html__('Hide Publicly', 'civic-engagement') . '</button>';
        echo '</form>';
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
     * Render an admin notice.
     *
     * @param array{type: string, message: string}|null $notice Notice data.
     * @return void
     */
    private function renderNotice(?array $notice): void
    {
        if (null === $notice) {
            return;
        }

        $class = 'success' === $notice['type'] ? 'notice-success' : 'notice-error';

        echo '<div class="notice ' . esc_attr($class) . '"><p>' . esc_html($notice['message']) . '</p></div>';
    }

    /**
     * Render an admin error when the response cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Response not found.', 'civic-engagement') . '</p></div>';
    }

    /**
     * Build a readable consultation title.
     *
     * @param array<string, mixed> $response Response row.
     * @param array<string, mixed>|null $thread Thread row.
     * @return string Consultation title.
     */
    private function threadTitle(array $response, ?array $thread): string
    {
        $title = isset($thread['title']) ? trim((string) $thread['title']) : '';

        if ('' !== $title) {
            return $title;
        }

        $threadId = isset($response['thread_id']) ? (int) $response['thread_id'] : 0;

        return $threadId > 0 ? sprintf(__('Thread #%d', 'civic-engagement'), $threadId) : '';
    }

    /**
     * Extract response text from stored response data.
     *
     * @param mixed $value Raw response_data value.
     * @return string Response text.
     */
    private function responseText($value): string
    {
        $data = $this->responseDataArray($value);

        if (isset($data['response_text']) && !is_array($data['response_text']) && !is_object($data['response_text'])) {
            return (string) $data['response_text'];
        }

        return '';
    }

    /**
     * Extract custom field values from response_data.
     *
     * @param mixed $value Raw response_data value.
     * @return array<string, string>
     */
    private function customFieldValues($value): array
    {
        $data = $this->responseDataArray($value);
        $customFields = isset($data['custom_fields']) && is_array($data['custom_fields'])
            ? $data['custom_fields']
            : [];
        $values = [];

        foreach ($customFields as $fieldKey => $fieldValue) {
            if (is_array($fieldValue) || is_object($fieldValue)) {
                continue;
            }

            $fieldKey = sanitize_key((string) $fieldKey);

            if ('' !== $fieldKey) {
                $values[$fieldKey] = (string) $fieldValue;
            }
        }

        return $values;
    }

    /**
     * Decode response_data to an array.
     *
     * @param mixed $value Raw response_data value.
     * @return array<string, mixed>
     */
    private function responseDataArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Build field label lookup for a consultation.
     *
     * @param int $threadId Thread ID.
     * @return array<string, string>
     */
    private function fieldLabels(int $threadId): array
    {
        if ($threadId <= 0) {
            return [];
        }

        $labels = [];

        foreach ($this->fields->findByThreadId($threadId) as $field) {
            $fieldKey = sanitize_key((string) ($field['field_key'] ?? ''));
            $label = trim((string) ($field['field_label'] ?? ''));

            if ('' !== $fieldKey && '' !== $label) {
                $labels[$fieldKey] = $label;
            }
        }

        return $labels;
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
     * Check whether the current request is a moderation submission.
     *
     * @return bool True when submitted.
     */
    private function isModerationSubmission(): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : '';

        return 'POST' === $method && isset($_POST['civic_thread_response_moderation']);
    }

    /**
     * Validate the moderation nonce.
     *
     * @return bool True when valid.
     */
    private function hasValidNonce(): bool
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return false;
        }

        $nonce = wp_unslash($_POST[self::NONCE_FIELD]);

        if (is_array($nonce) || is_object($nonce)) {
            return false;
        }

        return (bool) wp_verify_nonce(sanitize_text_field((string) $nonce), self::NONCE_ACTION);
    }

    /**
     * Get requested visibility from POST data.
     *
     * @return bool|null True to show, false to hide, null when invalid.
     */
    private function requestedVisibility(): ?bool
    {
        if (!isset($_POST['visibility'])) {
            return null;
        }

        $visibility = wp_unslash($_POST['visibility']);

        if (is_array($visibility) || is_object($visibility)) {
            return null;
        }

        $visibility = sanitize_key((string) $visibility);

        if ('show' === $visibility) {
            return true;
        }

        if ('hide' === $visibility) {
            return false;
        }

        return null;
    }

    /**
     * Get sanitized requested response ID.
     *
     * @return int Response ID.
     */
    private function responseId(): int
    {
        if (isset($_POST['response_id'])) {
            $responseId = wp_unslash($_POST['response_id']);
        } elseif (isset($_GET['response_id'])) {
            $responseId = wp_unslash($_GET['response_id']);
        } else {
            return 0;
        }

        if (is_array($responseId) || is_object($responseId)) {
            return 0;
        }

        return absint($responseId);
    }

    /**
     * Build the response listing URL.
     *
     * @param array<string, mixed>|null $response Response row.
     * @param array<string, mixed>|null $thread Thread row.
     * @return string List URL.
     */
    private function listUrl(?array $response, ?array $thread): string
    {
        $args = ['page' => 'civic-thread-responses'];
        $threadId = isset($response['thread_id']) ? (int) $response['thread_id'] : 0;

        if ($threadId <= 0 && isset($thread['id'])) {
            $threadId = (int) $thread['id'];
        }

        if ($threadId > 0) {
            $args['thread_id'] = $threadId;
        }

        return add_query_arg($args, admin_url('admin.php'));
    }
}
