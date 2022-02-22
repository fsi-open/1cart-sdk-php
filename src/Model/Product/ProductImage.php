<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

final class ProductImage
{
    private UriInterface $imageUri;
    private UriInterface $imageThumbnailUri;
    private int $position;

    /**
     * @param array<array{ image: string, image_thumbnail: string, position: int }> $data
     * @param UriFactoryInterface $uriFactory
     * @return array<self>
     */
    public static function parseInstancesFromResponse(array $data, UriFactoryInterface $uriFactory): array
    {
        return array_map(
            fn(array $image): self => new self(
                $uriFactory->createUri($image['image']),
                $uriFactory->createUri($image['image_thumbnail']),
                (int) $image['position']
            ),
            $data
        );
    }

    public function __construct(UriInterface $imageUri, UriInterface $imageThumbnailUri, int $position)
    {
        $this->imageUri = $imageUri;
        $this->imageThumbnailUri = $imageThumbnailUri;
        $this->position = $position;
    }

    public function getImageUri(): UriInterface
    {
        return $this->imageUri;
    }

    public function getImageThumbnailUri(): UriInterface
    {
        return $this->imageThumbnailUri;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
