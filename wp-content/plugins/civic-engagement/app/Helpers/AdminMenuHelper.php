<?php

declare(strict_types=1);

namespace CivicPlatform\Helpers;

/**
 * Small helpers for keeping registered admin pages out of the visible menu.
 */
class AdminMenuHelper
{
    /**
     * Hide registered submenu pages while preserving direct URL access.
     *
     * @param string $parentSlug Parent menu slug.
     * @param array<int, string> $menuSlugs Submenu page slugs to hide.
     * @return void
     */
    public static function hideSubmenuPages(string $parentSlug, array $menuSlugs): void
    {
        foreach ($menuSlugs as $menuSlug) {
            remove_submenu_page($parentSlug, $menuSlug);
        }
    }
}
