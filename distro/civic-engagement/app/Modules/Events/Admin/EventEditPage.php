<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\StatusLabelHelper;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Media\Admin\MediaAdminPanel;
use CivicPlatform\Services\MediaService;
use CivicPlatform\Services\ShortUrlService;

/**
 * Renders and processes the event add/edit admin page.
 *
 * This page handles request sanitization, nonce checks, redirects, and
 * presentation only. Event persistence is delegated to EventRepository.
 */
class EventEditPage
{
    /**
     * Required capability for editing events.
     */
    private const CAPABILITY = 'manage_civic_events';

    /**
     * Form action value.
     */
    private const ACTION = 'civic_event_save';

    /**
     * Nonce action prefix.
     */
    private const NONCE_ACTION = 'civic_event_save_';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_event_nonce';

    /**
     * Event repository.
     *
     * @var EventRepository
     */
    private EventRepository $events;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    private MediaService $media;

    private ShortUrlService $shortUrls;

    private MediaAdminPanel $mediaPanel;

    /**
     * @param EventRepository $events Event repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(EventRepository $events, DateHelper $dates, MediaService $media, ShortUrlService $shortUrls)
    {
        $this->events = $events;
        $this->dates = $dates;
        $this->media = $media;
        $this->shortUrls = $shortUrls;
        $this->mediaPanel = new MediaAdminPanel($media);
    }

    /**
     * Render and process the event add/edit page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $eventId = $this->eventId();
        $event = $eventId > 0 ? $this->events->findById($eventId) : null;
        $isView = $this->isViewMode();
        $response = $isView ? $this->buildResponse(false, false, null, [], [], null) : $this->processSubmission($eventId);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->pageTitle($eventId, $isView)) . '</h1>';
        echo '<p><a href="' . esc_url($this->listUrl()) . '">' . esc_html__('Back to Events', 'civic-engagement') . '</a></p>';

        if (!empty($_GET['media_error'])) {
            echo '<div class="notice notice-error"><p>' . esc_html__('The event was created, but one or more images could not be uploaded.', 'civic-engagement') . '</p></div>';
        }

        if ($eventId > 0 && !is_array($event)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        if ($isView && is_array($event)) {
            $this->renderView($event);
            echo '</div>';

            return;
        }

        $values = !empty($response['submitted']) && empty($response['success'])
            ? $response['values']
            : $this->valuesFromEvent($event);

        $this->renderMessage($response);
        $this->renderForm($eventId, $values, $response['errors']);
        echo '</div>';
    }

    /**
     * Process a submitted event save request.
     *
     * @param int $eventId Event ID, or 0 when creating.
     * @return array<string, mixed> Form response.
     */
    private function processSubmission(int $eventId): array
    {
        if (!$this->isSubmission()) {
            return $this->buildResponse(false, false, null, $this->defaultValues(), [], null);
        }

        $values = $this->sanitizeRequestValues();

        if (!$this->hasValidNonce($eventId)) {
            return $this->buildResponse(true, false, 'Security check failed. Please try again.', $values, [], 'invalid_nonce');
        }

        $errors = $this->validateValues($values, $eventId);

        if (!empty($errors)) {
            return $this->buildResponse(true, false, 'Please check the highlighted fields.', $values, $errors, 'validation_failed');
        }

        if ($eventId > 0) {
            $updated = $this->events->update($eventId, $this->buildEventData($values));

            if (!$updated) {
                return $this->buildResponse(true, false, 'The event could not be updated.', $values, [], 'event_update_failed');
            }

            $media = $this->synchronizeMedia($eventId);
            if (!empty($media['errors'])) {
                return $this->buildResponse(true, false, implode(' ', $media['errors']), $values, ['media' => implode(' ', $media['errors'])], 'media_save_failed');
            }

            $url = $this->listUrl(['updated' => 1]);
            if (!headers_sent()) {
                wp_safe_redirect($url);
                exit;
            }

            echo '<script>window.location.href = ' . wp_json_encode($url) . ';</script>';
            exit;
        }

        $newEventId = $this->events->create($this->buildEventData($values));

        if ($newEventId <= 0) {
            return $this->buildResponse(true, false, 'The event could not be created.', $values, [], 'event_create_failed');
        }

        $media = $this->synchronizeMedia($newEventId);
        if (!empty($media['errors'])) {
            $this->redirectToEditWithMediaError($newEventId);
        }

        $url = $this->listUrl(['created' => 1]);
        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        }

        echo '<script>window.location.href = ' . wp_json_encode($url) . ';</script>';
        exit;
    }

    /**
     * Render the event form.
     *
     * @param int $eventId Event ID, or 0 when creating.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @return void
     */
    private function renderForm(int $eventId, array $values, array $errors): void
    {
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field(self::NONCE_ACTION . $eventId, self::NONCE_FIELD);
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->renderTextInput('title', __('Title', 'civic-engagement'), $values, $errors, true);
        $this->renderTextInput('slug', __('Slug', 'civic-engagement'), $values, $errors, false);
        $this->renderTextInput('short_code', __('Short URL Code', 'civic-engagement'), $values, $errors, false);
        $this->renderTextarea('summary', __('Summary', 'civic-engagement'), $values, $errors, 3);
        $this->renderTextarea('description', __('Description', 'civic-engagement'), $values, $errors, 8);
        $this->renderTextInput('location', __('Location', 'civic-engagement'), $values, $errors, false);
        $this->renderIsPublicField($values);
        $this->renderRegistrationEnabledField($values);
        $this->renderTextInput('start_date', __('Start Date', 'civic-engagement'), $values, $errors, false);
        $this->renderTextInput('end_date', __('End Date', 'civic-engagement'), $values, $errors, false);
        $this->renderStatusSelect($values, $errors);
        echo '</tbody></table>';
        $this->mediaPanel->render('event', $eventId);
        submit_button($eventId > 0 ? __('Update Event', 'civic-engagement') : __('Create Event', 'civic-engagement'));
        echo '</form>';
    }

    /**
     * Render read-only event details for the View action.
     *
     * @param array<string, mixed> $event Event row.
     * @return void
     */
    private function renderView(array $event): void
    {
        $eventId = isset($event['id']) ? (int) $event['id'] : 0;

        echo '<p><a class="button" href="' . esc_url($this->editUrl($eventId)) . '">' . esc_html__('Edit Event', 'civic-engagement') . '</a></p>';
        echo '<table class="widefat striped"><tbody>';
        $this->renderDetailRow(__('ID', 'civic-engagement'), (string) ($event['id'] ?? ''));
        $this->renderDetailRow(__('Title', 'civic-engagement'), (string) ($event['title'] ?? ''));
        $this->renderDetailRow(__('Slug', 'civic-engagement'), (string) ($event['slug'] ?? ''));
        if ('' !== (string) ($event['short_code'] ?? '')) {
            $this->renderDetailRow(__('Short URL', 'civic-engagement'), ShortUrlService::url((string) $event['short_code']));
        }
        $this->renderDetailRow(__('Summary', 'civic-engagement'), (string) ($event['summary'] ?? ''));
        $this->renderDetailRow(__('Description', 'civic-engagement'), (string) ($event['description'] ?? ''));
        $this->renderDetailRow(__('Location', 'civic-engagement'), (string) ($event['location'] ?? ''));
        $this->renderDetailRow(__('Public', 'civic-engagement'), !empty($event['is_public']) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement'));
        $this->renderDetailRow(__('Registrations', 'civic-engagement'), !empty($event['registration_enabled']) ? __('Enabled', 'civic-engagement') : __('Disabled', 'civic-engagement'));
        $this->renderDetailRow(__('Start Date', 'civic-engagement'), $this->dates->formatDateTime($event['start_date'] ?? null));
        $this->renderDetailRow(__('End Date', 'civic-engagement'), $this->dates->formatDateTime($event['end_date'] ?? null));
        $this->renderDetailRow(__('Status', 'civic-engagement'), StatusLabelHelper::format($event['status'] ?? ''));
        $this->renderDetailRow(__('Created At', 'civic-engagement'), $this->dates->formatDateTime($event['created_at'] ?? null));
        $this->renderDetailRow(__('Updated At', 'civic-engagement'), $this->dates->formatDateTime($event['updated_at'] ?? null));
        echo '</tbody></table>';
        $this->mediaPanel->renderReadOnly('event', $eventId);
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
        echo '<th scope="row"><label for="civic-event-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<input class="regular-text" id="civic-event-' . esc_attr($key) . '" name="civic_event[' . esc_attr($key) . ']" type="text" value="' . esc_attr((string) ($values[$key] ?? '')) . '"' . ($required ? ' required' : '') . '>';

        if ('slug' === $key) {
            echo '<p class="description">' . esc_html__('Leave blank to generate a slug from the title.', 'civic-engagement') . '</p>';
        }

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
        echo '<th scope="row"><label for="civic-event-' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<textarea class="large-text" id="civic-event-' . esc_attr($key) . '" name="civic_event[' . esc_attr($key) . ']" rows="' . esc_attr((string) $rows) . '">' . esc_textarea((string) ($values[$key] ?? '')) . '</textarea>';
        $this->renderFieldError($key, $errors);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render the registration enabled checkbox.
     *
     * @param array<string, mixed> $values Form values.
     * @return void
     */
    private function renderRegistrationEnabledField(array $values): void
    {
        $enabled = !empty($values['registration_enabled']);

        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Registrations', 'civic-engagement') . '</th>';
        echo '<td>';
        echo '<label><input type="checkbox" name="civic_event[registration_enabled]" value="1"' . checked($enabled, true, false) . '> ' . esc_html__('Enable public registrations', 'civic-engagement') . '</label>';
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Render the Is Public checkbox.
     *
     * @param array<string, mixed> $values Form values.
     * @return void
     */
    private function renderIsPublicField(array $values): void
    {
        $isPublic = !empty($values['is_public']);

        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Public', 'civic-engagement') . '</th>';
        echo '<td>';
        echo '<label><input type="checkbox" name="civic_event[is_public]" value="1"' . checked($isPublic, true, false) . '> ' . esc_html__('Make this event visible to the public', 'civic-engagement') . '</label>';
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
        echo '<th scope="row"><label for="civic-event-status">' . esc_html__('Status', 'civic-engagement') . '</label></th>';
        echo '<td>';
        echo '<select id="civic-event-status" name="civic_event[status]">';
        echo '<option value="draft"' . selected($status, 'draft', false) . '>' . esc_html__('Draft', 'civic-engagement') . '</option>';
        echo '<option value="published"' . selected($status, 'published', false) . '>' . esc_html__('Published', 'civic-engagement') . '</option>';
        echo '<option value="closed"' . selected($status, 'closed', false) . '>' . esc_html__('Closed', 'civic-engagement') . '</option>';
        echo '</select>';
        $this->renderFieldError('status', $errors);
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
     * Check whether the current request is an event save submission.
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
     * @param int $eventId Event ID, or 0 when creating.
     * @return bool True when nonce is valid.
     */
    private function hasValidNonce(int $eventId): bool
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return false;
        }

        $nonce = wp_unslash($_POST[self::NONCE_FIELD]);

        if (is_array($nonce) || is_object($nonce)) {
            return false;
        }

        $nonce = sanitize_text_field((string) $nonce);

        return '' !== $nonce && (bool) wp_verify_nonce($nonce, self::NONCE_ACTION . $eventId);
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
            'slug' => sanitize_title($this->requestValue($data, 'slug')),
            'short_code' => $this->shortUrls->normalize($this->requestValue($data, 'short_code')),
            'summary' => sanitize_textarea_field($this->requestValue($data, 'summary')),
            'description' => sanitize_textarea_field($this->requestValue($data, 'description')),
            'location' => sanitize_text_field($this->requestValue($data, 'location')),
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'registration_enabled' => !empty($data['registration_enabled']) ? 1 : 0,
            'start_date' => sanitize_text_field($this->requestValue($data, 'start_date')),
            'end_date' => sanitize_text_field($this->requestValue($data, 'end_date')),
            'status' => sanitize_text_field($this->requestValue($data, 'status')),
        ];
    }

    /**
     * Get structured event request data.
     *
     * @return array<string, mixed> Unslashed request data.
     */
    private function requestData(): array
    {
        if (!isset($_POST['civic_event'])) {
            return [];
        }

        $data = wp_unslash($_POST['civic_event']);

        return is_array($data) ? $data : [];
    }

    /** @return array{errors: array<int, string>, created: int} */
    private function synchronizeMedia(int $eventId): array
    {
        $request = isset($_POST['civic_media']) ? wp_unslash($_POST['civic_media']) : [];
        $uploads = isset($_FILES['civic_media']) && is_array($_FILES['civic_media']) ? $_FILES['civic_media'] : [];

        return $this->media->synchronize('event', $eventId, is_array($request) ? $request : [], $uploads, get_current_user_id());
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
    private function validateValues(array $values, int $eventId = 0): array
    {
        $errors = [];

        if ('' === $values['title']) {
            $errors['title'] = 'Title is required.';
        }

        if (!in_array($values['status'], ['draft', 'published', 'closed'], true)) {
            $errors['status'] = 'Status must be draft, published, or closed.';
        }

        $slug = $this->buildSlug((string) $values['slug'], (string) $values['title']);

        if ($this->events->slugExists($slug, $eventId > 0 ? $eventId : null)) {
            $errors['slug'] = 'An event with this URL slug already exists.';
        }

        $shortUrlError = $this->shortUrls->validationError((string) $values['short_code'], 'event', $eventId > 0 ? $eventId : null);
        if (null !== $shortUrlError) {
            $errors['short_code'] = $shortUrlError;
        }

        return $errors;
    }

    /**
     * Build repository data for civic_events.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return array<string, mixed> Event data.
     */
    private function buildEventData(array $values): array
    {
        return [
            'title' => $values['title'],
            'slug' => $this->buildSlug((string) $values['slug'], (string) $values['title']),
            'short_code' => $values['short_code'],
            'summary' => $values['summary'],
            'description' => $values['description'],
            'location' => $values['location'],
            'is_public' => $values['is_public'] ? 1 : 0,
            'registration_enabled' => $values['registration_enabled'],
            'start_date' => $values['start_date'],
            'end_date' => $values['end_date'],
            'status' => $values['status'],
        ];
    }

    /**
     * Build a URL-friendly slug from a manual value or title.
     *
     * @param string $slug Submitted slug.
     * @param string $title Event title.
     * @return string Event slug.
     */
    private function buildSlug(string $slug, string $title): string
    {
        $slug = sanitize_title($slug);

        if ('' !== $slug) {
            return $slug;
        }

        $slug = sanitize_title($title);

        if ('' !== $slug) {
            return $slug;
        }

        return 'event-' . time();
    }

    /**
     * Map an event row to form values.
     *
     * @param array<string, mixed>|null $event Event row.
     * @return array<string, mixed>
     */
    private function valuesFromEvent(?array $event): array
    {
        if (!is_array($event)) {
            return $this->defaultValues();
        }

        return [
            'title' => (string) ($event['title'] ?? ''),
            'slug' => (string) ($event['slug'] ?? ''),
            'short_code' => (string) ($event['short_code'] ?? ''),
            'summary' => (string) ($event['summary'] ?? ''),
            'description' => (string) ($event['description'] ?? ''),
            'location' => (string) ($event['location'] ?? ''),
            'is_public' => !empty($event['is_public']) ? 1 : 0,
            'registration_enabled' => !empty($event['registration_enabled']) ? 1 : 0,
            'start_date' => $this->dateFormValue($event['start_date'] ?? null),
            'end_date' => $this->dateFormValue($event['end_date'] ?? null),
            'status' => (string) ($event['status'] ?? 'draft'),
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
            'title' => '',
            'slug' => '',
            'short_code' => '',
            'summary' => '',
            'description' => '',
            'location' => '',
            'is_public' => 0,
            'registration_enabled' => 1,
            'start_date' => '',
            'end_date' => '',
            'status' => 'draft',
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
     * Get sanitized requested event ID.
     *
     * @return int Event ID.
     */
    private function eventId(): int
    {
        if (!isset($_GET['event_id'])) {
            return 0;
        }

        $eventId = wp_unslash($_GET['event_id']);

        if (is_array($eventId) || is_object($eventId)) {
            return 0;
        }

        return absint($eventId);
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
     * @param int $eventId Event ID.
     * @param bool $isView Whether view mode is active.
     * @return string Page title.
     */
    private function pageTitle(int $eventId, bool $isView): string
    {
        if ($isView) {
            return __('View Event', 'civic-engagement');
        }

        return $eventId > 0
            ? __('Edit Event', 'civic-engagement')
            : __('Add Event', 'civic-engagement');
    }

    /**
     * Render an admin error when the event cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Event not found.', 'civic-engagement') . '</p></div>';
    }

    /**
     * Build event edit URL.
     *
     * @param int $eventId Event ID.
     * @return string Edit URL.
     */
    private function editUrl(int $eventId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-event-edit',
                'event_id' => $eventId,
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
            array_merge(['page' => 'civic-events'], $args),
            admin_url('admin.php')
        );
    }

    /** Redirect a newly created event back to its edit page after a media error. */
    private function redirectToEditWithMediaError(int $eventId): void
    {
        $url = add_query_arg(['media_error' => 1], $this->editUrl($eventId));

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
