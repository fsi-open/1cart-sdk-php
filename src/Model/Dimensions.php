<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

use JsonSerializable;

final class Dimensions implements JsonSerializable
{
    private int $length;
    private int $width;
    private int $height;

    /**
     * @param array<string,int> $data
     * @return static
     */
    public static function fromData(array $data): self
    {
        return new self(
            $data['length'],
            $data['width'],
            $data['height']
        );
    }

    public function __construct(int $length, int $width, int $height)
    {
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function jsonSerialize()
    {
        return [
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
