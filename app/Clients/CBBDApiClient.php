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

class CBBDApiClient
{
    public function __construct(
        private readonly ?string $token,
        private readonly string $baseUrl,
    ) {}

    /**
     * Fetches games from the CBBD API based on the provided query parameters.
     *
     * @param  array<string, mixed>  $query
     * @return \Generator<int, array<string, mixed>>
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
     * Fetches teams from the CBBD API based on the provided query parameters.
     *
     * @param  array<string, mixed>  $query
     * @return \Generator<int, array<string, mixed>>
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
     * Creates a new pending HTTP request with the appropriate base URL, headers and authentication for the CBBD API.
     */
    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($this->token)
            ->asJson();
    }

    /**
     * Sends a request to the specified CBBD API endpoint with the provided query parameters,
     * and returns a generator that yields each item in the response as an associative array.
     *
     * @param  array<string, mixed>  $query
     * @param  class-string<GameImportException|TeamImportException>  $exceptionClass
     * @return \Generator<int, array<string, mixed>>
     */
    private function streamCollection(string $endpoint, array $query, string $exceptionClass): \Generator
    {
        $this->ensureTokenConfigured($exceptionClass);

        $response = $this->sendStreamRequest($endpoint, $query, $exceptionClass);
        $stream = $response->toPsrResponse()->getBody()->detach();

        if (! is_resource($stream)) {
            throw $this->newImportException($exceptionClass, 'CBBD returned an invalid response payload.');
        }

        try {
            foreach (Items::fromStream($stream, ['decoder' => new ExtJsonDecoder(true)]) as $item) {
                if (! is_array($item)) {
                    throw $this->newImportException($exceptionClass, 'CBBD returned an invalid response payload.');
                }

                yield $item;
            }
        } catch (GameImportException|TeamImportException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw $this->newImportException($exceptionClass, 'CBBD returned an invalid response payload.', $exception);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Sends a GET request to the specified CBBD API endpoint with the provided query parameters, using streaming to handle large responses.
     *
     * @param  array<string, mixed>  $query
     * @param  class-string<GameImportException|TeamImportException>  $exceptionClass
     */
    private function sendStreamRequest(string $endpoint, array $query, string $exceptionClass): Response
    {
        try {
            return $this->request()
                ->withOptions(['stream' => true])
                ->get($endpoint, $query)
                ->throw();
        } catch (RequestException $exception) {
            throw $this->newImportException(
                $exceptionClass,
                'CBBD API request failed: '.$exception->getMessage(),
                $exception,
            );
        }
    }

    /**
     * Makes sure the CBBD API token is configured, throwing an appropriate exception if it is not.
     *
     * @param  class-string<GameImportException|TeamImportException>  $exceptionClass
     */
    private function ensureTokenConfigured(string $exceptionClass): void
    {
        if (blank($this->token)) {
            throw $this->newImportException($exceptionClass, 'CBBD API credentials are not configured.');
        }
    }

    /**
     * Creates a new instance of the specified exception class with the provided message and optional previous exception.
     *
     * @param  class-string<GameImportException|TeamImportException>  $exceptionClass
     */
    private function newImportException(string $exceptionClass, string $message, ?Throwable $previous = null): GameImportException|TeamImportException
    {
        return new $exceptionClass($message, previous: $previous);
    }
}
