<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Responses\Frontend;

use CivicPlatform\Modules\Threads\Repository\ThreadFieldRepository;
use CivicPlatform\Modules\Threads\Responses\Services\ThreadResponseService;
use CivicPlatform\Repositories\ElectoralAreaRepository;
use CivicPlatform\Services\CaptchaService;
use CivicPlatform\Helpers\FormRenderer;

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
     * Thread field repository.
     *
     * @var ThreadFieldRepository
     */
    private ThreadFieldRepository $fields;

    /**
     * Electoral area repository.
     *
     * @var ElectoralAreaRepository
     */
    private ElectoralAreaRepository $electoralAreas;

    /**
     * Shared CAPTCHA service.
     *
     * @var CaptchaService
     */
    private CaptchaService $captcha;

    /**
     * @param ThreadResponseService $responses Thread response workflow service.
     * @param ThreadFieldRepository $fields Thread field repository.
     * @param ElectoralAreaRepository $electoralAreas Electoral area repository.
     */
    public function __construct(
        ThreadResponseService $responses,
        ThreadFieldRepository $fields,
        ElectoralAreaRepository $electoralAreas,
        ?CaptchaService $captcha = null
    ) {
        $this->responses = $responses;
        $this->fields = $fields;
        $this->electoralAreas = $electoralAreas;
        $this->captcha = $captcha ?? new CaptchaService();
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
        $fieldDefinitions = $this->fieldDefinitions($threadId);
        $response = $this->processSubmission($threadId, $fieldDefinitions);
        $values = $response['values'];
        $errors = $response['errors'];

        ob_start();

        echo '<section class="civic-thread-response-form civic-form">';
        echo '<h2 class="civic-thread-response-form__title civic-form__title">' . esc_html__('Submit a Response', 'civic-engagement') . '</h2>';

        if (!empty($response['message'])) {
            $class = !empty($response['success']) ? 'civic-thread-response-form__message--success civic-form__message--success' : 'civic-thread-response-form__message--error civic-form__message--error';
            echo '<p class="civic-thread-response-form__message civic-form__message ' . esc_attr($class) . '">' . esc_html((string) $response['message']) . '</p>';
        }

        echo '<form method="post" class="civic-thread-response-form__form civic-form__form">';
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<input type="hidden" name="civic_thread_response[thread_id]" value="' . esc_attr((string) $threadId) . '">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        $this->renderTextField('name', __('Name', 'civic-engagement'), (string) $values['name'], $errors, true);
        $this->renderEmailField('email', __('Email', 'civic-engagement'), (string) $values['email'], $errors, true);
        $this->renderTextField('phone', __('Phone', 'civic-engagement'), (string) $values['phone'], $errors, false);
        $this->renderTextareaField('address', __('Address', 'civic-engagement'), (string) $values['address'], $errors, false);
        $this->renderTextField('eircode', __('Eircode', 'civic-engagement'), (string) $values['eircode'], $errors, false);
        $this->renderElectoralAreaField((int) ($values['electoral_area_id'] ?? 0));        
        $this->renderTextareaField('response_text', __('Response', 'civic-engagement'), (string) $values['response_text'], $errors, true);
        $this->renderCustomFields($fieldDefinitions, $values, $errors);
        echo $this->captcha->renderWidget('civic-thread-response-form');
        
        $this->renderConsentFields($values);
        echo FormRenderer::privacyConsent('civic-thread-response-form', 'civic_thread_response');
        echo '<p class="civic-thread-response-form__actions civic-form__actions">';
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
     * @param array<int, array<string, mixed>> $fieldDefinitions Custom field definitions.
     * @return array<string, mixed> Structured form response.
     */
    public function processSubmission(int $threadId, array $fieldDefinitions = []): array
    {
        if (!$this->isSubmission($threadId)) {
            return $this->buildResponse(false, false, null, $this->defaultValues($threadId), [], null);
        }

        if (!$this->hasValidNonce()) {
            return $this->buildResponse(true, false, 'Security check failed. Please try again.', $this->defaultValues($threadId), [], 'invalid_nonce');
        }

        $values = $this->sanitizeRequestValues($threadId, $fieldDefinitions);
        $errors = $this->validateValues($values, $fieldDefinitions);
        $captcha = $this->captcha->validateRequest($_POST);

        if (empty($captcha['success'])) {
            $errors['captcha'] = $this->captcha->failureMessage($captcha);
        }

        if (!empty($errors)) {
            $message = isset($errors['captcha'])
                ? $errors['captcha']
                : 'Please check the highlighted fields.';

            return $this->buildResponse(true, false, $message, $values, $errors, 'validation_failed');
        }

        $result = $this->responses->submit($values);

        if (empty($result['success'])) {
            if ('responses_closed' === (string) ($result['error'] ?? '')) {
                return $this->buildResponse(true, false, 'This consultation has closed and is no longer accepting responses.', $values, [], 'responses_closed');
            }

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
        echo FormRenderer::textInput('civic-thread-response-form', 'civic-thread-response-' . $name, 'civic_thread_response[' . $name . ']', $label, $value, $errors, $name, $required);
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
        echo FormRenderer::textInput('civic-thread-response-form', 'civic-thread-response-' . $name, 'civic_thread_response[' . $name . ']', $label, $value, $errors, $name, $required, 'email');
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
        if ('address' === $name) {
            echo FormRenderer::addressTextarea('civic-thread-response-form', 'civic-thread-response-address', 'civic_thread_response[address]', $value, $errors);
            return;
        }

        echo FormRenderer::textarea('civic-thread-response-form', 'civic-thread-response-' . $name, 'civic_thread_response[' . $name . ']', $label, $value, $errors, $name, $required, 5);
    }

    /**
     * Render the electoral area dropdown.
     *
     * @param int $selectedAreaId Selected electoral area ID.
     * @return void
     */
    private function renderElectoralAreaField(int $selectedAreaId): void
    {
        echo '<p class="civic-thread-response-form__field civic-form__field">';
        echo '<label for="civic-thread-response-electoral-area">' . esc_html__('Electoral Area', 'civic-engagement') . '</label>';
        echo '<select id="civic-thread-response-electoral-area" name="civic_thread_response[electoral_area_id]">';
        echo '<option value="">' . esc_html__('Select an electoral area', 'civic-engagement') . '</option>';

        foreach ($this->electoralAreas->getAllActive() as $area) {
            $areaId = isset($area['id']) ? (int) $area['id'] : 0;
            echo '<option value="' . esc_attr((string) $areaId) . '"' . selected($selectedAreaId, $areaId, false) . '>' . esc_html((string) ($area['name'] ?? '')) . '</option>';
        }

        echo '</select>';
        echo '</p>';
    }

    /**
     * Render contact consent options.
     *
     * @param array<string, mixed> $values Current form values.
     * @return void
     */
    private function renderConsentFields(array $values): void
    {
        echo FormRenderer::communicationPreferences('civic-thread-response-form', 'civic_thread_response', $values);
    }

    /**
     * Render consultation-specific custom fields.
     *
     * @param array<int, array<string, mixed>> $fields Custom field definitions.
     * @param array<string, mixed> $values Current form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderCustomFields(array $fields, array $values, array $errors): void
    {
        if (empty($fields)) {
            return;
        }

        foreach ($fields as $field) {
            $fieldKey = $this->fieldKey($field);

            if ('' === $fieldKey) {
                continue;
            }

            $type = (string) ($field['field_type'] ?? '');
            $label = (string) ($field['field_label'] ?? $fieldKey);
            $required = !empty($field['is_required']);
            $value = isset($values['custom_fields'][$fieldKey])
                ? (string) $values['custom_fields'][$fieldKey]
                : '';

            if ('textarea' === $type) {
                $this->renderCustomTextareaField($fieldKey, $label, $value, $errors, $required);
                continue;
            }

            if ('select' === $type) {
                $this->renderCustomSelectField($field, $fieldKey, $label, $value, $errors, $required);
                continue;
            }

            $this->renderCustomTextField($fieldKey, $label, $value, $errors, $required);
        }
    }

    /**
     * Render a custom text field.
     *
     * @param string $fieldKey Field key.
     * @param string $label Field label.
     * @param string $value Field value.
     * @param array<string, string> $errors Validation errors.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderCustomTextField(string $fieldKey, string $label, string $value, array $errors, bool $required): void
    {
        echo FormRenderer::textInput('civic-thread-response-form', 'civic-thread-response-custom-' . $fieldKey, 'civic_thread_response[custom_fields][' . $fieldKey . ']', $label, $value, $errors, 'custom_fields.' . $fieldKey, $required);
    }

    /**
     * Render a custom textarea field.
     *
     * @param string $fieldKey Field key.
     * @param string $label Field label.
     * @param string $value Field value.
     * @param array<string, string> $errors Validation errors.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderCustomTextareaField(string $fieldKey, string $label, string $value, array $errors, bool $required): void
    {
        echo FormRenderer::textarea('civic-thread-response-form', 'civic-thread-response-custom-' . $fieldKey, 'civic_thread_response[custom_fields][' . $fieldKey . ']', $label, $value, $errors, 'custom_fields.' . $fieldKey, $required, 4);
    }

    /**
     * Render a custom select field.
     *
     * @param array<string, mixed> $field Field definition.
     * @param string $fieldKey Field key.
     * @param string $label Field label.
     * @param string $value Selected value.
     * @param array<string, string> $errors Validation errors.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderCustomSelectField(array $field, string $fieldKey, string $label, string $value, array $errors, bool $required): void
    {
        echo '<p class="civic-thread-response-form__field civic-form__field">';
        echo '<label for="civic-thread-response-custom-' . esc_attr($fieldKey) . '">' . esc_html($label) . '</label>';
        echo '<select id="civic-thread-response-custom-' . esc_attr($fieldKey) . '" name="civic_thread_response[custom_fields][' . esc_attr($fieldKey) . ']"' . ($required ? ' required' : '') . '>';
        echo '<option value="">' . esc_html__('Select an option', 'civic-engagement') . '</option>';

        foreach ($this->fieldOptions($field) as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($value, $option, false) . '>' . esc_html($option) . '</option>';
        }

        echo '</select>';
        $this->renderFieldError('custom_fields.' . $fieldKey, $errors);
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
        echo FormRenderer::validationMessage('civic-thread-response-form', $name, $errors);
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
     * @param array<int, array<string, mixed>> $fieldDefinitions Custom field definitions.
     * @return array<string, mixed> Sanitized workflow data.
     */
    private function sanitizeRequestValues(int $threadId, array $fieldDefinitions): array
    {
        $data = $this->requestData();

        return [
            'thread_id' => $threadId,
            'name' => sanitize_text_field($this->requestValue($data, 'name')),
            'email' => sanitize_email($this->requestValue($data, 'email')),
            'phone' => sanitize_text_field($this->requestValue($data, 'phone')),
            'address' => sanitize_textarea_field($this->requestValue($data, 'address')),
            'eircode' => sanitize_text_field($this->requestValue($data, 'eircode')),
            'electoral_area_id' => absint($this->requestValue($data, 'electoral_area_id')),
            'electoral_area' => $this->electoralAreaName(absint($this->requestValue($data, 'electoral_area_id'))),
            'consent_email' => !empty($data['consent_email']) ? 1 : 0,
            'consent_call' => !empty($data['consent_call']) ? 1 : 0,
            'consent_sms' => !empty($data['consent_sms']) ? 1 : 0,
            'consent_post' => !empty($data['consent_post']) ? 1 : 0,
            'response_text' => sanitize_textarea_field($this->requestValue($data, 'response_text')),
            'custom_fields' => $this->sanitizeCustomFields($data, $fieldDefinitions),
        ];
    }

    /**
     * Validate sanitized values.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return array<string, string> Validation errors keyed by field.
     */
    private function validateValues(array $values, array $fieldDefinitions): array
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

        foreach ($fieldDefinitions as $field) {
            $fieldKey = $this->fieldKey($field);

            if ('' === $fieldKey || empty($field['is_required'])) {
                continue;
            }

            $value = isset($values['custom_fields'][$fieldKey])
                ? trim((string) $values['custom_fields'][$fieldKey])
                : '';

            if ('' === $value) {
                $errors['custom_fields.' . $fieldKey] = sprintf(
                    '%s is required.',
                    (string) ($field['field_label'] ?? $fieldKey)
                );
            }
        }

        return $errors;
    }

    /**
     * Sanitize custom field values using field definitions.
     *
     * @param array<string, mixed> $data Request data.
     * @param array<int, array<string, mixed>> $fieldDefinitions Custom field definitions.
     * @return array<string, string>
     */
    private function sanitizeCustomFields(array $data, array $fieldDefinitions): array
    {
        $submitted = isset($data['custom_fields']) && is_array($data['custom_fields'])
            ? $data['custom_fields']
            : [];
        $customFields = [];

        foreach ($fieldDefinitions as $field) {
            $fieldKey = $this->fieldKey($field);

            if ('' === $fieldKey) {
                continue;
            }

            $rawValue = $submitted[$fieldKey] ?? '';
            $value = 'textarea' === (string) ($field['field_type'] ?? '')
                ? sanitize_textarea_field($this->scalarValue($rawValue))
                : sanitize_text_field($this->scalarValue($rawValue));

            $customFields[$fieldKey] = $value;
        }

        return $customFields;
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
            'eircode' => '',
            'electoral_area_id' => 0,
            'electoral_area' => '',
            'consent_email' => 1,
            'consent_call' => 1,
            'consent_sms' => 1,
            'consent_post' => 1,
            'response_text' => '',
            'custom_fields' => [],
        ];
    }

    /**
     * Get supported custom fields for a thread.
     *
     * @param int $threadId Thread ID.
     * @return array<int, array<string, mixed>>
     */
    private function fieldDefinitions(int $threadId): array
    {
        if ($threadId <= 0) {
            return [];
        }

        return array_values(
            array_filter(
                $this->fields->findByThreadId($threadId),
                function (array $field): bool {
                    return in_array((string) ($field['field_type'] ?? ''), ['text', 'textarea', 'select'], true)
                        && '' !== $this->fieldKey($field);
                }
            )
        );
    }

    /**
     * Get a normalized field key.
     *
     * @param array<string, mixed> $field Field definition.
     * @return string Field key.
     */
    private function fieldKey(array $field): string
    {
        return sanitize_key((string) ($field['field_key'] ?? ''));
    }

    /**
     * Decode select field options.
     *
     * @param array<string, mixed> $field Field definition.
     * @return array<int, string>
     */
    private function fieldOptions(array $field): array
    {
        $options = $field['field_options'] ?? '';

        if (is_array($options)) {
            return array_values(array_filter(array_map('strval', $options), 'strlen'));
        }

        if (is_object($options)) {
            return [];
        }

        $decoded = json_decode((string) $options, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded), 'strlen'));
    }

    /**
     * Resolve an electoral area display name.
     *
     * @param int $id Electoral area ID.
     * @return string Electoral area name, or empty string when invalid/inactive.
     */
    private function electoralAreaName(int $id): string
    {
        $area = $this->electoralAreas->findById($id);

        if (!is_array($area) || empty($area['is_active'])) {
            return '';
        }

        return trim((string) ($area['name'] ?? ''));
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
