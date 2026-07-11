<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Events\Registrations\Frontend\EventRegistrationForm;
use CivicPlatform\Modules\Media\Frontend\MediaRenderer;
use CivicPlatform\Services\MediaService;

/**
 * Registers and renders the public event detail shortcode.
 *
 * Rendering remains frontend-focused. Event retrieval is delegated to the
 * repository.
 */
class EventDetailShortcode
{
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

    /**
     * Event registration form handler.
     *
     * @var EventRegistrationForm
     */
    private EventRegistrationForm $registrationForm;

    private MediaService $media;

    /**
     * @param EventRepository $events Event repository.
     * @param DateHelper $dates Date helper.
     * @param EventRegistrationForm $registrationForm Event registration form handler.
     */
    public function __construct(
        EventRepository $events,
        DateHelper $dates,
        EventRegistrationForm $registrationForm,
        MediaService $media
    ) {
        $this->events = $events;
        $this->dates = $dates;
        $this->registrationForm = $registrationForm;
        $this->media = $media;
    }

    /**
     * Register the public event detail shortcode.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('civic_event_detail', [$this, 'render']);
    }

    /**
     * Render a public event detail.
     *
     * @param mixed $atts Shortcode attributes.
     * @return string Rendered shortcode output.
     */
    public function render($atts = []): string
    {
        unset($atts);

        $slug = $this->slug();
        $event = '' !== $slug
            ? $this->events->findPublicBySlug($slug)
            : $this->events->findPublicById($this->eventId());

        ob_start();

        echo '<div class="civic-event-detail">';

        if (!is_array($event)) {
            echo '<p class="civic-event-detail__empty">' . esc_html__('Event not found.', 'civic-engagement') . '</p>';
            echo '</div>';

            return (string) ob_get_clean();
        }

        $acceptingRegistrations = $this->events->isAcceptingRegistrations($event);
        $this->renderEvent($event, $acceptingRegistrations);
        $this->renderRegistrationFormSection($event, $acceptingRegistrations);

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Render public event content.
     *
     * @param array<string, mixed> $event Event row.
     * @param bool $acceptingRegistrations Whether registrations are currently accepted.
     * @return void
     */
    private function renderEvent(array $event, bool $acceptingRegistrations): void
    {
        echo '<article class="civic-card civic-card-main-details civic-event-detail__content">';
        echo '<div class="civic-card__content">';
        echo '<h1 class="civic-card-detail__title civic-card__title civic-event-detail__title">' . esc_html((string) ($event['title'] ?? '')) . '</h1>';
        echo MediaRenderer::gallery($this->media->getByEntity('event', (int) ($event['id'] ?? 0)), 'event-' . (int) ($event['id'] ?? 0));

        if (!empty($event['summary'])) {
            echo '<p class="civic-card__summary civic-event-detail__summary">' . esc_html((string) $event['summary']) . '</p>';
        }

        if (!empty($event['description'])) {
            echo '<div class="civic-card__description civic-event-detail__description">' . wpautop(esc_html((string) $event['description'])) . '</div>';
        }

        echo '<dl class="civic-card__meta civic-event-detail__meta">';

        if (!empty($event['location'])) {
            echo '<dt class="civic-card__location civic-event-detail__location">';
            echo '<strong>' . esc_html__('Location:', 'civic-engagement') . '</strong>';
            echo '</dt>';
            echo '<dd>' . esc_html((string) $event['location']) . '</dd>';
        }

        echo '<dt class="civic-card__date civic-event-detail__date">';
        echo '<strong>' . esc_html__('Date:', 'civic-engagement') . '</strong></dt>';
        echo '<dd>From <span class="civic-events__date-start">' . esc_html($this->dates->formatDate($event['start_date'] ?? null)) . '</span> to <span class="civic-events__date-end">' . esc_html($this->dates->formatDate($event['end_date'] ?? null)) . '</span></dd>';

        echo '<dt class="civic-card__status civic-event-detail__registration-status">';
        echo '<strong>' . esc_html__('Registration Status:', 'civic-engagement') . '</strong>';
        echo '</dt>';
        echo '<dd>' . esc_html($this->registrationStatus($acceptingRegistrations)) . '</dd>';
        echo '</dl>';
       
        echo '</div>';
        echo '</article>';
    }

    /**
     * Render the registration form section when registrations are open.
     *
     * @param array<string, mixed> $event Event row.
     * @param bool $acceptingRegistrations Whether registrations are currently accepted.
     * @return void
     */
    private function renderRegistrationFormSection(array $event, bool $acceptingRegistrations): void
    {
        echo '<section id="civic-event-registration-form" class="civic-event-detail__registration-form civic-form">';

        if ($acceptingRegistrations) {
            echo $this->registrationForm->render($event);
        } else {
            echo '<p class="civic-event-detail__closed-message">' . esc_html__('Registrations for this event have closed.', 'civic-engagement') . '</p>';
        }

        echo '</section>';
    }

    /**
     * Build a registration status label.
     *
     * @param bool $acceptingRegistrations Whether registrations are currently accepted.
     * @return string Registration status.
     */
    private function registrationStatus(bool $acceptingRegistrations): string
    {
        return $acceptingRegistrations
            ? __('Registration is currently open', 'civic-engagement')
            : __('Registration is currently closed', 'civic-engagement');
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

    private function slug(): string
    {
        $slug = get_query_var('civic_slug');

        return is_scalar($slug) ? sanitize_title((string) $slug) : '';
    }
}
