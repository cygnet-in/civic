<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

/**
 * Renders and processes the consultation creation admin page.
 *
 * Request handling and presentation live here. Thread persistence
 * logging are delegated to repositories.
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


    /**
     * @param ThreadRepository $threads Thread repository.
     */
    public function __construct(ThreadRepository $threads)
    {
        $this->threads = $threads;
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

        return $this->buildResponse(true, true, 'Consultation created successfully.', $this->defaultValues(), [], null);
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
        $this->renderTextarea('summary', __('Summary', 'civic-engagement'), $values, $errors, 3);
        $this->renderTextarea('content', __('Content', 'civic-engagement'), $values, $errors, 8);
        $this->renderStatusSelect($values, $errors);
        $this->renderResponseEnabledField($values);
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
        echo '<th scope="row"><label for="civic-thread-status">' . esc_html__('Status', 'civic-engagement') . '</label></th>';
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
     * Render the response enabled checkbox.
     *
     * @param array<string, mixed> $values Form values.
     * @return void
     */
    private function renderResponseEnabledField(array $values): void
    {
        $enabled = !empty($values['response_enabled']);

        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Responses', 'civic-engagement') . '</th>';
        echo '<td>';
        echo '<label><input type="checkbox" name="civic_thread[response_enabled]" value="1"' . checked($enabled, true, false) . '> ' . esc_html__('Enable public responses', 'civic-engagement') . '</label>';
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
            'summary' => sanitize_textarea_field($this->requestValue($data, 'summary')),
            'content' => sanitize_textarea_field($this->requestValue($data, 'content')),
            'status' => sanitize_text_field($this->requestValue($data, 'status')),
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

        if (!in_array($values['status'], ['draft', 'published'], true)) {
            $errors['status'] = 'Status must be draft or published.';
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
            'description' => $this->buildDescription($values),
            'is_public' => 'published' === $values['status'] ? 1 : 0,
            'created_by' => get_current_user_id(),
            'status' => $values['status'],
        ];
    }

    /**
     * Combine summary/content while preserving both submitted values.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return string Thread description.
     */
    private function buildDescription(array $values): string
    {
        $summary = trim((string) ($values['summary'] ?? ''));
        $content = trim((string) ($values['content'] ?? ''));

        if ('' === $summary) {
            return $content;
        }

        if ('' === $content) {
            return $summary;
        }

        return $summary . "\n\n" . $content;
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
            'summary' => '',
            'content' => '',
            'status' => 'draft',
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
