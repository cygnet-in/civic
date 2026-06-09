<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Admin;

use CivicPlatform\Modules\Events\Repository\EventFieldRepository;
use CivicPlatform\Modules\Events\Repository\EventRepository;

/**
 * Renders the admin create/edit form for event registration fields.
 *
 * This page handles request sanitization, nonce validation, and presentation.
 * Field persistence is delegated to EventFieldRepository.
 */
class EventFieldEditPage
{
    /**
     * Required capability for event field administration.
     */
    private const CAPABILITY = 'manage_civic_events';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'civic_event_field_save';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_event_field_nonce';

    /**
     * Supported event registration field types.
     *
     * @var array<int, string>
     */
    private array $fieldTypes = [
        'text',
        'textarea',
        'dropdown',
    ];

    /**
     * Event field repository.
     *
     * @var EventFieldRepository
     */
    private EventFieldRepository $fields;

    /**
     * Event repository.
     *
     * @var EventRepository
     */
    private EventRepository $events;

    /**
     * Submitted form values retained after validation errors.
     *
     * @var array<string, mixed>|null
     */
    private ?array $submittedValues = null;

    /**
     * @param EventFieldRepository $fields Event field repository.
     * @param EventRepository $events Event repository.
     */
    public function __construct(EventFieldRepository $fields, EventRepository $events)
    {
        $this->fields = $fields;
        $this->events = $events;
    }

    /**
     * Render the field create/edit page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $eventId = $this->eventId();
        $fieldId = $this->fieldId();
        $event = $this->events->findById($eventId);
        $field = $fieldId > 0 ? $this->fields->findById($fieldId) : null;
        $notice = null;

        if (is_array($event)) {
            $notice = $this->processSubmission($eventId, $fieldId, $field);
            $field = $fieldId > 0 ? $this->fields->findById($fieldId) : $field;
        }

        $values = $this->formValues($field, $eventId);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->pageTitle($fieldId)) . '</h1>';
        echo '<p><a href="' . esc_url($this->fieldsUrl($eventId)) . '">' . esc_html__('Back to Fields', 'civic-engagement') . '</a></p>';
        $this->renderNotice($notice);

        if (!is_array($event)) {
            $this->renderNotFound(__('Event not found.', 'civic-engagement'));
            echo '</div>';

            return;
        }

        if ($fieldId > 0 && (!is_array($field) || (int) ($field['event_id'] ?? 0) !== $eventId)) {
            $this->renderNotFound(__('Field not found.', 'civic-engagement'));
            echo '</div>';

            return;
        }

        $this->renderForm($values, $event);
        echo '</div>';
    }

    /**
     * Process a create/edit submission.
     *
     * @param int $eventId Event ID.
     * @param int $fieldId Field ID.
     * @param array<string, mixed>|null $field Existing field row.
     * @return array{type: string, message: string}|null Notice data.
     */
    private function processSubmission(int $eventId, int $fieldId, ?array $field): ?array
    {
        if (!$this->isSubmission()) {
            return null;
        }

        if (!$this->hasValidNonce()) {
            return $this->notice('error', __('Security check failed. Please try again.', 'civic-engagement'));
        }

        if ($fieldId > 0 && (!is_array($field) || (int) ($field['event_id'] ?? 0) !== $eventId)) {
            return $this->notice('error', __('Field not found.', 'civic-engagement'));
        }

        $values = $this->sanitizeRequestValues($eventId, 0 === $fieldId);
        $this->submittedValues = $values;
        $errors = $this->validateValues($values, $fieldId);

        if (!empty($errors)) {
            return $this->notice('error', implode(' ', $errors));
        }

        if ($fieldId > 0) {
            $updated = $this->fields->update($fieldId, $this->fieldData($values, false));

            if ($updated) {
                $this->redirectToFields($eventId, 'updated');
            }

            return $this->notice('error', __('Field could not be updated.', 'civic-engagement'));
        }

        $created = $this->fields->create($this->fieldData($values, true));

        if ($created > 0) {
            $this->redirectToFields($eventId, 'created');
        }

        return $this->notice('error', __('Field could not be created.', 'civic-engagement'));
    }

    /**
     * Render the create/edit form.
     *
     * @param array<string, mixed> $values Form values.
     * @param array<string, mixed> $event Event row.
     * @return void
     */
    private function renderForm(array $values, array $event): void
    {
        echo '<form method="post">';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->renderReadonlyRow(__('Event', 'civic-engagement'), (string) ($event['title'] ?? ''));
        $this->renderTextInput('field_label', __('Field Label', 'civic-engagement'), (string) $values['field_label'], true);
        $this->renderTextInput('field_key', __('Field Key', 'civic-engagement'), (string) $values['field_key'], false);
        $this->renderTypeSelect((string) $values['field_type']);
        $this->renderRequiredCheckbox(!empty($values['is_required']));
        $this->renderNumberInput('sort_order', __('Sort Order', 'civic-engagement'), (string) $values['sort_order']);
        $this->renderOptionsTextarea((string) $values['field_options']);
        echo '</tbody></table>';
        echo '<input type="hidden" name="civic_event_field[event_id]" value="' . esc_attr((string) $values['event_id']) . '">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        submit_button(__('Save Field', 'civic-engagement'));
        echo '</form>';
    }

    /**
     * Render a readonly form row.
     *
     * @param string $label Row label.
     * @param string $value Row value.
     * @return void
     */
    private function renderReadonlyRow(string $label, string $value): void
    {
        echo '<tr>';
        echo '<th scope="row">' . esc_html($label) . '</th>';
        echo '<td>' . esc_html($value) . '</td>';
        echo '</tr>';
    }

    /**
     * Render a text input row.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $value Field value.
     * @param bool $required Whether the field is required.
     * @return void
     */
    private function renderTextInput(string $name, string $label, string $value, bool $required): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-event-field-' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
        echo '<td><input class="regular-text" type="text" id="civic-event-field-' . esc_attr($name) . '" name="civic_event_field[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '>';

        if ('field_key' === $name) {
            echo '<p class="description">' . esc_html__('Leave blank on new fields to generate from the field label.', 'civic-engagement') . '</p>';
        }

        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render field type selector.
     *
     * @param string $value Selected field type.
     * @return void
     */
    private function renderTypeSelect(string $value): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-event-field-field-type">' . esc_html__('Field Type', 'civic-engagement') . '</label></th>';
        echo '<td><select id="civic-event-field-field-type" name="civic_event_field[field_type]">';

        foreach ($this->fieldTypes as $fieldType) {
            echo '<option value="' . esc_attr($fieldType) . '"' . selected($value, $fieldType, false) . '>' . esc_html($fieldType) . '</option>';
        }

        echo '</select></td>';
        echo '</tr>';
    }

    /**
     * Render required checkbox.
     *
     * @param bool $checked Whether field is required.
     * @return void
     */
    private function renderRequiredCheckbox(bool $checked): void
    {
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Required', 'civic-engagement') . '</th>';
        echo '<td><label><input type="checkbox" name="civic_event_field[is_required]" value="1"' . checked($checked, true, false) . '> ' . esc_html__('Require a response for this field', 'civic-engagement') . '</label></td>';
        echo '</tr>';
    }

    /**
     * Render a number input row.
     *
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $value Field value.
     * @return void
     */
    private function renderNumberInput(string $name, string $label, string $value): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-event-field-' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
        echo '<td><input class="small-text" type="number" id="civic-event-field-' . esc_attr($name) . '" name="civic_event_field[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"></td>';
        echo '</tr>';
    }

    /**
     * Render dropdown options textarea.
     *
     * @param string $value Newline-separated options.
     * @return void
     */
    private function renderOptionsTextarea(string $value): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-event-field-options">' . esc_html__('Dropdown Options', 'civic-engagement') . '</label></th>';
        echo '<td><textarea class="large-text" id="civic-event-field-options" name="civic_event_field[field_options]" rows="6">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('For dropdown fields, enter one option per line.', 'civic-engagement') . '</p></td>';
        echo '</tr>';
    }

    /**
     * Sanitize submitted values.
     *
     * @param int $eventId Event ID.
     * @param bool $isCreate Whether this is a create request.
     * @return array<string, mixed>
     */
    private function sanitizeRequestValues(int $eventId, bool $isCreate): array
    {
        $data = $this->requestData();
        $fieldLabel = sanitize_text_field($this->requestValue($data, 'field_label'));
        $fieldKey = sanitize_key($this->requestValue($data, 'field_key'));

        if ($isCreate && '' === $fieldKey) {
            $fieldKey = sanitize_key($fieldLabel);
        }

        return [
            'event_id' => $eventId,
            'field_label' => $fieldLabel,
            'field_key' => $fieldKey,
            'field_type' => sanitize_key($this->requestValue($data, 'field_type')),
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'sort_order' => isset($data['sort_order']) ? (int) $this->requestValue($data, 'sort_order') : 0,
            'field_options' => sanitize_textarea_field($this->requestValue($data, 'field_options')),
        ];
    }

    /**
     * Validate sanitized values.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @param int $fieldId Current field ID, or 0 when creating.
     * @return array<int, string> Error messages.
     */
    private function validateValues(array $values, int $fieldId): array
    {
        $errors = [];

        if ('' === $values['field_label']) {
            $errors[] = __('Field label is required.', 'civic-engagement');
        }

        if ('' === $values['field_key']) {
            $errors[] = __('Field key is required.', 'civic-engagement');
        }

        if (
            '' !== $values['field_key']
            && $this->fields->fieldKeyExists((int) $values['event_id'], (string) $values['field_key'], $fieldId)
        ) {
            $errors[] = __('Field key already exists for this event.', 'civic-engagement');
        }

        if (!in_array((string) $values['field_type'], $this->fieldTypes, true)) {
            $errors[] = __('Field type is invalid.', 'civic-engagement');
        }

        return $errors;
    }

    /**
     * Build repository data.
     *
     * @param array<string, mixed> $values Sanitized form values.
     * @param bool $includeEvent Whether to include event_id for create.
     * @return array<string, mixed>
     */
    private function fieldData(array $values, bool $includeEvent): array
    {
        $data = [
            'field_label' => $values['field_label'],
            'field_key' => $values['field_key'],
            'field_type' => $values['field_type'],
            'field_options' => $this->optionsJson((string) $values['field_options']),
            'sort_order' => (int) $values['sort_order'],
            'is_required' => (int) $values['is_required'],
        ];

        if ($includeEvent) {
            $data['event_id'] = (int) $values['event_id'];
        }

        return $data;
    }

    /**
     * Build default/current form values.
     *
     * @param array<string, mixed>|null $field Field row.
     * @param int $eventId Event ID.
     * @return array<string, mixed>
     */
    private function formValues(?array $field, int $eventId): array
    {
        if (is_array($this->submittedValues)) {
            return $this->submittedValues;
        }

        return [
            'event_id' => $eventId,
            'field_label' => is_array($field) ? (string) ($field['field_label'] ?? '') : '',
            'field_key' => is_array($field) ? (string) ($field['field_key'] ?? '') : '',
            'field_type' => is_array($field) ? (string) ($field['field_type'] ?? 'text') : 'text',
            'is_required' => is_array($field) ? (int) ($field['is_required'] ?? 0) : 0,
            'sort_order' => is_array($field) ? (int) ($field['sort_order'] ?? 0) : 0,
            'field_options' => is_array($field) ? $this->optionsText($field['field_options'] ?? '') : '',
        ];
    }

    /**
     * Convert newline-separated options to JSON.
     *
     * @param string $value Newline-separated options.
     * @return string JSON options.
     */
    private function optionsJson(string $value): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $value);
        $options = [];

        foreach (is_array($lines) ? $lines : [] as $line) {
            $line = trim((string) $line);

            if ('' !== $line) {
                $options[] = $line;
            }
        }

        $encoded = wp_json_encode($options);

        return false === $encoded ? '[]' : $encoded;
    }

    /**
     * Convert stored JSON options to newline-separated text.
     *
     * @param mixed $value Stored options.
     * @return string Newline-separated options.
     */
    private function optionsText($value): string
    {
        if (is_array($value)) {
            return implode("\n", array_map('strval', $value));
        }

        if (is_object($value)) {
            return '';
        }

        $decoded = json_decode((string) $value, true);

        if (!is_array($decoded)) {
            return '';
        }

        return implode("\n", array_map('strval', $decoded));
    }

    /**
     * Get structured request data.
     *
     * @return array<string, mixed> Unslashed request data.
     */
    private function requestData(): array
    {
        if (!isset($_POST['civic_event_field'])) {
            return [];
        }

        $data = wp_unslash($_POST['civic_event_field']);

        return is_array($data) ? $data : [];
    }

    /**
     * Get scalar request value.
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
     * Check whether the form was submitted.
     *
     * @return bool True when submitted.
     */
    private function isSubmission(): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : '';

        return 'POST' === $method && isset($_POST['civic_event_field']);
    }

    /**
     * Validate the save nonce.
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
     * Build a notice.
     *
     * @param string $type Notice type.
     * @param string $message Notice message.
     * @return array{type: string, message: string}
     */
    private function notice(string $type, string $message): array
    {
        return [
            'type' => $type,
            'message' => $message,
        ];
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
     * Render an admin error.
     *
     * @param string $message Error message.
     * @return void
     */
    private function renderNotFound(string $message): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }

    /**
     * Build the fields listing URL.
     *
     * @param int $eventId Event ID.
     * @return string Fields URL.
     */
    private function fieldsUrl(int $eventId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-event-fields',
                'event_id' => $eventId,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Redirect to the field listing page after successful save.
     *
     * @param int $eventId Event ID.
     * @param string $status Status query flag.
     * @return void
     */
    private function redirectToFields(int $eventId, string $status): void
    {
        $args = [
            'page' => 'civic-event-fields',
            'event_id' => $eventId,
        ];

        if ('created' === $status) {
            $args['created'] = 1;
        }

        if ('updated' === $status) {
            $args['updated'] = 1;
        }

        $url = add_query_arg($args, admin_url('admin.php'));

        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        }

        echo '<script>window.location.href = ' . wp_json_encode($url) . ';</script>';
        exit;
    }

    /**
     * Build the page title.
     *
     * @param int $fieldId Field ID.
     * @return string Page title.
     */
    private function pageTitle(int $fieldId): string
    {
        return $fieldId > 0
            ? __('Edit Event Field', 'civic-engagement')
            : __('Add Event Field', 'civic-engagement');
    }

    /**
     * Get sanitized requested event ID.
     *
     * @return int Event ID.
     */
    private function eventId(): int
    {
        if (isset($_POST['civic_event_field']['event_id'])) {
            $eventId = wp_unslash($_POST['civic_event_field']['event_id']);
        } elseif (isset($_GET['event_id'])) {
            $eventId = wp_unslash($_GET['event_id']);
        } else {
            return 0;
        }

        if (is_array($eventId) || is_object($eventId)) {
            return 0;
        }

        return absint($eventId);
    }

    /**
     * Get sanitized requested field ID.
     *
     * @return int Field ID.
     */
    private function fieldId(): int
    {
        if (!isset($_GET['field_id'])) {
            return 0;
        }

        $fieldId = wp_unslash($_GET['field_id']);

        if (is_array($fieldId) || is_object($fieldId)) {
            return 0;
        }

        return absint($fieldId);
    }
}
