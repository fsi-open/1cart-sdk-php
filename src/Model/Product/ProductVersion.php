<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use OneCart\Api\Model\FormattedMoney;
use Psr\Http\Message\UriInterface;

final class ProductVersion
{
    private string $name;
    private ?UriInterface $pageUri;
    private ?UriInterface $imageThumbnailUri;
    private FormattedMoney $price;
    private float $tax;
    private ?ProductProperties $properties;
    /**
     * @var array<ProductExtension>
     */
    private array $extensions;

    /**
     * @param UriInterface|null $pageUri
     * @param UriInterface|null $imageThumbnailUri
     * @param FormattedMoney $price
     * @param float $tax
     * @param ProductProperties|null $properties
     * @param array<ProductExtension> $extensions
     */
    public function __construct(
        string $name,
        ?UriInterface $pageUri,
        ?UriInterface $imageThumbnailUri,
        FormattedMoney $price,
        float $tax,
        ?ProductProperties $properties,
        array $extensions
    ) {
        $this->name = $name;
        $this->pageUri = $pageUri;
        $this->imageThumbnailUri = $imageThumbnailUri;
        $this->price = $price;
        $this->tax = $tax;
        $this->properties = $properties;
        $this->extensions = $extensions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPageUri(): ?UriInterface
    {
        return $this->pageUri;
    }

    public function getImageThumbnailUri(): ?UriInterface
    {
        return $this->imageThumbnailUri;
    }

    public function getPrice(): FormattedMoney
    {
        return $this->price;
    }

    public function getTax(): float
    {
        return $this->tax;
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
