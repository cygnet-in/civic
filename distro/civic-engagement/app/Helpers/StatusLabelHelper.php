<?php

declare(strict_types=1);

namespace CivicPlatform\Helpers;

/** Converts stored workflow status keys into readable admin labels. */
class StatusLabelHelper
{
    public static function format($status): string
    {
        if (is_array($status) || is_object($status)) {
            return '';
        }

        return ucwords(str_replace('_', ' ', trim((string) $status)));
    }
}
