<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use OneCart\Api\Model\Dimensions;
use OneCart\Api\Model\Product\ProductProperties;

final class PhysicalProperties implements ProductProperties
{
    private Dimensions $dimensions;
    private float $weight;

    public function __construct(Dimensions $dimensions, float $weight)
    {
        $this->dimensions = $dimensions;
        $this->weight = $weight;
    }

    public function getDimensions(): Dimensions
    {
        return $this->dimensions;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }
}
