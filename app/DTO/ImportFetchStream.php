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
     * @param \Generator<int, TItem, void, array<int, string>> $generator A generator that yields imported DTO
     *     items one at a time and returns an array of error strings as its return value.
     */
    public function __construct(private readonly \Generator $generator) {}

    /**
     * Exposes the item stream for incremental processing.
     *
     * Callers must fully exhaust this generator before calling errors(). Calling
     * errors() before iteration is complete will return an incomplete error list
     * because the generator return value is only set once the function body exits.
     *
     * @return \Generator<int, TItem> The underlying generator yielding one DTO per iteration.
     */
    public function items(): \Generator
    {
        return $this->generator;
    }

    /**
     * Returns fetch-time errors accumulated while the stream was consumed.
     *
     * Must only be called after items() has been fully iterated; the generator
     * return value (and therefore this array) is not available until the generator
     * has run to completion.
     *
     * @return array<int, string> Zero or more error message strings recorded during fetching.
     */
    public function errors(): array
    {
        return $this->generator->getReturn();
    }

    /**
     * Builds a stream wrapper from in-memory items for tests and non-streaming contexts.
     *
     * Wraps a plain array in a generator so callers that depend on ImportFetchStream
     * can be exercised without a real HTTP streaming response.
     *
     * @template TFromArrayItem
     *
     * @param array<int, TFromArrayItem> $items The items to yield during iteration.
     * @param array<int, string> $errors Error strings to return after the generator completes.
     * @return self<TFromArrayItem> A fully constructed stream backed by the provided arrays.
     */
    public static function fromArray(array $items, array $errors = []): self
    {
        return new self((static function () use ($items, $errors): \Generator {
            yield from $items;

            return $errors;
        })());
    }
}
