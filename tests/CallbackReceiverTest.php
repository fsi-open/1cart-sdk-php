<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Api;

use Closure;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\Uri;
use Laminas\Diactoros\UriFactory;
use OneCart\Api\CallbackReceiver;
use OneCart\Api\Model\Order\OrderDetails;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

use function base64_encode;
use function hash;
use function hash_hmac;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class CallbackReceiverTest extends TestCase
{
    private StreamFactory $streamFactory;
    private CallbackReceiver $callbackReceiver;

    public function testNonPostCallback(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('DELETE');

        $response = $this->callbackReceiver->receiveCallback($request, $this->createDummyCallbackProcessor());

        self::assertEquals(405, $response->getStatusCode());
        self::assertEquals('HTTP METHOD ERROR', (string) $response->getBody());
    }

    public function testCallbackWithWrongContentType(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('plain/text');

        $response = $this->callbackReceiver->receiveCallback($request, $this->createDummyCallbackProcessor());

        self::assertEquals(415, $response->getStatusCode());
        self::assertEquals('CONTENT TYPE ERROR', (string) $response->getBody());
    }

    public function testCallbackWithInvalidDate(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')
            ->withConsecutive(['Content-Type'], ['Date'])
            ->willReturnOnConsecutiveCalls('application/json', 'wrong date');

        $response = $this->callbackReceiver->receiveCallback($request, $this->createDummyCallbackProcessor());

        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('REQUEST DATE ERROR', (string) $response->getBody());
    }

    public function testCallbackWithTooOldDate(): void
    {
        $requestDate = (new DateTimeImmutable())->sub(new DateInterval('PT5M1S'))->format(DateTimeInterface::RFC3339);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')
            ->withConsecutive(['Content-Type'], ['Date'])
            ->willReturnOnConsecutiveCalls('application/json', $requestDate);

        $response = $this->callbackReceiver->receiveCallback($request, $this->createDummyCallbackProcessor());

        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('REQUEST DATE ERROR', (string) $response->getBody());
    }

    public function testCallbackWithTooEarlyDate(): void
    {
        $requestDate = (new DateTimeImmutable())->add(new DateInterval('PT1M1S'))->format(DateTimeInterface::RFC3339);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')
            ->withConsecutive(['Content-Type'], ['Date'])
            ->willReturnOnConsecutiveCalls('application/json', $requestDate);

        $response = $this->callbackReceiver->receiveCallback($request, $this->createDummyCallbackProcessor());

        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('REQUEST DATE ERROR', (string) $response->getBody());
    }

    public function testCallbackSignatureWithInvalidSignature(): void
    {
        $bodyContent = $this->getMockFileContents('order-callback.json');
        $requestDate = (new DateTimeImmutable())->format(DateTimeInterface::RFC3339);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn(new Uri('https://receiver.com/shipment'));
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($this->streamFactory->createStream($bodyContent));
        $request->method('getParsedBody')->willReturn(json_decode($bodyContent, true, 512, JSON_THROW_ON_ERROR));
        $request->method('getHeaderLine')
            ->withConsecutive(['Content-Type'], ['Date'], ['Signature'], ['Date'])
            ->willReturnOnConsecutiveCalls(
                'application/json',
                $requestDate,
                $this->createSignatureHeader($this->calculateSignature($requestDate, 'invalid body digest')),
                $requestDate
            );

        $response = $this->callbackReceiver->receiveCallback($request, $this->createDummyCallbackProcessor());

        self::assertEquals(401, $response->getStatusCode());
        self::assertEquals('AUTHENTICATION ERROR', (string) $response->getBody());
    }

    public function testReceivingCallback(): void
    {
        $bodyContent = $this->getMockFileContents('order-callback.json');
        $bodyDigest = base64_encode(hash('sha512', $bodyContent, true));
        $requestDate = (new DateTimeImmutable())->format(DateTimeInterface::RFC3339);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn(new Uri('https://receiver.com/shipment'));
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($this->streamFactory->createStream($bodyContent));
        $request->method('getParsedBody')->willReturn(json_decode($bodyContent, true, 512, JSON_THROW_ON_ERROR));
        $request->method('getHeaderLine')
            ->withConsecutive(['Content-Type'], ['Date'], ['Signature'], ['Date'])
            ->willReturnOnConsecutiveCalls(
                'application/json',
                $requestDate,
                $this->createSignatureHeader($this->calculateSignature($requestDate, $bodyDigest)),
                $requestDate
            );

        $response = $this->callbackReceiver->receiveCallback(
            $request,
            function (string $event, OrderDetails $order): bool {
                self::assertEquals('shipmentStateChanged', $event);
                self::assertEquals('3GN-VAV-JUA-5V5-B5P', $order->getOrder()->getNumber());

                return true;
            }
        );

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('OK', (string) $response->getBody());
    }

    protected function setUp(): void
    {
        $this->streamFactory = new StreamFactory();
        $this->callbackReceiver = new CallbackReceiver(
            new ResponseFactory(),
            $this->streamFactory,
            new UriFactory(),
            'api client id',
            'api signing key'
        );
    }

    private function getMockFileContents(string $filename): string
    {
        $contents = file_get_contents(sprintf('%s/fixtures/%s', __DIR__, $filename));
        self::assertIsString($contents);

        return $contents;
    }

    private function createSignatureHeader(string $signature): string
    {
        return sprintf(
            'keyId="api client id",algorithm="sha3-512",headers="(request-target) date digest",signature="%s"',
            base64_encode(
                hash_hmac(
                    'sha3-512',
                    $signature,
                    'api signing key'
                )
            )
        );
    }

    /**
     * @param string $requestDate
     * @param string $bodyDigest
     * @return string
     */
    private function calculateSignature(string $requestDate, string $bodyDigest): string
    {
        return sprintf(
            '(request-target): post /shipment\nDate: %s\nDigest: SHA-512=%s',
            $requestDate,
            $bodyDigest
        );
    }

    private function createDummyCallbackProcessor(): Closure
    {
        return static function (string $event, OrderDetails $order): bool {
            return true;
        };
    }
}
