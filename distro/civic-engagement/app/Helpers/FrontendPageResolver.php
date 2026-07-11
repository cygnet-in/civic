<?php

declare(strict_types=1);

namespace CivicPlatform\Helpers;

/**
 * Resolves public pages that host Civic Platform shortcodes.
 */
class FrontendPageResolver
{
    /**
     * Find the first published page containing a shortcode.
     *
     * @param string $shortcode Shortcode tag without brackets.
     * @return int Page ID, or 0 when not found.
     */
    public function findPageIdByShortcode(string $shortcode): int
    {
        $shortcode = trim($shortcode);

        if ('' === $shortcode) {
            return 0;
        }

        $pages = get_posts(
            [
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => 10,
                's' => '[' . $shortcode,
                'orderby' => 'ID',
                'order' => 'ASC',
            ]
        );

        foreach ($pages as $page) {
            if (!is_object($page) || empty($page->post_content)) {
                continue;
            }

            if (has_shortcode((string) $page->post_content, $shortcode)) {
                return (int) $page->ID;
            }
        }

        return 0;
    }

    /**
     * Find the first published page containing a shortcode.
     *
     * @param string $shortcode Shortcode tag without brackets.
     * @return string Page URL, or empty string when not found.
     */
    public function findPageUrlByShortcode(string $shortcode): string
    {
        $shortcode = trim($shortcode);

        if ('' === $shortcode) {
            return '';
        }

        $pageId = $this->findPageIdByShortcode($shortcode);
        $url = $pageId > 0 ? get_permalink($pageId) : '';

        return is_string($url) ? $url : '';
    }
}
