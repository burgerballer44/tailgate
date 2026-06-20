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
 * Base client for CollegeData APIs (CFBD and CBBD).
 * 
 * https://collegefootballdata.com
 * https://collegebasketballdata.com/
 * 
 */
abstract class BaseCollegeDataApiClient
{
    /**
     * Returns the provider identifier used in error messages, e.g. "CFBD".
     */
    abstract protected function providerCode(): string;

    public function __construct(
        private readonly ?string $token,
        private readonly string $baseUrl,
    ) {}

    /**
     * Fetches games from the provider's API, yielding each game as an associative array.
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
     * Fetches teams from the provider's API, yielding each team as an associative array.
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
     * Creates a configured HTTP client for making API requests.
     * 
     * @return PendingRequest
     */
    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($this->token)
            ->asJson();
    }

    /**
     * Streams a collection of items from the provider's API.
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
            throw $this->newImportException(
                $exceptionClass,
                $this->providerCode().' returned an invalid response payload.',
            );
        }

        try {
            foreach (Items::fromStream($stream, ['decoder' => new ExtJsonDecoder(true)]) as $item) {
                if (! is_array($item)) {
                    throw $this->newImportException(
                        $exceptionClass,
                        $this->providerCode().' returned an invalid response payload.',
                    );
                }

                yield $item;
            }
        } catch (GameImportException|TeamImportException $exception) {
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
     * Sends a streaming request to the provider's API.
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
            $responseBody = $exception->response?->body();
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
     * Ensures that the API token is configured.
     * 
     * @param  class-string<GameImportException|TeamImportException>  $exceptionClass
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
     * Creates a new import exception.
     *
     * @param  class-string<GameImportException|TeamImportException>  $exceptionClass
     * @param  string  $message
     * @param  Throwable|null  $previous
     * @return GameImportException|TeamImportException
     */
    private function newImportException(string $exceptionClass, string $message, ?Throwable $previous = null): GameImportException|TeamImportException
    {
        return new $exceptionClass($message, previous: $previous);
    }
}