<?php

declare(strict_types=1);

use App\Models\Sprint;

test('weekly slug formats correctly', function (): void {
    $timestamp = mktime(0, 0, 0, 3, 10, 2025); // March 10, 2025 = Week 10

    expect(Sprint::dateSlug('weekly', $timestamp))->toBe('2025W10');
});

test('monthly slug formats correctly', function (): void {
    $timestamp = mktime(0, 0, 0, 3, 10, 2025); // March 2025

    expect(Sprint::dateSlug('monthly', $timestamp))->toBe('2025-03');
});

test('quarterly slug formats correctly for Q1', function (): void {
    $timestamp = mktime(0, 0, 0, 1, 15, 2025); // January = Q1

    expect(Sprint::dateSlug('quarterly', $timestamp))->toBe('2025Q1');
});

test('quarterly slug formats correctly for Q2', function (): void {
    $timestamp = mktime(0, 0, 0, 4, 1, 2025); // April = Q2

    expect(Sprint::dateSlug('quarterly', $timestamp))->toBe('2025Q2');
});

test('quarterly slug formats correctly for Q3', function (): void {
    $timestamp = mktime(0, 0, 0, 7, 1, 2025); // July = Q3

    expect(Sprint::dateSlug('quarterly', $timestamp))->toBe('2025Q3');
});

test('quarterly slug formats correctly for Q4', function (): void {
    $timestamp = mktime(0, 0, 0, 10, 1, 2025); // October = Q4

    expect(Sprint::dateSlug('quarterly', $timestamp))->toBe('2025Q4');
});

test('default slug formats as date', function (): void {
    $timestamp = mktime(0, 0, 0, 3, 10, 2025);

    expect(Sprint::dateSlug('custom', $timestamp))->toBe('2025-03-10');
});

test('unknown cycle falls back to date format', function (): void {
    $timestamp = mktime(0, 0, 0, 6, 15, 2025);

    expect(Sprint::dateSlug('biweekly', $timestamp))->toBe('2025-06-15');
});
