<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Money\Money;
use OneCart\Api\Model\Dimensions;
use OneCart\Api\Model\FormattedMoney;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function array_keys;
use function array_reduce;

final class ProductVersion implements JsonSerializable
{
    private string $name;
    private ?string $shortDescription;
    private ?UriInterface $pageUri;
    private ?UriInterface $imageThumbnailUri;
    private Money $price;
    private float $tax;
    private ?ProductProperties $properties;
    /**
     * @var array<ProductExtension>
     */
    private array $extensions;

    /**
     * @var array<ProductImage>
     */
    private array $images;

    /**
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromData(array $data, UriFactoryInterface $uriFactory): self
    {
        $pageUri = (null !== ($data['page_uri'] ?? null)) ? $uriFactory->createUri($data['page_uri']) : null;

        $imageThumbnailUri = (null !== ($data['image_thumbnail'] ?? null))
            ? $uriFactory->createUri($data['image_thumbnail'])
            : null;

        return new self(
            $data['name'],
            $data['short_description'] ?? null,
            $pageUri,
            $imageThumbnailUri,
            FormattedMoney::fromData($data['price'] ?? []),
            $data['tax_rate'],
            self::parseProductProperties($data['properties'] ?? null, $uriFactory),
            ProductImage::parseInstancesFromResponse($data['images'] ?? [], $uriFactory),
            self::parseProductExtensions($data['extensions'] ?? [])
        );
    }

    /**
     * @param string $name
     * @param string|null $shortDescription
     * @param UriInterface|null $pageUri
     * @param UriInterface|null $imageThumbnailUri
     * @param Money $price
     * @param float $tax
     * @param ProductProperties|null $properties
     * @param array<ProductImage> $images
     * @param array<ProductExtension> $extensions
     */
    public function __construct(
        string $name,
        ?string $shortDescription,
        ?UriInterface $pageUri,
        ?UriInterface $imageThumbnailUri,
        Money $price,
        float $tax,
        ?ProductProperties $properties,
        array $images,
        array $extensions
    ) {
        $this->name = $name;
        $this->shortDescription = $shortDescription;
        $this->pageUri = $pageUri;
        $this->imageThumbnailUri = $imageThumbnailUri;
        $this->price = $price;
        $this->tax = $tax;
        $this->properties = $properties;
        $this->images = $images;
        $this->extensions = $extensions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function getPageUri(): ?UriInterface
    {
        return $this->pageUri;
    }

    public function getImageThumbnailUri(): ?UriInterface
    {
        return $this->imageThumbnailUri;
    }

    public function getPrice(): Money
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

    /**
     * @return array<ProductImage>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->name,
            'short_description' => $this->shortDescription,
            'page_uri' => $this->pageUri,
            'price' => $this->price->getAmount(),
            'tax_rate' => $this->tax,
            'properties' => $this->properties,
            'extensions' => [],
        ];

        foreach ($this->extensions as $extension) {
            $result['extensions'][$extension->getKey()] = $extension->jsonSerialize();
        }

        return $result;
    }

    /**
     * @param array<string,mixed>|null $properties
     * @param UriFactoryInterface $uriFactory
     * @return ProductProperties|null
     */
    private static function parseProductProperties(
        ?array $properties,
        UriFactoryInterface $uriFactory
    ): ?ProductProperties {
        if (null === $properties) {
            return null;
        }

        switch ($properties['type'] ?? null) {
            case 'digital-uri':
                return new DigitalUriProperties($uriFactory->createUri($properties['uri']));

            case 'digital-file':
                return new DigitalFileProperties(
                    $uriFactory->createUri($properties['uri']),
                    new DateTimeImmutable($properties['expires_at'])
                );

            case 'physical':
                return new PhysicalProperties(
                    Dimensions::fromData($properties['dimensions']),
                    (float) $properties['weight']
                );
        }

        throw new InvalidArgumentException("Unknown product properties of type {$properties['type']}");
    }

    /**
     * @param array<string,mixed> $extensionsData
     * @return array<ProductExtension>
     */
    private static function parseProductExtensions(array $extensionsData): array
    {
        return array_reduce(
            array_keys($extensionsData),
            static function (array $parsedExtensions, string $extensionKey) use (&$extensionsData): array {
                $parsedExtensions[] = self::parseProductExtension($extensionKey, $extensionsData[$extensionKey]);

                return $parsedExtensions;
            },
            []
        );
    }

    /**
     * @param string $extensionKey
     * @param array<string,mixed> $extensionData
     * @return ProductExtension
     */
    private static function parseProductExtension(string $extensionKey, array $extensionData): ProductExtension
    {
        switch ($extensionKey) {
            case 'eu_vat_exemption':
                return new EuVatExemptionExtension($extensionData['vat_exemption'] ?? '');
            case 'eu_return_rights_forfeit':
                return new EuReturnRightsForfeitExtension($extensionData['forfeit_required'] ?? false);
            case 'pl_vat_gtu_code':
                return new PlVatGTUExtension($extensionData['vat_gtu_code'] ?? null);
        }

        throw new InvalidArgumentException("Unknown product extension of type {$extensionKey}");
    }
}
