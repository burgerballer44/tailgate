<?php

namespace App\Clients;

use App\Exceptions\GameImportException;
use App\Exceptions\TeamImportException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use Throwable;

/**
 * Base client for the CollegeFootballData (CFBD) and CollegeBasketballData (CBBD) APIs.
 *
 * Both providers share the same REST conventions and JSON streaming response format,
 * so all transport, streaming, and error-handling logic lives here. Subclasses only
 * need to supply the provider code used in error messages.
 *
 * @see https://collegefootballdata.com
 * @see https://collegebasketballdata.com
 */
abstract class BaseCollegeDataApiClient
{
    /**
     * Returns the short provider identifier used in error and log messages.
     *
     * This is used to prefix exception messages so callers can tell which
     * provider failed without inspecting exception types (e.g. "CFBD", "CBBD").
     *
     * @return string The provider code, e.g. "CFBD" or "CBBD".
     */
    abstract protected function providerCode(): string;

    /**
     * @param string|null $token Bearer token for the provider's API. A null or
     *     blank value is allowed at construction time so that misconfigured
     *     environments fail at call time with a descriptive exception rather than at boot.
     * @param string $baseUrl Root URL of the provider's API (no trailing slash).
     */
    public function __construct(
        private readonly ?string $token,
        private readonly string $baseUrl,
    ) {}

    /**
     * Streams games from the provider's API, yielding one game per iteration.
     *
     * The response is consumed as a stream rather than buffered in memory, which
     * keeps peak memory usage constant regardless of how many games the API returns.
     *
    * @param array<string, mixed> $query Query parameters forwarded verbatim to
    *     the /games endpoint (e.g. year, season type).
     * @return \Generator<int, array<string, mixed>>  Yields each game as an associative
     *                                                array matching the provider's schema.
     *
     * @throws GameImportException  If the token is not configured, the HTTP request fails,
     *                              or the response body cannot be parsed as a JSON array.
     */
    public function fetchGames(array $query): \Generator
    {
        return $this->streamCollection(
            endpoint: '/games',
            query: $query,
            exceptionClass: GameImportException::class,
        );
    }

    /**
     * Streams teams from the provider's API, yielding one team per iteration.
     *
     * The response is consumed as a stream rather than buffered in memory, which
     * keeps peak memory usage constant regardless of how many teams the API returns.
     *
    * @param array<string, mixed> $query Query parameters forwarded verbatim to
    *     the /teams endpoint.
     * @return \Generator<int, array<string, mixed>>  Yields each team as an associative
     *                                                array matching the provider's schema.
     *
     * @throws TeamImportException  If the token is not configured, the HTTP request fails,
     *                              or the response body cannot be parsed as a JSON array.
     */
    public function fetchTeams(array $query): \Generator
    {
        return $this->streamCollection(
            endpoint: '/teams',
            query: $query,
            exceptionClass: TeamImportException::class,
        );
    }

    /**
     * Builds a configured HTTP client for the provider.
     *
     * `asJson()` is included alongside `acceptJson()` because some provider endpoints
     * return a 400 if the Content-Type header is absent, even for GET requests.
     *
     * @return PendingRequest A pre-configured client that targets the provider's base URL.
     */
    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($this->token)
            ->asJson();
    }

    /**
     * Streams a JSON array from the given endpoint, yielding one decoded element at a time.
     *
     * Uses JsonMachine for incremental parsing so that large API responses do not require
     * the entire payload to be held in memory. The underlying PHP stream is always closed
     * in the `finally` block to prevent resource leaks, even if the caller abandons the
     * generator mid-iteration.
     *
    * @param string $endpoint API path relative to the base URL (e.g. "/games").
    * @param array<string, mixed> $query Query parameters for the request.
    * @param class-string<GameImportException|TeamImportException> $exceptionClass Exception type to throw on failure;
    *     allows the same transport logic to serve both game and team imports.
     * @return \Generator<int, array<string, mixed>>  Each yielded value is one top-level
     *                                                element from the response JSON array.
     *
     * @throws GameImportException|TeamImportException  On missing token, HTTP error,
     *                                                  non-resource stream, or JSON parse failure.
     */
    private function streamCollection(string $endpoint, array $query, string $exceptionClass): \Generator
    {
        $this->ensureTokenConfigured($exceptionClass);

        $response = $this->sendStreamRequest($endpoint, $query, $exceptionClass);
        $stream = $response->toPsrResponse()->getBody()->detach();

        // `detach()` returns null when the stream has already been consumed or closed.
        // Treat a non-resource as a malformed response rather than letting PHP throw
        // an opaque type error deeper in JsonMachine.
        if (! is_resource($stream)) {
            throw $this->newImportException(
                $exceptionClass,
                $this->providerCode().' returned an invalid response payload.',
            );
        }

        try {
            foreach (Items::fromStream($stream, ['decoder' => new ExtJsonDecoder(true)]) as $item) {
                // The providers always return a top-level JSON array of objects, so each
                // item must be an array. Anything else indicates a malformed response
                // (e.g. the API returned a plain error string instead of an array).
                if (! is_array($item)) {
                    throw $this->newImportException(
                        $exceptionClass,
                        $this->providerCode().' returned an invalid response payload.',
                    );
                }

                yield $item;
            }
        } catch (GameImportException|TeamImportException $exception) {
            // Re-throw import exceptions without wrapping so callers receive the
            // original message rather than a double-wrapped exception chain.
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->newImportException(
                $exceptionClass,
                $this->providerCode().' returned an invalid response payload.',
                $exception,
            );
        } finally {
            fclose($stream);
        }
    }

    /**
     * Issues a streaming GET request to the provider and returns the raw response.
     *
     * The `stream` cURL option prevents the response body from being buffered by
     * Guzzle, which is required for JsonMachine to parse the body incrementally.
     * Without it the entire payload would be loaded into memory before yielding begins.
     *
    * @param string $endpoint API path relative to the base URL.
    * @param array<string, mixed> $query Query parameters for the request.
    * @param class-string<GameImportException|TeamImportException> $exceptionClass Exception type to wrap HTTP errors in.
     * @return Response  The successful (2xx) streaming response.
     *
     * @throws GameImportException|TeamImportException  If the HTTP request returns a
     *                                                  non-2xx status or a connection error occurs.
     */
    private function sendStreamRequest(string $endpoint, array $query, string $exceptionClass): Response
    {
        try {
            return $this->request()
                ->withOptions(['stream' => true])
                ->get($endpoint, $query)
                ->throw();
        } catch (RequestException $exception) {
            $responseBody = $exception->response?->body();

            // Append the raw response body to the message when it is not already
            // included by Laravel's RequestException, so the operator can see the
            // provider's error detail (e.g. "invalid token", rate-limit messages)
            // without needing to inspect the original exception separately.
            $messageSuffix = filled($responseBody) && ! str_contains($exception->getMessage(), $responseBody)
                ? ' Response body: '.$responseBody
                : '';

            throw $this->newImportException(
                $exceptionClass,
                $this->providerCode().' API request failed: '.$exception->getMessage().$messageSuffix,
                $exception,
            );
        }
    }

    /**
     * Throws an import exception if the API token has not been configured.
     *
     * Failing early with a clear message is preferable to letting the HTTP client
     * send a request with a null/empty Authorization header and receiving a cryptic
     * 401 response from the provider.
     *
    * @param class-string<GameImportException|TeamImportException> $exceptionClass Exception type to throw.
     *
     * @throws GameImportException|TeamImportException  When the token is null or blank.
     */
    private function ensureTokenConfigured(string $exceptionClass): void
    {
        if (blank($this->token)) {
            throw $this->newImportException(
                $exceptionClass,
                $this->providerCode().' API credentials are not configured.',
            );
        }
    }

    /**
     * Instantiates the appropriate import exception with the given message and optional cause.
     *
     * Centralises exception construction so that `streamCollection` and its helpers
     * do not need to know which concrete class to instantiate — the caller passes the
     * class string and this method handles the `new` call.
     *
    * @param class-string<GameImportException|TeamImportException> $exceptionClass The exception class to instantiate.
    * @param string $message Human-readable error message.
    * @param Throwable|null $previous Underlying cause, if any, for exception chaining.
     * @return GameImportException|TeamImportException  A new exception instance of the requested type.
     */
    private function newImportException(string $exceptionClass, string $message, ?Throwable $previous = null): GameImportException|TeamImportException
    {
        return new $exceptionClass($message, previous: $previous);
    }
}