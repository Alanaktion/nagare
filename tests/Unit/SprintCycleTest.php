<?php

use App\Enums\SprintCycle;
use Carbon\CarbonImmutable;

test('slugs follow each cycle', function (SprintCycle $cycle, string $date, string $slug) {
    expect($cycle->slugFor(CarbonImmutable::parse($date)))->toBe($slug);
})->with([
    'weekly' => [SprintCycle::Weekly, '2026-10-07', '2026W41'],
    'weekly at year end uses the ISO week year' => [SprintCycle::Weekly, '2027-01-01', '2026W53'],
    'monthly' => [SprintCycle::Monthly, '2026-10-07', '2026-10'],
    'quarterly' => [SprintCycle::Quarterly, '2026-10-07', '2026Q4'],
    'custom falls back to the ISO date' => [SprintCycle::Custom, '2026-10-07', '2026-10-07'],
]);

test('fixed cycles cover their whole calendar period', function (SprintCycle $cycle, string $date, string $start, string $end) {
    [$first, $last] = $cycle->periodFor(CarbonImmutable::parse($date));

    expect($first->toDateString())->toBe($start)->and($last->toDateString())->toBe($end);
})->with([
    'weekly runs Monday to Sunday' => [SprintCycle::Weekly, '2026-10-07', '2026-10-05', '2026-10-11'],
    'weekly across a year end' => [SprintCycle::Weekly, '2027-01-01', '2026-12-28', '2027-01-03'],
    'monthly' => [SprintCycle::Monthly, '2026-02-17', '2026-02-01', '2026-02-28'],
    'quarterly' => [SprintCycle::Quarterly, '2026-11-30', '2026-10-01', '2026-12-31'],
]);

test('custom cycles have no fixed period and are not automatic', function () {
    expect(SprintCycle::Custom->periodFor(CarbonImmutable::parse('2026-10-07')))->toBeNull()
        ->and(SprintCycle::Custom->isAutomatic())->toBeFalse()
        ->and(SprintCycle::Weekly->isAutomatic())->toBeTrue();
});
