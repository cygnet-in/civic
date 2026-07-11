<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Dashboard\Frontend;

use CivicPlatform\Modules\Dashboard\Services\PublicStatisticsService;

/**
 * Renders the public Civic statistics shortcode.
 */
class StatisticsShortcode
{
    private PublicStatisticsService $statistics;

    public function __construct(PublicStatisticsService $statistics)
    {
        $this->statistics = $statistics;
    }

    public function register(): void
    {
        add_shortcode('civic_statistics', [$this, 'render']);
    }

    /**
     * Render public Civic statistics.
     *
     * @param mixed $atts Shortcode attributes.
     * @return string Rendered shortcode output.
     */
    public function render($atts = []): string
    {
        $items = $this->statistics->getStatistics();

        ob_start();

        echo '<div class="civic-statistics">';

        foreach ($items as $item) {
            echo '<div class="civic-stat-card">';
            echo '<div class="civic-stat-card__number">' . esc_html(number_format_i18n($item['value'])) . '</div>';
            echo '<div class="civic-stat-card__title">' . esc_html($item['title']) . '</div>';
            echo '</div>';
        }

        echo '</div>';

        return (string) ob_get_clean();
    }
}
