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
        $results = [];

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
                    $ticketId = Arr::get($ticket, 'id');
                    if (! is_string($ticketId)) {
                        throw new UnexpectedValueException('The Expo push API returned a ticket without an ID.');
                    }

                    $results[] = new ExpoPushTicket($batch[$index]->token, true, $ticketId);

                    continue;
                }

                $error = Arr::get($ticket, 'details.error');
                $token = $batch[$index]->token;
                $message = Arr::get($ticket, 'message');
                $results[] = new ExpoPushTicket(
                    token: $token,
                    accepted: false,
                    errorCode: is_string($error) ? $error : 'UnknownExpoPushError',
                    errorMessage: is_string($message) ? $message : null,
                );
            }
        }

        return new ExpoPushResult($results);
    }

    public function receipts(string ...$ticketIds): array
    {
        $results = [];

        foreach (array_chunk($ticketIds, 1000) as $batch) {
            $response = $this->request()->post('/push/getReceipts', ['ids' => $batch])->throw();
            $receipts = $response->json('data');

            if (! is_array($receipts)) {
                throw new UnexpectedValueException('The Expo push API returned an invalid receipt response.');
            }

            foreach ($receipts as $ticketId => $receipt) {
                if (! is_string($ticketId) || ! is_array($receipt)) {
                    throw new UnexpectedValueException('The Expo push API returned an invalid receipt.');
                }

                $delivered = Arr::get($receipt, 'status') === 'ok';
                $error = Arr::get($receipt, 'details.error');
                $message = Arr::get($receipt, 'message');
                $results[$ticketId] = new ExpoPushReceipt(
                    ticketId: $ticketId,
                    delivered: $delivered,
                    errorCode: $delivered ? null : (is_string($error) ? $error : 'UnknownExpoPushReceiptError'),
                    errorMessage: is_string($message) ? $message : null,
                );
            }
        }

        return $results;
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
                    || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429)),
            );

        return $this->accessToken === ''
            ? $request
            : $request->withToken($this->accessToken);
    }
}
