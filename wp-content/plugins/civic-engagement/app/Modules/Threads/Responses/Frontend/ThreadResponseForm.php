<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Responses\Frontend;

use CivicPlatform\Modules\Threads\Responses\Services\ThreadResponseService;

/**
 * Handles public consultation response form rendering and submission processing.
 *
 * This frontend handler validates request intent, sanitizes submitted values,
 * and delegates the workflow to ThreadResponseService.
 */
class ThreadResponseForm
{
    /**
     * Form action value.
     */
    private const ACTION = 'civic_thread_response_submit';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'civic_thread_response_form';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_thread_response_nonce';

    /**
     * Thread response workflow service.
     *
     * @var ThreadResponseService
     */
    private ThreadResponseService $responses;

    /**
     * @param ThreadResponseService $responses Thread response workflow service.
     */
    public function __construct(ThreadResponseService $responses)
    {
        $this->responses = $responses;
    }

    /**
     * Render the response form for a consultation.
     *
     * @param array<string, mixed> $thread Thread row.
     * @return string Rendered form markup.
     */
    public function render(array $thread): string
    {
        $threadId = isset($thread['id']) ? (int) $thread['id'] : 0;
        $response = $this->processSubmission($threadId);
        $values = $response['values'];
        $errors = $response['errors'];

        ob_start();

        echo '<section class="civic-thread-response-form">';
        echo '<h2>' . esc_html__('Submit a Response', 'civic-engagement') . '</h2>';

        if (!empty($response['message'])) {
            $class = !empty($response['success']) ? 'civic-thread-response-form__message--success' : 'civic-thread-response-form__message--error';
            echo '<p class="civic-thread-response-form__message ' . esc_attr($class) . '">' . esc_html((string) $response['message']) . '</p>';
        }

        echo '<form method="post">';
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<input type="hidden" name="civic_thread_response[thread_id]" value="' . esc_attr((string) $threadId) . '">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        $this->renderTextField('name', __('Name', 'civic-engagement'), (string) $values['name'], $errors, true);
        $this->renderEmailField('email', __('Email', 'civic-engagement'), (string) $values['email'], $errors, true);
        $this->renderTextField('phone', __('Phone', 'civic-engagement'), (string) $values['phone'], $errors, false);
        $this->renderTextareaField('address', __('Address', 'civic-engagement'), (string) $values['address'], $errors, false);
        $this->renderTextareaField('response_text', __('Response', 'civic-engagement'), (string) $values['response_text'], $errors, true);

        
        echo '<p>';
        echo '<button type="submit" class="button button-primary">';
        echo esc_html__('Submit Response', 'civic-engagement');
        echo '</button>';
        echo '</p>';

        echo '</form>';
        echo '</section>';

        return (string) ob_get_clean();
    }

    /**
     * Process a submitted response form for the given thread.
     *
     * @param int $threadId Current thread ID.
     * @return array<string, mixed> Structured form response.
     */
    public function processSubmission(int $threadId): array
    {
        if (!$this->isSubmission($threadId)) {
            return $this->buildResponse(false, false, null, $this->defaultValues($threadId), [], null);
        }

        if (!$this->hasValidNonce()) {
            return $this->buildResponse(true, false, 'Security check failed. Please try again.', $this->defaultValues($threadId), [], 'invalid_nonce');
        }

        $values = $this->sanitizeRequestValues($threadId);
        $errors = $this->validateValues($values);

        if (!empty($errors)) {
            return $this->buildResponse(true, false, 'Please check the highlighted fields.', $values, $errors, 'validation_failed');
        }

        $result = $this->responses->submit($values);

        if (empty($result['success'])) {
            return $this->buildResponse(true, false, 'We could not submit your response. Please try again.', $values, [], (string) ($result['error'] ?? 'submission_failed'));
        }

        return $this->buildResponse(true, true, 'Thank you. Your response has been submitted.', $this->defaultValues($threadId), [], null, $result);
    }

    /**
     * Render a text field.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $value Field value.
     * @param array<string, string> $errors Validation errors.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderTextField(string $name, string $label, string $value, array $errors, bool $required): void
    {
        echo '<p>';
        echo '<label for="civic-thread-response-' . esc_attr($name) . '">' . esc_html($label) . '</label><br>';
        echo '<input type="text" id="civic-thread-response-' . esc_attr($name) . '" name="civic_thread_response[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '>';
        $this->renderFieldError($name, $errors);
        echo '</p>';
    }

    /**
     * Render an email field.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $value Field value.
     * @param array<string, string> $errors Validation errors.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderEmailField(string $name, string $label, string $value, array $errors, bool $required): void
    {
        echo '<p>';
        echo '<label for="civic-thread-response-' . esc_attr($name) . '">' . esc_html($label) . '</label><br>';
        echo '<input type="email" id="civic-thread-response-' . esc_attr($name) . '" name="civic_thread_response[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '>';
        $this->renderFieldError($name, $errors);
        echo '</p>';
    }

    /**
     * Render a textarea field.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $value Field value.
     * @param array<string, string> $errors Validation errors.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderTextareaField(string $name, string $label, string $value, array $errors, bool $required): void
    {
        echo '<p>';
        echo '<label for="civic-thread-response-' . esc_attr($name) . '">' . esc_html($label) . '</label><br>';
        echo '<textarea id="civic-thread-response-' . esc_attr($name) . '" name="civic_thread_response[' . esc_attr($name) . ']" rows="5"' . ($required ? ' required' : '') . '>' . esc_textarea($value) . '</textarea>';
        $this->renderFieldError($name, $errors);
        echo '</p>';
    }

    /**
     * Render a field validation error.
     *
     * @param string $name Field name.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderFieldError(string $name, array $errors): void
    {
        if (empty($errors[$name])) {
            return;
        }

        echo '<br><span class="civic-thread-response-form__error">' . esc_html($errors[$name]) . '</span>';
    }

    /**
     * Check whether the current request is a response submission for this thread.
     *
     * @param int $threadId Current thread ID.
     * @return bool True when submitted.
     */
    private function isSubmission(int $threadId): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : '';

        if ('POST' !== $method) {
            return false;
        }

        $action = isset($_POST['civic_action']) ? $this->requestScalar($_POST['civic_action']) : '';

        if (self::ACTION !== $action) {
            return false;
        }

        $data = $this->requestData();
        $submittedThreadId = isset($data['thread_id']) ? absint($this->requestScalar($data['thread_id'])) : 0;

        return $threadId > 0 && $submittedThreadId === $threadId;
    }

    /**
     * Validate the submitted nonce.
     *
     * @return bool True when the nonce is valid.
     */
    private function hasValidNonce(): bool
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return false;
        }

        $nonce = sanitize_text_field($this->requestScalar($_POST[self::NONCE_FIELD]));

        return '' !== $nonce && (bool) wp_verify_nonce($nonce, self::NONCE_ACTION);
    }

    /**
     * Sanitize submitted request values.
     *
     * @param int $threadId Current thread ID.
     * @return array<string, mixed> Sanitized workflow data.
     */
    private function sanitizeRequestValues(int $threadId): array
    {
        $data = $this->requestData();

        return [
            'thread_id' => $threadId,
            'name' => sanitize_text_field($this->requestValue($data, 'name')),
            'email' => sanitize_email($this->requestValue($data, 'email')),
            'phone' => sanitize_text_field($this->requestValue($data, 'phone')),
            'address' => sanitize_textarea_field($this->requestValue($data, 'address')),
            'response_text' => sanitize_textarea_field($this->requestValue($data, 'response_text')),
        ];
    }

    /**
     * Validate sanitized values.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return array<string, string> Validation errors keyed by field.
     */
    private function validateValues(array $values): array
    {
        $errors = [];

        if ('' === $values['name']) {
            $errors['name'] = 'Name is required.';
        }

        if ('' === $values['email']) {
            $errors['email'] = 'Email is required.';
        } elseif (function_exists('is_email') && !is_email((string) $values['email'])) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if ('' === $values['response_text']) {
            $errors['response_text'] = 'Response is required.';
        }

        return $errors;
    }

    /**
     * Get structured response request data.
     *
     * @return array<string, mixed> Unslashed request data.
     */
    private function requestData(): array
    {
        if (!isset($_POST['civic_thread_response'])) {
            return [];
        }

        $data = wp_unslash($_POST['civic_thread_response']);

        return is_array($data) ? $data : [];
    }

    /**
     * Get a scalar request value from structured request data.
     *
     * @param array<string, mixed> $data Request data.
     * @param string $key Request key.
     * @return string Request value.
     */
    private function requestValue(array $data, string $key): string
    {
        if (!isset($data[$key])) {
            return '';
        }

        return $this->scalarValue($data[$key]);
    }

    /**
     * Normalize a raw request value to a scalar string.
     *
     * @param mixed $value Raw request value.
     * @return string Scalar string.
     */
    private function requestScalar($value): string
    {
        $value = wp_unslash($value);

        return $this->scalarValue($value);
    }

    /**
     * Normalize a value to a scalar string without changing slash state.
     *
     * @param mixed $value Raw value.
     * @return string Scalar string.
     */
    private function scalarValue($value): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Return default form values.
     *
     * @param int $threadId Current thread ID.
     * @return array<string, mixed>
     */
    private function defaultValues(int $threadId): array
    {
        return [
            'thread_id' => $threadId,
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'response_text' => '',
        ];
    }

    /**
     * Build a consistent form response.
     *
     * @param bool $submitted Whether a submission was received.
     * @param bool $success Whether the submission succeeded.
     * @param string|null $message User-facing message.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @param string|null $error Error code.
     * @param array<string, mixed>|null $workflowResult Service result.
     * @return array<string, mixed>
     */
    private function buildResponse(
        bool $submitted,
        bool $success,
        ?string $message,
        array $values,
        array $errors,
        ?string $error,
        ?array $workflowResult = null
    ): array {
        return [
            'submitted' => $submitted,
            'success' => $success,
            'message' => $message,
            'values' => $values,
            'errors' => $errors,
            'error' => $error,
            'result' => $workflowResult,
        ];
    }
}
