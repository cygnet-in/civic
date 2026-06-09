<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Modules\Schedules\Services\ScheduleService;

/**
 * Renders and processes the schedule add/edit admin page.
 *
 * This page handles request sanitization, nonce checks, redirects, and
 * presentation only. Schedule persistence is delegated to ScheduleService.
 */
class ScheduleEditPage
{
    private const CAPABILITY = 'manage_civic_schedules';
    private const ACTION = 'civic_schedule_save';
    private const NONCE_ACTION = 'civic_schedule_save_';
    private const NONCE_FIELD = 'civic_schedule_nonce';

    /**
     * Supported schedule types.
     *
     * @var array<int, string>
     */
    private array $types = [
        'meeting',
        'motion',
        'question',
        'rep_followup',
        'public_announcement',
        'other',
    ];

    /**
     * Supported schedule statuses.
     *
     * @var array<int, string>
     */
    private array $statuses = [
        'open',
        'pending',
        'scheduled',
        'completed',
        'cancelled',
    ];

    /**
     * Schedule repository.
     *
     * @var ScheduleRepository
     */
    private ScheduleRepository $schedules;

    /**
     * Schedule service.
     *
     * @var ScheduleService
     */
    private ScheduleService $service;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    /**
     * @param ScheduleRepository $schedules Schedule repository.
     * @param ScheduleService $service Schedule service.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(ScheduleRepository $schedules, ScheduleService $service, DateHelper $dates)
    {
        $this->schedules = $schedules;
        $this->service = $service;
        $this->dates = $dates;
    }

    /**
     * Render and process the schedule add/edit page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $scheduleId = $this->scheduleId();
        $schedule = $scheduleId > 0 ? $this->schedules->findById($scheduleId) : null;
        $isView = $this->isViewMode();
        $response = $isView ? $this->buildResponse(false, false, null, [], [], null) : $this->processSubmission($scheduleId);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->pageTitle($scheduleId, $isView)) . '</h1>';
        echo '<p><a href="' . esc_url($this->listUrl()) . '">' . esc_html__('Back to Schedules', 'civic-engagement') . '</a></p>';

        if ($scheduleId > 0 && !is_array($schedule)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        if ($isView && is_array($schedule)) {
            $this->renderView($schedule);
            echo '</div>';

            return;
        }

        $values = !empty($response['submitted']) && empty($response['success'])
            ? $response['values']
            : $this->valuesFromSchedule($schedule);

        $this->renderMessage($response);
        $this->renderForm($scheduleId, $values, $response['errors']);
        echo '</div>';
    }

    /**
     * Process a submitted schedule save request.
     *
     * @param int $scheduleId Schedule ID, or 0 when creating.
     * @return array<string, mixed> Form response.
     */
    private function processSubmission(int $scheduleId): array
    {
        if (!$this->isSubmission()) {
            return $this->buildResponse(false, false, null, $this->defaultValues(), [], null);
        }

        $values = $this->sanitizeRequestValues();

        if (!$this->hasValidNonce($scheduleId)) {
            return $this->buildResponse(true, false, 'Security check failed. Please try again.', $values, [], 'invalid_nonce');
        }

        $errors = $this->validateValues($values);

        if (!empty($errors)) {
            return $this->buildResponse(true, false, 'Please check the highlighted fields.', $values, $errors, 'validation_failed');
        }

        if ($scheduleId > 0) {
            $updated = $this->service->update($scheduleId, $this->buildScheduleData($values));

            if (!$updated) {
                return $this->buildResponse(true, false, 'The schedule could not be updated.', $values, [], 'schedule_update_failed');
            }

            $this->redirectToList(['updated' => 1]);
        }

        $newScheduleId = $this->service->create($this->buildScheduleData($values));

        if ($newScheduleId <= 0) {
            return $this->buildResponse(true, false, 'The schedule could not be created.', $values, [], 'schedule_create_failed');
        }

        $this->redirectToList(['created' => 1]);
    }

    /**
     * Render the schedule form.
     *
     * @param int $scheduleId Schedule ID, or 0 when creating.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderForm(int $scheduleId, array $values, array $errors): void
    {
        echo '<form method="post">';
        wp_nonce_field(self::NONCE_ACTION . $scheduleId, self::NONCE_FIELD);
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->renderTypeSelect($values, $errors);
        $this->renderTextInput('title', __('Title', 'civic-engagement'), $values, $errors, true);
        $this->renderTextarea('details', __('Details', 'civic-engagement'), $values, $errors, 6);
        $this->renderTextInput('reported_by', __('Reported By', 'civic-engagement'), $values, $errors, false);
        $this->renderStatusSelect($values, $errors);
        $this->renderTextInput('review_date', __('Review Date', 'civic-engagement'), $values, $errors, false);
        $this->renderTextarea('internal_comment', __('Internal Comment', 'civic-engagement'), $values, $errors, 5);
        $this->renderTextarea('response', __('Response', 'civic-engagement'), $values, $errors, 5);
        $this->renderCheckbox('is_public', __('Public', 'civic-engagement'), __('Make this schedule visible publicly', 'civic-engagement'), $values);
        $this->renderCheckbox('is_archived', __('Archived', 'civic-engagement'), __('Mark this schedule as archived', 'civic-engagement'), $values);
        $this->renderTextInput('start_date', __('Start Date', 'civic-engagement'), $values, $errors, false);
        $this->renderTextInput('end_date', __('End Date', 'civic-engagement'), $values, $errors, false);
        $this->renderTextInput('source_type', __('Source Type', 'civic-engagement'), $values, $errors, false);
        $this->renderNumberInput('source_id', __('Source ID', 'civic-engagement'), $values, $errors);
        $this->renderNumberInput('created_by', __('Created By User ID', 'civic-engagement'), $values, $errors);
        echo '</tbody></table>';
        submit_button($scheduleId > 0 ? __('Update Schedule', 'civic-engagement') : __('Create Schedule', 'civic-engagement'));
        echo '</form>';
    }

    /**
     * Render read-only schedule details for View mode.
     *
     * @param array<string, mixed> $schedule Schedule row.
     * @return void
     */
    private function renderView(array $schedule): void
    {
        $scheduleId = isset($schedule['id']) ? (int) $schedule['id'] : 0;

        echo '<p><a class="button" href="' . esc_url($this->editUrl($scheduleId)) . '">' . esc_html__('Edit Schedule', 'civic-engagement') . '</a></p>';
        echo '<table class="widefat striped"><tbody>';

        foreach ($this->detailRows($schedule) as $label => $value) {
            $this->renderDetailRow($label, $value);
        }

        echo '</tbody></table>';
    }

    /**
     * Build read-only detail rows.
     *
     * @param array<string, mixed> $schedule Schedule row.
     * @return array<string, string>
     */
    private function detailRows(array $schedule): array
    {
        return [
            __('ID', 'civic-engagement') => (string) ($schedule['id'] ?? ''),
            __('Type', 'civic-engagement') => (string) ($schedule['type'] ?? ''),
            __('Title', 'civic-engagement') => (string) ($schedule['title'] ?? ''),
            __('Details', 'civic-engagement') => (string) ($schedule['details'] ?? ''),
            __('Reported By', 'civic-engagement') => (string) ($schedule['reported_by'] ?? ''),
            __('Status', 'civic-engagement') => (string) ($schedule['status'] ?? ''),
            __('Review Date', 'civic-engagement') => $this->dates->formatDateTime($schedule['review_date'] ?? null),
            __('Internal Comment', 'civic-engagement') => (string) ($schedule['internal_comment'] ?? ''),
            __('Response', 'civic-engagement') => (string) ($schedule['response'] ?? ''),
            __('Public', 'civic-engagement') => !empty($schedule['is_public']) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement'),
            __('Archived', 'civic-engagement') => !empty($schedule['is_archived']) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement'),
            __('Start Date', 'civic-engagement') => $this->dates->formatDateTime($schedule['start_date'] ?? null),
            __('End Date', 'civic-engagement') => $this->dates->formatDateTime($schedule['end_date'] ?? null),
            __('Source Type', 'civic-engagement') => (string) ($schedule['source_type'] ?? ''),
            __('Source ID', 'civic-engagement') => (string) ($schedule['source_id'] ?? ''),
            __('Created By', 'civic-engagement') => (string) ($schedule['created_by'] ?? ''),
            __('Created At', 'civic-engagement') => $this->dates->formatDateTime($schedule['created_at'] ?? null),
            __('Updated At', 'civic-engagement') => $this->dates->formatDateTime($schedule['updated_at'] ?? null),
        ];
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
        echo '<th scope="row"><label for="civic-schedule-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td><input class="regular-text" id="civic-schedule-' . esc_attr($key) . '" name="civic_schedule[' . esc_attr($key) . ']" type="text" value="' . esc_attr((string) ($values[$key] ?? '')) . '"' . ($required ? ' required' : '') . '>';
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render a number input field.
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
        echo '<th scope="row"><label for="civic-schedule-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td><input class="small-text" id="civic-schedule-' . esc_attr($key) . '" name="civic_schedule[' . esc_attr($key) . ']" type="number" value="' . esc_attr((string) ($values[$key] ?? '')) . '">';
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
        echo '<th scope="row"><label for="civic-schedule-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td><textarea class="large-text" id="civic-schedule-' . esc_attr($key) . '" name="civic_schedule[' . esc_attr($key) . ']" rows="' . esc_attr((string) $rows) . '">' . esc_textarea((string) ($values[$key] ?? '')) . '</textarea>';
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render schedule type selector.
     *
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderTypeSelect(array $values, array $errors): void
    {
        $this->renderSelect('type', __('Type', 'civic-engagement'), $this->types, (string) ($values['type'] ?? 'meeting'), $errors);
    }

    /**
     * Render schedule status selector.
     *
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderStatusSelect(array $values, array $errors): void
    {
        $this->renderSelect('status', __('Status', 'civic-engagement'), $this->statuses, (string) ($values['status'] ?? 'open'), $errors);
    }

    /**
     * Render a select field.
     *
     * @param string $key Field key.
     * @param string $label Field label.
     * @param array<int, string> $options Options.
     * @param string $value Selected value.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderSelect(string $key, string $label, array $options, string $value, array $errors): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-schedule-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td><select id="civic-schedule-' . esc_attr($key) . '" name="civic_schedule[' . esc_attr($key) . ']">';

        foreach ($options as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($value, $option, false) . '>' . esc_html($option) . '</option>';
        }

        echo '</select>';
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render a checkbox field.
     *
     * @param string $key Field key.
     * @param string $label Row label.
     * @param string $text Checkbox label.
     * @param array<string, mixed> $values Form values.
     * @return void
     */
    private function renderCheckbox(string $key, string $label, string $text, array $values): void
    {
        echo '<tr>';
        echo '<th scope="row">' . esc_html($label) . '</th>';
        echo '<td><label><input type="checkbox" name="civic_schedule[' . esc_attr($key) . ']" value="1"' . checked(!empty($values[$key]), true, false) . '> ' . esc_html($text) . '</label></td>';
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
     * Render a read-only detail row.
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
     * Check whether the current request is a schedule save submission.
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
     * @param int $scheduleId Schedule ID, or 0 when creating.
     * @return bool True when nonce is valid.
     */
    private function hasValidNonce(int $scheduleId): bool
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return false;
        }

        $nonce = wp_unslash($_POST[self::NONCE_FIELD]);

        if (is_array($nonce) || is_object($nonce)) {
            return false;
        }

        return (bool) wp_verify_nonce(sanitize_text_field((string) $nonce), self::NONCE_ACTION . $scheduleId);
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
            'type' => sanitize_key($this->requestValue($data, 'type')),
            'title' => sanitize_text_field($this->requestValue($data, 'title')),
            'details' => sanitize_textarea_field($this->requestValue($data, 'details')),
            'reported_by' => sanitize_text_field($this->requestValue($data, 'reported_by')),
            'status' => sanitize_key($this->requestValue($data, 'status')),
            'review_date' => sanitize_text_field($this->requestValue($data, 'review_date')),
            'internal_comment' => sanitize_textarea_field($this->requestValue($data, 'internal_comment')),
            'response' => sanitize_textarea_field($this->requestValue($data, 'response')),
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'is_archived' => !empty($data['is_archived']) ? 1 : 0,
            'start_date' => sanitize_text_field($this->requestValue($data, 'start_date')),
            'end_date' => sanitize_text_field($this->requestValue($data, 'end_date')),
            'source_type' => sanitize_key($this->requestValue($data, 'source_type')),
            'source_id' => $this->optionalAbsint($this->requestValue($data, 'source_id')),
            'created_by' => $this->optionalAbsint($this->requestValue($data, 'created_by')),
        ];
    }

    /**
     * Get structured schedule request data.
     *
     * @return array<string, mixed> Unslashed request data.
     */
    private function requestData(): array
    {
        if (!isset($_POST['civic_schedule'])) {
            return [];
        }

        $data = wp_unslash($_POST['civic_schedule']);

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
     * Convert optional numeric text to an integer or empty string.
     *
     * @param string $value Raw value.
     * @return int|string
     */
    private function optionalAbsint(string $value)
    {
        return '' === trim($value) ? '' : absint($value);
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

        if (!in_array((string) $values['type'], $this->types, true)) {
            $errors['type'] = 'Type is invalid.';
        }

        if (!in_array((string) $values['status'], $this->statuses, true)) {
            $errors['status'] = 'Status is invalid.';
        }

        return $errors;
    }

    /**
     * Build repository data for civic_schedules.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return array<string, mixed> Schedule data.
     */
    private function buildScheduleData(array $values): array
    {
        return [
            'type' => $values['type'],
            'title' => $values['title'],
            'details' => $values['details'],
            'reported_by' => $values['reported_by'],
            'status' => $values['status'],
            'review_date' => $values['review_date'],
            'internal_comment' => $values['internal_comment'],
            'response' => $values['response'],
            'is_public' => $values['is_public'] ? 1 : 0,
            'is_archived' => $values['is_archived'] ? 1 : 0,
            'start_date' => $values['start_date'],
            'end_date' => $values['end_date'],
            'source_type' => $values['source_type'],
            'source_id' => $values['source_id'],
            'created_by' => $values['created_by'],
        ];
    }

    /**
     * Map a schedule row to form values.
     *
     * @param array<string, mixed>|null $schedule Schedule row.
     * @return array<string, mixed>
     */
    private function valuesFromSchedule(?array $schedule): array
    {
        if (!is_array($schedule)) {
            return $this->defaultValues();
        }

        return [
            'type' => (string) ($schedule['type'] ?? 'meeting'),
            'title' => (string) ($schedule['title'] ?? ''),
            'details' => (string) ($schedule['details'] ?? ''),
            'reported_by' => (string) ($schedule['reported_by'] ?? ''),
            'status' => (string) ($schedule['status'] ?? 'open'),
            'review_date' => $this->dateFormValue($schedule['review_date'] ?? null),
            'internal_comment' => (string) ($schedule['internal_comment'] ?? ''),
            'response' => (string) ($schedule['response'] ?? ''),
            'is_public' => !empty($schedule['is_public']) ? 1 : 0,
            'is_archived' => !empty($schedule['is_archived']) ? 1 : 0,
            'start_date' => $this->dateFormValue($schedule['start_date'] ?? null),
            'end_date' => $this->dateFormValue($schedule['end_date'] ?? null),
            'source_type' => (string) ($schedule['source_type'] ?? ''),
            'source_id' => (string) ($schedule['source_id'] ?? ''),
            'created_by' => (string) ($schedule['created_by'] ?? ''),
        ];
    }

    /**
     * Return default form values.
     *
     * @return array<string, mixed>
     */
    private function defaultValues(): array
    {
        return [
            'type' => 'meeting',
            'title' => '',
            'details' => '',
            'reported_by' => '',
            'status' => 'open',
            'review_date' => '',
            'internal_comment' => '',
            'response' => '',
            'is_public' => 0,
            'is_archived' => 0,
            'start_date' => '',
            'end_date' => '',
            'source_type' => '',
            'source_id' => '',
            'created_by' => get_current_user_id() > 0 ? (string) get_current_user_id() : '',
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

        return (string) $value;
    }

    /**
     * Get sanitized requested schedule ID.
     *
     * @return int Schedule ID.
     */
    private function scheduleId(): int
    {
        if (!isset($_GET['schedule_id'])) {
            return 0;
        }

        $scheduleId = wp_unslash($_GET['schedule_id']);

        if (is_array($scheduleId) || is_object($scheduleId)) {
            return 0;
        }

        return absint($scheduleId);
    }

    /**
     * Check whether the current page is read-only view mode.
     *
     * @return bool True when viewing.
     */
    private function isViewMode(): bool
    {
        if (!isset($_GET['mode'])) {
            return false;
        }

        $mode = wp_unslash($_GET['mode']);

        if (is_array($mode) || is_object($mode)) {
            return false;
        }

        return 'view' === sanitize_key((string) $mode);
    }

    /**
     * Get the admin page title.
     *
     * @param int $scheduleId Schedule ID.
     * @param bool $isView Whether view mode is active.
     * @return string Page title.
     */
    private function pageTitle(int $scheduleId, bool $isView): string
    {
        if ($isView) {
            return __('View Schedule', 'civic-engagement');
        }

        return $scheduleId > 0
            ? __('Edit Schedule', 'civic-engagement')
            : __('Add Schedule', 'civic-engagement');
    }

    /**
     * Render an admin error when the schedule cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Schedule not found.', 'civic-engagement') . '</p></div>';
    }

    /**
     * Build schedule edit URL.
     *
     * @param int $scheduleId Schedule ID.
     * @return string Edit URL.
     */
    private function editUrl(int $scheduleId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-schedule-edit',
                'schedule_id' => $scheduleId,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build the list page URL.
     *
     * @param array<string, mixed> $args Additional query args.
     * @return string List URL.
     */
    private function listUrl(array $args = []): string
    {
        return add_query_arg(
            array_merge(['page' => 'civic-schedules'], $args),
            admin_url('admin.php')
        );
    }

    /**
     * Redirect to the list page.
     *
     * @param array<string, mixed> $args Query args.
     * @return void
     */
    private function redirectToList(array $args): void
    {
        $url = $this->listUrl($args);

        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        }

        echo '<script>window.location.href = ' . wp_json_encode($url) . ';</script>';
        exit;
    }

    /**
     * Build a consistent form response.
     *
     * @param bool $submitted Whether a submission was received.
     * @param bool $success Whether save succeeded.
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
