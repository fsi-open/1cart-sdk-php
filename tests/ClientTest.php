<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Api;

use OneCart\Api\Client;
use OneCart\Api\Model\Product;
use OneCart\Api\Model\ProductPrice;
use OneCart\Api\Model\ProductStock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientTest extends TestCase
{
    /**
     * @var MockObject|ClientInterface
     */
    private $httpClient;

    /**
     * @var MockObject|RequestFactoryInterface
     */
    private $messageFactory;

    /**
     * @var Client
     */
    private $apiClient;

    public function testStocks(): void
    {
        $this->mockRequest('stocks/all', 'stocks.json');

        $stocks = [];
        foreach ($this->apiClient->allStocks() as $sellerId => $stock) {
            $stocks[$sellerId] = $stock;
        }

        $stock1 = $stocks['product1'];
        $this->assertInstanceOf(ProductStock::class, $stock1);
        $this->assertEquals('product1', $stock1->getForeignId());
        $this->assertEquals(1, $stock1->getAvailableQuantity());

        $stock2 = $stocks['product2'];
        $this->assertInstanceOf(ProductStock::class, $stock2);
        $this->assertEquals('product2', $stock2->getForeignId());
        $this->assertEquals(2, $stock2->getAvailableQuantity());

        $stock3 = $stocks['product3'];
        $this->assertInstanceOf(ProductStock::class, $stock3);
        $this->assertEquals('product3', $stock3->getForeignId());
        $this->assertEquals(3, $stock3->getAvailableQuantity());

        $stock4 = $stocks['product4'];
        $this->assertInstanceOf(ProductStock::class, $stock4);
        $this->assertEquals('product4', $stock4->getForeignId());
        $this->assertEquals(4, $stock4->getAvailableQuantity());

        $stock5 = $stocks['product5'];
        $this->assertInstanceOf(ProductStock::class, $stock5);
        $this->assertEquals('product5', $stock5->getForeignId());
        $this->assertEquals(5, $stock5->getAvailableQuantity());
    }

    public function testProducts(): void
    {
        $this->mockRequest('products/all', 'products.json');

        $products = [];
        foreach ($this->apiClient->allProducts() as $sellerId => $product1) {
            $products[$sellerId] = $product1;
        }

        /** @var Product $product1 */
        $product1 = $products['product1'];
        $this->assertInstanceOf(Product::class, $product1);
        $this->assertEquals('198ab331-bd02-4323-ad43-5928130f9697', $product1->getId());
        $this->assertEquals('product1', $product1->getForeignId());
        $this->assertEquals('https://1ct.eu/MWm', $product1->getShortCodeUri());
        $this->assertEquals(true, $product1->isDisabled());
        $this->assertEquals(23, $product1->getTax());

        $price1 = $product1->getPrice();
        $this->assertInstanceOf(ProductPrice::class, $price1);
        $this->assertEquals('1234', $price1->getAmount());
        $this->assertEquals('PLN', $price1->getCurrency());
        $this->assertEquals('12,34 zł', $price1->getFormatted());

        /** @var Product $product2 */
        $product2 = $products['product2'];
        $this->assertInstanceOf(Product::class, $product2);
        $this->assertEquals('0c8ec1f9-f2b7-42f3-94db-08f6a9a4a35a', $product2->getId());
        $this->assertEquals('product2', $product2->getForeignId());
        $this->assertEquals('https://1ct.eu/ZWc', $product2->getShortCodeUri());
        $this->assertEquals(false, $product2->isDisabled());
        $this->assertEquals(23, $product2->getTax());

        $price2 = $product2->getPrice();
        $this->assertInstanceOf(ProductPrice::class, $price2);
        $this->assertEquals('45512', $price2->getAmount());
        $this->assertEquals('PLN', $price2->getCurrency());
        $this->assertEquals('455,12 zł', $price2->getFormatted());
    }

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->messageFactory = $this->createMock(RequestFactoryInterface::class);
        $this->apiClient = new Client($this->httpClient, $this->messageFactory);
    }

    private function mockRequest(string $uri, string $mockResponseFilename): void
    {
        $request = $this->createMock(RequestInterface::class);
        $this->messageFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', $uri)
            ->willReturn($request)
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($this->getMockFileContents($mockResponseFilename))
        ;

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response)
        ;
    }

    private function getMockFileContents(string $filename): string
    {
        return file_get_contents(sprintf('%s/fixtures/%s', __DIR__, $filename));
    }
}
