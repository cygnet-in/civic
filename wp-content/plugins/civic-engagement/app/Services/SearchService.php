<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Core\CanonicalSlugRouter;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

/**
 * Coordinates public search across Civic modules and WordPress content.
 */
class SearchService
{
    private ThreadRepository $threads;
    private EventRepository $events;
    private ScheduleRepository $schedules;

    public function __construct(ThreadRepository $threads, EventRepository $events, ScheduleRepository $schedules)
    {
        $this->threads = $threads;
        $this->events = $events;
        $this->schedules = $schedules;
    }

    /**
     * Search public Civic content and optional WordPress content.
     *
     * @param string $keyword Search keyword.
     * @param array<string, mixed> $args Search options.
     * @return array<string, array{label: string, items: array<int, array<string, string>>}>
     */
    public function search(string $keyword, array $args = []): array
    {
        $keyword = trim($keyword);
        $limit = $this->limit($args['limit'] ?? 5);
        $includePages = $this->truthy($args['include_pages'] ?? true);

        return [
            'consultations' => [
                'label' => __('Consultations', 'civic-engagement'),
                'items' => $this->mapCivicResults(
                    $this->threads->searchPublic($keyword, ['page' => 1, 'per_page' => $limit])['items'] ?? [],
                    'consultation',
                    'summary'
                ),
            ],
            'events' => [
                'label' => __('Events', 'civic-engagement'),
                'items' => $this->mapCivicResults(
                    $this->events->searchPublic($keyword, ['page' => 1, 'per_page' => $limit])['items'] ?? [],
                    'event',
                    'summary'
                ),
            ],
            'schedules' => [
                'label' => __('Schedules', 'civic-engagement'),
                'items' => $this->mapCivicResults(
                    $this->schedules->searchPublic($keyword, ['page' => 1, 'per_page' => $limit])['items'] ?? [],
                    'schedule',
                    'details'
                ),
            ],
            'posts' => [
                'label' => $includePages ? __('Posts and Pages', 'civic-engagement') : __('Posts', 'civic-engagement'),
                'items' => $this->searchWordPressContent($keyword, $limit, $includePages),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, string>>
     */
    private function mapCivicResults(array $items, string $route, string $excerptField): array
    {
        $results = [];

        foreach ($items as $item) {
            $slug = isset($item['slug']) ? sanitize_title((string) $item['slug']) : '';

            if ('' === $slug) {
                continue;
            }

            $results[] = [
                'title' => $this->plainText($item['title'] ?? ''),
                'excerpt' => $this->excerpt($item[$excerptField] ?? ''),
                'url' => CanonicalSlugRouter::url($route, $slug),
                'type' => $route,
            ];
        }

        return $results;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function searchWordPressContent(string $keyword, int $limit, bool $includePages): array
    {
        if ('' === trim($keyword) || !class_exists('\WP_Query')) {
            return [];
        }

        $query = new \WP_Query([
            's' => $keyword,
            'post_type' => $includePages ? ['post', 'page'] : ['post'],
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ]);

        $items = [];

        foreach ($query->posts as $post) {
            $items[] = [
                'title' => get_the_title($post),
                'excerpt' => $this->excerpt(has_excerpt($post) ? get_the_excerpt($post) : $post->post_content),
                'url' => get_permalink($post),
                'type' => get_post_type($post) ?: 'post',
            ];
        }

        wp_reset_postdata();

        return $items;
    }

    private function excerpt($value): string
    {
        $text = $this->plainText($value);

        if ('' === $text) {
            return '';
        }

        return wp_trim_words($text, 24, '...');
    }

    private function plainText($value): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return trim(wp_strip_all_tags((string) $value));
    }

    private function limit($value): int
    {
        return max(1, min(20, absint($value)));
    }

    private function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
