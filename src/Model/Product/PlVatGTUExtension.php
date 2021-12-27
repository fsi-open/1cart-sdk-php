<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model\Product;

use OneCart\Api\Model\Product\ProductExtension;

final class PlVatGTUExtension implements ProductExtension
{
    private ?string $gtuCode;

    public function __construct(?string $gtuCode)
    {
        $this->gtuCode = $gtuCode;
    }

    public function getGtuCode(): ?string
    {
        return $this->gtuCode;
    }
}
