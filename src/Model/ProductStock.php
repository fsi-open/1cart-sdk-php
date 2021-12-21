<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

final class ProductStock
{
    private string $sellerId;
    private int $availableQuantity;

    public function __construct(string $foreignId, int $availableQuantity)
    {
        $this->sellerId = $foreignId;
        $this->availableQuantity = $availableQuantity;
    }

    public function getSellerId(): string
    {
        return $this->sellerId;
    }

    public function getAvailableQuantity(): int
    {
        return $this->availableQuantity;
    }
}
