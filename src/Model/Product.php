<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\UuidInterface;

final class Product
{
    private UuidInterface $id;
    private string $sellerId;
    private string $name;
    private bool $disabled;
    private ?UriInterface $pageUri;
    private ?UriInterface $imageThumbnailUri;
    private UriInterface $shortCodeUri;
    private ProductPrice $price;
    private float $tax;
    /**
     * @var array<UuidInterface>
     */
    private array $suppliersIds;
    private ?ProductProperties $properties;
    /**
     * @var array<ProductExtension>
     */
    private array $extensions;

    /**
     * @param UuidInterface $id
     * @param string $sellerId
     * @param bool $disabled
     * @param UriInterface|null $pageUri
     * @param UriInterface|null $imageThumbnailUri
     * @param UriInterface $shortCodeUri
     * @param ProductPrice $price
     * @param float $tax
     * @param array<UuidInterface> $suppliersIds
     * @param ProductProperties|null $properties
     * @param array<ProductExtension> $extensions
     */
    public function __construct(
        UuidInterface $id,
        string $sellerId,
        string $name,
        bool $disabled,
        ?UriInterface $pageUri,
        ?UriInterface $imageThumbnailUri,
        UriInterface $shortCodeUri,
        ProductPrice $price,
        float $tax,
        array $suppliersIds,
        ?ProductProperties $properties,
        array $extensions
    ) {
        $this->id = $id;
        $this->sellerId = $sellerId;
        $this->name = $name;
        $this->disabled = $disabled;
        $this->pageUri = $pageUri;
        $this->imageThumbnailUri = $imageThumbnailUri;
        $this->shortCodeUri = $shortCodeUri;
        $this->price = $price;
        $this->tax = $tax;
        $this->suppliersIds = $suppliersIds;
        $this->properties = $properties;
        $this->extensions = $extensions;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getSellerId(): string
    {
        return $this->sellerId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getPageUri(): ?UriInterface
    {
        return $this->pageUri;
    }

    public function getImageThumbnailUri(): ?UriInterface
    {
        return $this->imageThumbnailUri;
    }

    public function getShortCodeUri(): UriInterface
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

    /**
     * @return array<UuidInterface>
     */
    public function getSuppliersIds(): array
    {
        return $this->suppliersIds;
    }

    public function getProperties(): ?ProductProperties
    {
        return $this->properties;
    }

    /**
     * @return array<ProductExtension>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
