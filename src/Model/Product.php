<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

class Product
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $foreignId;

    /**
     * @var bool
     */
    private $disabled;

    /**
     * @var string
     */
    private $shortCodeUri;

    /**
     * @var ProductPrice
     */
    private $price;

    /**
     * @var float
     */
    private $tax;

    public function __construct(
        string $id,
        string $foreignId,
        bool $disabled,
        string $shortCodeUri,
        ProductPrice $price,
        float $tax
    ) {
        $this->id = $id;
        $this->foreignId = $foreignId;
        $this->disabled = $disabled;
        $this->shortCodeUri = $shortCodeUri;
        $this->price = $price;
        $this->tax = $tax;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getForeignId(): string
    {
        return $this->foreignId;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getShortCodeUri(): string
    {
        return $this->shortCodeUri;
    }

    public function getPrice(): ProductPrice
    {
        return $this->price;
    }

    public function getTax(): float
    {
        return $this->tax;
    }
}
