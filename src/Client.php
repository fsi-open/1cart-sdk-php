<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api;

use Generator;
use LogicException;
use OneCart\Api\Model\Product;
use OneCart\Api\Model\ProductPrice;
use OneCart\Api\Model\ProductStock;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Client
{
    private const CURRENT_VERSION_API_URI = 'https://api.1cart.eu/v1';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiClientId;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        string $apiKey,
        string $apiClientId
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->apiKey = $apiKey;
        $this->apiClientId = $apiClientId;
    }

    /**
     * @return Generator|ProductStock[]
     */
    public function allStocks(): Generator
    {
        foreach ($this->parseResponseForQuery('stocks/all') as $stock) {
            yield $stock['seller_id'] => new ProductStock($stock['seller_id'], $stock['available_quantity']);
        }
    }

    /**
     * @return Generator|Product[]
     */
    public function allProducts(): Generator
    {
        foreach ($this->parseResponseForQuery('products/all') as $product) {
            yield $product['seller_id'] => new Product(
                $product['id'],
                $product['seller_id'],
                $product['disabled'],
                $product['short_code_uri'],
                new ProductPrice(
                    $product['price']['amount'],
                    $product['price']['currency'],
                    $product['price']['formatted']
                ),
                $product['tax_rate']
            );
        }
    }

    private function parseResponseForQuery(string $uri): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->prependUri($uri))
            ->withHeader('X-API-Key', $this->apiKey)
            ->withHeader('X-Client-Id', $this->apiClientId)
        ;

        $response = $this->httpClient->sendRequest($request);
        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException(
                sprintf(
                    'The request to "%s" has returned an unexpected response code "%s"',
                    $uri,
                    $response->getStatusCode()
                )
            );
        }

        return $this->decodeBody($response);
    }

    private function decodeBody(ResponseInterface $response): array
    {
        $data = json_decode((string) $response->getBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LogicException('Unable to decode response body');
        }

        if (false === is_array($data)) {
            throw new RuntimeException(
                sprintf(
                    'Expected the decoded response body to be an array, got "%s"',
                    true === is_object($data) ? get_class($data) : gettype($data)
                )
            );
        }

        return $data;
    }

    private function prependUri(string $uri): string
    {
        return sprintf('%s/%s', self::CURRENT_VERSION_API_URI, $uri);
    }
}
