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
use OneCart\Api\Model\EmailAddress;
use OneCart\Api\Model\InvoiceData;
use OneCart\Api\Model\Order\Order;
use OneCart\Api\Model\Person;
use OneCart\Api\Model\Product\DigitalUriProperties;
use OneCart\Api\Model\Dimensions;
use OneCart\Api\Model\Product\EuReturnRightsForfeitExtension;
use OneCart\Api\Model\Product\EuVatExemptionExtension;
use OneCart\Api\Model\Product\PhysicalProperties;
use OneCart\Api\Model\Product\PlVatGTUExtension;
use OneCart\Api\Model\Product\Product;
use OneCart\Api\Model\Product\ProductExtension;
use OneCart\Api\Model\FormattedMoney;
use OneCart\Api\Model\Product\ProductProperties;
use OneCart\Api\Model\Product\ProductVersion;
use OneCart\Api\Model\ProductStock;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_reduce;
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
            yield $order['number'] => $this->parseOrder($order);
        }
    }

    /**
     * @return Generator<Product>
     */
    public function allProducts(): Generator
    {
        foreach ($this->sendRequest('get', 'products/all') as $product) {
            yield $product['seller_id'] => $this->parseProduct($product);
        }
    }

    /**
     * @param array<string> $identities
     * @return Generator<Product>
     */
    public function products(array $identities): Generator
    {
        foreach ($this->sendRequest('post', 'products', $identities) as $product) {
            yield $product['seller_id'] => $this->parseProduct($product);
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
            $request = $request->withBody($this->streamFactory->createStream(json_encode($bodyData, JSON_THROW_ON_ERROR)));
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

    /**
     * @param array<array-key,mixed> $orderData
     * @return Order
     */
    private function parseOrder(array $orderData): Order
    {
        return new Order(
            Uuid::fromString($orderData['id']),
            $orderData['number'],
            new DateTimeImmutable($orderData['created_at']),
            new EmailAddress($orderData['customer']['email'] ?? ''),
            (null !== ($orderData['cancelled_at'] ?? null)) ? new DateTimeImmutable($orderData['cancelled_at']) : null,
            $orderData['payment_type'] ?? null,
            $orderData['shipping_type'] ?? null,
            $this->parseFormattedMoney($orderData['total'] ?? []),
            $this->parseFormattedMoney($orderData['total_with_shipping'] ?? []),
            $this->parseFormattedMoney($orderData['total_with_shipping_without_discount'] ?? []),
            $orderData['payment_state'] ?? null,
            $orderData['shipping_state'] ?? null,
            $orderData['comments'] ?? null,
            Person::fromData($orderData['contact_person'] ?? null),
            InvoiceData::fromData($orderData['invoice_data'] ?? null)
        );
    }

    /**
     * @param array<string,mixed> $productData
     * @return Product
     */
    private function parseProduct(array $productData): Product
    {
        $pageUri = (true === array_key_exists('page_uri', $productData))
            ? $this->uriFactory->createUri($productData['page_uri'])
            : null;

        $imageThumbnailUri = (true === array_key_exists('image_thumbnail', $productData))
            ? $this->uriFactory->createUri($productData['image_thumbnail'])
            : null;

        return new Product(
            Uuid::fromString($productData['id']),
            $productData['seller_id'],
            $this->uriFactory->createUri($productData['short_code_uri']),
            $productData['disabled'],
            array_map(static fn(string $uuid): UuidInterface => Uuid::fromString($uuid), $productData['suppliers']),
            new ProductVersion(
                $productData['name'],
                $pageUri,
                $imageThumbnailUri,
                $this->parseFormattedMoney($productData['price'] ?? []),
                $productData['tax_rate'],
                $this->parseProductProperties($productData['properties'] ?? null),
                $this->parseProductExtensions($productData['extensions'] ?? [])
            ),
        );
    }

    /**
     * @param array<string,mixed>|null $properties
     * @return ProductProperties|null
     */
    private function parseProductProperties(?array $properties): ?ProductProperties
    {
        if (null === $properties) {
            return null;
        }

        switch ($properties['type'] ?? null) {
            case 'digital-uri':
                return new DigitalUriProperties($this->uriFactory->createUri($properties['uri']));

            case 'physical':
                return new PhysicalProperties(
                    new Dimensions(
                        $properties['dimensions']['length'],
                        $properties['dimensions']['width'],
                        $properties['dimensions']['height']
                    ),
                    $properties['weight']
                );
        }

        throw new RuntimeException("Unknown product properties of type {$properties['type']}");
    }

    /**
     * @param string $extensionKey
     * @param array<string,mixed> $extensionData
     * @return ProductExtension
     */
    private function parseProductExtension(string $extensionKey, array $extensionData): ProductExtension
    {
        switch ($extensionKey) {
            case 'eu_vat_exemption':
                return new EuVatExemptionExtension($extensionData['vat_exemption'] ?? '');
            case 'eu_return_rights_forfeit':
                return new EuReturnRightsForfeitExtension($extensionData['forfeit_required'] ?? false);
            case 'pl_vat_gtu_code':
                return new PlVatGTUExtension($extensionData['vat_gtu_code'] ?? null);
        }

        throw new RuntimeException("Unknown product extension of type {$extensionKey}");
    }

    /**
     * @param array<string,mixed> $extensionsData
     * @return array<ProductExtension>
     */
    private function parseProductExtensions(array $extensionsData): array
    {
        return array_reduce(
            array_keys($extensionsData),
            function (array $parsedExtensions, string $extensionKey) use (&$extensionsData): array {
                $parsedExtensions[] = $this->parseProductExtension($extensionKey, $extensionsData[$extensionKey]);

                return $parsedExtensions;
            },
            []
        );
    }

    /**
     * @param array<array-key,mixed> $moneyData
     * @return FormattedMoney
     */
    private function parseFormattedMoney(array $moneyData): FormattedMoney
    {
        return new FormattedMoney(
            $moneyData['amount'] ?? '',
            $moneyData['currency'] ?? '',
            $moneyData['formatted'] ?? ''
        );
    }
}
