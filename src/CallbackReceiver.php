<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api;

use DateInterval;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use JsonException;
use OneCart\Api\Model\Order\OrderDetails;
use OneCart\Api\Model\Subscription\Event;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

use function array_key_exists;
use function array_reduce;
use function base64_decode;
use function base64_encode;
use function explode;
use function hash;
use function hash_equals;
use function hash_hmac;
use function is_array;
use function json_decode;
use function mb_strtolower;
use function preg_match;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class CallbackReceiver
{
    private const DIGEST_ALGORITHM = 'sha512';
    private const SIGNATURE_ALGORITHM = 'sha3-512';
    private const SIGNATURE_PARAMETER_PATTERN = '/\A(keyId|algorithm|headers|signature)="(.*)"\z/';
    private const SIGNED_CONTENT_FORMAT = '(request-target): post %s\nDate: %s\nDigest: SHA-512=%s';

    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    private UriFactoryInterface $uriFactory;
    private string $apiClientId;
    private string $apiSigningKey;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        string $apiClientId,
        string $apiSigningKey
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
        $this->apiClientId = $apiClientId;
        $this->apiSigningKey = $apiSigningKey;
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable(string,OrderDetails):bool $processor
     * @return ResponseInterface
     */
    public function receiveCallback(ServerRequestInterface $request, callable $processor): ResponseInterface
    {
        if ('post' !== mb_strtolower($request->getMethod())) {
            return $this->responseFactory->createResponse(405)
                ->withBody($this->streamFactory->createStream('HTTP METHOD ERROR'))
            ;
        }

        if ('application/json' !== $request->getHeaderLine('Content-Type')) {
            return $this->responseFactory->createResponse(415)
                ->withBody($this->streamFactory->createStream('CONTENT TYPE ERROR'))
            ;
        }

        if (false === $this->verifyRequestDate($request)) {
            return $this->responseFactory->createResponse(400)
                ->withBody($this->streamFactory->createStream('REQUEST DATE ERROR'))
            ;
        }

        if (false === $this->verifySignature($request)) {
            return $this->responseFactory->createResponse(401)
                ->withBody($this->streamFactory->createStream('AUTHENTICATION ERROR'))
            ;
        }

        try {
            $requestData = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            return $this->responseFactory->createResponse(400)
                ->withBody($this->streamFactory->createStream('BODY DECODING ERROR'))
            ;
        }

        if (false === is_array($requestData)) {
            return $this->responseFactory->createResponse(400)
                ->withBody($this->streamFactory->createStream('BODY FORMAT ERROR'))
            ;
        }

        $event = $requestData['event'];
        try {
            Event::assertExists($event);
            $order = OrderDetails::fromData($requestData['order'], $this->uriFactory);
        } catch (InvalidArgumentException $exception) {
            return $this->responseFactory->createResponse(400)
                ->withBody($this->streamFactory->createStream('INVALID REQUEST DATA ERROR'))
            ;
        }

        if (true !== $processor($event, $order)) {
            return $this->responseFactory->createResponse(500)
                ->withBody($this->streamFactory->createStream('PROCESSING ERROR'))
            ;
        }

        return $this->responseFactory->createResponse()->withBody($this->streamFactory->createStream('OK'));
    }

    private function verifyRequestDate(ServerRequestInterface $request): bool
    {
        $requestDate = null;
        try {
            $requestDate = new DateTimeImmutable($request->getHeaderLine('Date'));
        } catch (Exception $exception) {
        }

        $now = new DateTimeImmutable();
        return null !== $requestDate
            && $requestDate >= $now->sub(new DateInterval('PT5M'))
            && $requestDate <= $now->add(new DateInterval('PT1M'))
        ;
    }

    private function verifySignature(ServerRequestInterface $request): bool
    {
        $signatureParams = $this->extractSignatureParameters($request);

        if (
            ($signatureParams['keyId'] ?? null) !== $this->apiClientId
            || ($signatureParams['algorithm'] ?? null) !== self::SIGNATURE_ALGORITHM
            || ($signatureParams['headers'] ?? null) !== '(request-target) date digest'
            || false === array_key_exists('signature', $signatureParams)
        ) {
            return false;
        }

        $requestTarget = $request->getUri()->withScheme('')->withHost('')->withPort(null)->withUserInfo('');
        $requestDate = $request->getHeaderLine('Date');
        $requestBodyDigest = base64_encode(hash(self::DIGEST_ALGORITHM, (string) $request->getBody(), true));
        $signedContent = sprintf(self::SIGNED_CONTENT_FORMAT, $requestTarget, $requestDate, $requestBodyDigest);
        $signature = hash_hmac(self::SIGNATURE_ALGORITHM, $signedContent, $this->apiSigningKey);

        return hash_equals($signature, base64_decode($signatureParams['signature']));
    }

    /**
     * @param ServerRequestInterface $request
     * @return array{keyId?:string,algorithm?:string,headers?:string,signature?:string}
     */
    private function extractSignatureParameters(ServerRequestInterface $request): array
    {
        // phpcs:ignore Generic.Files.LineLength
        // keyId="23b790de-10d3-48d8-a7ca-d2470e6b15c8",algorithm="sha3-512",headers="(request-target) date digest",signature="MDA2...OWY="
        return array_reduce(
            explode(',', $request->getHeaderLine('Signature')),
            static function (array $params, string $segment): array {
                $matches = [];
                $result = preg_match(self::SIGNATURE_PARAMETER_PATTERN, $segment, $matches);
                if (1 !== $result) {
                    return $params;
                }
                $params[$matches[1]] = $matches[2];

                return $params;
            },
            []
        );
    }
}
