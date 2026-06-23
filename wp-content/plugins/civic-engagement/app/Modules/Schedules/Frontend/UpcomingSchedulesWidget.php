<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Frontend;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\FrontendPageResolver;
use CivicPlatform\Core\CanonicalSlugRouter;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;

/**
 * Frontend widget displaying upcoming public schedules.
 */
class UpcomingSchedulesWidget extends \WP_Widget
{
    private const DEFAULT_TITLE = 'Upcoming Schedules';
    private const DEFAULT_COUNT = 5;

    /**
     * Schedule repository.
     *
     * @var ScheduleRepository
     */
    private ScheduleRepository $schedules;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    /**
     * Public page resolver.
     *
     * @var FrontendPageResolver
     */
    private FrontendPageResolver $pages;

    /**
     * @param ScheduleRepository $schedules Schedule repository.
     * @param DateHelper $dates Date helper.
     * @param FrontendPageResolver $pages Public page resolver.
     */
    public function __construct(ScheduleRepository $schedules, DateHelper $dates, FrontendPageResolver $pages)
    {
        parent::__construct(
            'civic_upcoming_schedules',
            __('Civic: Upcoming Schedules', 'civic-engagement'),
            ['description' => __('Displays upcoming public schedules.', 'civic-engagement')]
        );

        $this->schedules = $schedules;
        $this->dates = $dates;
        $this->pages = $pages;
    }

    /**
     * Render widget output.
     *
     * @param array<string, mixed> $args Widget wrapper args.
     * @param array<string, mixed> $instance Widget instance settings.
     * @return void
     */
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $this->title($instance));
        $count = $this->count($instance);
        $result = $this->schedules->getPaginated(
            [
                'page' => 1,
                'per_page' => $count,
                'is_public' => 1,
                'is_archived' => 0,
                'start_date_from' => current_time('mysql'),
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $detailUrl = $this->pages->findPageUrlByShortcode('civic_schedule_detail');
        $listUrl = $this->pages->findPageUrlByShortcode('civic_schedules');

        echo wp_kses_post((string) ($args['before_widget'] ?? ''));

        if ('' !== $title) {
            echo wp_kses_post((string) ($args['before_title'] ?? ''));
            echo esc_html($title);
            echo wp_kses_post((string) ($args['after_title'] ?? ''));
        }

        if (!empty($items)) {
            echo '<ul class="civic-widget civic-widget--upcoming-schedules">';

            foreach ($items as $item) {
                $this->renderItem($item, $detailUrl);
            }

            echo '</ul>';
        }

        $this->renderViewAll($listUrl);

        echo wp_kses_post((string) ($args['after_widget'] ?? ''));
    }

    /**
     * Render widget admin form.
     *
     * @param array<string, mixed> $instance Widget instance settings.
     * @return void
     */
    public function form($instance)
    {
        $title = $this->title($instance);
        $count = $this->count($instance);

        echo '<p>';
        echo '<label for="' . esc_attr($this->get_field_id('title')) . '">' . esc_html__('Title:', 'civic-engagement') . '</label>';
        echo '<input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '">';
        echo '</p>';
        echo '<p>';
        echo '<label for="' . esc_attr($this->get_field_id('count')) . '">' . esc_html__('Item count:', 'civic-engagement') . '</label>';
        echo '<input class="tiny-text" id="' . esc_attr($this->get_field_id('count')) . '" name="' . esc_attr($this->get_field_name('count')) . '" type="number" min="1" max="20" step="1" value="' . esc_attr((string) $count) . '">';
        echo '</p>';
    }

    /**
     * Sanitize widget settings.
     *
     * @param array<string, mixed> $newInstance New settings.
     * @param array<string, mixed> $oldInstance Old settings.
     * @return array<string, mixed>
     */
    public function update($newInstance, $oldInstance)
    {
        unset($oldInstance);

        return [
            'title' => sanitize_text_field((string) ($newInstance['title'] ?? self::DEFAULT_TITLE)),
            'count' => $this->normalizeCount($newInstance['count'] ?? self::DEFAULT_COUNT),
        ];
    }

    /**
     * Render one schedule item.
     *
     * @param array<string, mixed> $item Schedule row.
     * @param string $detailUrl Detail page URL.
     * @return void
     */
    private function renderItem(array $item, string $detailUrl): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;
        $slug = (string) ($item['slug'] ?? '');
        $title = (string) ($item['title'] ?? '');

        echo '<li class="civic-widget__item">';

        if ('' !== $slug) {
            echo '<a href="' . esc_url(CanonicalSlugRouter::url('schedule', $slug)) . '">' . esc_html($title) . '</a>';
        } elseif ('' !== $detailUrl && $id > 0) {
            echo '<a href="' . esc_url(add_query_arg(['schedule_id' => $id], $detailUrl)) . '">' . esc_html($title) . '</a>';
        } else {
            echo esc_html($title);
        }

        echo '<br><span class="civic-widget__date">' . esc_html($this->dates->formatDate($item['start_date'] ?? null)) . '</span>';
        echo '</li>';
    }

    /**
     * Render the View All link.
     *
     * @param string $listUrl Listing page URL.
     * @return void
     */
    private function renderViewAll(string $listUrl): void
    {
        if ('' === $listUrl) {
            return;
        }

        echo '<p class="civic-widget__view-all"><a href="' . esc_url($listUrl) . '">' . esc_html__('View All', 'civic-engagement') . '</a></p>';
    }

    /**
     * Get widget title.
     *
     * @param array<string, mixed> $instance Widget instance settings.
     * @return string
     */
    private function title(array $instance): string
    {
        return sanitize_text_field((string) ($instance['title'] ?? self::DEFAULT_TITLE));
    }

    /**
     * Get item count.
     *
     * @param array<string, mixed> $instance Widget instance settings.
     * @return int
     */
    private function count(array $instance): int
    {
        return $this->normalizeCount($instance['count'] ?? self::DEFAULT_COUNT);
    }

    /**
     * Normalize item count.
     *
     * @param mixed $count Raw count.
     * @return int
     */
    private function normalizeCount($count): int
    {
        return max(1, min(20, absint($count)));
    }
}
