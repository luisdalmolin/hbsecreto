<?php

namespace App\Notifications\ExpoPush;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Throwable;
use UnexpectedValueException;

final readonly class HttpExpoPushTransport implements ExpoPushTransport
{
    public function __construct(
        private Factory $http,
        private string $baseUrl,
        private string $accessToken,
        private int $timeout,
        private int $connectTimeout,
    ) {}

    public function send(ExpoPushMessage ...$messages): ExpoPushResult
    {
        $acceptedCount = 0;
        $invalidTokens = [];
        $errors = [];

        foreach (array_chunk($messages, 100) as $batch) {
            $response = $this->request()
                ->post('/push/send', array_map(
                    fn (ExpoPushMessage $message): array => $message->toArray(),
                    $batch,
                ))
                ->throw();
            $tickets = $response->json('data');

            if (! is_array($tickets) || count($tickets) !== count($batch)) {
                throw new UnexpectedValueException('The Expo push API returned an invalid ticket response.');
            }

            foreach ($tickets as $index => $ticket) {
                if (! is_array($ticket)) {
                    throw new UnexpectedValueException('The Expo push API returned an invalid ticket.');
                }

                if (Arr::get($ticket, 'status') === 'ok') {
                    $acceptedCount++;

                    continue;
                }

                $error = Arr::get($ticket, 'details.error');
                $token = $batch[$index]->token;

                if ($error === 'DeviceNotRegistered') {
                    $invalidTokens[] = $token;
                }

                $errors[] = is_string($error) ? $error : 'UnknownExpoPushError';
            }
        }

        return new ExpoPushResult($acceptedCount, array_values(array_unique($invalidTokens)), $errors);
    }

    private function request(): PendingRequest
    {
        $request = $this->http
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->retry(
                [100, 500, 1000],
                when: fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()),
            );

        return $this->accessToken === ''
            ? $request
            : $request->withToken($this->accessToken);
    }
}
