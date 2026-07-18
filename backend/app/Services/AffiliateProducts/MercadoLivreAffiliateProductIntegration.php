<?php

namespace App\Services\AffiliateProducts;

use App\Exceptions\AffiliateProductCatalogUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Throwable;

final readonly class MercadoLivreAffiliateProductIntegration implements AffiliateProductCatalog
{
    public function __construct(
        private Factory $http,
        private string $baseUrl,
        private string $siteId,
        private string $accessToken,
        private int $timeout,
        private int $connectTimeout,
    ) {}

    public function search(string $query, int $limit): array
    {
        try {
            $response = $this->request()
                ->get("/sites/{$this->siteId}/search", [
                    'q' => $query,
                    'limit' => $limit,
                ])
                ->throw();
            $results = $response->json('results');

            if (! is_array($results)) {
                throw new \UnexpectedValueException('The Mercado Livre API returned an invalid search response.');
            }

            $products = [];

            foreach ($results as $result) {
                if (! is_array($result)) {
                    continue;
                }

                $product = $this->mapProduct($result);

                if ($product !== null) {
                    $products[] = $product;
                }
            }

            return $products;
        } catch (AffiliateProductCatalogUnavailable $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new AffiliateProductCatalogUnavailable($exception);
        }
    }

    private function request(): PendingRequest
    {
        $request = $this->http
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->retry(
                [100, 500, 1000],
                when: fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429)),
            );

        return $this->accessToken === '' ? $request : $request->withToken($this->accessToken);
    }

    /** @param array<mixed, mixed> $result */
    private function mapProduct(array $result): ?AffiliateProductData
    {
        $raw = [];

        foreach ($result as $key => $value) {
            if (is_string($key)) {
                $raw[$key] = $value;
            }
        }

        $externalId = Arr::get($raw, 'id');
        $title = Arr::get($raw, 'title');
        $url = Arr::get($raw, 'permalink');
        $currency = Arr::get($raw, 'currency_id');

        if (! is_string($externalId) || $externalId === ''
            || ! is_string($title) || $title === ''
            || ! is_string($url) || $url === ''
            || ! is_string($currency) || strlen($currency) !== 3) {
            return null;
        }

        $price = Arr::get($raw, 'price');
        $imageUrl = Arr::get($raw, 'secure_thumbnail', Arr::get($raw, 'thumbnail'));

        return new AffiliateProductData(
            provider: 'mercadolivre',
            externalId: $externalId,
            title: $title,
            url: $url,
            affiliateUrl: null,
            priceCents: is_numeric($price) ? (int) round((float) $price * 100) : null,
            currency: strtoupper($currency),
            imageUrl: is_string($imageUrl) && $imageUrl !== '' ? $imageUrl : null,
            raw: $raw,
        );
    }
}
