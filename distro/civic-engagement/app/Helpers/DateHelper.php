<?php

declare(strict_types=1);

namespace CivicPlatform\Helpers;

/**
 * Shared date formatting utilities.
 */
class DateHelper
{
    /**
     * Friendly empty date display value.
     */
    private const EMPTY_VALUE = '—';

    /**
     * Format a date using Ireland/UK style.
     *
     * @param mixed $value Raw date value.
     * @return string Formatted date or friendly empty state.
     */
    public function formatDate($value): string
    {
        return $this->format($value, 'd/m/Y');
    }

    /**
     * Format a date/time using Ireland/UK style.
     *
     * @param mixed $value Raw date/time value.
     * @return string Formatted date/time or friendly empty state.
     */
    public function formatDateTime($value): string
    {
        return $this->format($value, 'd/m/Y h:i A');
    }

    /**
     * Format a raw date value.
     *
     * @param mixed $value Raw date value.
     * @param string $format PHP date format.
     * @return string Formatted date or empty state.
     */
    private function format($value, string $format): string
    {
        if ($this->isEmptyDate($value)) {
            return self::EMPTY_VALUE;
        }

        $timestamp = strtotime((string) $value);

        if (false === $timestamp) {
            return self::EMPTY_VALUE;
        }

        return date($format, $timestamp);
    }

    /**
     * Check whether a date value should be treated as empty.
     *
     * @param mixed $value Raw date value.
     * @return bool True when empty.
     */
    private function isEmptyDate($value): bool
    {
        if (null === $value) {
            return true;
        }

        if (is_array($value) || is_object($value)) {
            return true;
        }

        $value = trim((string) $value);

        return '' === $value
            || '0000-00-00' === $value
            || '0000-00-00 00:00:00' === $value;
    }
}
