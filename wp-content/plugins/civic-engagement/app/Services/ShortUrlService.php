<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Repositories\ShortUrlRepository;

/**
 * Coordinates global short-code validation and short URL construction.
 */
class ShortUrlService
{
    private const DEFAULT_PREFIX = 'go';

    private ShortUrlRepository $repository;

    public function __construct(ShortUrlRepository $repository)
    {
        $this->repository = $repository;
    }

    public static function prefix(): string
    {
        $prefix = apply_filters('civic_short_url_prefix', self::DEFAULT_PREFIX);
        $prefix = strtolower(trim(is_scalar($prefix) ? (string) $prefix : self::DEFAULT_PREFIX));

        return preg_match('/^[a-z0-9-]+$/', $prefix) ? $prefix : self::DEFAULT_PREFIX;
    }

    public static function url(string $shortCode): string
    {
        return home_url('/' . self::prefix() . '/' . rawurlencode($shortCode) . '/');
    }

    public function normalize(string $shortCode): string
    {
        return strtolower(trim($shortCode));
    }

    public function validationError(string $shortCode, string $entityType, ?int $entityId = null): ?string
    {
        if ('' === $shortCode) {
            return null;
        }

        if (1 !== preg_match('/^[a-z0-9-]+$/', $shortCode)) {
            return __('Short URL code may contain only lowercase letters, numbers, and hyphens.', 'civic-engagement');
        }

        if ($this->repository->existsForAnotherEntity($shortCode, $entityType, $entityId)) {
            return __('This Short URL code is already in use.', 'civic-engagement');
        }

        return null;
    }

    /** @return array{entity_type: string, id: int, slug: string}|null */
    public function findByShortCode(string $shortCode): ?array
    {
        if ('' === $shortCode || 1 !== preg_match('/^[a-z0-9-]+$/', $shortCode)) {
            return null;
        }

        return $this->repository->findByShortCode($shortCode);
    }
}
