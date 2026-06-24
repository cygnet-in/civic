<?php

declare(strict_types=1);

namespace CivicPlatform\Core;

use CivicPlatform\Helpers\FrontendPageResolver;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Repositories\ShortUrlRepository;
use CivicPlatform\Services\ShortUrlService;

/**
 * Maps prefixed public civic URLs to the existing shortcode detail pages.
 */
class CanonicalSlugRouter
{
    private \wpdb $wpdb;

    private ShortUrlService $shortUrls;

    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->shortUrls = new ShortUrlService(new ShortUrlRepository($wpdb));
    }

    public function register(): void
    {
        add_action('init', [$this, 'registerRewriteRules']);
        add_filter('query_vars', [$this, 'registerQueryVars']);
        add_action('parse_request', [$this, 'resolveRoute']);
        add_filter('redirect_canonical', [$this, 'preventWordPressCanonicalRedirect'], 10, 2);
        add_action('template_redirect', [$this, 'redirectLegacyIdUrl'], 1);
        add_action('template_redirect', [$this, 'redirectShortUrl'], 1);
    }

    public function registerRewriteRules(): void
    {
        add_rewrite_rule('^consultation/([^/]+)/?$', 'index.php?civic_route=consultation&civic_slug=$matches[1]', 'top');
        add_rewrite_rule('^event/([^/]+)/?$', 'index.php?civic_route=event&civic_slug=$matches[1]', 'top');
        add_rewrite_rule('^schedule/([^/]+)/?$', 'index.php?civic_route=schedule&civic_slug=$matches[1]', 'top');
        add_rewrite_rule('^' . preg_quote(ShortUrlService::prefix(), '/') . '/([^/]+)/?$', 'index.php?civic_short_code=$matches[1]', 'top');
    }

    /** @param array<int, string> $vars @return array<int, string> */
    public function registerQueryVars(array $vars): array
    {
        $vars[] = 'civic_route';
        $vars[] = 'civic_slug';
        $vars[] = 'civic_short_code';

        return array_values(array_unique($vars));
    }

    public function resolveRoute(\WP $wp): void
    {
        $shortCode = isset($wp->query_vars['civic_short_code']) ? trim((string) $wp->query_vars['civic_short_code']) : '';

        if ('' !== $shortCode) {
            $record = $this->shortUrls->findByShortCode($shortCode);

            if (!is_array($record) || !$this->findPublicRecord($record['entity_type'], $record['slug'])) {
                $wp->query_vars = ['error' => '404'];
            }

            return;
        }

        $route = isset($wp->query_vars['civic_route']) ? (string) $wp->query_vars['civic_route'] : '';
        $slug = isset($wp->query_vars['civic_slug']) ? sanitize_title((string) $wp->query_vars['civic_slug']) : '';

        if ('' === $route) {
            return;
        }

        $shortcodes = [
            'consultation' => 'civic_thread_detail',
            'event' => 'civic_event_detail',
            'schedule' => 'civic_schedule_detail',
        ];

        if ('' === $slug || !isset($shortcodes[$route]) || !$this->findPublicRecord($route, $slug)) {
            $wp->query_vars = ['error' => '404'];

            return;
        }

        $pageId = (new FrontendPageResolver())->findPageIdByShortcode($shortcodes[$route]);

        if ($pageId <= 0) {
            $wp->query_vars = ['error' => '404'];

            return;
        }

        $wp->query_vars['page_id'] = $pageId;
        $wp->query_vars['civic_slug'] = $slug;
    }

    public function redirectLegacyIdUrl(): void
    {
        if (is_admin() || !empty(get_query_var('civic_route'))) {
            return;
        }

        foreach (['consultation' => 'thread_id', 'event' => 'event_id', 'schedule' => 'schedule_id'] as $route => $key) {
            $id = $this->requestId($key);

            if ($id <= 0) {
                continue;
            }

            $record = $this->findPublicRecordById($route, $id);

            if (!is_array($record) || empty($record['slug'])) {
                return;
            }

            wp_safe_redirect(self::url($route, (string) $record['slug']), 301);
            exit;
        }
    }

    /** Redirect a valid short URL to its public canonical slug URL. */
    public function redirectShortUrl(): void
    {
        $shortCode = get_query_var('civic_short_code');

        if (!is_scalar($shortCode) || '' === (string) $shortCode) {
            return;
        }

        $record = $this->shortUrls->findByShortCode(trim((string) $shortCode));

        if (!is_array($record) || !$this->findPublicRecord($record['entity_type'], $record['slug'])) {
            return;
        }

        wp_safe_redirect(self::url($record['entity_type'], $record['slug']), 301);
        exit;
    }

    /**
     * Keep a prefixed civic route canonical rather than redirecting to the
     * WordPress page that hosts its detail shortcode.
     *
     * @param string|false $redirectUrl Proposed canonical URL.
     * @param string $requestedUrl Requested URL.
     * @return string|false
     */
    public function preventWordPressCanonicalRedirect($redirectUrl, string $requestedUrl)
    {
        unset($requestedUrl);

        return ('' !== (string) get_query_var('civic_route') || '' !== (string) get_query_var('civic_short_code')) ? false : $redirectUrl;
    }

    public static function url(string $route, string $slug): string
    {
        return home_url('/' . trim($route, '/') . '/' . rawurlencode(sanitize_title($slug)) . '/');
    }

    private function findPublicRecord(string $route, string $slug): ?array
    {
        if ('consultation' === $route) {
            return (new ThreadRepository($this->wpdb))->findPublicBySlug($slug);
        }

        if ('event' === $route) {
            return (new EventRepository($this->wpdb))->findPublicBySlug($slug);
        }

        if ('schedule' === $route) {
            return (new ScheduleRepository($this->wpdb))->findPublicBySlug($slug);
        }

        return null;
    }

    private function findPublicRecordById(string $route, int $id): ?array
    {
        if ('consultation' === $route) {
            return (new ThreadRepository($this->wpdb))->findPublicById($id);
        }

        if ('event' === $route) {
            return (new EventRepository($this->wpdb))->findPublicById($id);
        }

        if ('schedule' === $route) {
            return (new ScheduleRepository($this->wpdb))->findPublicById($id);
        }

        return null;
    }

    private function requestId(string $key): int
    {
        if (!isset($_GET[$key])) {
            return 0;
        }

        $value = wp_unslash($_GET[$key]);

        return is_scalar($value) ? absint($value) : 0;
    }
}
