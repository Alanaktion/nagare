<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

enum SprintCycle: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Custom = 'custom';

    /**
     * Whether sprints follow a fixed calendar period, so they can be created
     * automatically. Custom sprints always have manually chosen dates.
     */
    public function isAutomatic(): bool
    {
        return $this !== self::Custom;
    }

    /**
     * The slug of the sprint that starts on (or, for fixed cycles, contains) the given date.
     */
    public function slugFor(CarbonInterface $date): string
    {
        return match ($this) {
            self::Weekly => $date->format('o\WW'),
            self::Monthly => $date->format('Y-m'),
            self::Quarterly => $date->format('Y').'Q'.$date->quarter,
            self::Custom => $date->format('Y-m-d'),
        };
    }

    /**
     * The first and last day of the fixed calendar period containing the
     * given date, or null for custom cycles.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function periodFor(CarbonInterface $date): ?array
    {
        $date = $date->toImmutable()->startOfDay();

        return match ($this) {
            self::Weekly => [$date->startOfWeek(), $date->endOfWeek()->startOfDay()],
            self::Monthly => [$date->startOfMonth(), $date->endOfMonth()->startOfDay()],
            self::Quarterly => [$date->startOfQuarter(), $date->endOfQuarter()->startOfDay()],
            self::Custom => null,
        };
    }
}
