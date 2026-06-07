<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Registrations\Frontend;

use CivicPlatform\Modules\Events\Registrations\Services\EventRegistrationService;
use CivicPlatform\Repositories\ElectoralAreaRepository;

/**
 * Handles public event registration form rendering and submission processing.
 *
 * This frontend handler validates request intent, sanitizes submitted values,
 * and delegates the workflow to EventRegistrationService.
 */
class EventRegistrationForm
{
    /**
     * Form action value.
     */
    private const ACTION = 'civic_event_registration_submit';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'civic_event_registration_form';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_event_registration_nonce';

    /**
     * Event registration workflow service.
     *
     * @var EventRegistrationService
     */
    private EventRegistrationService $registrations;

    /**
     * Electoral area repository.
     *
     * @var ElectoralAreaRepository
     */
    private ElectoralAreaRepository $electoralAreas;

    /**
     * @param EventRegistrationService $registrations Event registration workflow service.
     * @param ElectoralAreaRepository $electoralAreas Electoral area repository.
     */
    public function __construct(
        EventRegistrationService $registrations,
        ElectoralAreaRepository $electoralAreas
    ) {
        $this->registrations = $registrations;
        $this->electoralAreas = $electoralAreas;
    }

    /**
     * Render the registration form for an event.
     *
     * @param array<string, mixed> $event Event row.
     * @return string Rendered form markup.
     */
    public function render(array $event): string
    {
        $eventId = isset($event['id']) ? (int) $event['id'] : 0;
        $response = $this->processSubmission($eventId);
        $values = $response['values'];
        $errors = $response['errors'];

        ob_start();

        echo '<section class="civic-event-registration-form">';
        echo '<h2>' . esc_html__('Register for this Event', 'civic-engagement') . '</h2>';

        if (!empty($response['message'])) {
            $class = !empty($response['success']) ? 'civic-event-registration-form__message--success' : 'civic-event-registration-form__message--error';
            echo '<p class="civic-event-registration-form__message ' . esc_attr($class) . '">' . esc_html((string) $response['message']) . '</p>';
        }

        echo '<form method="post">';
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<input type="hidden" name="civic_event_registration[event_id]" value="' . esc_attr((string) $eventId) . '">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        $this->renderTextField('name', __('Name', 'civic-engagement'), (string) $values['name'], $errors, true);
        $this->renderEmailField('email', __('Email', 'civic-engagement'), (string) $values['email'], $errors, true);
        $this->renderTextField('phone', __('Phone', 'civic-engagement'), (string) $values['phone'], $errors, false);
        $this->renderTextareaField('address', __('Address', 'civic-engagement'), (string) $values['address'], $errors, false);
        $this->renderTextField('eircode', __('Eircode', 'civic-engagement'), (string) $values['eircode'], $errors, false);
        $this->renderElectoralAreaField((int) ($values['electoral_area_id'] ?? 0));

        echo '<p>';
        echo '<button type="submit" class="button button-primary">';
        echo esc_html__('Submit Registration', 'civic-engagement');
        echo '</button>';
        echo '</p>';

        echo '</form>';
        echo '</section>';

        return (string) ob_get_clean();
    }

    /**
     * Process a submitted registration form for the given event.
     *
     * @param int $eventId Current event ID.
     * @return array<string, mixed> Structured form response.
     */
    public function processSubmission(int $eventId): array
    {
        if (!$this->isSubmission($eventId)) {
            return $this->buildResponse(false, false, null, $this->defaultValues($eventId), [], null);
        }

        if (!$this->hasValidNonce()) {
            return $this->buildResponse(true, false, 'Security check failed. Please try again.', $this->defaultValues($eventId), [], 'invalid_nonce');
        }

        $values = $this->sanitizeRequestValues($eventId);
        $errors = $this->validateValues($values);

        if (!empty($errors)) {
            return $this->buildResponse(true, false, 'Please check the highlighted fields.', $values, $errors, 'validation_failed');
        }

        $result = $this->registrations->submit($values);

        if (empty($result['success'])) {
            return $this->buildResponse(true, false, 'We could not submit your registration. Please try again.', $values, [], (string) ($result['error'] ?? 'submission_failed'));
        }

        return $this->buildResponse(true, true, 'Thank you. Your registration has been submitted.', $this->defaultValues($eventId), [], null, $result);
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
        echo '<label for="civic-event-registration-' . esc_attr($name) . '">' . esc_html($label) . '</label><br>';
        echo '<input type="text" id="civic-event-registration-' . esc_attr($name) . '" name="civic_event_registration[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '>';
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
        echo '<label for="civic-event-registration-' . esc_attr($name) . '">' . esc_html($label) . '</label><br>';
        echo '<input type="email" id="civic-event-registration-' . esc_attr($name) . '" name="civic_event_registration[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '>';
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
        echo '<label for="civic-event-registration-' . esc_attr($name) . '">' . esc_html($label) . '</label><br>';
        echo '<textarea id="civic-event-registration-' . esc_attr($name) . '" name="civic_event_registration[' . esc_attr($name) . ']" rows="4"' . ($required ? ' required' : '') . '>' . esc_textarea($value) . '</textarea>';
        $this->renderFieldError($name, $errors);
        echo '</p>';
    }

    /**
     * Render the electoral area dropdown.
     *
     * @param int $selectedAreaId Selected electoral area ID.
     * @return void
     */
    private function renderElectoralAreaField(int $selectedAreaId): void
    {
        echo '<p>';
        echo '<label for="civic-event-registration-electoral-area">' . esc_html__('Electoral Area', 'civic-engagement') . '</label><br>';
        echo '<select id="civic-event-registration-electoral-area" name="civic_event_registration[electoral_area_id]">';
        echo '<option value="">' . esc_html__('Select an electoral area', 'civic-engagement') . '</option>';

        foreach ($this->electoralAreas->getAllActive() as $area) {
            $areaId = isset($area['id']) ? (int) $area['id'] : 0;
            echo '<option value="' . esc_attr((string) $areaId) . '"' . selected($selectedAreaId, $areaId, false) . '>' . esc_html((string) ($area['name'] ?? '')) . '</option>';
        }

        echo '</select>';
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

        echo '<br><span class="civic-event-registration-form__error">' . esc_html($errors[$name]) . '</span>';
    }

    /**
     * Check whether the current request is a registration submission for this event.
     *
     * @param int $eventId Current event ID.
     * @return bool True when submitted.
     */
    private function isSubmission(int $eventId): bool
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
        $submittedEventId = isset($data['event_id']) ? absint($this->requestScalar($data['event_id'])) : 0;

        return $eventId > 0 && $submittedEventId === $eventId;
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
     * @param int $eventId Current event ID.
     * @return array<string, mixed> Sanitized workflow data.
     */
    private function sanitizeRequestValues(int $eventId): array
    {
        $data = $this->requestData();
        $electoralAreaId = absint($this->requestValue($data, 'electoral_area_id'));

        return [
            'event_id' => $eventId,
            'name' => sanitize_text_field($this->requestValue($data, 'name')),
            'email' => sanitize_email($this->requestValue($data, 'email')),
            'phone' => sanitize_text_field($this->requestValue($data, 'phone')),
            'address' => sanitize_textarea_field($this->requestValue($data, 'address')),
            'eircode' => sanitize_text_field($this->requestValue($data, 'eircode')),
            'electoral_area_id' => $electoralAreaId,
            'electoral_area' => $this->electoralAreaName($electoralAreaId),
            'registration_data' => [],
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

        return $errors;
    }

    /**
     * Get structured registration request data.
     *
     * @return array<string, mixed> Unslashed request data.
     */
    private function requestData(): array
    {
        if (!isset($_POST['civic_event_registration'])) {
            return [];
        }

        $data = wp_unslash($_POST['civic_event_registration']);

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
     * Return default form values.
     *
     * @param int $eventId Current event ID.
     * @return array<string, mixed>
     */
    private function defaultValues(int $eventId): array
    {
        return [
            'event_id' => $eventId,
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'eircode' => '',
            'electoral_area_id' => 0,
            'electoral_area' => '',
            'registration_data' => [],
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
