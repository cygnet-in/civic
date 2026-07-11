<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Search\Frontend;

use CivicPlatform\Services\SearchService;

/**
 * Renders grouped public Civic search results.
 */
class SearchResultsShortcode
{
    private const QUERY_VAR = 'civic_search';

    private SearchService $search;

    public function __construct(SearchService $search)
    {
        $this->search = $search;
    }

    public function register(): void
    {
        add_shortcode('civic_search_results', [$this, 'render']);
    }

    /**
     * @param array<string, mixed> $atts Shortcode attributes.
     */
    public function render(array $atts = []): string
    {
        $atts = shortcode_atts(
            [
                'limit' => 5,
                'include_pages' => '1',
            ],
            $atts,
            'civic_search_results'
        );

        $query = $this->query();

        ob_start();
        echo '<div class="civic-search civic-search-results">';

        if ('' === $query) {
            echo '<p class="civic-search-results__message">' . esc_html__('Enter a search term to find consultations, events, schedules and news.', 'civic-engagement') . '</p>';
            echo '</div>';

            return (string) ob_get_clean();
        }

        $groups = $this->search->search($query, [
            'limit' => $atts['limit'],
            'include_pages' => $atts['include_pages'],
        ]);
        $total = 0;

        echo '<h2 class="civic-search-results__title">' . esc_html(sprintf(__('Search results for "%s"', 'civic-engagement'), $query)) . '</h2>';

        foreach ($groups as $key => $group) {
            $items = $group['items'];
            $total += count($items);

            echo '<section class="civic-search-results__group civic-search-results__group--' . esc_attr((string) $key) . '">';
            echo '<h3 class="civic-search-results__group-title">' . esc_html($group['label']) . '</h3>';

            if (empty($items)) {
                echo '<p class="civic-search-results__empty">' . esc_html__('No results found.', 'civic-engagement') . '</p>';
            } else {
                echo '<ul class="civic-search-results__list">';

                foreach ($items as $item) {
                    $this->renderItem($item);
                }

                echo '</ul>';
            }

            echo '</section>';
        }

        if (0 === $total) {
            echo '<p class="civic-search-results__message">' . esc_html__('No matching public content was found.', 'civic-engagement') . '</p>';
        }

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, string> $item Result item.
     */
    private function renderItem(array $item): void
    {
        echo '<li class="civic-search-results__item">';
        echo '<a class="civic-search-results__link" href="' . esc_url($item['url'] ?? '') . '">' . esc_html($item['title'] ?? '') . '</a>';

        if ('' !== ($item['excerpt'] ?? '')) {
            echo '<p class="civic-search-results__excerpt">' . esc_html($item['excerpt']) . '</p>';
        }

        echo '</li>';
    }

    private function query(): string
    {
        if (!isset($_GET[self::QUERY_VAR])) {
            return '';
        }

        $value = wp_unslash($_GET[self::QUERY_VAR]);

        return is_scalar($value) ? sanitize_text_field((string) $value) : '';
    }
}
