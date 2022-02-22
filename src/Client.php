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
use finfo;
use Generator;
use OneCart\Api\Model\Order\Order;
use OneCart\Api\Model\Order\OrderDetails;
use OneCart\Api\Model\Product\Product;
use OneCart\Api\Model\Product\ProductVersion;
use OneCart\Api\Model\ProductStock;
use OneCart\Api\Model\Subscription\Event;
use OneCart\Api\Model\Subscription\Subscription;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

use function array_map;
use function array_walk;
use function get_class;
use function http_build_query;
use function is_object;
use function json_decode;
use function json_encode;
use function sprintf;

use const FILEINFO_MIME_TYPE;
use const JSON_THROW_ON_ERROR;

class Client
{
    public const CURRENT_VERSION_API_URI = 'https://api.1cart.eu/v1';

    private const CONTENT_TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';
    private const CONTENT_TYPE_APPLICATION_JSON = 'application/json';

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
     * @param UriInterface $uri
     * @param array<int,Event::*> $events
     * @return Generator<string,Subscription>
     */
    public function subscribe(UriInterface $uri, array $events): Generator
    {
        array_walk($events, static function (string $event): void {
            Event::assertExists($event);
        });

        $requestData = [
            'callbackUrl' => (string) $uri,
            'events' => $events
        ];

        foreach ($this->sendRequest('POST', 'subscription', $requestData) as $subscription) {
            yield $subscription['id'] => Subscription::fromData($subscription, $this->uriFactory);
        }
    }

    /**
     * @param array<UuidInterface> $subscriptionIds
     */
    public function unsubscribe(array $subscriptionIds): void
    {
        $requestData = array_map(
            static fn(UuidInterface $subscriptionId): string => $subscriptionId->toString(),
            $subscriptionIds
        );

        $this->sendRequest('DELETE', 'subscription', $requestData);
    }

    /**
     * @return Generator<string,ProductStock>
     */
    public function allStocks(): Generator
    {
        foreach ($this->sendRequest('GET', 'stocks/all') as $stock) {
            yield $stock['seller_id'] => new ProductStock($stock['seller_id'], $stock['available_quantity']);
        }
    }

    /**
     * @return Generator<string,Order>
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

        foreach ($this->sendRequest('GET', 'orders/all', null, $queryData) as $order) {
            yield $order['number'] => Order::fromData($order);
        }
    }

    /**
     * @param array<int,string> $ordersNumbers
     * @return Generator<string,OrderDetails>
     */
    public function ordersDetails(array $ordersNumbers): Generator
    {
        foreach ($this->sendRequest('POST', 'orders', $ordersNumbers) as $order) {
            yield $order['number'] => OrderDetails::fromData($order, $this->uriFactory);
        }
    }

    /**
     * @return Generator<string,Product>
     */
    public function allProducts(?UuidInterface $categoryId = null, ?bool $disabled = null): Generator
    {
        $queryData = null;
        if (null !== $categoryId || null !== $disabled) {
            $queryData = [];
            if (null !== $categoryId) {
                $queryData['category'] = $categoryId->toString();
            }
            if (null !== $disabled) {
                $queryData['disabled'] = (int) $disabled;
            }
        }

        foreach ($this->sendRequest('GET', 'products/all', null, $queryData) as $product) {
            yield $product['seller_id'] => Product::fromData($product, $this->uriFactory);
        }
    }

    /**
     * @param array<string> $identities
     * @return Generator<string,Product>
     */
    public function products(array $identities): Generator
    {
        foreach ($this->sendRequest('POST', 'products', $identities) as $product) {
            yield $product['seller_id'] => Product::fromData($product, $this->uriFactory);
        }
    }

    /**
     * @param string $sellerId
     * @param ProductVersion $productVersion
     * @param array<UuidInterface>|null $suppliersIds
     * @param bool $disabled
     */
    public function createProduct(
        string $sellerId,
        ProductVersion $productVersion,
        ?array $suppliersIds = null,
        bool $disabled = false
    ): Product {
        $requestData = $productVersion->jsonSerialize();
        $requestData['seller_id'] = $sellerId;
        $requestData['disabled'] = $disabled;
        if (null !== $suppliersIds) {
            $requestData['suppliers'] = $suppliersIds;
        }

        $responseData = $this->sendRequest('POST', 'product', $requestData);

        return Product::fromData($responseData, $this->uriFactory);
    }

    /**
     * @param string $sellerId
     * @param ProductVersion $productVersion
     * @param array<UuidInterface>|null $suppliersIds
     * @param bool $disabled
     */
    public function updateProduct(
        string $sellerId,
        ProductVersion $productVersion,
        ?array $suppliersIds = [],
        bool $disabled = false
    ): Product {
        $requestData = $productVersion->jsonSerialize();
        $requestData['disabled'] = $disabled;
        if (null !== $suppliersIds) {
            $requestData['suppliers'] = $suppliersIds;
        }

        $responseData = $this->sendRequest('PUT', "product/{$sellerId}", $requestData);

        return Product::fromData($responseData, $this->uriFactory);
    }

    public function updateProductImage(string $sellerId, StreamInterface $imageStream, ?string $filename): void
    {
        $request = $this->buildFormDataRequest(
            $this->createRequest($this->buildUri("product/{$sellerId}/image"), 'POST'),
            ['image' => $this->createFileDataPart($imageStream, $filename)]
        );

        $response = $this->httpClient->sendRequest($request);

        $this->parseResponse($this->buildUri('product'), $response);
    }

    public function updateProductDigitalFile(
        string $sellerId,
        StreamInterface $fileStream,
        ?string $filename
    ): void {
        $uri = $this->buildUri("product/{$sellerId}/product-digital-file");

        $request = $this->buildFormDataRequest(
            $this->createRequest($uri, 'PUT'),
            ['files' => ['file' => $this->createFileDataPart($fileStream, $filename)]]
        );

        $response = $this->httpClient->sendRequest($request);

        $this->parseResponse($this->buildUri('product'), $response);
    }

    public function addImage(string $sellerId, StreamInterface $imageStream, ?string $filename): void
    {
        $uri = $this->buildUri("product/{$sellerId}/image");

        $request = $this->buildFormDataRequest(
            $this->createRequest($uri, 'POST'),
            ['image' => $this->createFileDataPart($imageStream, $filename)]
        );

        $response = $this->httpClient->sendRequest($request);

        $this->parseResponse($this->buildUri('product'), $response);
    }

    public function addImageAtPosition(
        string $sellerId,
        StreamInterface $imageStream,
        int $position,
        ?string $filename
    ): void {
        $uri = $this->buildUri("product/{$sellerId}/image/{$position}");

        $request = $this->buildFormDataRequest(
            $this->createRequest($uri, 'POST'),
            ['image' => $this->createFileDataPart($imageStream, $filename)]
        );

        $response = $this->httpClient->sendRequest($request);

        $this->parseResponse($this->buildUri('product'), $response);
    }

    public function replaceImageAtPosition(
        string $sellerId,
        StreamInterface $imageStream,
        int $position,
        ?string $filename
    ): void {
        $uri = $this->buildUri("product/{$sellerId}/image/{$position}");

        $request = $this->buildFormDataRequest(
            $this->createRequest($uri, 'PUT'),
            ['image' => $this->createFileDataPart($imageStream, $filename)]
        );

        $response = $this->httpClient->sendRequest($request);

        $this->parseResponse($this->buildUri('product'), $response);
    }

    public function deleteImageAtPosition(string $sellerId, int $position): void
    {
        $uri = $this->buildUri("product/{$sellerId}/image/{$position}");

        $request = $this->buildFormDataRequest(
            $this->createRequest($uri, 'DELETE'),
            null
        );

        $response = $this->httpClient->sendRequest($request);

        $this->parseResponse($this->buildUri('product'), $response);
    }

    public function moveImage(string $sellerId, int $position): void
    {
        $uri = $this->buildUri("product/{$sellerId}/image/{$position}/move");

        $request = $this->buildFormDataRequest(
            $this->createRequest($uri, 'POST'),
            null
        );

        $response = $this->httpClient->sendRequest($request);

        $this->parseResponse($this->buildUri('product'), $response);
    }

    public function moveImageInDirection(string $sellerId, int $position, int $destination): void
    {
        $uri = $this->buildUri("product/{$sellerId}/image/{$position}/move/{$destination}");

        $request = $this->buildFormDataRequest(
            $this->createRequest($uri, 'POST'),
            null
        );

        $response = $this->httpClient->sendRequest($request);

        $this->parseResponse($this->buildUri('product'), $response);
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
        $uri = $this->buildUri($path, $queryData);
        $request = $this->buildJsonRequest($this->createRequest($uri, $method), $bodyData);

        $response = $this->httpClient->sendRequest($request);

        return $this->parseResponse($uri, $response);
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

    /**
     * @param array<int,mixed> $responseData
     * @return array<int,ApiError>
     */
    private function parseResponseErrors(array $responseData): array
    {
        return array_map(
            static fn (array $errorData): ApiError => ApiError::fromData($errorData),
            $responseData
        );
    }

    /**
     * @param string $path
     * @param array<string,mixed>|null $queryData
     * @return UriInterface
     */
    private function buildUri(string $path, ?array $queryData = null): UriInterface
    {
        $uri = $this->baseUri->withPath(trim($this->baseUri->getPath(), '/') . '/' . trim($path, '/'));
        if (null === $queryData) {
            return $uri;
        }

        return $uri->withQuery(http_build_query($queryData));
    }

    private function createRequest(UriInterface $uri, string $method): RequestInterface
    {
        return $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('User-Agent', '1cart API Client')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-Client-Id', $this->apiClientId)
            ->withHeader('X-API-Key', $this->apiKey)
        ;
    }

    /**
     * @param RequestInterface $request
     * @param array<string,mixed>|null $bodyData
     * @return RequestInterface
     */
    private function buildJsonRequest(RequestInterface $request, ?array $bodyData): RequestInterface
    {
        if (null === $bodyData) {
            return $request;
        }

        return $request
            ->withHeader('Content-Type', self::CONTENT_TYPE_APPLICATION_JSON)
            ->withBody(
                $this->streamFactory->createStream(json_encode($bodyData, JSON_THROW_ON_ERROR))
            )
        ;
    }

    /**
     * @param RequestInterface $request
     * @param array<string,mixed>|null $formData
     * @return RequestInterface
     */
    private function buildFormDataRequest(RequestInterface $request, ?array $formData): RequestInterface
    {
        if (null === $formData) {
            return $request;
        }

        $formDataPart = new FormDataPart($formData);

        return $request
            ->withHeader('Content-Type', self::CONTENT_TYPE_MULTIPART_FORM_DATA)
            ->withBody($this->streamFactory->createStream($formDataPart->bodyToString()))
        ;
    }

    /**
     * @param UriInterface $uri
     * @param ResponseInterface $response
     * @return array<array-key,mixed>
     */
    private function parseResponse(UriInterface $uri, ResponseInterface $response): array
    {
        $contentType = $response->getHeaderLine('Content-Type');
        if (self::CONTENT_TYPE_APPLICATION_JSON === $contentType) {
            $responseData = $this->decodeBody($response);
        } else {
            throw new RuntimeException(sprintf(
                'Expected response of type "%s" but got "%s"',
                self::CONTENT_TYPE_APPLICATION_JSON,
                $contentType
            ));
        }

        $statusCode = $response->getStatusCode();
        if (200 !== $statusCode) {
            throw new ApiException(
                sprintf(
                    'The request to "%s" has returned an unexpected response code "%s"',
                    $uri->getPath(),
                    $statusCode
                ),
                $this->parseResponseErrors($responseData['errors'] ?? [])
            );
        }

        return $responseData;
    }

    private function createFileDataPart(StreamInterface $stream, ?string $filename): DataPart
    {
        $fileData = (string) $stream;
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($fileData);
        if (false === $mimeType) {
            throw new RuntimeException('Unable to determine the MIME type of the file');
        }

        return new DataPart($fileData, $filename, $mimeType);
    }
}
