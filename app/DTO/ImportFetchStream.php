<?php

namespace App\DTO;

/**
 * Generic streaming result from an import source fetch.
 *
 * Rather than buffering all fetched DTOs into an array, this class wraps a PHP Generator
 * that yields items one at a time. This keeps peak memory proportional to processing chunk size
 * rather than the total number of imported rows.
 *
 * The generator is expected to return an array of error strings as its return value
 * (i.e. via `return $errors;` at the end of the generator body), which becomes accessible
 * via errors() after the generator has been fully iterated.
 *
 * @template TItem
 */
class ImportFetchStream
{
    /**
     * @param \Generator<int, TItem, void, array<int, string>> $generator
     *   A generator that yields imported DTO items and returns an array of error strings.
     */
    public function __construct(private readonly \Generator $generator) {}

    /**
     * Returns the generator for iteration. Callers must fully exhaust this before calling errors().
     *
     * @return \Generator<int, TItem>
     */
    public function items(): \Generator
    {
        return $this->generator;
    }

    /**
     * Returns the error strings collected during the fetch.
     * This must only be called after items() has been fully iterated.
     *
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->generator->getReturn();
    }

    /**
     * Convenience factory for creating a stream backed by a plain array.
     * Intended for use in tests and other contexts where a real generator is not needed.
     *
     * @template TFromArrayItem
     * @param array<int, TFromArrayItem> $items
     * @param array<int, string> $errors
     * @return self<TFromArrayItem>
     */
    public static function fromArray(array $items, array $errors = []): self
    {
        return new self((static function () use ($items, $errors): \Generator {
            yield from $items;

            return $errors;
        })());
    }
}