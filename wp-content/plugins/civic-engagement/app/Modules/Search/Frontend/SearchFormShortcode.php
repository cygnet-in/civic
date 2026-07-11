<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Search\Frontend;

use CivicPlatform\Services\CivicSettingsService;

/**
 * Renders the reusable public Civic search form.
 */
class SearchFormShortcode
{
    private const QUERY_VAR = 'civic_search';

    private CivicSettingsService $settings;

    public function __construct(?CivicSettingsService $settings = null)
    {
        $this->settings = $settings ?? new CivicSettingsService();
    }

    public function register(): void
    {
        add_shortcode('civic_search_form', [$this, 'render']);
    }

    /**
     * @param array<string, mixed> $atts Shortcode attributes.
     */
    public function render(array $atts = []): string
    {
        $atts = shortcode_atts(
            [
                'action' => '',
                'title' => __('Search consultations, events, schedules and news', 'civic-engagement'),
                'placeholder' => __('Search all', 'civic-engagement'),
                'button_text' => __('Search', 'civic-engagement'),
            ],
            $atts,
            'civic_search_form'
        );

        $action = trim((string) $atts['action']);
        $action = '' !== $action ? $action : $this->configuredActionUrl();
        $query = $this->query();

        ob_start();
        ?>
        <div class="civic-search civic-search-form">
            <form class="civic-search-form__form" method="get" action="<?php echo esc_url($action); ?>">
                <label class="screen-reader-text" for="civic-search-input"><?php echo esc_html__('Search', 'civic-engagement'); ?></label>
                <input
                    class="civic-search-form__input"
                    type="search"
                    id="civic-search-input"
                    name="<?php echo esc_attr(self::QUERY_VAR); ?>"
                    value="<?php echo esc_attr($query); ?>"                     
                    title="<?php echo esc_attr((string) $atts['title']); ?>"
                    placeholder="<?php echo esc_attr((string) $atts['placeholder']); ?>"
                >
                <button class="civic-search-form__button" type="submit"><?php echo esc_html((string) $atts['button_text']); ?></button>
            </form>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private function query(): string
    {
        if (!isset($_GET[self::QUERY_VAR])) {
            return '';
        }

        $value = wp_unslash($_GET[self::QUERY_VAR]);

        return is_scalar($value) ? sanitize_text_field((string) $value) : '';
    }

    private function configuredActionUrl(): string
    {
        $settings = $this->settings->publicSettings();
        $pageId = absint($settings['search_results_page_id'] ?? 0);

        if ($pageId > 0) {
            $url = get_permalink($pageId);

            if (is_string($url) && '' !== $url) {
                return $url;
            }
        }

        $fallback = get_permalink();

        return is_string($fallback) && '' !== $fallback ? $fallback : '';
    }
}
