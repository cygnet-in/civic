<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Frontend;

use CivicPlatform\Helpers\FrontendPageResolver;
use CivicPlatform\Core\CanonicalSlugRouter;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

/**
 * Frontend widget displaying latest public consultations.
 */
class LatestConsultationsWidget extends \WP_Widget
{
    private const DEFAULT_TITLE = 'Latest Consultations';
    private const DEFAULT_COUNT = 5;

    /**
     * Thread repository.
     *
     * @var ThreadRepository
     */
    private ThreadRepository $threads;

    /**
     * Public page resolver.
     *
     * @var FrontendPageResolver
     */
    private FrontendPageResolver $pages;

    /**
     * @param ThreadRepository $threads Thread repository.
     * @param FrontendPageResolver $pages Public page resolver.
     */
    public function __construct(ThreadRepository $threads, FrontendPageResolver $pages)
    {
        parent::__construct(
            'civic_latest_consultations',
            __('Civic: Latest Consultations', 'civic-engagement'),
            ['description' => __('Displays the latest public consultations.', 'civic-engagement')]
        );

        $this->threads = $threads;
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
        $result = $this->threads->getPublicActiveThreads(
            [
                'page' => 1,
                'per_page' => $count,
                'orderby' => 'created_at',
                'order' => 'DESC',
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $detailUrl = $this->pages->findPageUrlByShortcode('civic_thread_detail');
        $listUrl = $this->pages->findPageUrlByShortcode('civic_threads');

        echo wp_kses_post((string) ($args['before_widget'] ?? ''));

        if ('' !== $title) {
            echo wp_kses_post((string) ($args['before_title'] ?? ''));
            echo esc_html($title);
            echo wp_kses_post((string) ($args['after_title'] ?? ''));
        }

        if (!empty($items)) {
            echo '<ul class="civic-widget civic-widget--latest-consultations">';

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
     * Render one consultation item.
     *
     * @param array<string, mixed> $item Consultation row.
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
            echo '<a href="' . esc_url(CanonicalSlugRouter::url('consultation', $slug)) . '">' . esc_html($title) . '</a>';
        } elseif ('' !== $detailUrl && $id > 0) {
            echo '<a href="' . esc_url(add_query_arg(['thread_id' => $id], $detailUrl)) . '">' . esc_html($title) . '</a>';
        } else {
            echo esc_html($title);
        }

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
