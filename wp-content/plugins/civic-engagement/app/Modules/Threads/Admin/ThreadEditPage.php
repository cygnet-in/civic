<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadFieldRepository;
use CivicPlatform\Modules\Media\Admin\MediaAdminPanel;
use CivicPlatform\Services\MediaService;
use CivicPlatform\Services\ShortUrlService;

/**
 * Renders and processes the consultation edit admin page.
 *
 * This page handles request sanitization and presentation only. Thread updates
 * are delegated to ThreadRepository.
 */
class ThreadEditPage
{
    /**
     * Required capability for editing threads.
     */
    private const CAPABILITY = 'manage_civic_threads';

    /**
     * Form action value.
     */
    private const ACTION = 'civic_thread_edit';

    /**
     * Nonce action prefix.
     */
    private const NONCE_ACTION = 'civic_thread_edit_';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_thread_nonce';

    /**
     * Thread repository.
     *
     * @var ThreadRepository
     */
    private ThreadRepository $threads;

    private ThreadFieldRepository $fields;

    private ShortUrlService $shortUrls;

    private MediaService $media;

    private MediaAdminPanel $mediaPanel;

    /**
     * @param ThreadRepository $threads Thread repository.
     */
    public function __construct(ThreadRepository $threads, ThreadFieldRepository $fields, MediaService $media, ShortUrlService $shortUrls)
    {
        $this->threads = $threads;
        $this->fields = $fields;
        $this->media = $media;
        $this->shortUrls = $shortUrls;
        $this->mediaPanel = new MediaAdminPanel($media);
    }

    /**
     * Render and process the thread edit page.
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
        echo '<h1>' . esc_html__('Edit Consultation', 'civic-engagement') . '</h1>';
        echo '<p><a href="' . esc_url($this->listUrl()) . '">' . esc_html__('Back to Threads', 'civic-engagement') . '</a></p>';

        if (!is_array($thread)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        $response = $this->processSubmission($threadId);
        $thread = $this->threads->findById($threadId) ?: $thread;
        $values = !empty($response['submitted']) && empty($response['success'])
            ? $response['values']
            : $this->valuesFromThread($thread);

        $this->renderMessage($response);
        $this->renderForm($threadId, $thread, $values, $response['errors']);
        echo '</div>';
    }

    /**
     * Process a submitted edit request.
     *
     * @param int $threadId Thread ID.
     * @return array<string, mixed> Form response.
     */
    private function processSubmission(int $threadId): array
    {
        if (!$this->isSubmission()) {
            return $this->buildResponse(false, false, null, [], [], null);
        }

        $values = $this->sanitizeRequestValues();

        if (!$this->hasValidNonce($threadId)) {
            return $this->buildResponse(true, false, 'Security check failed. Please try again.', $values, [], 'invalid_nonce');
        }

        $errors = $this->validateValues($values, $threadId);

        if (!empty($errors)) {
            return $this->buildResponse(true, false, $this->validationMessage($errors), $values, $errors, 'validation_failed');
        }

        $updated = $this->threads->update($threadId, $this->buildThreadData($values));

        if (!$updated) {
            return $this->buildResponse(true, false, 'The consultation could not be updated.', $values, [], 'thread_update_failed');
        }

        $media = $this->synchronizeMedia('consultation', $threadId);
        if (!empty($media['errors'])) {
            return $this->buildResponse(true, false, implode(' ', $media['errors']), $values, ['media' => implode(' ', $media['errors'])], 'media_save_failed');
        }

        return $this->buildResponse(true, true, 'Consultation updated successfully.', [], [], null);
    }

    /**
     * Render the form message.
     *
     * @param array<string, mixed> $response Form response.
     * @return void
     */
    private function renderMessage(array $response): void
    {
        $message = isset($response['message']) ? (string) $response['message'] : '';

        if ('' === $message) {
            return;
        }

        $class = !empty($response['success']) ? 'notice notice-success' : 'notice notice-error';

        echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($message) . '</p></div>';
    }

    /**
     * Render the edit form.
     *
     * @param int $threadId Thread ID.
     * @param array<string, mixed> $thread Thread row.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderForm(int $threadId, array $thread, array $values, array $errors): void
    {
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field(self::NONCE_ACTION . $threadId, self::NONCE_FIELD);
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->renderReadOnlyRow(__('Slug', 'civic-engagement'), (string) ($thread['slug'] ?? ''));
        $this->renderTextInput('short_code', __('Short URL Code', 'civic-engagement'), $values, $errors, false);
        $this->renderTextInput('title', __('Title', 'civic-engagement'), $values, $errors, true);
        $this->renderTextarea('summary', __('Summary', 'civic-engagement'), $values, $errors, 3);
        $this->renderTextarea('description', __('Description', 'civic-engagement'), $values, $errors, 8);
        $this->renderStatusSelect($values, $errors);
        $this->renderNumberInput('starting_response_count', __('Starting Response Count', 'civic-engagement'), $values, $errors);
        $this->renderResponseEnabledField($values, $errors);
        $this->renderDateInput('start_date', __('Start Date', 'civic-engagement'), $values, $errors, false);
        $this->renderDateInput('end_date', __('End Date', 'civic-engagement'), $values, $errors, false);
        echo '</tbody></table>';
        $this->mediaPanel->render('consultation', $threadId);
        submit_button(__('Update Consultation', 'civic-engagement'));
        echo '</form>';
    }

    /**
     * Render a read-only row.
     *
     * @param string $label Row label.
     * @param string $value Row value.
     * @return void
     */
    private function renderReadOnlyRow(string $label, string $value): void
    {
        echo '<tr>';
        echo '<th scope="row">' . esc_html($label) . '</th>';
        echo '<td><code>' . esc_html($value) . '</code></td>';
        echo '</tr>';
    }

    /**
     * Render a text input field.
     *
     * @param string $key Field key.
     * @param string $label Field label.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderTextInput(string $key, string $label, array $values, array $errors, bool $required): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-thread-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<input class="regular-text" id="civic-thread-' . esc_attr($key) . '" name="civic_thread[' . esc_attr($key) . ']" type="text" value="' . esc_attr((string) ($values[$key] ?? '')) . '"' . ($required ? ' required' : '') . '>';
        if ('short_code' === $key) {
            echo '<p class="description">' . esc_html__('Optional. Use lowercase letters, numbers, and hyphens only.', 'civic-engagement') . '</p>';
            if ('' !== (string) ($values[$key] ?? '')) {
                echo '<p class="description"><code>' . esc_html(ShortUrlService::url((string) $values[$key])) . '</code></p>';
            }
        }
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render a date input field.
     *
     * @param string $key Field key.
     * @param string $label Field label.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderDateInput(string $key, string $label, array $values, array $errors, bool $required): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-thread-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<input class="regular-text" id="civic-thread-' . esc_attr($key) . '" name="civic_thread[' . esc_attr($key) . ']" type="date" value="' . esc_attr((string) ($values[$key] ?? '')) . '"' . ($required ? ' required' : '') . '>';
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render a textarea field.
     *
     * @param string $key Field key.
     * @param string $label Field label.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @param int $rows Row count.
     * @return void
     */
    private function renderTextarea(string $key, string $label, array $values, array $errors, int $rows): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-thread-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<textarea class="large-text" id="civic-thread-' . esc_attr($key) . '" name="civic_thread[' . esc_attr($key) . ']" rows="' . esc_attr((string) $rows) . '">' . esc_textarea((string) ($values[$key] ?? '')) . '</textarea>';
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render the status select.
     *
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderStatusSelect(array $values, array $errors): void
    {
        $status = (string) ($values['status'] ?? 'draft');

        echo '<tr>';
        echo '<th scope="row"><label for="civic-thread-status">' . esc_html__('Publication Status', 'civic-engagement') . '</label></th>';
        echo '<td>';
        echo '<select id="civic-thread-status" name="civic_thread[status]">';
        echo '<option value="draft"' . selected($status, 'draft', false) . '>' . esc_html__('Draft', 'civic-engagement') . '</option>';
        echo '<option value="published"' . selected($status, 'published', false) . '>' . esc_html__('Published', 'civic-engagement') . '</option>';
        echo '</select>';
        $this->renderFieldError('status', $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render a non-negative number input.
     *
     * @param string $key Field key.
     * @param string $label Field label.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderNumberInput(string $key, string $label, array $values, array $errors): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-thread-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td><input class="small-text" id="civic-thread-' . esc_attr($key) . '" name="civic_thread[' . esc_attr($key) . ']" type="number" min="0" step="1" value="' . esc_attr((string) ($values[$key] ?? 0)) . '">';
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render the response enabled checkbox.
     *
     * @param array<string, mixed> $values Form values.
     * @return void
     */
    private function renderResponseEnabledField(array $values, array $errors): void
    {
        $enabled = !empty($values['response_enabled']);

        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Responses', 'civic-engagement') . '</th>';
        echo '<td>';
        echo '<label><input type="checkbox" name="civic_thread[response_enabled]" value="1"' . checked($enabled, true, false) . '> ' . esc_html__('Enable public responses', 'civic-engagement') . '</label>';
        $this->renderFieldError('response_enabled', $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render a field error if present.
     *
     * @param string $key Field key.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderFieldError(string $key, array $errors): void
    {
        if (!isset($errors[$key])) {
            return;
        }

        echo '<p class="description">' . esc_html($errors[$key]) . '</p>';
    }

    /**
     * Check whether the current request is a thread edit submission.
     *
     * @return bool True when submitted.
     */
    private function isSubmission(): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : '';

        if ('POST' !== $method) {
            return false;
        }

        $action = isset($_POST['civic_action']) ? wp_unslash($_POST['civic_action']) : '';

        if (is_array($action) || is_object($action)) {
            return false;
        }

        return self::ACTION === $action;
    }

    /**
     * Validate the submitted nonce.
     *
     * @param int $threadId Thread ID.
     * @return bool True when nonce is valid.
     */
    private function hasValidNonce(int $threadId): bool
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return false;
        }

        $nonce = wp_unslash($_POST[self::NONCE_FIELD]);

        if (is_array($nonce) || is_object($nonce)) {
            return false;
        }

        $nonce = sanitize_text_field((string) $nonce);

        return '' !== $nonce && (bool) wp_verify_nonce($nonce, self::NONCE_ACTION . $threadId);
    }

    /**
     * Sanitize submitted request values.
     *
     * @return array<string, mixed> Sanitized values.
     */
    private function sanitizeRequestValues(): array
    {
        $data = $this->requestData();

        return [
            'title' => sanitize_text_field($this->requestValue($data, 'title')),
            'short_code' => $this->shortUrls->normalize($this->requestValue($data, 'short_code')),
            'summary' => sanitize_textarea_field($this->requestValue($data, 'summary')),
            'description' => sanitize_textarea_field($this->requestValue($data, 'description')),
            'status' => sanitize_text_field($this->requestValue($data, 'status')),
            'starting_response_count' => absint($this->requestValue($data, 'starting_response_count')),
            'response_enabled' => !empty($data['response_enabled']) ? 1 : 0,
            'start_date' => sanitize_text_field($this->requestValue($data, 'start_date')),
            'end_date' => sanitize_text_field($this->requestValue($data, 'end_date')),
        ];
    }

    /**
     * Get structured thread request data.
     *
     * @return array<string, mixed> Unslashed request data.
     */
    private function requestData(): array
    {
        if (!isset($_POST['civic_thread'])) {
            return [];
        }

        $data = wp_unslash($_POST['civic_thread']);

        return is_array($data) ? $data : [];
    }

    /** @return array{errors: array<int, string>, created: int} */
    private function synchronizeMedia(string $entityType, int $entityId): array
    {
        $request = isset($_POST['civic_media']) ? wp_unslash($_POST['civic_media']) : [];
        $uploads = isset($_FILES['civic_media']) && is_array($_FILES['civic_media']) ? $_FILES['civic_media'] : [];

        return $this->media->synchronize($entityType, $entityId, is_array($request) ? $request : [], $uploads, get_current_user_id());
    }

    /**
     * Get a scalar request value.
     *
     * @param array<string, mixed> $data Request data.
     * @param string $key Request key.
     * @return string Request value.
     */
    private function requestValue(array $data, string $key): string
    {
        if (!isset($data[$key]) || is_array($data[$key]) || is_object($data[$key])) {
            return '';
        }

        return (string) $data[$key];
    }

    /**
     * Validate sanitized values.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return array<string, string> Validation errors.
     */
    private function validateValues(array $values, int $threadId = 0): array
    {
        $errors = [];

        if ('' === $values['title']) {
            $errors['title'] = 'Title is required.';
        }

        if (!in_array($values['status'], ['draft', 'published'], true)) {
            $errors['status'] = 'Status must be draft or published.';
        }

        foreach (['start_date', 'end_date'] as $dateField) {
            if (!$this->isValidDateValue((string) ($values[$dateField] ?? ''))) {
                $errors[$dateField] = sprintf(
                    __('%s must use the YYYY-MM-DD format.', 'civic-engagement'),
                    $this->dateFieldLabel($dateField)
                );
            }
        }

        $shortUrlError = $this->shortUrls->validationError((string) $values['short_code'], 'consultation', $threadId);
        if (null !== $shortUrlError) {
            $errors['short_code'] = $shortUrlError;
        }

        if ('published' === $values['status'] && !empty($values['response_enabled']) && !$this->hasResponseFields($threadId)) {
            $errors['response_enabled'] = 'Add at least one custom response field before publishing a consultation that accepts public responses.';
        }

        return $errors;
    }

    private function hasResponseFields(int $threadId): bool
    {
        return $threadId > 0 && !empty($this->fields->findByThreadId($threadId));
    }

    /**
     * Build the top-level validation message for the edit form.
     *
     * @param array<string, string> $errors Validation errors.
     * @return string Validation message.
     */
    private function validationMessage(array $errors): string
    {
        if (isset($errors['response_enabled'])) {
            return __('This consultation cannot be published because no Response Fields have been configured. Please add at least one Response Field before publishing.', 'civic-engagement');
        }

        return __('Please check the highlighted fields.', 'civic-engagement');
    }

    /**
     * Build repository update data for civic_threads.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return array<string, mixed> Thread data.
     */
    private function buildThreadData(array $values): array
    {
        return [
            'title' => $values['title'],
            'short_code' => $values['short_code'],
            'summary' => $values['summary'],
            'description' => $values['description'],
            'response_enabled' => $values['response_enabled'],
            'is_public' => 'published' === $values['status'] ? 1 : 0,
            'start_date' => $this->dateStorageValue((string) $values['start_date']),
            'end_date' => $this->dateStorageValue((string) $values['end_date']),
            'status' => $values['status'],
            'starting_response_count' => $values['starting_response_count'],
        ];
    }

    /**
     * Map a thread row to form values.
     *
     * @param array<string, mixed> $thread Thread row.
     * @return array<string, mixed>
     */
    private function valuesFromThread(array $thread): array
    {
        return [
            'title' => (string) ($thread['title'] ?? ''),
            'short_code' => (string) ($thread['short_code'] ?? ''),
            'summary' => (string) ($thread['summary'] ?? ''),
            'description' => (string) ($thread['description'] ?? ''),
            'status' => (string) ($thread['status'] ?? 'draft'),
            'starting_response_count' => isset($thread['starting_response_count']) ? (int) $thread['starting_response_count'] : 0,
            'response_enabled' => !empty($thread['response_enabled']) ? 1 : 0,
            'start_date' => $this->dateFormValue($thread['start_date'] ?? null),
            'end_date' => $this->dateFormValue($thread['end_date'] ?? null),
        ];
    }

    /**
     * Normalize optional date values for form display.
     *
     * @param mixed $value Raw date value.
     * @return string Form value.
     */
    private function dateFormValue($value): string
    {
        if (null === $value || '' === trim((string) $value) || '0000-00-00 00:00:00' === $value) {
            return '';
        }

        $date = substr(trim((string) $value), 0, 10);

        return $this->isValidDateValue($date) ? $date : '';
    }

    /**
     * Convert a date form value to MySQL datetime storage format.
     *
     * @param string $value Submitted date value.
     * @return string Storage value.
     */
    private function dateStorageValue(string $value): string
    {
        $value = trim($value);

        return '' === $value ? '' : $value . ' 00:00:00';
    }

    /**
     * Validate an optional Y-m-d date value.
     *
     * @param string $value Date value.
     * @return bool True when empty or valid.
     */
    private function isValidDateValue(string $value): bool
    {
        $value = trim($value);

        if ('' === $value) {
            return true;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false !== $date && $date->format('Y-m-d') === $value;
    }

    /**
     * Get a user-facing label for a date field.
     *
     * @param string $key Date field key.
     * @return string Field label.
     */
    private function dateFieldLabel(string $key): string
    {
        return 'end_date' === $key
            ? __('End Date', 'civic-engagement')
            : __('Start Date', 'civic-engagement');
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
     * Render an admin error when the thread cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Thread not found.', 'civic-engagement') . '</p></div>';
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

    /**
     * Build a consistent form response.
     *
     * @param bool $submitted Whether a submission was received.
     * @param bool $success Whether update succeeded.
     * @param string|null $message User-facing message.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @param string|null $error Error code.
     * @return array<string, mixed>
     */
    private function buildResponse(
        bool $submitted,
        bool $success,
        ?string $message,
        array $values,
        array $errors,
        ?string $error
    ): array {
        return [
            'submitted' => $submitted,
            'success' => $success,
            'message' => $message,
            'values' => $values,
            'errors' => $errors,
            'error' => $error,
        ];
    }
}
