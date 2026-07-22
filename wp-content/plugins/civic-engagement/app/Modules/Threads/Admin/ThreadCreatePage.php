<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Services\ShortUrlService;

/**
 * Renders and processes the consultation creation admin page.
 *
 * Request handling and presentation live here. Thread persistence is delegated
 * to the repository.
 */
class ThreadCreatePage
{
    /**
     * Required capability for creating threads.
     */
    private const CAPABILITY = 'manage_civic_threads';

    /**
     * Form action value.
     */
    private const ACTION = 'civic_thread_create';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'civic_thread_create';

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

    private ShortUrlService $shortUrls;

    /**
     * @param ThreadRepository $threads Thread repository.
     */
    public function __construct(ThreadRepository $threads, ShortUrlService $shortUrls)
    {
        $this->threads = $threads;
        $this->shortUrls = $shortUrls;
    }

    /**
     * Render and process the thread creation page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $response = $this->processSubmission();
        $values = $response['values'];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Create Consultation', 'civic-engagement') . '</h1>';
        $this->renderMessage($response);
        $this->renderForm($values, $response['errors']);
        echo '</div>';
    }

    /**
     * Process a submitted thread creation request.
     *
     * @return array<string, mixed> Form response.
     */
    private function processSubmission(): array
    {
        if (!$this->isSubmission()) {
            return $this->buildResponse(false, false, null, $this->defaultValues(), [], null);
        }

        if (!$this->hasValidNonce()) {
            return $this->buildResponse(true, false, 'Security check failed. Please try again.', $this->defaultValues(), [], 'invalid_nonce');
        }

        $values = $this->sanitizeRequestValues();
        $errors = $this->validateValues($values);

        if (!empty($errors)) {
            return $this->buildResponse(true, false, 'Please check the highlighted fields.', $values, $errors, 'validation_failed');
        }

        $threadId = $this->threads->create($this->buildThreadData($values));

        if ($threadId <= 0) {
            return $this->buildResponse(true, false, 'The consultation could not be created.', $values, [], 'thread_create_failed');
        }

        return $this->buildResponse(true, true, 'Consultation created as a draft. Add response fields before publishing; publishing is available from the Edit screen.', $this->defaultValues(), [], null);
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
     * Render the consultation creation form.
     *
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderForm(array $values, array $errors): void
    {
        echo '<form method="post">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->renderTextInput('title', __('Title', 'civic-engagement'), $values, $errors, true);
        $this->renderTextInput('short_code', __('Short URL Code', 'civic-engagement'), $values, $errors, false);
        $this->renderTextarea('summary', __('Summary', 'civic-engagement'), $values, $errors, 3);
        $this->renderTextarea('content', __('Content', 'civic-engagement'), $values, $errors, 8);
        $this->renderNumberInput('starting_response_count', __('Starting Response Count', 'civic-engagement'), $values, $errors);
        $this->renderResponseEnabledField($values, $errors);
        echo '</tbody></table>';
        submit_button(__('Create Consultation', 'civic-engagement'));
        echo '</form>';
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
        $this->renderShortUrlDescription($key, (string) ($values[$key] ?? ''));
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    private function renderShortUrlDescription(string $key, string $shortCode): void
    {
        if ('short_code' !== $key) {
            return;
        }

        echo '<p class="description">' . esc_html__('Optional. Use lowercase letters, numbers, and hyphens only.', 'civic-engagement') . '</p>';

        if ('' !== $shortCode) {
            echo '<p class="description"><code>' . esc_html(ShortUrlService::url($shortCode)) . '</code></p>';
        }
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
     * Check whether the current request is a thread create submission.
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
     * @return bool True when nonce is valid.
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

        $nonce = sanitize_text_field((string) $nonce);

        return '' !== $nonce && (bool) wp_verify_nonce($nonce, self::NONCE_ACTION);
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
            'content' => sanitize_textarea_field($this->requestValue($data, 'content')),
            'starting_response_count' => absint($this->requestValue($data, 'starting_response_count')),
            'response_enabled' => !empty($data['response_enabled']) ? 1 : 0,
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
    private function validateValues(array $values): array
    {
        $errors = [];

        if ('' === $values['title']) {
            $errors['title'] = 'Title is required.';
        }

        $shortUrlError = $this->shortUrls->validationError((string) $values['short_code'], 'consultation');
        if (null !== $shortUrlError) {
            $errors['short_code'] = $shortUrlError;
        }

        if ('' === $values['title'] || $this->threads->slugExists($this->buildSlug((string) $values['title']))) {
            $errors['title'] = 'A consultation with this URL slug already exists.';
        }

        return $errors;
    }

    /**
     * Build repository data for civic_threads.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return array<string, mixed> Thread data.
     */
    private function buildThreadData(array $values): array
    {
        return [
            'title' => $values['title'],
            'slug' => $this->buildSlug((string) $values['title']),
            'short_code' => $values['short_code'],
            'summary' => $values['summary'],
            'description' => $values['content'],
            'response_enabled' => $values['response_enabled'],
            'is_public' => 0,
            'starting_response_count' => $values['starting_response_count'],
            'created_by' => get_current_user_id(),
            'status' => 'draft',
        ];
    }

    /**
     * Build a URL-friendly slug from the title.
     *
     * @param string $title Thread title.
     * @return string Thread slug.
     */
    private function buildSlug(string $title): string
    {
        $slug = sanitize_title($title);

        if ('' !== $slug) {
            return $slug;
        }

        return 'consultation-' . time();
    }

    /**
     * Return default form values.
     *
     * @return array<string, mixed>
     */
    private function defaultValues(): array
    {
        return [
            'title' => '',
            'short_code' => '',
            'summary' => '',
            'content' => '',
            'starting_response_count' => 0,
            'response_enabled' => 1,
        ];
    }

    /**
     * Build a consistent form response.
     *
     * @param bool $submitted Whether a submission was received.
     * @param bool $success Whether creation succeeded.
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
