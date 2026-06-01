<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Fields\Admin;

use CivicPlatform\Modules\Threads\Repository\ThreadFieldRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

/**
 * Renders the admin create/edit form for consultation response fields.
 *
 * This page handles request sanitization, nonce validation, and presentation.
 * Field persistence is delegated to ThreadFieldRepository.
 */
class ThreadFieldEditPage
{
    /**
     * Required capability for thread field administration.
     */
    private const CAPABILITY = 'manage_civic_threads';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'civic_thread_field_save';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_thread_field_nonce';

    /**
     * Supported Version 1 field types.
     *
     * @var array<int, string>
     */
    private array $fieldTypes = [
        'text',
        'textarea',
        'select',
    ];

    /**
     * Thread field repository.
     *
     * @var ThreadFieldRepository
     */
    private ThreadFieldRepository $fields;

    /**
     * Thread repository.
     *
     * @var ThreadRepository
     */
    private ThreadRepository $threads;

    /**
     * Submitted form values retained after validation errors.
     *
     * @var array<string, mixed>|null
     */
    private ?array $submittedValues = null;

    /**
     * @param ThreadFieldRepository $fields Thread field repository.
     * @param ThreadRepository $threads Thread repository.
     */
    public function __construct(ThreadFieldRepository $fields, ThreadRepository $threads)
    {
        $this->fields = $fields;
        $this->threads = $threads;
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

        $threadId = $this->threadId();
        $fieldId = $this->fieldId();
        $thread = $this->threads->findById($threadId);
        $field = $fieldId > 0 ? $this->fields->findById($fieldId) : null;
        $notice = null;

        if (is_array($thread)) {
            $notice = $this->processSubmission($threadId, $fieldId, $field);
            $field = $fieldId > 0 ? $this->fields->findById($fieldId) : $field;
        }

        $values = $this->formValues($field, $threadId);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->pageTitle($fieldId)) . '</h1>';
        echo '<p><a href="' . esc_url($this->fieldsUrl($threadId)) . '">' . esc_html__('Back to Fields', 'civic-engagement') . '</a></p>';
        $this->renderNotice($notice);

        if (!is_array($thread)) {
            $this->renderNotFound(__('Consultation not found.', 'civic-engagement'));
            echo '</div>';

            return;
        }

        if ($fieldId > 0 && (!is_array($field) || (int) ($field['thread_id'] ?? 0) !== $threadId)) {
            $this->renderNotFound(__('Field not found.', 'civic-engagement'));
            echo '</div>';

            return;
        }

        $this->renderForm($values, $thread);
        echo '</div>';
    }

    /**
     * Process a create/edit submission.
     *
     * @param int $threadId Thread ID.
     * @param int $fieldId Field ID.
     * @param array<string, mixed>|null $field Existing field row.
     * @return array{type: string, message: string}|null Notice data.
     */
    private function processSubmission(int $threadId, int $fieldId, ?array $field): ?array
    {
        if (!$this->isSubmission()) {
            return null;
        }

        if (!$this->hasValidNonce()) {
            return $this->notice('error', __('Security check failed. Please try again.', 'civic-engagement'));
        }

        if ($fieldId > 0 && (!is_array($field) || (int) ($field['thread_id'] ?? 0) !== $threadId)) {
            return $this->notice('error', __('Field not found.', 'civic-engagement'));
        }

        $values = $this->sanitizeRequestValues($threadId);
        $this->submittedValues = $values;
        $errors = $this->validateValues($values);

        if (!empty($errors)) {
            return $this->notice('error', implode(' ', $errors));
        }

        if ($fieldId > 0) {
            $updated = $this->fields->update($fieldId, $this->fieldData($values, false));

            if ($updated) {
                $this->redirectToFields($threadId, 'updated');
            }

            return $this->notice('error', __('Field could not be updated.', 'civic-engagement'));
        }

        $created = $this->fields->create($this->fieldData($values, true));

        if ($created > 0) {
            $this->redirectToFields($threadId, 'created');
        }

        return $this->notice('error', __('Field could not be created.', 'civic-engagement'));
    }

    /**
     * Render the create/edit form.
     *
     * @param array<string, mixed> $values Form values.
     * @param array<string, mixed> $thread Thread row.
     * @return void
     */
    private function renderForm(array $values, array $thread): void
    {
        echo '<form method="post">';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->renderReadonlyRow(__('Consultation', 'civic-engagement'), (string) ($thread['title'] ?? ''));
        $this->renderTextInput('field_label', __('Field Label', 'civic-engagement'), (string) $values['field_label'], true);
        $this->renderTextInput('field_key', __('Field Key', 'civic-engagement'), (string) $values['field_key'], true);
        $this->renderTypeSelect((string) $values['field_type']);
        $this->renderRequiredCheckbox(!empty($values['is_required']));
        $this->renderNumberInput('sort_order', __('Sort Order', 'civic-engagement'), (string) $values['sort_order']);
        $this->renderOptionsTextarea((string) $values['field_options']);
        echo '</tbody></table>';
        echo '<input type="hidden" name="civic_thread_field[thread_id]" value="' . esc_attr((string) $values['thread_id']) . '">';
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
        echo '<th scope="row"><label for="civic-thread-field-' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
        echo '<td><input class="regular-text" type="text" id="civic-thread-field-' . esc_attr($name) . '" name="civic_thread_field[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '></td>';
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
        echo '<th scope="row"><label for="civic-thread-field-field-type">' . esc_html__('Field Type', 'civic-engagement') . '</label></th>';
        echo '<td><select id="civic-thread-field-field-type" name="civic_thread_field[field_type]">';

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
        echo '<td><label><input type="checkbox" name="civic_thread_field[is_required]" value="1"' . checked($checked, true, false) . '> ' . esc_html__('Require a response for this field', 'civic-engagement') . '</label></td>';
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
        echo '<th scope="row"><label for="civic-thread-field-' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
        echo '<td><input class="small-text" type="number" id="civic-thread-field-' . esc_attr($name) . '" name="civic_thread_field[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"></td>';
        echo '</tr>';
    }

    /**
     * Render select options textarea.
     *
     * @param string $value Newline-separated options.
     * @return void
     */
    private function renderOptionsTextarea(string $value): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-thread-field-options">' . esc_html__('Select Options', 'civic-engagement') . '</label></th>';
        echo '<td><textarea class="large-text" id="civic-thread-field-options" name="civic_thread_field[field_options]" rows="6">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('For select fields, enter one option per line.', 'civic-engagement') . '</p></td>';
        echo '</tr>';
    }

    /**
     * Sanitize submitted values.
     *
     * @param int $threadId Thread ID.
     * @return array<string, mixed>
     */
    private function sanitizeRequestValues(int $threadId): array
    {
        $data = $this->requestData();

        return [
            'thread_id' => $threadId,
            'field_label' => sanitize_text_field($this->requestValue($data, 'field_label')),
            'field_key' => sanitize_key($this->requestValue($data, 'field_key')),
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
     * @return array<int, string> Error messages.
     */
    private function validateValues(array $values): array
    {
        $errors = [];

        if ('' === $values['field_label']) {
            $errors[] = __('Field label is required.', 'civic-engagement');
        }

        if ('' === $values['field_key']) {
            $errors[] = __('Field key is required.', 'civic-engagement');
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
     * @param bool $includeThread Whether to include thread_id for create.
     * @return array<string, mixed>
     */
    private function fieldData(array $values, bool $includeThread): array
    {
        $data = [
            'field_label' => $values['field_label'],
            'field_key' => $values['field_key'],
            'field_type' => $values['field_type'],
            'field_options' => $this->optionsJson((string) $values['field_options']),
            'sort_order' => (int) $values['sort_order'],
            'is_required' => (int) $values['is_required'],
        ];

        if ($includeThread) {
            $data['thread_id'] = (int) $values['thread_id'];
        }

        return $data;
    }

    /**
     * Build default/current form values.
     *
     * @param array<string, mixed>|null $field Field row.
     * @param int $threadId Thread ID.
     * @return array<string, mixed>
     */
    private function formValues(?array $field, int $threadId): array
    {
        if (is_array($this->submittedValues)) {
            return $this->submittedValues;
        }

        return [
            'thread_id' => $threadId,
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
        if (!isset($_POST['civic_thread_field'])) {
            return [];
        }

        $data = wp_unslash($_POST['civic_thread_field']);

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

        return 'POST' === $method && isset($_POST['civic_thread_field']);
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
     * @param int $threadId Thread ID.
     * @return string Fields URL.
     */
    private function fieldsUrl(int $threadId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-thread-fields',
                'thread_id' => $threadId,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Redirect to the field listing page after successful save.
     *
     * @param int $threadId Thread ID.
     * @param string $status Status query flag.
     * @return void
     */
    private function redirectToFields(int $threadId, string $status): void
    {
        $args = [
            'page' => 'civic-thread-fields',
            'thread_id' => $threadId,
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
            ? __('Edit Consultation Field', 'civic-engagement')
            : __('Add Consultation Field', 'civic-engagement');
    }

    /**
     * Get sanitized requested thread ID.
     *
     * @return int Thread ID.
     */
    private function threadId(): int
    {
        if (isset($_POST['civic_thread_field']['thread_id'])) {
            $threadId = wp_unslash($_POST['civic_thread_field']['thread_id']);
        } elseif (isset($_GET['thread_id'])) {
            $threadId = wp_unslash($_GET['thread_id']);
        } else {
            return 0;
        }

        if (is_array($threadId) || is_object($threadId)) {
            return 0;
        }

        return absint($threadId);
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
