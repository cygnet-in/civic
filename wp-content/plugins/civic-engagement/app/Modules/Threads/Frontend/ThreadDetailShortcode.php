<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Threads\Repository\ThreadFieldRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;
use CivicPlatform\Modules\Threads\Responses\Frontend\ThreadResponseForm;
use CivicPlatform\Modules\Media\Frontend\MediaRenderer;
use CivicPlatform\Services\MediaService;

/**
 * Registers and renders the public consultation detail shortcode.
 *
 * Rendering remains frontend-focused. Response submission is delegated to the
 * response form handler.
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
     * Thread response repository.
     *
     * @var ThreadResponseRepository
     */
    private ThreadResponseRepository $responses;

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
     * Thread response form handler.
     *
     * @var ThreadResponseForm
     */
    private ThreadResponseForm $responseForm;

    private MediaService $media;

    /**
     * @param ThreadRepository $threads Thread repository.
     * @param ThreadResponseRepository $responses Thread response repository.
     * @param ThreadFieldRepository $fields Thread field repository.
     * @param DateHelper $dates Date helper.
     * @param ThreadResponseForm $responseForm Thread response form handler.
     */
    public function __construct(
        ThreadRepository $threads,
        ThreadResponseRepository $responses,
        ThreadFieldRepository $fields,
        DateHelper $dates,
        ThreadResponseForm $responseForm,
        MediaService $media
    ) {
        $this->threads = $threads;
        $this->responses = $responses;
        $this->fields = $fields;
        $this->dates = $dates;
        $this->responseForm = $responseForm;
        $this->media = $media;
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
        if (!is_array($atts)) {
            $atts = [];
        }

        $atts = shortcode_atts(
            [
                'show_public_responses' => '0',
            ],
            $atts,
            'civic_thread_detail'
        );
        $showPublicResponses = '1' === (string) $atts['show_public_responses'];

        $slug = $this->slug();
        $thread = '' !== $slug
            ? $this->threads->findPublicBySlug($slug)
            : $this->threads->findPublicById($this->threadId());

        ob_start();

        echo '<div class="civic-thread-detail">';

        if (!is_array($thread)) {
            echo '<p class="civic-thread-detail__empty">' . esc_html__('Consultation not found.', 'civic-engagement') . '</p>';
            echo '</div>';

            return (string) ob_get_clean();
        }

        $actualResponseCount = $this->responses->countByThreadId((int) ($thread['id'] ?? 0));
        $responseCount = max(0, (int) ($thread['starting_response_count'] ?? 0)) + $actualResponseCount;
        $publicResponses = $showPublicResponses
            ? $this->publicResponses((int) ($thread['id'] ?? 0))
            : ['items' => [], 'total' => 0];
        $this->renderThread($thread, $publicResponses, $showPublicResponses, $responseCount, $this->media->getByEntity('consultation', (int) ($thread['id'] ?? 0)));
        $this->renderResponseFormSection($thread);

        if ($showPublicResponses) {
            $this->renderPublicResponsesSection($publicResponses['items']);
        }

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Render consultation content.
     *
     * @param array<string, mixed> $thread Thread row.
     * @param array<string, mixed> $publicResponses Public response result.
     * @param bool $showPublicResponses Whether public responses are enabled.
     * @param int $responseCount Displayed response count.
     * @return void
     */
    private function renderThread(
        array $thread,
        array $publicResponses,
        bool $showPublicResponses,
        int $responseCount,
        array $media
    ): void
    {
        echo '<article class="civic-card civic-thread-detail__content">';
        echo '<div class="civic-card__content">';
        echo '<h1 class="civic-card-detail__title civic-thread-detail__title">' . esc_html((string) ($thread['title'] ?? '')) . '</h1>';
        echo MediaRenderer::gallery($media, 'consultation-' . (int) ($thread['id'] ?? 0));

        if (!empty($thread['summary'])) {
            echo '<p class="civic-card__summary civic-thread-detail__summary">' . esc_html((string) $thread['summary']) . '</p>';
        }

        if (!empty($thread['description'])) {
            echo '<div class="civic-card__description civic-thread-detail__description">' . wpautop(esc_html((string) $thread['description'])) . '</div>';
        }

        echo '<dl class="civic-card__meta civic-thread-detail__meta">';
        $this->renderMetaItem(__('Created', 'civic-engagement'), $this->dates->formatDate((string) ($thread['created_at'] ?? '')));
        $this->renderMetaItem(__('Start Date', 'civic-engagement'), $this->dates->formatDate($thread['start_date'] ?? null));
        $this->renderMetaItem(__('End Date', 'civic-engagement'), $this->dates->formatDate($thread['end_date'] ?? null));
        echo '</dl>';
        $this->renderActionLinks($thread, $publicResponses, $showPublicResponses, $responseCount);
        echo '</div>';
        echo '</article>';
    }

    /**
     * Render lightweight action links for responding and viewing responses.
     *
     * @param array<string, mixed> $thread Thread row.
     * @param array<string, mixed> $publicResponses Public response result.
     * @param bool $showPublicResponses Whether public responses are enabled.
     * @param int $responseCount Displayed response count.
     * @return void
     */
    private function renderActionLinks(
        array $thread,
        array $publicResponses,
        bool $showPublicResponses,
        int $responseCount
    ): void
    {
        $publicResponseTotal = $showPublicResponses && isset($publicResponses['total'])
            ? (int) $publicResponses['total']
            : 0;

        if (empty($thread['response_enabled']) && $responseCount <= 0) {
            return;
        }

        echo '<p class="civic-card__actions civic-thread-detail__actions">';

        if (!empty($thread['response_enabled'])) {
            echo '<a href="#civic-thread-response-form">' . esc_html__('Have Your Say', 'civic-engagement') . '</a>';
        }

        if (!empty($thread['response_enabled']) || $responseCount > 0) {
            if (!empty($thread['response_enabled'])) {
                echo ' | ';
            }

            if ($showPublicResponses && $publicResponseTotal > 0) {
                echo '<a href="#civic-thread-public-responses">' . esc_html(sprintf(__('Responses Received (%d)', 'civic-engagement'), $responseCount)) . '</a>';
            } else {
                echo '<span class="civic-thread-detail__response-count">' . esc_html(sprintf(__('Responses Received (%d)', 'civic-engagement'), $responseCount)) . '</span>';
            }
        }

        echo '</p>';
    }

    /**
     * Render the response form section.
     *
     * @param array<string, mixed> $thread Thread row.
     * @return void
     */
    private function renderResponseFormSection(array $thread): void
    {
        echo '<section id="civic-thread-response-form" class="civic-thread-detail__response-form">';

        if (!empty($thread['response_enabled'])) {
            echo $this->responseForm->render($thread);
        } else {
            echo '<p>' . esc_html__('Responses are currently closed for this consultation.', 'civic-engagement') . '</p>';
        }

        echo '</section>';
    }

    /**
     * Render public consultation responses.
     *
     * @param array<int, array<string, mixed>> $responses Public response rows.
     * @return void
     */
    private function renderPublicResponsesSection(array $responses): void
    {
        if (empty($responses)) {
            return;
        }

        $threadId = isset($responses[0]['thread_id']) ? (int) $responses[0]['thread_id'] : 0;
        $fieldLabels = $this->fieldLabels($threadId);

        echo '<section id="civic-thread-public-responses" class="civic-thread-detail__public-responses">';
        echo '<h2>' . esc_html__('Public Responses', 'civic-engagement') . '</h2>';

        foreach ($responses as $response) {
            $this->renderPublicResponse($response, $fieldLabels);
        }

        echo '</section>';
    }

    /**
     * Render a single public response.
     *
     * @param array<string, mixed> $response Public response row.
     * @param array<string, string> $fieldLabels Field labels keyed by field key.
     * @return void
     */
    private function renderPublicResponse(array $response, array $fieldLabels): void
    {
        echo '<article class="civic-thread-response">';
        echo '<h3 class="civic-card__title civic-thread-response__name">' . esc_html((string) ($response['name_snapshot'] ?? '')) . '</h3>';
        echo '<p class="civic-card__date civic-thread-response__date">' . esc_html($this->dates->formatDate((string) ($response['created_at'] ?? ''))) . '</p>';
        echo '<div class="civic-card__text civic-thread-response__text">' . wpautop(esc_html($this->responseText($response['response_data'] ?? ''))) . '</div>';
        $this->renderCustomFields($response['response_data'] ?? '', $fieldLabels);
        echo '</article>';
    }

    /**
     * Render public custom field values with labels.
     *
     * @param mixed $responseData Stored response_data value.
     * @param array<string, string> $fieldLabels Field labels keyed by field key.
     * @return void
     */
    private function renderCustomFields($responseData, array $fieldLabels): void
    {
        $customFields = $this->customFieldValues($responseData);

        if (empty($customFields) || empty($fieldLabels)) {
            return;
        }

        echo '<dl class="civic-card__custom-fields civic-thread-response__custom-fields">';

        foreach ($fieldLabels as $fieldKey => $label) {
            $value = isset($customFields[$fieldKey]) ? trim((string) $customFields[$fieldKey]) : '';

            if ('' === $value) {
                continue;
            }

            echo '<dt>' . esc_html($label) . '</dt>';
            echo '<dd>' . nl2br(esc_html($value)) . '</dd>';
        }

        echo '</dl>';
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
     * Get public responses for the current consultation.
     *
     * @param int $threadId Thread ID.
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    private function publicResponses(int $threadId): array
    {
        if ($threadId <= 0) {
            return ['items' => [], 'total' => 0];
        }

        $result = $this->responses->getPublicResponses(
            [
                'thread_id' => $threadId,
                'page' => 1,
                'per_page' => 50,
                'orderby' => 'created_at',
                'order' => 'DESC',
            ]
        );

        return [
            'items' => isset($result['items']) && is_array($result['items']) ? $result['items'] : [],
            'total' => isset($result['total']) ? (int) $result['total'] : 0,
        ];
    }

    /**
     * Extract the public response text from stored JSON.
     *
     * @param mixed $responseData Stored response_data value.
     * @return string Response text.
     */
    private function responseText($responseData): string
    {
        $decoded = $this->responseDataArray($responseData);

        if (is_array($decoded) && isset($decoded['response_text'])) {
            return (string) $decoded['response_text'];
        }

        return '';
    }

    /**
     * Extract custom field values from response_data.
     *
     * @param mixed $responseData Stored response_data value.
     * @return array<string, string>
     */
    private function customFieldValues($responseData): array
    {
        $data = $this->responseDataArray($responseData);
        $customFields = isset($data['custom_fields']) && is_array($data['custom_fields'])
            ? $data['custom_fields']
            : [];
        $values = [];

        foreach ($customFields as $fieldKey => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $fieldKey = sanitize_key((string) $fieldKey);
            $value = trim((string) $value);

            if ('' !== $fieldKey && '' !== $value) {
                $values[$fieldKey] = $value;
            }
        }

        return $values;
    }

    /**
     * Decode response_data to an array.
     *
     * @param mixed $responseData Stored response_data value.
     * @return array<string, mixed>
     */
    private function responseDataArray($responseData): array
    {
        if (is_array($responseData)) {
            return $responseData;
        }

        if (is_object($responseData)) {
            return [];
        }

        $decoded = json_decode((string) $responseData, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Build ordered field label lookup for a consultation.
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

    private function slug(): string
    {
        $slug = get_query_var('civic_slug');

        return is_scalar($slug) ? sanitize_title((string) $slug) : '';
    }
}
