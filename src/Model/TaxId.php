<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace OneCart\Api\Model;

final class TaxId
{
    private string $vatId;

    public function __construct(string $vatId)
    {
        // TODO validation (duplicate OneCart\Invoicing\Domain\Validator\VatIdValidator?)
        $this->vatId = $vatId;
    }

    public function getVatId(): string
    {
        return $this->vatId;
    }

    public function __toString(): string
    {
        return $this->getVatId();
    }
}
