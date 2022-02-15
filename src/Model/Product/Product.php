<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use Money\Money;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

use function array_map;

final class Product
{
    private UuidInterface $id;
    private string $sellerId;
    private UriInterface $shortCodeUri;
    private bool $disabled;
    /**
     * @var array<UuidInterface>
     */
    private array $suppliersIds;
    private ProductVersion $version;

    /**
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromData(array $data, UriFactoryInterface $uriFactory): self
    {
        return new self(
            Uuid::fromString($data['id']),
            $data['seller_id'],
            $uriFactory->createUri($data['short_code_uri']),
            $data['disabled'],
            array_map(static fn(string $uuid): UuidInterface => Uuid::fromString($uuid), $data['suppliers']),
            ProductVersion::fromData($data, $uriFactory)
        );
    }

    /**
     * @param UuidInterface $id
     * @param string $sellerId
     * @param UriInterface $shortCodeUri
     * @param bool $disabled
     * @param array<UuidInterface> $suppliersIds
     * @param ProductVersion $version
     */
    public function __construct(
        UuidInterface $id,
        string $sellerId,
        UriInterface $shortCodeUri,
        bool $disabled,
        array $suppliersIds,
        ProductVersion $version
    ) {
        $this->id = $id;
        $this->sellerId = $sellerId;
        $this->disabled = $disabled;
        $this->suppliersIds = $suppliersIds;
        $this->version = $version;
        $this->shortCodeUri = $shortCodeUri;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getSellerId(): string
    {
        return $this->sellerId;
    }

    public function getShortCodeUri(): UriInterface
    {
        return $this->shortCodeUri;
    }

    public function getName(): string
    {
        return $this->version->getName();
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getPageUri(): ?UriInterface
    {
        return $this->version->getPageUri();
    }

    public function getImageThumbnailUri(): ?UriInterface
    {
        return $this->version->getImageThumbnailUri();
    }

    public function getPrice(): Money
    {
        return $this->version->getPrice();
    }

    public function getTax(): float
    {
        return $this->version->getTax();
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
        return $this->version->getProperties();
    }

    /**
     * @return array<ProductExtension>
     */
    public function getExtensions(): array
    {
        return $this->version->getExtensions();
    }

    /**
     * @return array<ProductImage>
     */
    public function getImages(): array
    {
        return $this->version->getImages();
    }
}
