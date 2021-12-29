<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Api;

use DateTimeInterface;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Money\Money;
use OneCart\Api\Client;
use OneCart\Api\Model\Address;
use OneCart\Api\Model\InvoiceData;
use OneCart\Api\Model\Order\Order;
use OneCart\Api\Model\Product\DigitalUriProperties;
use OneCart\Api\Model\Product\EuReturnRightsForfeitExtension;
use OneCart\Api\Model\Product\EuVatExemption;
use OneCart\Api\Model\Product\EuVatExemptionExtension;
use OneCart\Api\Model\Product\PlVatGTUExtension;
use OneCart\Api\Model\Product\Product;
use OneCart\Api\Model\FormattedMoney;
use OneCart\Api\Model\ProductStock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

use function get_class;
use function iterator_to_array;
use function sort;

final class ClientTest extends TestCase
{
    /**
     * @var MockObject&ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * @var MockObject&RequestFactoryInterface
     */
    private RequestFactoryInterface$messageFactory;

    private Client $apiClient;

    public function testStocks(): void
    {
        $this->mockApiCall('stocks/all', 'stocks.json');

        $stocks = [];
        foreach ($this->apiClient->allStocks() as $sellerId => $stock) {
            $stocks[$sellerId] = $stock;
        }

        $stock1 = $stocks['product1'];
        self::assertInstanceOf(ProductStock::class, $stock1);
        self::assertEquals('product1', $stock1->getSellerId());
        self::assertEquals(1, $stock1->getAvailableQuantity());

        $stock2 = $stocks['product2'];
        self::assertInstanceOf(ProductStock::class, $stock2);
        self::assertEquals('product2', $stock2->getSellerId());
        self::assertEquals(2, $stock2->getAvailableQuantity());

        $stock3 = $stocks['product3'];
        self::assertInstanceOf(ProductStock::class, $stock3);
        self::assertEquals('product3', $stock3->getSellerId());
        self::assertEquals(3, $stock3->getAvailableQuantity());

        $stock4 = $stocks['product4'];
        self::assertInstanceOf(ProductStock::class, $stock4);
        self::assertEquals('product4', $stock4->getSellerId());
        self::assertEquals(4, $stock4->getAvailableQuantity());

        $stock5 = $stocks['product5'];
        self::assertInstanceOf(ProductStock::class, $stock5);
        self::assertEquals('product5', $stock5->getSellerId());
        self::assertEquals(5, $stock5->getAvailableQuantity());
    }

    public function testAllOrders(): void
    {
        $this->mockApiCall('orders/all', 'orders.json');

        $orders = iterator_to_array($this->apiClient->allOrders());

        $order1 = $orders['2WJ-JJV-JS4-QAP-KD6'];
        self::assertInstanceOf(Order::class, $order1);
        self::assertEquals('4dd9d235-f0a1-4423-9230-41b3e33918e6', $order1->getId());
        self::assertEquals('2021-12-29T11:40:47+00:00', $order1->getCreatedAt()->format(DateTimeInterface::RFC3339));
        self::assertEquals('test@example.com', $order1->getCustomer());
        self::assertNull($order1->getCancelledAt());
        self::assertEquals('cod', $order1->getPaymentType());
        self::assertEquals('furgonetka-dpd', $order1->getShippingType());
        $total1 = $order1->getTotal();
        self::assertInstanceOf(FormattedMoney::class, $total1);
        self::assertEquals('280,98 zł', (string) $total1);
        $totalWithShipping1 = $order1->getTotalWithShipping();
        self::assertInstanceOf(FormattedMoney::class, $totalWithShipping1);
        self::assertEquals('301,84 zł', (string) $totalWithShipping1);
        $totalWithShippingWithoutDiscount1 = $order1->getTotalWithShippingWithoutDiscount();
        self::assertInstanceOf(FormattedMoney::class, $totalWithShippingWithoutDiscount1);
        self::assertEquals('301,84 zł', (string) $totalWithShippingWithoutDiscount1);
        self::assertEquals('in_progress', $order1->getPaymentState());
        self::assertEquals('not_begun', $order1->getShippingState());
        self::assertEquals('test comment', $order1->getComments());
        self::assertNull($order1->getContactPerson());
        $invoiceData1 = $order1->getInvoiceData();
        self::assertInstanceOf(InvoiceData::class, $invoiceData1);
        self::assertEquals('Johny', $invoiceData1->getGivenName());
        self::assertEquals('Walker', $invoiceData1->getFamilyName());
        self::assertEquals('JOHNY WALKER', $invoiceData1->getOrganization());
        self::assertEquals('PL6612057750', $invoiceData1->getTaxId());
        $invoiceDataAddress1 = $invoiceData1->getAddress();
        self::assertInstanceOf(Address::class, $invoiceDataAddress1);
        self::assertEquals('PL', $invoiceDataAddress1->getCountryCode());
        self::assertNull($invoiceDataAddress1->getAdministrativeArea());
        self::assertEquals('Kraków', $invoiceDataAddress1->getLocality());
        self::assertNull($invoiceDataAddress1->getDependentLocality());
        self::assertEquals('31-323', $invoiceDataAddress1->getPostalCode());
        self::assertNull($invoiceDataAddress1->getSortingCode());
        self::assertEquals('Gdyńska', $invoiceDataAddress1->getStreet());
        self::assertEquals('19', $invoiceDataAddress1->getBuildingNumber());
        self::assertEquals('21', $invoiceDataAddress1->getFlatNumber());

        $order2 = $orders['3GN-VAV-JUA-5V5-B5P'];
        self::assertInstanceOf(Order::class, $order2);
        self::assertEquals('baf976f5-befc-45c8-b3fe-610395a00335', $order2->getId());
        self::assertEquals('2021-12-16T15:33:33+00:00', $order2->getCreatedAt()->format(DateTimeInterface::RFC3339));
        self::assertEquals('test@example.org', $order2->getCustomer());
        self::assertNull($order2->getCancelledAt());
        self::assertEquals('blue_media', $order2->getPaymentType());
        self::assertEquals('furgonetka-dpd', $order2->getShippingType());
        $total2 = $order2->getTotal();
        self::assertInstanceOf(FormattedMoney::class, $total2);
        self::assertEquals('239,00 zł', (string) $total2);
        $totalWithShipping2 = $order2->getTotalWithShipping();
        self::assertInstanceOf(FormattedMoney::class, $totalWithShipping2);
        self::assertEquals('301,85 zł', (string) $totalWithShipping2);
        $totalWithShippingWithoutDiscount2 = $order2->getTotalWithShippingWithoutDiscount();
        self::assertInstanceOf(FormattedMoney::class, $totalWithShippingWithoutDiscount2);
        self::assertEquals('301,85 zł', (string) $totalWithShippingWithoutDiscount2);
        self::assertEquals('completed', $order2->getPaymentState());
        self::assertEquals('partially_delivered', $order2->getShippingState());
        self::assertNull($order2->getComments());
        self::assertNull($order2->getContactPerson());
        self::assertNull($order2->getInvoiceData());
    }

    public function testAllProducts(): void
    {
        $this->mockApiCall('products/all', 'products.json');

        $products = [];
        foreach ($this->apiClient->allProducts() as $sellerId => $product1) {
            $products[$sellerId] = $product1;
        }

        /** @var Product $product1 */
        $product1 = $products['product1'];
        self::assertInstanceOf(Product::class, $product1);
        self::assertEquals('198ab331-bd02-4323-ad43-5928130f9697', $product1->getId());
        self::assertEquals('product1', $product1->getSellerId());
        self::assertEquals('https://1ct.eu/MWm', $product1->getShortCodeUri());
        self::assertEquals('https://example.com/product', (string) $product1->getPageUri());
        self::assertEquals(
            'https://onecart-public.s3.atman.pl/web-image/05f/61b/def/c0c4dc181ee9c6aefb1b035/product.png',
            (string) $product1->getImageThumbnailUri()
        );
        self::assertEquals(false, $product1->isDisabled());
        self::assertEquals(23, $product1->getTax());

        $product1Properties = $product1->getProperties();
        self::assertInstanceOf(DigitalUriProperties::class, $product1Properties);
        self::assertEquals('https://www.youtube.com/watch?v=aVeaUrJpwGg', (string) $product1Properties->getUri());

        $product1Extensions = $product1->getExtensions();
        self::assertIsArray($product1Extensions);
        $extensionsClasses = [];
        foreach ($product1Extensions as $product1Extension) {
            $extensionsClasses[] = get_class($product1Extension);
            if (true === $product1Extension instanceof EuVatExemptionExtension) {
                self::assertEquals(EuVatExemption::SUBJECT, $product1Extension->getVatExemption());
                continue;
            }
            if (true === $product1Extension instanceof EuReturnRightsForfeitExtension) {
                self::assertFalse($product1Extension->isForfeitRequired());
                continue;
            }
            if (true === $product1Extension instanceof PlVatGTUExtension) {
                self::assertNull($product1Extension->getGtuCode());
            }
        }
        sort($extensionsClasses);
        self::assertEquals(
            [EuReturnRightsForfeitExtension::class, EuVatExemptionExtension::class, PlVatGTUExtension::class],
            $extensionsClasses
        );

        $price1 = $product1->getPrice();
        self::assertInstanceOf(FormattedMoney::class, $price1);
        self::assertEquals('12,34 zł', (string) $price1);

        $moneyPrice1 = $price1->asMoneyObject();
        self::assertInstanceOf(Money::class, $moneyPrice1);
        self::assertEquals('1234', $moneyPrice1->getAmount());
        self::assertEquals('PLN', $moneyPrice1->getCurrency());

        /** @var Product $product2 */
        $product2 = $products['product2'];
        self::assertInstanceOf(Product::class, $product2);
        self::assertEquals('0c8ec1f9-f2b7-42f3-94db-08f6a9a4a35a', $product2->getId());
        self::assertEquals('product2', $product2->getSellerId());
        self::assertEquals('https://1ct.eu/ZWc', $product2->getShortCodeUri());
        self::assertEquals(true, $product2->isDisabled());
        self::assertEquals(23, $product2->getTax());

        $price2 = $product2->getPrice();
        self::assertInstanceOf(FormattedMoney::class, $price2);
        self::assertEquals('455,12 zł', (string) $price2);

        $moneyPrice2 = $price2->asMoneyObject();
        self::assertInstanceOf(Money::class, $moneyPrice2);
        self::assertEquals('45512', $moneyPrice2->getAmount());
        self::assertEquals('PLN', $moneyPrice2->getCurrency());
    }

    public function testProducts(): void
    {
        $this->mockApiCall('products', 'products.json', 'post');

        $products = [];
        foreach ($this->apiClient->products(['product1', 'product2']) as $sellerId => $product1) {
            $products[$sellerId] = $product1;
        }

        /** @var Product $product1 */
        $product1 = $products['product1'];
        self::assertInstanceOf(Product::class, $product1);
        self::assertEquals('198ab331-bd02-4323-ad43-5928130f9697', $product1->getId());
        self::assertEquals('product1', $product1->getSellerId());
        self::assertEquals('https://1ct.eu/MWm', $product1->getShortCodeUri());
        self::assertEquals(false, $product1->isDisabled());
        self::assertEquals(23, $product1->getTax());

        $price1 = $product1->getPrice();
        self::assertInstanceOf(FormattedMoney::class, $price1);
        self::assertEquals('12,34 zł', (string) $price1);

        $moneyPrice1 = $price1->asMoneyObject();
        self::assertInstanceOf(Money::class, $moneyPrice1);
        self::assertEquals('1234', $moneyPrice1->getAmount());
        self::assertEquals('PLN', $moneyPrice1->getCurrency());

        /** @var Product $product2 */
        $product2 = $products['product2'];
        self::assertInstanceOf(Product::class, $product2);
        self::assertEquals('0c8ec1f9-f2b7-42f3-94db-08f6a9a4a35a', $product2->getId());
        self::assertEquals('product2', $product2->getSellerId());
        self::assertEquals('https://1ct.eu/ZWc', $product2->getShortCodeUri());
        self::assertEquals(true, $product2->isDisabled());
        self::assertEquals(23, $product2->getTax());

        $product1Properties = $product1->getProperties();
        self::assertInstanceOf(DigitalUriProperties::class, $product1Properties);
        self::assertEquals('https://www.youtube.com/watch?v=aVeaUrJpwGg', (string) $product1Properties->getUri());

        $product1Extensions = $product1->getExtensions();
        self::assertIsArray($product1Extensions);
        $extensionsClasses = [];
        foreach ($product1Extensions as $product1Extension) {
            $extensionsClasses[] = get_class($product1Extension);
            if (true === $product1Extension instanceof EuVatExemptionExtension) {
                self::assertEquals(EuVatExemption::SUBJECT, $product1Extension->getVatExemption());
                continue;
            }
            if (true === $product1Extension instanceof EuReturnRightsForfeitExtension) {
                self::assertFalse($product1Extension->isForfeitRequired());
                continue;
            }
            if (true === $product1Extension instanceof PlVatGTUExtension) {
                self::assertNull($product1Extension->getGtuCode());
            }
        }
        sort($extensionsClasses);
        self::assertEquals(
            [EuReturnRightsForfeitExtension::class, EuVatExemptionExtension::class, PlVatGTUExtension::class],
            $extensionsClasses
        );

        $price2 = $product2->getPrice();
        self::assertInstanceOf(FormattedMoney::class, $price2);
        self::assertEquals('455,12 zł', (string) $price2);

        $moneyPrice2 = $price2->asMoneyObject();
        self::assertInstanceOf(Money::class, $moneyPrice2);
        self::assertEquals('45512', $moneyPrice2->getAmount());
        self::assertEquals('PLN', $moneyPrice2->getCurrency());
    }

    /**
     * @dataProvider failedHttpStatusCodesProvider()
     */
    public function testExceptionOnFailedStatus(int $statusCode): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::exactly(2))->method('getStatusCode')->willReturn($statusCode);

        $this->httpClient->expects(self::once())
            ->method('sendRequest')
            ->with($this->mockRequest('products/all'))
            ->willReturn($response)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "The request to \"products/all\" has returned an unexpected response code \"{$statusCode}\""
        );

        foreach ($this->apiClient->allProducts() as $product) {
            // At least one iteration needs to run in order for the exception to be thrown
            break;
        }
    }

    /**
     * @return array<array<int>>
     */
    public function failedHttpStatusCodesProvider(): array
    {
        return [[302], [401], [404], [403], [500]];
    }

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->messageFactory = $this->createMock(RequestFactoryInterface::class);
        $this->apiClient = new Client(
            $this->httpClient,
            $this->messageFactory,
            new StreamFactory(),
            new UriFactory(),
            Client::CURRENT_VERSION_API_URI,
            'api client id',
            'api key'
        );
    }

    private function mockApiCall(string $uri, string $mockResponseFilename, string $method = 'get'): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('getStatusCode')->willReturn(200);
        $response->expects(self::once())
            ->method('getBody')
            ->willReturn($this->getMockFileContents($mockResponseFilename))
        ;

        $this->httpClient->expects(self::once())
            ->method('sendRequest')
            ->with($this->mockRequest($uri, $method))
            ->willReturn($response)
        ;
    }

    private function mockRequest(string $path, string $method = 'get'): MockObject
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('withBody')->with(self::isInstanceOf(StreamInterface::class))->willReturn($request);
        $request->expects(self::exactly(4))
            ->method('withHeader')
            ->withConsecutive(
                ['User-Agent', '1cart API Client'],
                ['Accept', 'application/json'],
                ['X-Client-Id', 'api client id'],
                ['X-API-Key', 'api key'],
            )
            ->willReturnSelf()
        ;
        $this->messageFactory->expects(self::once())
            ->method('createRequest')
            ->with(
                $method,
                self::callback(
                    static fn(UriInterface $uri): bool => (string) $uri === sprintf('https://api.1cart.eu/v1/%s', $path)
                )
            )
            ->willReturn($request)
        ;

        return $request;
    }

    private function getMockFileContents(string $filename): string
    {
        $contents = file_get_contents(sprintf('%s/fixtures/%s', __DIR__, $filename));
        self::assertIsString($contents);

        return $contents;
    }
}
