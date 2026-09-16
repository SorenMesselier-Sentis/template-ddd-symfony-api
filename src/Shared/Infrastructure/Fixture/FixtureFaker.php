<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture;

use Faker\Factory;
use Faker\Generator;

/**
 * Entry point for the optional random-bulk layer fixtures can add on top of
 * their hand-curated, named rows (see README "Fixtures and test data").
 * Each call returns a fresh generator — fixture classes should keep their own
 * instance for the duration of their `load()` rather than sharing one, so
 * `unique()` state from one BC's fields never collides with another's.
 */
final class FixtureFaker
{
    public static function create(): Generator
    {
        return Factory::create();
    }

    /**
     * `Generator::randomElement()` is stubbed as returning `mixed` (it accepts
     * any array), so callers picking from a known list of strings would
     * otherwise need a `(string)` cast — which PHPStan's strict rules refuse
     * on a `mixed` value. Centralize the narrowing here instead.
     *
     * @param non-empty-list<string> $choices
     */
    public static function randomElement(Generator $faker, array $choices): string
    {
        /** @var string $choice */
        $choice = $faker->randomElement($choices);

        return $choice;
    }
}
