<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Dashboard\Services;

use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Reps\Repository\RepRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;

/**
 * Collects public-facing Civic Platform statistics.
 */
class PublicStatisticsService
{
    private RepRepository $reps;
    private ThreadRepository $threads;
    private ThreadResponseRepository $responses;
    private EventRepository $events;

    public function __construct(RepRepository $reps, ThreadRepository $threads, ThreadResponseRepository $responses, EventRepository $events)
    {
        $this->reps = $reps;
        $this->threads = $threads;
        $this->responses = $responses;
        $this->events = $events;
    }

    /**
     * Get statistics for public display.
     *
     * @return array<int, array{title: string, value: int}>
     */
    public function getStatistics(): array
    {
        return [
            [
                'title' => __('Community Representations', 'civic-engagement'),
                'value' => $this->count($this->reps),
            ],
            [
                'title' => __('Public Consultations', 'civic-engagement'),
                'value' => $this->count($this->threads),
            ],
            [
                'title' => __('Citizen Responses', 'civic-engagement'),
                'value' => $this->responseCountWithOffsets(),
            ],
            [
                'title' => __('Community Events', 'civic-engagement'),
                'value' => $this->count($this->events),
            ],
        ];
    }

    /** @param object $repository */
    private function count($repository): int
    {
        $result = $repository->getPaginated(['page' => 1, 'per_page' => 1]);

        return (int) ($result['total'] ?? 0);
    }

    private function responseCountWithOffsets(): int
    {
        return $this->count($this->responses) + $this->threads->sumStartingResponseCounts();
    }
}
