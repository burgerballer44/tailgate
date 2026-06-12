<?php

namespace App\DTO;

/**
 * Wraps streamed import items and deferred fetch errors in one transport object.
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
     * @param  \Generator<int, TItem, void, array<int, string>>  $generator
     *                                                                       A generator that yields imported DTO items and returns an array of error strings.
     */
    public function __construct(private readonly \Generator $generator) {}

    /**
     * Exposes the item stream for incremental processing.
     * Callers must fully exhaust this before calling errors().
     *
     * @return \Generator<int, TItem>
     */
    public function items(): \Generator
    {
        return $this->generator;
    }

    /**
     * Exposes fetch-time errors accumulated while the stream was consumed.
     * This must only be called after items() has been fully iterated.
     *
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->generator->getReturn();
    }

    /**
     * Builds a stream wrapper from in-memory items for tests and non-streaming contexts.
     *
     * @template TFromArrayItem
     *
     * @param  array<int, TFromArrayItem>  $items
     * @param  array<int, string>  $errors
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
