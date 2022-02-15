<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Api;

use DateTimeImmutable;
use DateTimeInterface;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Money\Money;
use OneCart\Api\ApiError;
use OneCart\Api\ApiException;
use OneCart\Api\Client;
use OneCart\Api\Model\Address;
use OneCart\Api\Model\InvoiceData;
use OneCart\Api\Model\Order\Order;
use OneCart\Api\Model\Order\OrderDetails;
use OneCart\Api\Model\Payment\BlueMediaPayment;
use OneCart\Api\Model\Product\DigitalFileProperties;
use OneCart\Api\Model\Product\DigitalUriProperties;
use OneCart\Api\Model\Product\EuReturnRightsForfeitExtension;
use OneCart\Api\Model\Product\EuVatExemption;
use OneCart\Api\Model\Product\EuVatExemptionExtension;
use OneCart\Api\Model\Product\PhysicalProperties;
use OneCart\Api\Model\Product\PlVatGTUExtension;
use OneCart\Api\Model\Product\Product;
use OneCart\Api\Model\Product\ProductVersion;
use OneCart\Api\Model\ProductStock;
use OneCart\Api\Model\Shipping\FurgonetkaDpdShipment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

use function count;
use function fopen;
use function get_class;
use function iterator_to_array;
use function json_decode;
use function sort;

final class ClientTest extends TestCase
{
    private const HEADERS = [
        ['User-Agent', '1cart API Client'],
        ['Accept', 'application/json'],
        ['X-Client-Id', 'api client id'],
        ['X-API-Key', 'api key'],
    ];

    /**
     * @var MockObject&ClientInterface
     */
    private MockObject $httpClient;
    /**
     * @var MockObject&RequestFactoryInterface
     */
    private MockObject$messageFactory;
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
        self::assertInstanceOf(Money::class, $total1);
        self::assertEquals('28098', $total1->getAmount());
        self::assertEquals('PLN', $total1->getCurrency());
        $totalWithShipping1 = $order1->getTotalWithShipping();
        self::assertInstanceOf(Money::class, $totalWithShipping1);
        self::assertEquals('30184', $totalWithShipping1->getAmount());
        self::assertEquals('PLN', $totalWithShipping1->getCurrency());
        $totalWithShippingWithoutDiscount1 = $order1->getTotalWithShippingWithoutDiscount();
        self::assertInstanceOf(Money::class, $totalWithShippingWithoutDiscount1);
        self::assertEquals('30184', $totalWithShippingWithoutDiscount1->getAmount());
        self::assertEquals('PLN', $totalWithShippingWithoutDiscount1->getCurrency());
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
        self::assertInstanceOf(Money::class, $total2);
        self::assertEquals('23900', $total2->getAmount());
        self::assertEquals('PLN', $total2->getCurrency());
        $totalWithShipping2 = $order2->getTotalWithShipping();
        self::assertInstanceOf(Money::class, $totalWithShipping2);
        self::assertEquals('30185', $totalWithShipping2->getAmount());
        self::assertEquals('PLN', $totalWithShipping2->getCurrency());
        $totalWithShippingWithoutDiscount2 = $order2->getTotalWithShippingWithoutDiscount();
        self::assertInstanceOf(Money::class, $totalWithShippingWithoutDiscount2);
        self::assertEquals('30185', $totalWithShippingWithoutDiscount2->getAmount());
        self::assertEquals('PLN', $totalWithShippingWithoutDiscount2->getCurrency());
        self::assertEquals('completed', $order2->getPaymentState());
        self::assertEquals('partially_delivered', $order2->getShippingState());
        self::assertNull($order2->getComments());
        self::assertNull($order2->getContactPerson());
        self::assertNull($order2->getInvoiceData());
    }

    public function testOrderDetails(): void
    {
        $this->mockApiCall('orders', 'order-details.json', 'POST');

        $orders = iterator_to_array($this->apiClient->ordersDetails(['3GN-VAV-JUA-5V5-B5P']));

        $orderDetails = $orders['3GN-VAV-JUA-5V5-B5P'];
        self::assertInstanceOf(OrderDetails::class, $orderDetails);
        self::assertEquals('baf976f5-befc-45c8-b3fe-610395a00335', $orderDetails->getOrder()->getId());
        self::assertEquals(
            '2021-12-16T15:33:33+00:00',
            $orderDetails->getOrder()->getCreatedAt()->format(DateTimeInterface::RFC3339)
        );
        self::assertEquals('test@example.org', $orderDetails->getOrder()->getCustomer());
        self::assertNull($orderDetails->getOrder()->getCancelledAt());
        self::assertEquals('blue_media', $orderDetails->getOrder()->getPaymentType());
        self::assertEquals('furgonetka-dpd', $orderDetails->getOrder()->getShippingType());
        $total = $orderDetails->getOrder()->getTotal();
        self::assertInstanceOf(Money::class, $total);
        self::assertEquals('23900', $total->getAmount());
        self::assertEquals('PLN', $total->getCurrency());
        $totalWithShipping = $orderDetails->getOrder()->getTotalWithShipping();
        self::assertInstanceOf(Money::class, $totalWithShipping);
        self::assertEquals('30185', $totalWithShipping->getAmount());
        self::assertEquals('PLN', $totalWithShipping->getCurrency());
        $totalWithShippingWithoutDiscount = $orderDetails->getOrder()->getTotalWithShippingWithoutDiscount();
        self::assertInstanceOf(Money::class, $totalWithShippingWithoutDiscount);
        self::assertEquals('30185', $totalWithShippingWithoutDiscount->getAmount());
        self::assertEquals('PLN', $totalWithShippingWithoutDiscount->getCurrency());
        self::assertEquals('completed', $orderDetails->getOrder()->getPaymentState());
        self::assertEquals('partially_delivered', $orderDetails->getOrder()->getShippingState());
        self::assertNull($orderDetails->getOrder()->getComments());
        self::assertNull($orderDetails->getOrder()->getContactPerson());
        self::assertNull($orderDetails->getOrder()->getInvoiceData());

        $items = $orderDetails->getItems();
        self::assertCount(3, $items);
        foreach ($items as $item) {
            switch ($item->getSellerId()) {
                case 'doniczka-czarna':
                    self::assertEquals(3, $item->getQuantity());
                    self::assertEquals('4200', $item->getTotal()->getAmount());
                    self::assertEquals('PLN', $item->getTotal()->getCurrency());
                    self::assertEquals('4200', $item->getTotalWithoutDiscount()->getAmount());
                    self::assertEquals('PLN', $item->getTotalWithoutDiscount()->getCurrency());
                    self::assertEquals("Doniczka czarna", $item->getProductVersion()->getName());
                    self::assertNull($item->getProductVersion()->getPageUri());
                    self::assertEquals(
                        'https://onecart-public.s3.atman.pl/web-image/99d/fc6/762/ef14d998a86ff4583abba58/doniczka.jpg',
                        $item->getProductVersion()->getImageThumbnailUri()
                    );
                    self::assertEquals('1400', $item->getProductVersion()->getPrice()->getAmount());
                    self::assertEquals('PLN', $item->getProductVersion()->getPrice()->getCurrency());
                    self::assertEquals(0.23, $item->getProductVersion()->getTax());
                    $properties = $item->getProductVersion()->getProperties();
                    self::assertInstanceOf(PhysicalProperties::class, $properties);
                    self::assertEquals(200, $properties->getDimensions()->getLength());
                    self::assertEquals(200, $properties->getDimensions()->getWidth());
                    self::assertEquals(300, $properties->getDimensions()->getHeight());
                    self::assertEquals(0.5, $properties->getWeight());
                    break;

                case 'figurka':
                    self::assertEquals(1, $item->getQuantity());
                    self::assertEquals('11900', $item->getTotal()->getAmount());
                    self::assertEquals('PLN', $item->getTotal()->getCurrency());
                    self::assertEquals('11900', $item->getTotalWithoutDiscount()->getAmount());
                    self::assertEquals('PLN', $item->getTotalWithoutDiscount()->getCurrency());
                    self::assertEquals("Figurka", $item->getProductVersion()->getName());
                    self::assertNull($item->getProductVersion()->getPageUri());
                    self::assertEquals(
                        'https://onecart-public.s3.atman.pl/web-image/238/ac1/1b3/d114ceaaca3b78c7ae2fe74/figurka.jpg',
                        $item->getProductVersion()->getImageThumbnailUri()
                    );
                    self::assertEquals('11900', $item->getProductVersion()->getPrice()->getAmount());
                    self::assertEquals('PLN', $item->getProductVersion()->getPrice()->getCurrency());
                    self::assertEquals(0.23, $item->getProductVersion()->getTax());
                    $properties = $item->getProductVersion()->getProperties();
                    self::assertInstanceOf(PhysicalProperties::class, $properties);
                    self::assertEquals(200, $properties->getDimensions()->getLength());
                    self::assertEquals(200, $properties->getDimensions()->getWidth());
                    self::assertEquals(300, $properties->getDimensions()->getHeight());
                    self::assertEquals(1, $properties->getWeight());
                    break;

                case 'budzik':
                    self::assertEquals(2, $item->getQuantity());
                    self::assertEquals('7800', $item->getTotal()->getAmount());
                    self::assertEquals('PLN', $item->getTotal()->getCurrency());
                    self::assertEquals('7800', $item->getTotalWithoutDiscount()->getAmount());
                    self::assertEquals('PLN', $item->getTotalWithoutDiscount()->getCurrency());
                    self::assertEquals("Budzik", $item->getProductVersion()->getName());
                    self::assertNull($item->getProductVersion()->getPageUri());
                    self::assertEquals(
                        'https://onecart-public.s3.atman.pl/web-image/228/cf8/ff9/e67478396b8a29799981ba6/budzik.jpg',
                        $item->getProductVersion()->getImageThumbnailUri()
                    );
                    self::assertEquals('3900', $item->getProductVersion()->getPrice()->getAmount());
                    self::assertEquals('PLN', $item->getProductVersion()->getPrice()->getCurrency());
                    self::assertEquals(0.23, $item->getProductVersion()->getTax());
                    $properties = $item->getProductVersion()->getProperties();
                    self::assertInstanceOf(PhysicalProperties::class, $properties);
                    self::assertEquals(100, $properties->getDimensions()->getLength());
                    self::assertEquals(100, $properties->getDimensions()->getWidth());
                    self::assertEquals(100, $properties->getDimensions()->getHeight());
                    self::assertEquals(0.1, $properties->getWeight());
                    break;
            }
        }

        $payments = $orderDetails->getPayments();
        $payment = $payments[0];
        self::assertInstanceOf(BlueMediaPayment::class, $payment);
        $createdAt = $payment->getCreatedAt();
        self::assertNotNull($createdAt);
        self::assertEquals('2021-12-16T15:33:39+00:00', $createdAt->format(DateTimeInterface::RFC3339));
        $completedAt = $payment->getCompletedAt();
        self::assertNotNull($completedAt);
        self::assertEquals('2021-12-16T15:34:06+00:00', $completedAt->format(DateTimeInterface::RFC3339));
        self::assertNull($payment->getCancelledAt());
        self::assertEquals('30185', $payment->getValue()->getAmount());
        self::assertSame(106, $payment->getGateway());

        $shipments = $orderDetails->getShipments();
        foreach ($shipments as $shipment) {
            self::assertInstanceOf(FurgonetkaDpdShipment::class, $shipment);
            self::assertEquals(
                '2021-12-16T15:33:33+00:00',
                $shipment->getCreatedAt()->format(DateTimeInterface::RFC3339)
            );
            self::assertEquals(500, $shipment->getDimensions()->getLength());
            self::assertEquals(500, $shipment->getDimensions()->getWidth());
            self::assertEquals(500, $shipment->getDimensions()->getHeight());
            self::assertEquals('2095', $shipment->getPrice()->getAmount());
            $sender = $shipment->getSender();
            self::assertEquals('maciej@seller.pl', $sender->getPerson()->getEmail());
            self::assertEquals('Kowalski', $sender->getPerson()->getFamilyName());
            self::assertEquals('Maciej', $sender->getPerson()->getGivenName());
            self::assertEquals('Demo 1koszyk', $sender->getPerson()->getOrganization());
            self::assertEquals('600123123', $sender->getPerson()->getPhoneNumber());
            self::assertEquals('PL', $sender->getAddress()->getCountryCode());
            self::assertNull($sender->getAddress()->getAdministrativeArea());
            self::assertEquals('Kraków', $sender->getAddress()->getLocality());
            self::assertNull($sender->getAddress()->getDependentLocality());
            self::assertNull($sender->getAddress()->getSortingCode());
            self::assertEquals('31-042', $sender->getAddress()->getPostalCode());
            self::assertEquals('Rynek Główny', $sender->getAddress()->getStreet());
            self::assertEquals('1', $sender->getAddress()->getBuildingNumber());
            self::assertEquals('100', $sender->getAddress()->getFlatNumber());
            $recipient = $shipment->getRecipient();
            self::assertEquals('buyer@example.com', $recipient->getPerson()->getEmail());
            self::assertEquals('Test', $recipient->getPerson()->getFamilyName());
            self::assertEquals('Test', $recipient->getPerson()->getGivenName());
            self::assertNull($recipient->getPerson()->getOrganization());
            self::assertEquals('600123456', $recipient->getPerson()->getPhoneNumber());
            self::assertEquals('PL', $recipient->getAddress()->getCountryCode());
            self::assertNull($recipient->getAddress()->getAdministrativeArea());
            self::assertEquals('Kraków', $recipient->getAddress()->getLocality());
            self::assertNull($recipient->getAddress()->getDependentLocality());
            self::assertNull($recipient->getAddress()->getSortingCode());
            self::assertEquals('31-323', $recipient->getAddress()->getPostalCode());
            self::assertEquals('Gdyńska', $recipient->getAddress()->getStreet());
            self::assertEquals('19', $recipient->getAddress()->getBuildingNumber());
            self::assertNull($recipient->getAddress()->getFlatNumber());

            $productsIds = $shipment->getProductIds();
            sort($productsIds);
            switch ($shipment->getId()) {
                case '57a653fc-f43c-4860-8b0f-013673413407':
                    self::assertEquals(['budzik', 'budzik', 'doniczka-czarna'], $productsIds);
                    self::assertEquals(0.7, $shipment->getWeight());
                    self::assertNull($shipment->getCodValue());
                    self::assertNull($shipment->getPreparedAt());
                    self::assertNull($shipment->getPickedUpAt());
                    self::assertNull($shipment->getDeliveredAt());
                    self::assertNull($shipment->getReturnedAt());
                    self::assertNull($shipment->getCancelledAt());
                    self::assertNull($shipment->getSurcharge());
                    self::assertNull($shipment->getSurchargeDescription());
                    self::assertNull($shipment->getWaybillNumber());
                    break;

                case '231dcf97-3ff8-46ba-b32b-f025d53843a6':
                    self::assertEquals(['doniczka-czarna', 'doniczka-czarna'], $productsIds);
                    self::assertEquals(1, $shipment->getWeight());
                    self::assertNull($shipment->getCodValue());
                    self::assertNull($shipment->getPreparedAt());
                    self::assertNull($shipment->getPickedUpAt());
                    self::assertNull($shipment->getDeliveredAt());
                    self::assertNull($shipment->getReturnedAt());
                    self::assertNull($shipment->getCancelledAt());
                    self::assertNull($shipment->getSurcharge());
                    self::assertNull($shipment->getSurchargeDescription());
                    self::assertNull($shipment->getWaybillNumber());
                    break;

                case 'e6d59cd3-d65c-46bf-8a22-7fd3f5cdfd9d':
                    self::assertEquals(['figurka'], $productsIds);
                    self::assertEquals(1, $shipment->getWeight());
                    self::assertNull($shipment->getCodValue());
                    $preparedAt = $shipment->getPreparedAt();
                    self::assertNotNull($preparedAt);
                    self::assertEquals('2021-12-20T15:30:28+00:00', $preparedAt->format(DateTimeInterface::RFC3339));
                    $pickedUpAt = $shipment->getPickedUpAt();
                    self::assertNotNull($pickedUpAt);
                    self::assertEquals('2021-12-20T15:33:45+00:00', $pickedUpAt->format(DateTimeInterface::RFC3339));
                    $deliveredAt = $shipment->getDeliveredAt();
                    self::assertNotNull($deliveredAt);
                    self::assertEquals('2021-12-20T15:42:23+00:00', $deliveredAt->format(DateTimeInterface::RFC3339));
                    self::assertNull($shipment->getReturnedAt());
                    self::assertNull($shipment->getCancelledAt());
                    self::assertNull($shipment->getSurcharge());
                    self::assertNull($shipment->getSurchargeDescription());
                    self::assertEquals('0000000831130Q', $shipment->getWaybillNumber());
                    break;
            }
        }
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
        self::assertInstanceOf(Money::class, $price1);
        self::assertEquals('1234', $price1->getAmount());
        self::assertEquals('PLN', $price1->getCurrency());

        /** @var Product $product2 */
        $product2 = $products['product2'];
        self::assertInstanceOf(Product::class, $product2);
        self::assertEquals('0c8ec1f9-f2b7-42f3-94db-08f6a9a4a35a', $product2->getId());
        self::assertEquals('product2', $product2->getSellerId());
        self::assertEquals('https://1ct.eu/ZWc', $product2->getShortCodeUri());
        self::assertEquals(true, $product2->isDisabled());
        self::assertEquals(23, $product2->getTax());

        $price2 = $product2->getPrice();
        self::assertInstanceOf(Money::class, $price2);
        self::assertEquals('45512', $price2->getAmount());
        self::assertEquals('PLN', $price2->getCurrency());
    }

    public function testProducts(): void
    {
        $this->mockApiCall('products', 'products.json', 'POST');

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
        self::assertInstanceOf(Money::class, $price1);
        self::assertEquals('1234', $price1->getAmount());
        self::assertEquals('PLN', $price1->getCurrency());

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
        self::assertInstanceOf(Money::class, $price2);
        self::assertEquals('45512', $price2->getAmount());
        self::assertEquals('PLN', $price2->getCurrency());
    }

    public function testParsingProductCreationErrors(): void
    {
        $expectedRequestData = [
            'seller_id' => 'test',
            'name' => 'Product no 1',
            'page_uri' => null,
            'price' => '1000',
            'tax_rate' => 0.23,
            'properties' => null,
            'extensions' => [],
            'disabled' => false,
        ];
        $this->mockApiCall('product', 'product-errors.json', 'POST', $expectedRequestData, 400);

        try {
            $this->apiClient->createProduct(
                'test',
                new ProductVersion('Product no 1', null, null, Money::PLN(1000), 0.23, null, [])
            );
        } catch (ApiException $exception) {
            self::assertEquals(
                [
                    new ApiError('extensions.pl_vat_gtu_code.vat_gtu_code', 'Tego pola brakuje.'),
                    new ApiError('extensions.pl_vat_gtu_code.gtu_code', 'Tego pola się nie spodziewano.'),
                ],
                $exception->getErrors()
            );
        }
    }

    public function testProductCreation(): void
    {
        $this->mockApiCall('product', 'product-digital-uri.json', 'POST');

        $product = $this->apiClient->createProduct(
            'test',
            new ProductVersion('Product no 1', null, null, Money::PLN(1000), 0.23, null, [])
        );
        self::assertEquals('Yellow T-Shirt XXL', $product->getName());
    }

    public function testProductUpdate(): void
    {
        $this->mockApiCall('product/test', 'product-digital-uri.json', 'PUT');

        $product = $this->apiClient->updateProduct(
            'test',
            new ProductVersion('Product no 1', null, null, Money::PLN(1000), 0.23, null, [])
        );
        self::assertEquals('Yellow T-Shirt XXL', $product->getName());
    }

    public function testProductDigitalFileCreation(): void
    {
        $createHeaders = self::HEADERS;
        $createHeaders[] = ['Content-Type', 'application/json'];
        $createRequest = $this->createRequest($createHeaders, null);

        $fileHeaders = self::HEADERS;
        $fileHeaders[] = ['Content-Type', 'multipart/form-data'];
        $fileRequest = $this->createRequest($fileHeaders, null);

        $productHeaders = self::HEADERS;
        $productHeaders[] = ['Content-Type', 'application/json'];
        $productRequest = $this->createRequest($productHeaders, null);

        $this->messageFactory->expects(self::exactly(3))
            ->method('createRequest')
            ->withConsecutive(
                [
                    'POST',
                    self::callback(
                        static fn(UriInterface $uri): bool => 'https://api.1cart.eu/v1/product' === (string) $uri
                    )
                ],
                [
                    'PUT',
                    self::callback(
                        static fn(UriInterface $uri): bool
                            => 'https://api.1cart.eu/v1/product/test/product-digital-file' === (string) $uri
                    )
                ],
                [
                    'POST',
                    self::callback(
                        static fn(UriInterface $uri): bool => 'https://api.1cart.eu/v1/products' === (string) $uri
                    )
                ]
            )
            ->willReturnOnConsecutiveCalls($createRequest, $fileRequest, $productRequest)
        ;

        $createResponse = $this->createMock(ResponseInterface::class);
        $createResponse->expects(self::once())->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $createResponse->method('getStatusCode')->willReturn(200);
        $createResponse->expects(self::once())
            ->method('getBody')
            ->willReturn($this->getMockFileContents('product-digital-uri.json'))
        ;

        $productResponse = $this->createMock(ResponseInterface::class);
        $productResponse->expects(self::exactly(2))->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $productResponse->method('getStatusCode')->willReturn(200);
        $productResponse->expects(self::exactly(2))
            ->method('getBody')
            ->willReturnOnConsecutiveCalls(
                $this->getMockFileContents('product-digital-file.json'),
                $this->getMockFileContents('products-with-digital-file.json')
            )
        ;

        $this->httpClient->expects(self::exactly(3))
            ->method('sendRequest')
            ->withConsecutive([$createRequest], [$fileRequest], [$productRequest])
            ->willReturnOnConsecutiveCalls($createResponse, $productResponse, $productResponse)
        ;

        $this->apiClient->createProduct(
            'test',
            new ProductVersion('Product no 1', null, null, Money::PLN(1000), 0.23, null, [])
        );

        $fileHandle = fopen(__DIR__ . '/fixtures/image.jpg', 'r');
        $this->apiClient->updateProductDigitalProperties(
            'test',
            new Stream($fileHandle),
            'image.jpg'
        );

        foreach ($this->apiClient->products(['test']) as $product) {
            $properties = $product->getProperties();

            self::assertInstanceOf(DigitalFileProperties::class, $properties);
            self::assertInstanceOf(DateTimeImmutable::class, $properties->getExpiresAt());
            self::assertInstanceOf(UriInterface::class, $properties->getUri());
        }
    }

    /**
     * @dataProvider failedHttpStatusCodesProvider()
     */
    public function testExceptionOnFailedStatus(int $statusCode): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $response->expects(self::once())->method('getBody')->willReturn('[]');
        $response->expects(self::once())->method('getStatusCode')->willReturn($statusCode);

        $this->httpClient->expects(self::once())
            ->method('sendRequest')
            ->with($this->mockRequest('products/all'))
            ->willReturn($response)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "The request to \"v1/products/all\" has returned an unexpected response code \"{$statusCode}\""
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

    /**
     * @param string $uri
     * @param string $mockResponseFilename
     * @param string $method
     * @param array<string,mixed>|null $requestData
     * @param int $responseStatus
     * @return void
     */
    private function mockApiCall(
        string $uri,
        string $mockResponseFilename,
        string $method = 'GET',
        ?array $requestData = null,
        int $responseStatus = 200
    ): void {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::once())->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $response->method('getStatusCode')->willReturn($responseStatus);
        $response->expects(self::once())
            ->method('getBody')
            ->willReturn($this->getMockFileContents($mockResponseFilename))
        ;

        $this->httpClient->expects(self::once())
            ->method('sendRequest')
            ->with($this->mockRequest($uri, $method, $requestData))
            ->willReturn($response)
        ;
    }

    /**
     * @param string|array<string> $path
     * @param string|array<string> $method
     * @param array<string,mixed>|null $requestData
     * @return MockObject&RequestInterface
     */
    private function mockRequest($path, $method = 'GET', ?array $requestData = null): MockObject
    {
        $headers = $this->createRequestHeadersArray($method);
        $request = $this->createRequest($headers, $requestData);

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

    /**
     * @param array<array<string>> $headers
     * @param array<string,mixed>|null $requestData
     * @param StreamInterface $body
     * @return MockObject&RequestInterface
     */
    private function createRequest(array $headers, ?array $requestData): MockObject
    {
        $bodyConditions = [self::isInstanceOf(StreamInterface::class)];
        if (null !== $requestData) {
            $bodyConditions[] = self::callback(
                static fn (StreamInterface $body): bool
                    => json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR) == $requestData
            );
        }

        $request = $this->createMock(RequestInterface::class);
        $request->method('withBody')->with(self::logicalAnd(...$bodyConditions))->willReturn($request);
        $request->expects(self::exactly(count($headers)))
            ->method('withHeader')
            ->withConsecutive(...$headers)
            ->willReturnSelf()
        ;

        return $request;
    }

    /**
     * @param string $method
     * @return array<array<string>>
     */
    private function createRequestHeadersArray(string $method): array
    {
        $headers = self::HEADERS;
        if ('GET' !== $method) {
            $headers[] = ['Content-Type', 'application/json'];
        }

        return $headers;
    }

    private function getMockFileContents(string $filename): string
    {
        $contents = file_get_contents(sprintf('%s/fixtures/%s', __DIR__, $filename));
        self::assertIsString($contents);

        return $contents;
    }
}
