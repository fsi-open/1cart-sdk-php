<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Order;

use OneCart\Api\Model\FormattedMoney;
use OneCart\Api\Model\Product\ProductVersion;
use Psr\Http\Message\UriFactoryInterface;

final class OrderItem
{
    private string $sellerId;
    private ProductVersion $productVersion;
    private int $quantity;
    private FormattedMoney $total;
    private FormattedMoney $totalWithoutDiscount;

    /**
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromData(array $data, UriFactoryInterface $uriFactory): self
    {
        return new self(
            $data['product']['seller_id'] ?? '',
            ProductVersion::fromData($data['product'] ?? [], $uriFactory),
            $data['quantity'] ?? 0,
            FormattedMoney::fromData($data['total'] ?? []),
            FormattedMoney::fromData($data['total_without_discount'])
        );
    }

    public function __construct(
        string $sellerId,
        ProductVersion $productVersion,
        int $quantity,
        FormattedMoney $total,
        FormattedMoney $totalWithoutDiscount
    ) {
        $this->sellerId = $sellerId;
        $this->productVersion = $productVersion;
        $this->quantity = $quantity;
        $this->total = $total;
        $this->totalWithoutDiscount = $totalWithoutDiscount;
    }

    public function getSellerId(): string
    {
        return $this->sellerId;
    }

    public function getProductVersion(): ProductVersion
    {
        return $this->productVersion;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTotal(): FormattedMoney
    {
        return $this->total;
    }

    public function getTotalWithoutDiscount(): FormattedMoney
    {
        return $this->totalWithoutDiscount;
    }
}
