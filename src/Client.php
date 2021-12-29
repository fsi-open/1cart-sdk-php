<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api;

use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use OneCart\Api\Model\Order\Order;
use OneCart\Api\Model\Order\OrderDetails;
use OneCart\Api\Model\Product\Product;
use OneCart\Api\Model\ProductStock;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

use function http_build_query;
use function json_encode;

class Client
{
    public const CURRENT_VERSION_API_URI = 'https://api.1cart.eu/v1';

    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private UriFactoryInterface $uriFactory;
    private UriInterface $baseUri;
    private string $apiClientId;
    private string $apiKey;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        string $baseUri,
        string $apiClientId,
        string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
        $this->baseUri = $uriFactory->createUri($baseUri);
        $this->apiClientId = $apiClientId;
        $this->apiKey = $apiKey;
    }

    /**
     * @return Generator<ProductStock>
     */
    public function allStocks(): Generator
    {
        foreach ($this->sendRequest('get', 'stocks/all') as $stock) {
            yield $stock['seller_id'] => new ProductStock($stock['seller_id'], $stock['available_quantity']);
        }
    }

    /**
     * @return Generator<Order>
     */
    public function allOrders(
        ?DateTimeImmutable $createdAtFrom = null,
        ?DateTimeImmutable $createdAtTo = null
    ): Generator {
        $queryData = [];
        if (null !== $createdAtFrom) {
            $queryData['created_at_from'] = $createdAtFrom->format(DateTimeInterface::RFC3339);
        }
        if (null !== $createdAtTo) {
            $queryData['created_at_to'] = $createdAtTo->format(DateTimeInterface::RFC3339);
        }
        foreach ($this->sendRequest('get', 'orders/all', null, $queryData) as $order) {
            yield $order['number'] => Order::fromData($order);
        }
    }

    /**
     * @param array<int,string> $ordersNumbers
     * @return Generator<OrderDetails>
     */
    public function ordersDetails(array $ordersNumbers): Generator
    {
        foreach ($this->sendRequest('post', 'orders', $ordersNumbers) as $order) {
            yield $order['number'] => OrderDetails::fromData($order, $this->uriFactory);
        }
    }

    /**
     * @return Generator<Product>
     */
    public function allProducts(): Generator
    {
        foreach ($this->sendRequest('get', 'products/all') as $product) {
            yield $product['seller_id'] => Product::fromData($product, $this->uriFactory);
        }
    }

    /**
     * @param array<string> $identities
     * @return Generator<Product>
     */
    public function products(array $identities): Generator
    {
        foreach ($this->sendRequest('post', 'products', $identities) as $product) {
            yield $product['seller_id'] => Product::fromData($product, $this->uriFactory);
        }
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<array-key,mixed>|null $bodyData
     * @param array<array-key,mixed>|null $queryData
     * @return array<array-key,mixed>
     */
    private function sendRequest(string $method, string $path, ?array $bodyData = null, ?array $queryData = null): array
    {
        $uri = $this->buildUri($path);
        if (null !== $queryData && 0 !== count($queryData)) {
            $uri = $uri->withQuery(http_build_query($queryData));
        }
        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('User-Agent', '1cart API Client')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-Client-Id', $this->apiClientId)
            ->withHeader('X-API-Key', $this->apiKey)
        ;
        if (null !== $bodyData) {
            $request = $request->withBody(
                $this->streamFactory->createStream(json_encode($bodyData, JSON_THROW_ON_ERROR))
            );
        }

        $response = $this->httpClient->sendRequest($request);
        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException(
                sprintf(
                    'The request to "%s" has returned an unexpected response code "%s"',
                    $path,
                    $response->getStatusCode()
                )
            );
        }

        return $this->decodeBody($response);
    }

    /**
     * @param ResponseInterface $response
     * @return array<array-key,mixed>
     */
    private function decodeBody(ResponseInterface $response): array
    {
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

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

    private function buildUri(string $path): UriInterface
    {
        return $this->baseUri->withPath(trim($this->baseUri->getPath(), '/') . '/' . trim($path, '/'));
    }
}
