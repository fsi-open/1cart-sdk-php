<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use InvalidArgumentException;
use OneCart\Api\Model\Dimensions;
use OneCart\Api\Model\FormattedMoney;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function array_key_exists;
use function array_keys;
use function array_reduce;

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
            $pageUri,
            $imageThumbnailUri,
            FormattedMoney::fromData($data['price'] ?? []),
            $data['tax_rate'],
            self::parseProductProperties($data['properties'] ?? null, $uriFactory),
            self::parseProductExtensions($data['extensions'] ?? [])
        );
    }

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
